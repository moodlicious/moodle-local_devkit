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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Fixture file for testing SimplifyRequireConfigPathRector.
 *
 * Each require/require_once below should be transformed to use __DIR__.
 *
 * @package   local_devkit
 * @copyright 2026 Felix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Chain forms: dirname(dirname(...dirname(__DIR__)...)).
require_once(dirname(dirname(__DIR__)) . '/config.php');
require(dirname(dirname(__DIR__)) . '/config.php');
require_once(dirname(dirname(dirname(__DIR__))) . '/config.php');
require(dirname(dirname(dirname(__DIR__))) . '/config.php');
require(dirname(dirname(dirname(dirname(__DIR__)))) . '/config.php');

// Chain forms with __FILE__ instead of __DIR__.
require_once(dirname(dirname(__FILE__)) . '/config.php');
require(dirname(dirname(__FILE__)) . '/config.php');
require(dirname(dirname(dirname(__FILE__))) . '/config.php');

// Multi-arg forms: dirname(__DIR__, N).
require_once(dirname(__DIR__, 2) . '/config.php');
require(dirname(__DIR__, 2) . '/config.php');
require(dirname(__DIR__, 3) . '/config.php');
require(dirname(__DIR__, 4) . '/config.php');

// Multi-arg forms with __FILE__.
require_once(dirname(__FILE__, 2) . '/config.php');
require(dirname(__FILE__, 3) . '/config.php');

// Should NOT be transformed: different suffix.
require(dirname(dirname(__DIR__)) . '/lib.php');
require(dirname(__DIR__, 2) . '/setup.php');

// Should NOT be transformed: include/include_once.
include(dirname(dirname(__DIR__)) . '/config.php');
include_once(dirname(dirname(__DIR__)) . '/config.php');

// Depth 1: also normalised to __DIR__ form.
require_once(dirname(__DIR__) . '/config.php');
