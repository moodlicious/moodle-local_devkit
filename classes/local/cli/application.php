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

namespace local_devkit\local\cli;

use local_devkit\local\cli\commands\database\database_format;
use local_devkit\local\cli\commands\database\database_show;
use local_devkit\local\cli\commands\database\database_table;
use local_devkit\local\cli\commands\env\env_show;
use local_devkit\local\cli\commands\format;
use local_devkit\local\cli\commands\lint\handler as lint_handler;
use local_devkit\local\cli\commands\mcp\mcp_serve;
use local_devkit\local\cli\commands\plugins\plugins_list;
use local_devkit\local\cli\commands\purge\handler as purge_handler;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * DevKit console application.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class application extends BaseApplication {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('devkit');
        $this->addCommand(new plugins_list());
        $this->addCommand(new database_format());
        $this->addCommand(new database_show());
        $this->addCommand(new database_table());
        $this->addCommand(new env_show());
        $this->addCommand(new format());
        $this->addCommand(new mcp_serve());
        lint_handler::register($this);
        purge_handler::register($this);
    }
}
