<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_devkit\local\api;

use local_devkit\local\utils;

/**
 * Utilities for getting env.
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class env {
    /**
     * Gets an overview of the current Moodle environment.
     * @return array<string, mixed>
     */
    public static function overview(): array {
        $configkeys = [
            'gitbranch',
            'wwwroot',
            'version',
            'release',
            'branch',
            'os',
            'ostype',
            'dbtype',
        ];
        $overviewitems = array_map(function (string $key): array {
            global $CFG;
            $value = property_exists($CFG, $key) ? $CFG->$key : '';
            return [$key => $value];
        }, $configkeys);

        array_unshift($overviewitems, ['moodleroot' => utils::get_moodle_root_dir()]);

        /** @var array<string, mixed> $overview */
        $overview = array_merge(...$overviewitems);
        $overview['gitbranch'] = "MOODLE_{$overview['branch']}_STABLE";
        unset($overview['branch']);

        return $overview;
    }
}
