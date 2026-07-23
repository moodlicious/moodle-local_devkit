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

use function count;

/**
 * Unit tests for the component class.
 *
 * @package   local_devkit
 * @covers    \local_devkit\local\component
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class component_test extends advanced_testcase {
    /**
     * Test that get_component_path_map returns at least the devkit plugin itself.
     */
    public function test_get_component_path_map_contains_devkit(): void {
        $map = component::get_component_path_map();
        self::assertArrayHasKey('local_devkit', $map);
    }

    /**
     * Test that get_component_path_map_sorted_cached orders longest paths first.
     */
    public function test_sorted_map_orders_longest_paths_first(): void {
        $map = component::get_component_path_map_sorted_cached();
        $lengths = array_map(strlen(...), array_values($map));
        $counter = count($lengths);
        for ($i = 1; $i < $counter; $i++) {
            self::assertGreaterThanOrEqual($lengths[$i], $lengths[$i - 1]);
        }
    }

    /**
     * Test that resolve_component_from_path returns null for an unknown path.
     */
    public function test_resolve_unknown_path_returns_null(): void {
        $result = component::resolve_component_from_path('/nonexistent/plugin/templates/file.mustache');
        self::assertNull($result);
    }

    /**
     * Test that resolve_component_from_path resolves the devkit plugin itself.
     */
    public function test_resolve_devkit_path(): void {
        $map = component::get_component_path_map();
        self::assertArrayHasKey('local_devkit', $map);

        $devkitpath = $map['local_devkit'];
        $filepath = $devkitpath . '/templates/sometemplate.mustache';
        $result = component::resolve_component_from_path($filepath);
        self::assertSame('local_devkit', $result);
    }

    /**
     * Test that resolve_component_from_path rejects cross-directory prefix matches.
     */
    public function test_resolve_rejects_cross_directory_prefix_match(): void {
        $map = component::get_component_path_map();

        // Find any non-empty plugin path to test against for cross-directory matching.
        $shortpath = null;
        $shortcomponent = null;
        foreach ($map as $component => $path) {
            if ($path !== '') {
                $shortpath = $path;
                $shortcomponent = $component;
                break;
            }
        }

        if ($shortpath === null) {
            self::markTestSkipped('No suitable short plugin path found for cross-directory test');
        }

        $trickyfile = $shortpath . 'extra/templates/file.mustache';
        $result = component::resolve_component_from_path($trickyfile);
        self::assertNull($result, "Path '$trickyfile' should not match '$shortcomponent' via cross-directory prefix");
    }
}
