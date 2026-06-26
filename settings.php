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
 * DevKit plugin settings.
 *
 * @var bool $hassiteconfig
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\html_writer;
use local_devkit\local\data\editor;
use local_devkit\output\tables\linter_config;

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_devkit', get_string('pluginname', 'local_devkit'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_devkit/debugbar_enabled',
        new lang_string('settings:debugbar_enabled', 'local_devkit'),
        new lang_string('settings:debugbar_enabled_desc', 'local_devkit'),
        '0'
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_devkit/debugbar_collect_queries',
        new lang_string('settings:debugbar_collect_queries', 'local_devkit'),
        new lang_string('settings:debugbar_collect_queries_desc', 'local_devkit'),
        '0'
    ));

    $settings->add(new admin_setting_configselect(
        'local_devkit/debugbar_editor',
        new lang_string('settings:debugbar_editor', 'local_devkit'),
        new lang_string('settings:debugbar_editor_desc', 'local_devkit'),
        '',
        Closure::fromCallable([editor::class, 'get_menu'])
    ));

    $settings->add(new admin_setting_description(
        'local_devkit/linter_config',
        'Linter Configuration',
        html_writer::table(new linter_config()),
    ));
}
