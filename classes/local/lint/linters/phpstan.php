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

use local_devkit\local\attributes\linter;
use local_devkit\local\component;
use local_devkit\local\lint\schemas\file;
use local_devkit\local\lint\schemas\issue\phpstan as phpstan_issue;
use local_devkit\local\lint\severity;
use local_devkit\local\utils;
use MoodleQuickForm;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

use function count;
use function dirname;
use function in_array;

/**
 * The 'phpstan' linter.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[linter(
    name: 'phpstan',
    description: 'executes phpstan for static code analysis',
)]
class phpstan extends base {
    /** @var string */
    public const RESULT_CACHE_NORMAL = 'normal';
    /** @var string */
    public const RESULT_CACHE_PER_COMPONENT = 'per_component';

    /** @var string */
    public const CONFIG_KEY_RULE_LEVEL = 'rule_level';
    /** @var string */
    public const CONFIG_KEY_RESULT_CACHE_MODE = 'result_cache_mode';
    /** @var string */
    public const CONFIG_KEY_STRICT_RULES = 'struct_rules';
    /** @var string */
    public const CONFIG_KEY_STRICT_RULES_DEFAULT = '1';

    #[\Override]
    public static function get_include_patterns(): array {
        return [
            ...parent::get_include_patterns(),
            ...['*.php'],
        ];
    }

    /**
     * Get the rule level to be analysed.
     */
    public static function get_rule_level(): int {
        $config = self::get_config_value(self::CONFIG_KEY_RULE_LEVEL);
        if ($config === null || !is_numeric($config)) {
            return 8;
        }

        $level = (int) $config;
        if ($level < 0 || $level > 10) {
            return 8;
        }

        return $level;
    }

    /**
     * Get if per component result cache should be used.
     */
    public static function get_result_cache_mode(): string {
        $config = self::get_config_value(self::CONFIG_KEY_RESULT_CACHE_MODE);
        $validmodes = [self::RESULT_CACHE_NORMAL, self::RESULT_CACHE_PER_COMPONENT];
        if ($config === null || !in_array($config, $validmodes, true)) {
            return self::RESULT_CACHE_PER_COMPONENT;
        }

        return $config;
    }

    /**
     * Get if per component result cache should be used.
     */
    public static function get_strict_rules_enabled(): bool {
        return (bool) (self::get_config_value(self::CONFIG_KEY_STRICT_RULES) ?? self::CONFIG_KEY_STRICT_RULES_DEFAULT);
    }

    #[\Override]
    public function lint_file(string $filepath): array {
        $results = parent::lint_file($filepath);
        if (!$this->can_lint_file($filepath)) {
            return $results;
        }

        $filepath = realpath($filepath);
        if ($filepath === false) {
            return [];
        }

        return [...$results, ...$this->execute($filepath)];
    }

    #[\Override]
    public function lint_directory(string $directorypath): array {
        $this->set_progress_file($directorypath);
        return $this->execute($directorypath);
    }

    /**
     * Executes phpstan on a given path.
     * @param string $path
     * @return file[]
     */
    private function execute($path): array {
        $binary = self::get_phpstan_binary_path();
        $config = $this->get_config_neon($path);
        $process = new Process([
            'php',
            $binary,
            'analyze',
            "-c",
            $config,
            $path,
            '--memory-limit=2G',
            '--error-format',
            'json',
        ], timeout: MINSECS * 15);
        $process->run();

        $output = $process->getOutput();
        if ($output === '') {
            $error = $process->getErrorOutput();
            $issue = phpstan_issue::simple($error);
            return [new file($path, [$issue])];
        }

        return $this->parse_json($output, $path);
    }

    /**
     * Parses the phpstan JSON result.
     * @return file[]
     */
    private function parse_json(string $output, string $path): array {
        $results = [];
        $jsonoutput = json_decode($output);
        if ($jsonoutput === null) {
            $issue = new phpstan_issue(
                0,
                0,
                "'phpstan' returned non-JSON output",
                'phpstan-json-parse-error',
                self::get_name(),
                severity::error,
            );
            $results[] = new file($path, [$issue]);
            return $results;
        }

        // Log any fatal errors.
        foreach ($jsonoutput->errors as $error) {
            // Log the fatal issue but don't return it yet,
            // as there may still be things to log below in $jsonoutput->files loop.
            $results[] = self::create_file_with_fatal_issue($path, $error);
        }

        foreach ($jsonoutput->files as $filepath => $lintedfile) {
            $issues = [];
            $messages = $lintedfile->messages;

            [$filepath, $context] = self::strip_context_suffix($filepath);

            foreach ($messages as $message) {
                $issue = phpstan_issue::from_object($message);
                if ($issue === null) {
                    continue;
                }
                if ($context !== null) {
                    array_unshift($issue->suggestions, $context);
                }
                $issues[] = $issue;
            }

            $results[] = new file($filepath, $issues);
        }

        return $results;
    }

    /**
     * Strip the "(in context of class ...)" suffix that PHPStan appends to trait file paths.
     *
     * @param string $path The raw path from PHPStan's JSON output.
     * @return array{0: string, 1: string|null} The clean path and the context string, or null if no suffix.
     */
    private static function strip_context_suffix(string $path): array {
        if (preg_match('/^(.+)\s+\(in context of class (.+)\)$/', $path, $matches) === 1) {
            return [$matches[1], "In context of class {$matches[2]}"];
        }
        return [$path, null];
    }

    /**
     * Generates a temporary config neon for linting.
     */
    public function generate_temp_config_neon(string $path): string {
        global $CFG;

        $neondirpath = $this->generate_temp_dir($path);
        $neonpath = "$neondirpath/phpstan.neon";

        $devkitpath = "{$CFG->dirroot}/local/devkit";
        $moodleneonpath = realpath("$devkitpath/vendor/micaherne/phpstan-moodle/extension.neon");
        $deprecationrules = realpath("$devkitpath/vendor/phpstan/phpstan-deprecation-rules/rules.neon");
        $strictrules = realpath("$devkitpath/vendor/phpstan/phpstan-strict-rules/rules.neon");
        $devkitbootstrap = realpath("$devkitpath/phpstan-bootstrap.php");

        $usetempdir = match (self::get_result_cache_mode()) {
            self::RESULT_CACHE_PER_COMPONENT => true,
            self::RESULT_CACHE_NORMAL => false,
            default => null,
        };

        $thirdpartyexcludes = self::get_third_party_exclude_patterns();
        $excludes = self::get_exclude_patterns();

        $moodleroot = utils::get_moodle_root_dir();
        $rulelevel = self::get_rule_level();

        $requiredfiles = [$moodleneonpath, $deprecationrules, $devkitbootstrap];
        if (self::get_strict_rules_enabled()) {
            $requiredfiles[] = $strictrules;
        }
        $missingfiles = array_filter(
            $requiredfiles,
            fn($file): bool => $file === false,
        );
        if (count($missingfiles) > 0) {
            throw new \RuntimeException(
                'PHPStan rule files not found. Please run \'composer install\' in the devkit directory.',
            );
        }

        $config = [
            'includes' => [$moodleneonpath, $deprecationrules],
            'parameters' => [
                'level' => $rulelevel,
                'paths' => [$moodleroot],
                'excludePaths' => [
                    'analyse' => $thirdpartyexcludes,
                    'analyseAndScan' => $excludes,
                ],
                'moodle' => [
                    'rootDirectory' => $moodleroot,
                ],
                'bootstrapFiles' => [$devkitbootstrap],
            ],
        ];

        if (self::get_strict_rules_enabled()) {
            $config['includes'][] = $strictrules;
        }

        if ($usetempdir === true) {
            $config['parameters']['tmpDir'] = 'tmp';
        }

        $stubs = $this->get_stub_files();
        if (count($stubs) > 0) {
            $config['parameters']['stubFiles'] = $stubs;
        }

        $phpstandotneon = Yaml::dump($config, 10);

        file_put_contents($neonpath, $phpstandotneon);
        return $neonpath;
    }

    /**
     * Get all stub file paths.
     * @return string[]
     */
    public function get_stub_files(): array {
        global $CFG;

        require_once(__DIR__ . '/../../../../vendor/autoload.php');
        $stubsdir = "$CFG->dirroot/local/devkit/phpstan/stubs";

        if (!is_dir($stubsdir)) {
            return [];
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in($stubsdir)
            ->name('*.stub');

        $paths = [];
        foreach ($finder as $file) {
            $paths[] = $file->getRealPath();
        }

        return $paths;
    }

    /**
     * Generate a temp directory for the current run.
     */
    public function generate_temp_dir(string $path): string {
        global $CFG;

        // Component resolving might fail during CI so catch any errors and fallback to '_'.
        $component = null;
        try {
            $component = component::resolve_component_from_path(utils::get_path_relative_to_moodle_root($path)) ?? '_';
        } catch (\Exception) {
            $component = '_';
        }

        $phpstantempdir = "$CFG->tempdir/local_devkit/phpstan";
        $tmpdir = "$phpstantempdir/runs/$component";
        @mkdir($tmpdir, recursive: true);
        return $tmpdir;
    }

    /**
     * Walks up the file path until we find a phpstan.neon.
     * If none found, then generate one.
     */
    public function get_config_neon(string $path): string {
        $filename = 'phpstan.neon';
        $path = is_file($path) ? dirname($path) : $path;
        $currentdir = realpath($path);

        if ($currentdir === false) {
            return $this->generate_temp_config_neon($path);
        }

        while (true) {
            $neon = $currentdir . DIRECTORY_SEPARATOR . $filename;
            if (is_file($neon)) {
                return $neon;
            }

            $parentdir = dirname($currentdir);
            if ($parentdir === $currentdir) {
                break;
            }

            $currentdir = $parentdir;
        }

        return $this->generate_temp_config_neon($path);
    }

    /**
     * Gets the phpstan binary path.
     */
    public static function get_phpstan_binary_path(): ?string {
        global $CFG;
        $path = $CFG->dirroot . '/local/devkit/vendor/bin/phpstan';
        $path = realpath($path);
        if ($path === false) {
            return null;
        }
        return $path;
    }

    #[\Override]
    public static function is_installed(): bool {
        return self::get_phpstan_binary_path() !== null;
    }

    #[\Override]
    public static function define_config(MoodleQuickForm $form): void {
        parent::define_config($form);

        $levels = [];
        foreach (range(0, 10) as $level) {
            $levels["$level"] = "Level $level";
        }

        $form->addElement('select', self::CONFIG_KEY_RULE_LEVEL, 'Rule level', $levels);
        $form->setDefault(self::CONFIG_KEY_RULE_LEVEL, "8");

        $modes = [
            self::RESULT_CACHE_NORMAL => 'Normal (shared cache between runs)',
            self::RESULT_CACHE_PER_COMPONENT => 'Each component gets its own cache',
        ];
        $form->addElement('select', self::CONFIG_KEY_RESULT_CACHE_MODE, 'Result cache mode', $modes);
        $form->setDefault(self::CONFIG_KEY_RESULT_CACHE_MODE, self::RESULT_CACHE_PER_COMPONENT);

        $form->addElement('selectyesno', self::CONFIG_KEY_STRICT_RULES, 'Use strict rules');
        $form->setDefault(self::CONFIG_KEY_STRICT_RULES, self::CONFIG_KEY_STRICT_RULES_DEFAULT);
    }
}
