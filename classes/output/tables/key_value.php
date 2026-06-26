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

/**
 * Table for displaying object's properties and values.
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class key_value extends html_table {
    /**
     * Constructor.
     */
    public function __construct(object $data) {
        parent::__construct();
        $this->head = ['Key', 'Value'];
        $this->attributes['class'] = 'table table-hover';
        $this->data = [];
        foreach ((array) $data as $key => $value) {
            $encoded = html_writer::tag('code', (string) json_encode($value, JSON_PRETTY_PRINT));
            $this->data[] = [$key, html_writer::tag('pre', $encoded)];
        }
    }
}
