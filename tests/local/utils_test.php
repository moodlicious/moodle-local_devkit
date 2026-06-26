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

declare(strict_types=1);

namespace local_devkit\local;

use advanced_testcase;

/**
 * Unit tests for the utils class.
 *
 * @package   local_devkit
 * @covers    \local_devkit\local\utils
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class utils_test extends advanced_testcase {
    /**
     * Test that array_filter_left returns all items when all match.
     */
    public function test_array_filter_left_returns_all_items_when_all_match(): void {
        $array = [1, 2, 3];
        $result = utils::array_filter_left($array, fn($item) => $item > 0);

        $this->assertSame([1, 2, 3], $result);
    }

    /**
     * Test that array_filter_left stops when callback returns false.
     */
    public function test_array_filter_left_stops_on_first_false(): void {
        $array = [1, 2, 3, 4, 5];
        $result = utils::array_filter_left($array, fn($item) => $item < 3);

        $this->assertSame([1, 2], $result);
    }

    /**
     * Test that array_filter_left returns empty when first item fails.
     */
    public function test_array_filter_left_returns_empty_when_first_fails(): void {
        $array = [1, 2, 3];
        $result = utils::array_filter_left($array, fn($item) => $item > 1);

        $this->assertSame([], $result);
    }

    /**
     * Test that array_filter_left works with strings.
     */
    public function test_array_filter_left_works_with_strings(): void {
        $array = ['a', 'b', 'c', 'stop', 'd'];
        $result = utils::array_filter_left($array, fn($item) => $item !== 'stop');

        $this->assertSame(['a', 'b', 'c'], $result);
    }

    /**
     * Test that array_filter_left handles empty array.
     */
    public function test_array_filter_left_handles_empty_array(): void {
        $array = [];
        $result = utils::array_filter_left($array, fn($item) => true);

        $this->assertSame([], $result);
    }

    /**
     * Test that get_path_relative_to_moodle_root returns path relative to dirroot.
     */
    public function test_get_path_relative_to_moodle_root(): void {
        global $CFG;

        $root = make_temp_directory('devkit_test_rel_root');
        $CFG->root = null;
        $CFG->dirroot = $root;

        $subdir = $root . '/public/mod/forum';
        check_dir_exists($subdir, true, true);
        touch($subdir . '/version.php');

        $path = $subdir . '/version.php';
        $result = utils::get_path_relative_to_moodle_root($path);

        $this->assertSame('./public/mod/forum/version.php', $result);
    }

    /**
     * Test that get_path_relative_to_moodle_root uses $CFG->root when set.
     */
    public function test_get_path_relative_to_moodle_root_uses_cfg_root(): void {
        global $CFG;

        $root = make_temp_directory('devkit_test_rel_root2');
        $CFG->root = $root;

        $subdir = $root . '/mod/forum';
        check_dir_exists($subdir, true, true);
        touch($subdir . '/version.php');

        $path = $subdir . '/version.php';
        $result = utils::get_path_relative_to_moodle_root($path);

        $this->assertSame('./mod/forum/version.php', $result);
    }

    /**
     * Test that path outside root is returned as-is.
     */
    public function test_get_path_relative_to_moodle_root_outside_root(): void {
        global $CFG;

        $root = make_temp_directory('devkit_test_rel_root3');
        $CFG->root = $root;

        $outside = make_temp_directory('devkit_test_rel_outside');
        touch($outside . '/file.php');

        $path = $outside . '/file.php';
        $result = utils::get_path_relative_to_moodle_root($path);

        $this->assertSame($path, $result);
    }

    /**
     * Test that non-existent path returns the path as-is.
     */
    public function test_get_path_relative_to_moodle_root_nonexistent(): void {
        global $CFG;

        $root = make_temp_directory('devkit_test_rel_root4');
        $CFG->root = null;
        $CFG->dirroot = $root;

        $path = $root . '/nonexistent/file.php';
        $result = utils::get_path_relative_to_moodle_root($path);

        $this->assertSame($path, $result);
    }

    /**
     * Test that the root path itself returns './'.
     */
    public function test_get_path_relative_to_moodle_root_self(): void {
        global $CFG;

        $root = make_temp_directory('devkit_test_rel_root5');
        $CFG->root = null;
        $CFG->dirroot = $root;

        $result = utils::get_path_relative_to_moodle_root($root);

        $this->assertSame('./', $result);
    }

    /**
     * Test that nonexistent root returns the path as-is.
     */
    public function test_get_path_relative_to_moodle_root_nonexistent_root(): void {
        $path = '/nonexistent/path/file.php';
        $result = utils::get_path_relative_to_moodle_root($path);

        $this->assertSame($path, $result);
    }
}
