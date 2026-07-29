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

/**
 * Fixture file for testing NormaliseGlobalDbRector.
 *
 * `global $DB;` alone → `$DB = \core\di::get(\moodle_database::class);`
 * `global $X, $DB, $Y;` → `global $X, $Y;` + `$DB = \core\di::get(\moodle_database::class);`
 *
 * @package   local_devkit
 * @copyright 2026 Felix
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Full transformation: global $DB alone replaced with DI assignment.
function test_simple(): void {
    global $DB;

    $record = $DB->get_record('user', ['id' => 1]);
    $DB->insert_record('user', $record);
}

// Multiple items in one global: $DB removed, assignment added after.
function test_mixed_global(): void {
    global $CFG, $DB, $USER;

    $DB->get_records('course');
    $USER->id;
}

// $DB on its own line → replaced; other globals on separate lines stay.
function test_separate_globals(): void {
    global $DB;
    global $USER, $CFG;

    $DB->count_records('user');
}

// Should NOT be transformed: no global $DB at all.
function test_no_global_db(): void {
    global $USER;
    $USER->id;
}

// Should NOT be transformed: $DB used without global (outside scope).
function test_db_without_global(): void {
    $DB->get_record('course', ['id' => 1]);
}
