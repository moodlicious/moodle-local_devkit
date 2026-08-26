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

declare(strict_types=1);

namespace local_devkit\local;

/**
 * Utility class.
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class devkit {
    /**
     * Determines if the plugin should load.
     */
    public static function is_enabled(): bool {
        if (during_initial_install()) {
            return false;
        }

        // phpcs:ignore moodle.Commenting.InlineComment
        // @phpstan-ignore if.alwaysFalse
        if (PHPUNIT_TEST) {
            return false;
        }
        return !(bool) getenv('MDL_LOCAL_DEVKIT_DISABLE');
    }
}
