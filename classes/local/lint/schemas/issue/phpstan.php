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

namespace local_devkit\local\lint\schemas\issue;

use local_devkit\local\lint\schemas\issue;
use local_devkit\local\lint\severity;

use function in_array;

/**
 * Class representing a single phpcs linter issue.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class phpstan extends issue {
    /**
     * Factory method to create from an phpcs message object.
     * @param object $object
     * @return self|null
     */
    public static function from_object(object $object): ?self {
        $message = self::object_property($object, 'message');
        $tip = self::object_property($object, 'tip');
        $line = self::object_property($object, 'line');
        $ignorable = self::object_property($object, 'ignorable');
        $identifier = self::object_property($object, 'identifier');

        if (in_array(null, [$message, $line, $ignorable, $identifier], strict: true)) {
            return null;
        }

        return new self(
            $line,
            0,
            $message,
            $identifier,
            'phpstan',
            $ignorable ? severity::info : severity::warning,
            $tip ? [$tip] : [],
        );
    }
}
