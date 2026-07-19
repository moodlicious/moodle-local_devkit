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

namespace local_devkit\local\schema;

use core\exception\coding_exception;
use Exception;
use SimpleXMLElement;

use function dirname;

/**
 * Class representing a single thirdpartylib.
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class thirdpartylib {
    /** @var string */
    public string $location;

    /** @var string */
    public string $name;

    /** @var string|null */
    public ?string $description;

    /** @var string|null */
    public ?string $version;

    /** @var string */
    public string $license;

    /** @var string|null */
    public ?string $licenseversion;

    /** @var string|null */
    public ?string $repository;

    /** @var bool */
    public bool $customised;

    /** @var string[] */
    public array $copyrights;

    /**
     * Creates an instance from {@see \SimpleXMLElement}.
     * @param string $basepath
     * @param SimpleXMLElement $element
     * @throws Exception
     * @return thirdpartylib
     */
    public static function from_xml_element(string $basepath, SimpleXMLElement $element) {
        $instance = new self();
        $basepath = realpath(dirname($basepath));
        if ($basepath === false) {
            throw new coding_exception('Invalid $basepath provided.');
        }

        $fulllocation = realpath("$basepath/$element->location");
        if ($fulllocation === false) {
            throw new Exception('Unable to resolve third party path.');
        }

        $instance->location = $fulllocation;
        $instance->name = (string) $element->name;
        $instance->description = (string) ($element->description ?? '') ?: null;
        $instance->version = (string) ($element->version ?? '') ?: null;
        $instance->license = (string) $element->license;
        $instance->licenseversion = null;
        if (isset($element->licenseversion) && !empty((string) $element->licenseversion)) {
            $instance->licenseversion = (string) $element->licenseversion;
        }
        $instance->repository = (string) ($element->repository ?? '') ?: null;
        $instance->customised = (bool) ($element->customised ?? false);

        $instance->copyrights = [];
        if (isset($element->copyrights->copyright)) {
            foreach ($element->copyrights->copyright as $copyright) {
                $instance->copyrights[] = (string) $copyright;
            }
        }

        return $instance;
    }
}
