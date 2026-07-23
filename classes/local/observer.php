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

namespace local_devkit\local;

use core\event\base;
use DebugBar\DataCollector\TimeDataCollector;

/**
 * Observer.
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Observe everything any log it!
     */
    public static function observe_all_events(base $event): void {
        if (!devkit::is_enabled() || !\local_devkit\local\config\debugbar::is_enabled()) {
            return;
        }

        $collector = debugbar::instance()->get_time_data_collector();
        if (!$collector instanceof TimeDataCollector) {
            return;
        }

        $eventname = $event::get_name();
        $classname = $event::class;
        $label = "Event: $eventname ($classname)";
        $params = [
            'description' => $event->get_description(),
            'data' => $event->get_data(),
            'context' => $event->get_context(),
            'url' => $event->get_url(),
        ];

        $collector->addMeasure($label, params: $params);
    }
}
