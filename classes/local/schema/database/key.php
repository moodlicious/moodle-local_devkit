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

namespace local_devkit\local\schema\database;

/**
 * Class representing a database key.
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class key {
    /**
     * Constructor.
     * @param string $name
     * @param string|null $comment
     * @param string $type
     * @param string[] $fields
     * @param reference $references
     */
    public function __construct(
        /** @var string $name */
        public readonly string $name,
        /** @var string|null $comment */
        public readonly ?string $comment,
        /** @var string $type */
        public readonly string $type,
        /** @var string[] $fields */
        public readonly array $fields,
        /** @var reference $references */
        public readonly reference $references,
    ) {
    }
}
