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

namespace local_devkit\local\lint\linters;

use core\exception\coding_exception;
use dml_exception;
use Exception;
use local_devkit\local\api\thirdpartylibs;
use local_devkit\local\attributes\linter;
use local_devkit\local\lint\schemas\file;
use local_devkit\local\lint\schemas\issue;
use local_devkit\local\lint\severity;
use MoodleQuickForm;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\Console\Helper\ProgressIndicator;

use function array_key_exists;
use function count;
use function is_object;

/**
 * The abstract base linter.
 *
 * Linter patterns can be overridden in config.php, example:
 * <code>
 * $CFG->devkit = [
 *     'linters' => [
 *         'base' => ['exclude_patterns' => ['*\/.venv/*']],
 *         'phpcs' => ['exclude_patterns' => ['*\/classes/*']],
 *     ],
 * ];
 * </code>
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var string */
    public const LINTER_STATUS_ENABLED = 'enabled';
    /** @var string */
    public const LINTER_STATUS_DISABLED = 'disabled';
    /** @var string */
    public const CONFIG_KEY_STATUS = 'status';
    /** @var string */
    public const CONFIG_KEY_INCLUDE_PATTERNS_ENABLED = 'include_patterns_enabled';
    /** @var string */
    public const CONFIG_KEY_INCLUDE_PATTERNS = 'include_patterns';
    /** @var string */
    public const CONFIG_KEY_EXCLUDE_PATTERNS_ENABLED = 'exclude_patterns_enabled';
    /** @var string */
    public const CONFIG_KEY_EXCLUDE_PATTERNS = 'exclude_patterns';

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
        if ($this->progress === null) {
            return;
        }

        $name = self::get_name();
        $this->progress->setMessage("Running $name on $path...");
    }

    /**
     * Gets the {@see linter} attribute for this class.
     * @return linter
     */
    public static function get_linter_attribute(): linter {
        /** @var linter[] $cachedinstances */
        static $cachedinstances = [];

        if (array_key_exists(static::class, $cachedinstances)) {
            return $cachedinstances[static::class];
        }

        $class = new ReflectionClass(static::class);
        /** @var ReflectionAttribute<linter>[] $attributes */
        $attributes = $class->getAttributes(linter::class);
        [$attribute] = count($attributes) > 0 ? $attributes : [null];

        if ($attribute === null) {
            throw new coding_exception('linter classes must have the linter attribute set');
        }

        $cachedinstances[static::class] = $instance = $attribute->newInstance();
        return $instance;
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
     * Determines if the given linter is enabled.
     * @return bool
     */
    public static function is_enabled(): bool {
        $status = self::get_config_value(self::CONFIG_KEY_STATUS);
        return $status === null || $status === self::LINTER_STATUS_ENABLED;
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
        $includepatterns = static::get_config_value(self::CONFIG_KEY_INCLUDE_PATTERNS, self::CONFIG_KEY_INCLUDE_PATTERNS_ENABLED);
        if ($includepatterns !== null) {
            return self::parse_multiline_string_as_array($includepatterns);
        }

        return [];
    }

    /**
     * Declares file patterns to exclude.
     * @param bool $includethirdparty True if third party exclude patterns should be included.
     * @return string[]
     */
    public static function get_exclude_patterns(bool $includethirdparty = false): array {
        $thirdpartypatterns = $includethirdparty ? self::get_third_party_exclude_patterns() : [];
        $excludepatterns = static::get_config_value(self::CONFIG_KEY_EXCLUDE_PATTERNS, self::CONFIG_KEY_EXCLUDE_PATTERNS_ENABLED);

        if ($excludepatterns !== null) {
            return [
                ...self::parse_multiline_string_as_array($excludepatterns),
                ...$thirdpartypatterns,
            ];
        }

        return [
            '*/.git/*',
            '*/node_modules/*',
            '*/vendor/*',
            '*/tests/fixtures/*',
            ...$thirdpartypatterns,
        ];
    }

    /**
     * Gets exclude patterns matching third party libs.
     * @return string[]
     */
    public static function get_third_party_exclude_patterns(): array {
        static $paths = null;
        if ($paths !== null) {
            return $paths;
        }

        $paths = array_column(thirdpartylibs::list(), 'location');

        $paths = array_map(function (string $path) {
            if (!is_dir($path)) {
                return $path;
            }
            return $path . DIRECTORY_SEPARATOR . '*';
        }, $paths);

        // Change all \ to / to avoid issues with Windows paths.
        $paths = array_map(fn(string $path) => str_replace('\\', '/', $path), $paths);

        // Remove duplicates.
        $paths = array_values(array_unique($paths));

        return $paths;
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

        $this->set_progress_file($filepath);

        $result = new file($filepath);

        if (!file_exists($filepath)) {
            $result->add_issue(new issue(
                0,
                0,
                "File not found",
                "file-must-exist",
                "base",
                severity::error,
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
        $this->set_progress_file($directorypath);
        $results = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directorypath, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $path) {
            $lintresults = $this->lint_file($path);
            if (count($lintresults) > 0) {
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
        if (!static::is_enabled()) {
            $issue = issue::simple(
                'Linter disabled by user',
                'linter-disabled',
                static::get_name(),
                severity::info,
            );
            $file = new file($path, [$issue]);
            return [$file];
        }

        if (!static::is_installed()) {
            $issue = issue::simple(
                'Linter not available or is not installed',
                'linter-installed',
                static::get_name(),
                severity::warning,
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
            severity::error,
        );
        return [
            new file($path, [$issue]),
        ];
    }

    /**
     * Creates a file object with a fatal-level issue.
     */
    protected static function create_file_with_fatal_issue(string $path, string $message): file {
        return new file($path, [
            issue::simple(
                message: $message,
                rule: 'devkit-internal-fatal',
                severity: severity::fatal,
                source: self::get_name(),
            ),
        ]);
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
        $includematch = $this->path_match_patterns($filepath, static::get_include_patterns());
        if (!$includematch) {
            return false;
        }

        $excludematch = $this->path_match_patterns($filepath, static::get_exclude_patterns());
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
     * Helper function to get a specific linter config value, returns null if not set.
     * Optionally set togglekey to only return the value if it is set.
     * @param string $key
     * @param string|null $togglekey
     * @return mixed
     */
    protected static function get_config_value(string $key, ?string $togglekey = null): mixed {
        if ($togglekey !== null) {
            $enabled = (bool) self::get_config_value($togglekey);
            if (!$enabled) {
                return null;
            }
        }

        $config = static::get_config();
        if ($config === null) {
            return null;
        }

        if (!property_exists($config, $key)) {
            return null;
        }

        // phpcs:ignore moodle.Commenting.InlineComment
        // @phpstan-ignore-next-line phpstan/property.dynamicName (Checked above, probably fine)
        return $config->$key;
    }

    /**
     * Defines all configurable options for the current linter.
     * @param MoodleQuickForm $form
     * @return void
     */
    public static function define_config(MoodleQuickForm $form): void {
        $enabledoptions = [
            self::LINTER_STATUS_ENABLED => get_string('enable'),
            self::LINTER_STATUS_DISABLED => get_string('disable'),
        ];
        $form->addElement('select', self::CONFIG_KEY_STATUS, 'Linter status', $enabledoptions);
        $form->setDefault(self::CONFIG_KEY_STATUS, self::LINTER_STATUS_ENABLED);

        $patterns = [
            self::CONFIG_KEY_EXCLUDE_PATTERNS_ENABLED => self::CONFIG_KEY_EXCLUDE_PATTERNS,
            self::CONFIG_KEY_INCLUDE_PATTERNS_ENABLED => self::CONFIG_KEY_INCLUDE_PATTERNS,
        ];
        foreach ($patterns as $togglename => $name) {
            self::define_config_textarea($form, $name, $togglename);
        }

        return;
    }

    /**
     * Utility function for adding a toggleable textarea.
     * @param MoodleQuickForm $form
     * @param string $name
     * @param string $togglename
     * @return void
     */
    public static function define_config_textarea(MoodleQuickForm $form, string $name, string $togglename): void {
        $form->addElement('checkbox', $togglename, "Enable $name");
        $form->addElement('textarea', $name, $name);
        $form->disabledIf($name, $togglename);
        $form->hideIf($name, $togglename);
    }

    /**
     * Get the configuration name for this linter.
     * @return string
     */
    public static function get_config_name(): string {
        $name = self::get_name();
        return "linter_config:$name";
    }

    /**
     * Gets the linter config.
     * @return object|null
     */
    public static function get_config(): ?object {
        try {
            $configstring = get_config('local_devkit', self::get_config_name());
        } catch (dml_exception) {
            return null;
        }
        if ($configstring === '' || $configstring === false) {
            return null;
        }
        $config = json_decode($configstring, false);
        return is_object($config) ? $config : null;
    }

    /**
     * Saves the linter config.
     * @param object $config
     * @return void
     */
    public static function save_config(object $config): void {
        $configstring = json_encode($config);
        if ($configstring === false) {
            throw new Exception('Something went while wrong encoding linter config.');
        }
        set_config(self::get_config_name(), $configstring, 'local_devkit');
    }

    /**
     * Utility function to parse textarea multiline strings as an array.
     * Splits at new lines, trims each line, and filters empty lines.
     * @param string $string
     * @return string[]
     */
    protected static function parse_multiline_string_as_array(string $string): array {
        $lines = explode("\n", $string);
        return array_filter(array_map(trim(...), $lines), fn($line) => $line !== '');
    }
}
