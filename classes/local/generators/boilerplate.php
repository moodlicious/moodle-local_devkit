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

namespace local_devkit\local\generators;

use Exception;

/**
 * Moodle boilerplate generator.
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class boilerplate {
    /**
     * Get the canonical GPL boilerplate.
     * @return string
     */
    public static function get_boilerplate(bool $usehttps): string {
        $path = __DIR__ . '/../../../content/gpl-boilerplate.txt';
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new Exception('Unable to load boilerplate');
        }

        if ($usehttps) {
            $raw = str_replace(
                ['http://moodle.org', 'http://www.gnu.org'],
                ['https://moodle.org', 'https://www.gnu.org'],
                $raw,
            );
        }

        return $raw;
    }

    /**
     * Get the canonical GPL boilerplate with JS line comments.
     * @param bool $usehttps
     * @return string
     */
    public static function generate_for_javascript(bool $usehttps): string {
        $raw = self::get_boilerplate($usehttps);

        $lines = explode("\n", rtrim($raw));
        $commented = array_map(fn(string $line): string => $line === '' ? '//' : "// $line", $lines);

        return implode("\n", $commented) . "\n";
    }

    /**
     * Get the canonical GPL boilerplate wrapped as a Mustache comment block.
     * @param bool $usehttps
     * @return string
     */
    public static function generate_for_mustache(bool $usehttps): string {
        $raw = self::get_boilerplate($usehttps);

        $lines = explode("\n", rtrim($raw));
        $indented = array_map(fn(string $line): string => $line === '' ? '' : "    $line", $lines);
        $inner = implode("\n", $indented);

        return "{{!\n$inner\n}}\n";
    }
}
