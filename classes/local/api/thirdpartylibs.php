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

namespace local_devkit\local\api;

use core_component;
use Exception;
use local_devkit\local\schema\thirdpartylib;

/**
 * Third party libs utilities.
 * Modified from public/admin/thirdpartylibs.php.
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class thirdpartylibs {
    /**
     * The third party lib xml file name.
     * @var string
     */
    public const XML_FILE_NAME = 'thirdpartylibs.xml';

    /**
     * Gets all thirdpartylibs.xml files.
     * @return string[]
     */
    public static function get_xml_files(): array {
        global $CFG;
        $xmlname = self::XML_FILE_NAME;
        $corefiles = [
            "$CFG->libdir/$xmlname",
        ];

        // The root lib/thirdpartylibs.xml only exists in 5.2 or above.
        if ($CFG->branch >= 502) {
            $corefiles[] = "$CFG->root/lib/$xmlname";
        }

        $plugintypes = array_keys(core_component::get_plugin_types());
        $pluginfiles = array_merge(
            ...array_map(
                fn(string $type): array => array_values(core_component::get_plugin_list_with_file($type, $xmlname)),
                $plugintypes,
            ),
        );

        return [
            ...$corefiles,
            ...$pluginfiles,
        ];
    }

    /**
     * Gets a list of absolute paths to all third party libraries.
     * @return thirdpartylib[]
     */
    public static function list(): array {
        $xmlfiles = self::get_xml_files();

        $libraries = [];
        foreach ($xmlfiles as $file) {
            $xml = simplexml_load_file($file);
            if ($xml === false) {
                continue;
            }

            foreach ($xml as $library) {
                try {
                    $libraries[] = thirdpartylib::from_xml_element($file, $library);
                } catch (Exception) {
                    continue;
                }
            }
        }

        return $libraries;
    }
}
