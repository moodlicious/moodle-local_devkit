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

namespace local_devkit\local\lint\formatters;

use local_devkit\local\lint\schemas\file;
use local_devkit\local\lint\severity;
use local_devkit\local\utils;

use function count;

/**
 * Class github
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class github extends base {
    /**
     * The plugin root directory for computing repo-relative annotation paths.
     * @var string|null
     */
    private ?string $pluginroot = null;

    #[\Override]
    public function output(array $linters, array $results): int {
        $filecount = count($results);
        $issuecount = 0;

        foreach ($results as $fileresult) {
            $issues = $fileresult->issues;
            $issuecount += count($issues);

            $component = $this->displaycomponent ? $fileresult->get_component() : null;

            foreach ($issues as $issue) {
                $severity = $this->map_severity($issue->severity);
                $message = $issue->message;
                $rule = "{$issue->source}/{$issue->rule}";

                $file = $this->get_annotation_path($fileresult);
                $line = $issue->line ?? 1;
                $column = $issue->column ?? 1;

                if ($component !== null) {
                    $message = "[{$component}] {$message}";
                }

                if ($issue->suggestions) {
                    $message = "$message\nSuggestions:\n" . implode($issue->suggestions);
                }

                $this->io->writeln(sprintf(
                    '::%s file=%s,line=%d,col=%d,title=%s::%s',
                    $severity,
                    $this->escape_property($file),
                    $line,
                    $column,
                    $this->escape_property($rule),
                    $this->escape_message($message),
                ));
            }
        }

        // Optional summary line for humans.
        $this->io->writeln("Linted $filecount files with $issuecount issues.");

        return self::exit_code($results);
    }

    /**
     * Maps devkit severity to github annotations.
     * @param severity $severity
     * @return string
     */
    protected function map_severity(severity $severity): string {
        return match ($severity) {
            severity::error, severity::fatal => 'error',
            severity::warning => 'warning',
            severity::info => 'notice',
            default => 'warning',
        };
    }

    /**
     * Escapes property value.
     * @param string $value
     * @return string
     */
    protected function escape_property(string $value): string {
        return str_replace(
            ['%', "\r", "\n", ':', ','],
            ['%25', '%0D', '%0A', '%3A', '%2C'],
            $value,
        );
    }

    /**
     * Escapes message.
     * @param string $value
     * @return string
     */
    protected function escape_message(string $value): string {
        return str_replace(
            ['%', "\r", "\n"],
            ['%25', '%0D', '%0A'],
            $value,
        );
    }

    /**
     * Set the plugin root path for annotation path computation.
     * @param string $path Absolute path to the plugin root.
     */
    public function set_plugin_root(string $path): void {
        $this->pluginroot = rtrim($path, '/');
    }

    /**
     * Gets path to file, a repo-relative path.
     * @param file $file
     * @return string
     */
    protected function get_annotation_path(file $file): string {
        if ($this->pluginroot !== null) {
            $prefix = $this->pluginroot . '/';
            if (str_starts_with($file->file, $prefix)) {
                return substr($file->file, strlen($prefix));
            }
        }
        $path = utils::get_path_relative_to_moodle_root($file->file);
        return str_starts_with($path, './') ? substr($path, 2) : $path;
    }
}
