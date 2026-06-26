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

namespace local_devkit\local;

/**
 * Observer.
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Like {@see array_filter} but stops when callback returns false.
     *
     *  // phpcs:ignore moodle.Commenting.ValidTags.Invalid
     * @template TItem
     *
     * @param TItem[] $array
     * @param callable $callback
     * @return TItem[]
     */
    public static function array_filter_left(array $array, callable $callback): array {
        $result = [];
        foreach ($array as $item) {
            $match = $callback($item);
            if (!$match) {
                break;
            }
            $result[] = $item;
        }
        return $result;
    }

    /**
     * Returns the root directory of the Moodle installation.
     *
     * @return string
     */
    public static function get_moodle_root_dir(): string {
        global $CFG;

        if (isset($CFG->root)) {
            return $CFG->root;
        }

        return $CFG->dirroot;
    }

    /**
     * Returns the relative path from Moodle root directory to the provided path.
     * The returned path will use forward slashes '/' regardless of platform.
     * E.g. /path/to/moodle/public/mod/forum/version.php -> ./public/mod/forum/version.php.
     *
     * Any errors the path will be returned as is, e.g. path does not exist.
     * @param string $path
     * @return string
     */
    public static function get_path_relative_to_moodle_root(string $path): string {
        $root = self::get_moodle_root_dir();

        $realroot = realpath($root);
        $realpath = realpath($path);

        if ($realroot === false || $realpath === false) {
            return $path;
        }

        $rootnormalised = str_replace('\\', '/', $realroot);
        $pathnormalised = str_replace('\\', '/', $realpath);

        $rootdir = rtrim($rootnormalised, '/') . '/';
        $pathdir = rtrim($pathnormalised, '/') . '/';

        if (strpos($pathdir, $rootdir) !== 0) {
            return $path;
        }

        $relative = rtrim(substr($pathdir, strlen($rootdir)), '/');
        return './' . $relative;
    }
}
