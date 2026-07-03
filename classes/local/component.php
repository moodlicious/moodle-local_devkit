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

use local_devkit\local\api\plugins;

/**
 * Class component
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class component {
    /**
     * Get an associative array with keys of component and values of the component directory.
     * @return array<string, string>
     */
    public static function get_component_path_map(): array {
        $plugins = plugins::list(true);
        $map = [];
        foreach ($plugins as $plugin) {
            $map[$plugin['component']] = utils::get_path_relative_to_moodle_root($plugin['directory']);
        }
        return $map;
    }

    /**
     * Returns a component-to-directory map, sorted with the longest paths first.
     *
     * This ordering ensures that when iterating with strict equality in
     * {@see self::resolve_component_from_path()}, sub-plugin directories
     * are checked before their parent plugin directory, avoiding false
     * prefix-based matches.
     *
     * Results are cached.
     * @return array<string, string>
     */
    public static function get_component_path_map_sorted_cached(): array {
        /** @var array<string, string>|null $result */
        static $result = null;
        if ($result !== null) {
            return $result;
        }

        $result = self::get_component_path_map();
        uasort($result, fn(string $a, string $b) => strlen($b) <=> strlen($a));
        return $result;
    }


    /**
     * Given a component directory, find the component name associated with the directory.
     * @param string $path
     * @return string|null
     */
    public static function resolve_component_from_path(string $path): ?string {
        $componentpathmap = self::get_component_path_map_sorted_cached();

        foreach ($componentpathmap as $component => $componentpath) {
            if (!str_starts_with($path, $componentpath)) {
                continue;
            }

            return $component;
        }

        return null;
    }
}
