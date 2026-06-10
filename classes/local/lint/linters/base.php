<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_devtools\local\lint\linters;

use core\exception\coding_exception;
use local_devtools\local\attributes\linter;
use local_devtools\local\lint\schemas\issue;
use local_devtools\local\lint\severity;
use local_devtools\local\lint\schemas\file;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\Console\Helper\ProgressIndicator;
use function array_key_exists;
use function get_called_class;
use function is_array;

/**
 * The abstract base linter.
 *
 * Linter patterns can be overridden in config.php, example:
 * <code>
 * $CFG->devtools = [
 *     'linters' => [
 *         'base' => ['exclude_patterns' => ['*\/.venv/*']],
 *         'phpcs' => ['exclude_patterns' => ['*\/classes/*']],
 *     ],
 * ];
 * </code>
 *
 * @package   local_devtools
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var ProgressIndicator|null */
    protected ?ProgressIndicator $progress;

    /**
     * Constructor
     * @param ProgressIndicator $progress
     */
    public function __construct(?ProgressIndicator $progress = null) {
        $this->progress = $progress;
    }

    /**
     * Sets the processing file in the progress.
     * @param string $path
     * @return void
     */
    public function set_progress_file(string $path): void {
        if (!$this->progress) {
            return;
        }
        $this->progress->setMessage("Running {$this->get_name()} on $path...");
    }

    /**
     * Gets the {@see linter} attribute for this class.
     * @return linter
     */
    public static function get_linter_attribute(): linter {
        $class = new ReflectionClass(static::class);
        /** @var ReflectionAttribute<linter>[] $attributes */
        $attributes = $class->getAttributes(linter::class);
        [$attribute] = $attributes;

        if (!$attribute) {
            throw new coding_exception('linter classes must have the linter attribute set');
        }

        return $attribute->newInstance();
    }

    /**
     * Gets the name of the linter.
     * @return string
     */
    public static function get_name(): string {
        return self::get_linter_attribute()->name;
    }

    /**
     * Gets the summary description of the linter.
     * @return string|null
     */
    public static function get_description(): ?string {
        return self::get_linter_attribute()->description;
    }

    /**
     * Determines if the given linter is installed.
     * @return bool
     */
    public static function is_installed(): bool {
        return true;
    }

    /**
     * Declares file patterns to include.
     * @return string[]
     */
    public static function get_include_patterns(): array {
        $includepatterns = static::get_config_value('include_patterns');
        if ($includepatterns !== null) {
            return $includepatterns;
        }

        $baseincludepatterns = self::get_config_value('include_patterns', 'base');
        if ($baseincludepatterns !== null) {
            return $baseincludepatterns;
        }

        return [];
    }

    /**
     * Declares file patterns to exclude.
     * @return string[]
     */
    public static function get_exclude_patterns(): array {
        $includepatterns = static::get_config_value('exclude_patterns');
        if ($includepatterns !== null) {
            return $includepatterns;
        }

        $baseexcludepatterns = self::get_config_value('exclude_patterns', 'base');
        if ($baseexcludepatterns !== null) {
            return $baseexcludepatterns;
        }

        return [
            '**/.git/**',
            '**/node_modules/**',
            '**/vendor/**',
        ];
    }

    /**
     * Lints a single file.
     * @param string $filepath
     * @return file[]
     */
    public function lint_file(string $filepath): array {
        if (!$this->can_lint_file($filepath)) {
            return [];
        }

        $result = new file($filepath);

        if (!file_exists($filepath)) {
            $result->add_issue(new issue(
                0,
                0,
                "File not found",
                "file-must-exist",
                "base",
                severity::error
            ));
        }

        return [$result];
    }

    /**
     * Lints a single directory.
     * @param string $directorypath
     * @return file[]
     */
    public function lint_directory(string $directorypath): array {
        $results = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directorypath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $path) {
            $this->set_progress_file($path);
            $lintresults = $this->lint_file($path);
            if ($lintresults) {
                $results = [...$results, ...$lintresults];
            }
        }

        return $results;
    }

    /**
     * Lints a given path.
     * @param string $path
     * @return file[]
     */
    public function lint(string $path): array {
        if (!static::is_installed()) {
            $issue = issue::simple(
                'Linter not available or is not installed',
                'linter-installed',
                static::get_name(),
                severity::warning
            );
            $file = new file($path, [$issue]);
            return [$file];
        }

        if (is_dir($path)) {
            return $this->lint_directory($path);
        }

        if (is_file($path)) {
            return $this->lint_file($path);
        }

        $issue = new issue(
            0,
            0,
            "Path not found",
            "path-must-exist",
            "base",
            severity::error
        );
        return [
            new file($path, [$issue]),
        ];
    }

    /**
     * Checks if a given path matches some patterns.
     * @param string $path
     * @param string[] $patterns
     * @return bool
     */
    private function path_match_patterns($path, $patterns): bool {
        // Normalise path to use forward slashes for consistency with glob patterns.
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        // As long as it matches one of the PATTERNS.
        foreach ($patterns as $pattern) {
            $match = fnmatch($pattern, $path);
            if (!$match) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Checks if a given filepath can be linted by the current linter.
     * Must match one of the include patterns AND none of the exclude patterns.
     * @param string $filepath
     * @return bool
     */
    public function can_lint_file(string $filepath): bool {
        $includematch = $this->path_match_patterns($filepath, $this->get_include_patterns());
        if (!$includematch) {
            return false;
        }

        $excludematch = $this->path_match_patterns($filepath, $this->get_exclude_patterns());
        if ($excludematch) {
            return false;
        }

        return true;
    }

    /**
     * Flatten linter results into one single array.
     * @param file[][] $linterresults
     * @return file[]
     */
    public static function flatten_results(array $linterresults): array {
        /** @var array<string,file> $filemap */
        $filemap = [];

        foreach ($linterresults as $results) {
            foreach ($results as $fileresult) {
                $file = $fileresult->file;
                $issues = $fileresult->issues;
                if (array_key_exists($file, $filemap)) {
                    $filemap[$file] = new file($file, [...$filemap[$file]->issues, ...$issues]);
                } else {
                    $filemap[$file] = new file($file, $issues);
                }
            }
        }

        return array_values($filemap);
    }

    /**
     * Gets the linter configuration from $CFG.
     * @param string|null $lintername
     * @return array<string, mixed>|null
     */
    protected static function get_config(?string $lintername = null): ?array {
        global $CFG;
        static $cache = [];

        $lintername = $lintername ?? static::get_name();
        if (array_key_exists($lintername, $cache)) {
            return $cache[$lintername];
        }

        if (!isset($CFG->devtools)) {
            return $cache[$lintername] = null;
        }

        if (!isset($CFG->devtools['linters'][$lintername])) {
            return $cache[$lintername] = null;
        }

        $config = $CFG->devtools['linters'][$lintername];
        if (!is_array($config)) {
            return $cache[$lintername] = null;
        }

        return $cache[$lintername] = $config;
    }

    /**
     * Helper function to get a specific linter config value, returns null if not set.
     * @param string $key
     * @param string|null $lintername
     * @return mixed
     */
    protected static function get_config_value(string $key, ?string $lintername = null): mixed {
        $config = static::get_config($lintername);
        if ($config === null) {
            return $config;
        }

        if (!array_key_exists($key, $config)) {
            return null;
        }

        return $config[$key];
    }
}
