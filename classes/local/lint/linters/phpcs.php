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
use local_devkit\local\lint\schemas\issue\phpcs as phpcs_issue;
use local_devkit\local\lint\severity;
use local_devkit\local\lint\schemas\file;
use MoodleQuickForm;
use Symfony\Component\Process\Process;

/**
 * The 'php -l' linter.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[linter(
    name: 'phpcs',
    description: 'executes "phpcs" php-codesniffer against project coding standards',
)]
class phpcs extends base {
    /** @var string */
    public const CONFIG_KEY_EXCLUDED_SNIFFS_ENABLED = 'excluded_sniffs_enabled';
    /** @var string */
    public const CONFIG_KEY_EXCLUDED_SNIFFS = 'excluded_sniffs';

    #[\Override]
    public static function get_include_patterns(): array {
        return [
            ...parent::get_include_patterns(),
            ...['*.php'],
        ];
    }

    /**
     * Get the sniffs to be excluded.
     * @return string[]
     */
    public static function get_excluded_sniffs(): array {
        $config = self::get_config_value(self::CONFIG_KEY_EXCLUDED_SNIFFS, self::CONFIG_KEY_EXCLUDED_SNIFFS_ENABLED);
        if (!$config) {
            return [];
        }

        return self::parse_multiline_string_as_array($config);
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

        $this->set_progress_file($filepath);
        return [...$results, ...$this->execute_phpcs($filepath)];
    }

    #[\Override]
    public function lint_directory(string $directorypath): array {
        $this->set_progress_file($directorypath);
        return $this->execute_phpcs($directorypath);
    }

    /**
     * Executes phpcs on a given path.
     * @param string $path
     * @return file[]
     */
    private function execute_phpcs($path): array {
        $excludepatterns = self::get_exclude_patterns();
        $excludedsniffs = self::get_excluded_sniffs();
        $ignore = $excludepatterns ? ['--ignore=' . implode(',', $excludepatterns)] : [];
        $exclude = $excludedsniffs ? ['--exclude=' . implode(',', $excludedsniffs)] : [];
        $process = new Process([
            'phpcs',
            '--cache',
            '-q',
            '--report=json',
            ...$ignore,
            ...$exclude,
            $path,
        ], timeout: MINSECS * 15);
        $process->run();

        $output = $process->getOutput();
        return $this->parse_phpcs_json($output, $path);
    }

    /**
     * Parses the PHPCS JSON result.
     * @param string $output
     * @param string $path
     * @return file[]
     */
    private function parse_phpcs_json(string $output, string $path) {
        $results = [];
        $jsonoutput = json_decode($output);
        if ($jsonoutput === null) {
            $issue = new phpcs_issue(
                0,
                0,
                "'phpcs' returned non-JSON output.",
                'phpcs-json-error',
                $this->get_name(),
                severity::error,
            );
            $results[] = new file($path, [$issue]);
            return $results;
        }

        foreach ($jsonoutput->files as $path => $lintedfile) {
            $issues = [];
            $messages = $lintedfile->messages;
            foreach ($messages as $message) {
                $issue = phpcs_issue::from_object($message);
                if ($issue) {
                    $issues[] = $issue;
                }
            }

            $results[] = new file($path, $issues);
        }

        return $results;
    }

    #[\Override]
    public static function define_config(MoodleQuickForm $form): void {
        parent::define_config($form);
        self::define_config_textarea($form, self::CONFIG_KEY_EXCLUDED_SNIFFS, self::CONFIG_KEY_EXCLUDED_SNIFFS_ENABLED);
    }
}
