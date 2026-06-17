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

namespace local_devkit\local\mcp\tools;

use Exception;
use local_devkit\local\cli\commands\database\database_show;
use local_devkit\local\cli\commands\database\database_table;
use Mcp\Exception\ToolCallException;

/**
 * Plugins API.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class database {
    /**
     * Show database tables defined, all or for a specific plugin component.
     * @param string|null $component The plugin component name. E.g. mod_assign.
     * @return object
     */
    public static function db_show_tables(?string $component): object {
        try {
            $data = database_show::get_data($component);
            return (object) ['data' => $data];
        } catch (Exception $e) {
            throw new ToolCallException($e->getMessage());
        }
    }

    /**
     * Get the fields, indexes and keys of a specific database table.
     * @param string $tablename The database table name.
     * @return object
     */
    public static function db_get_table(string $tablename): object {
        try {
            $data = database_table::get_data($tablename);
            return (object) ['data' => $data];
        } catch (Exception $e) {
            throw new ToolCallException($e->getMessage());
        }
    }
}
