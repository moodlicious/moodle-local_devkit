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
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

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

    #[\Override]
    public static function get_include_patterns(): array {
        return [
            ...parent::get_include_patterns(),
            ...['*.php'],
        ];
    }

    /**
     * Get the rule level to be analysed.
     * @return int
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
     * @return string
     */
    public static function get_result_cache_mode(): string {
        $config = self::get_config_value(self::CONFIG_KEY_RESULT_CACHE_MODE);
        $validmodes = [self::RESULT_CACHE_NORMAL, self::RESULT_CACHE_PER_COMPONENT];
        if ($config === null || !in_array($config, $validmodes, true)) {
            return self::RESULT_CACHE_PER_COMPONENT;
        }

        return $config;
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
        $binary = $this->get_phpstan_binary_path();
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
        if (!$output) {
            $error = $process->getErrorOutput();
            $issue = phpstan_issue::simple($error);
            $results[] = new file($path, [$issue]);
            return $results;
        }

        return $this->parse_json($output, $path);
    }

    /**
     * Parses the phpstan JSON result.
     * @param string $output
     * @param string $path
     * @return file[]
     */
    private function parse_json(string $output, string $path) {
        $results = [];
        $jsonoutput = json_decode($output);
        if ($jsonoutput === null) {
            $issue = new phpstan_issue(
                0,
                0,
                "'phpstan' returned non-JSON output",
                'phpstan-json-parse-error',
                $this->get_name(),
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

        foreach ($jsonoutput->files as $path => $lintedfile) {
            $issues = [];
            $messages = $lintedfile->messages;
            foreach ($messages as $message) {
                $issue = phpstan_issue::from_object($message);
                if ($issue) {
                    $issues[] = $issue;
                }
            }

            $results[] = new file($path, $issues);
        }

        return $results;
    }

    /**
     * Generates a temporary config neon for linting.
     * @return string
     */
    public function generate_temp_config_neon(string $path): string {
        global $CFG;

        $neondirpath = $this->generate_temp_dir($path);
        $neonpath = "$neondirpath/phpstan.neon";

        $devkitpath = "{$CFG->dirroot}/local/devkit";
        $moodleneonpath = realpath("$devkitpath/vendor/micaherne/phpstan-moodle/extension.neon");
        $deprecationrules = realpath("$devkitpath/vendor/phpstan/phpstan-deprecation-rules/rules.neon");
        $devkitbootstrap = realpath("$devkitpath/phpstan-bootstrap.php");

        $usetempdir = match (self::get_result_cache_mode()) {
            self::RESULT_CACHE_PER_COMPONENT => true,
            self::RESULT_CACHE_NORMAL => false,
            default => null,
        };

        $excludes = self::get_exclude_patterns(includethirdparty: true);

        $moodleroot = utils::get_moodle_root_dir();
        $rulelevel = self::get_rule_level();

        $config = [
            'includes' => [
                $moodleneonpath,
                $deprecationrules,
            ],
            'parameters' => [
                'level' => $rulelevel,
                'paths' => [$moodleroot],
                'excludePaths' => $excludes,
                'moodle' => [
                    'rootDirectory' => $moodleroot,
                ],
                'bootstrapFiles' => [
                    $devkitbootstrap,
                ],
            ],
        ];

        if ($usetempdir) {
            $config['parameters']['tmpDir'] = 'tmp';
        }

        $phpstandotneon = Yaml::dump($config, 10);

        file_put_contents($neonpath, $phpstandotneon);
        return $neonpath;
    }

    /**
     * Generate a temp directory for the current run.
     * @param string $path
     * @return string
     */
    public function generate_temp_dir(string $path): string {
        global $CFG;

        // Component resolving might fail during CI so catch any errors and fallback to '_'.
        $component = null;
        try {
            $component = component::resolve_component_from_path(utils::get_path_relative_to_moodle_root($path)) ?: '_';
        } catch (\Exception $th) {
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
     * @return string
     */
    public function get_config_neon(string $path): string {
        $filename = 'phpstan.neon';
        $path = is_file($path) ? dirname($path) : $path;
        $currentdir = realpath($path);

        if (!$currentdir) {
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
     * @return string|null
     */
    public static function get_phpstan_binary_path(): ?string {
        global $CFG;
        $path = $CFG->dirroot . '/local/devkit/vendor/bin/phpstan';
        return realpath($path) ?: null;
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
    }
}
