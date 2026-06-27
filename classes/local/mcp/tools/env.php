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

namespace local_devkit\local\mcp\tools;

use Mcp\Exception\ToolCallException;
use Throwable;

/**
 * Env API.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class env {
    /**
     * Get an overview of the current Moodle environment.
     * @return object
     */
    public static function env_overview(): object {
        try {
            $data = \local_devkit\local\api\env::overview();
            return (object) ['data' => $data];
        } catch (Throwable $e) {
            throw new ToolCallException($e->getMessage());
        }
    }
}
