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

namespace local_devkit\output\tables;

use core\output\html_writer;
use core_table\output\html_table;
use local_devkit\local\api\linter;

/**
 * Table for listing linter configs.
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class linter_config extends html_table {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->head = [
            get_string('linter_config_table:linter', 'local_devkit'),
            get_string('linter_config_table:config', 'local_devkit'),
            get_string('linter_config_table:actions', 'local_devkit'),
        ];

        $linters = linter::get_linters_classnames();
        $this->data = [];

        foreach ($linters as $linter) {
            $config = $linter::get_config();
            $row = [
                $linter::get_name(),
                $config !== null
                    ? html_writer::table(new key_value($config))
                    : get_string('linter_config_table:notconfigured', 'local_devkit'),
                html_writer::link(
                    '#',
                    get_string('linter_config_table:configure', 'local_devkit'),
                    [
                        'data-linter-config-form' => 'true',
                        'data-linter-classname' => $linter,
                        'data-linter-name' => $linter::get_name(),
                    ],
                ),
            ];
            $this->data[] = $row;
        }

        $this->init_js();
    }

    /**
     * Initialises JS.
     */
    public function init_js(): void {
        global $PAGE;
        $PAGE->requires->js_call_amd('local_devkit/linter_config', 'init', []);
    }
}
