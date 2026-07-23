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

namespace local_devkit\local\lint\schemas;

use JsonSerializable;
use local_devkit\local\component;
use local_devkit\local\utils;

/**
 * Class representing a single file with issues.
 *
 * // phpcs:disable moodle.Commenting.ValidTags.Invalid
 * @phpstan-type file_data array{file: string, issues: issue[]}
 * // phpcs:enable moodle.Commenting.ValidTags.Invalid
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file implements JsonSerializable {
    /** @var string|null */
    private ?string $component = null;
    /** @var bool */
    private bool $componentresolved = false;

    /**
     * Constructor.
     * @param issue[] $issues
     */
    public function __construct(
        /** @var string */
        public readonly string $file,
        /** @var issue[] */
        public array $issues = [],
    ) {
    }

    /**
     * Adds an issue.
     */
    public function add_issue(issue $issue): self {
        $this->issues[] = $issue;
        return $this;
    }

    /**
     * Resolves the component name from this file's path.
     */
    public function get_component(): ?string {
        if (!$this->componentresolved) {
            $relativepath = utils::get_path_relative_to_moodle_root($this->file);
            $this->component = component::resolve_component_from_path($relativepath);
            $this->componentresolved = true;
        }
        return $this->component;
    }

    /**
     * Get data to be serialised.
     * @return file_data
     */
    public function jsonSerialize(): array {
        return [
            'component' => $this->get_component(),
            'file' => $this->file,
            'issues' => $this->issues,
        ];
    }

    /**
     * Formats a given file issue into a clickable link.
     */
    public function format_path(?int $line = null, ?int $column = null, bool $decorate = false, bool $relative = false): string {
        $displaypath = $relative ? utils::get_path_relative_to_moodle_root($this->file) : $this->file;

        static $filter = fn(?string $item): bool => (bool) $item;

        $location = implode(":", utils::array_filter_left([$displaypath, $line, $column], $filter));

        if (!$decorate) {
            return $location;
        }

        $encodedpath = str_replace('%2F', '/', rawurlencode($this->file));
        $encodedlocation = implode(":", utils::array_filter_left([$encodedpath, $line, $column], $filter));
        return "<href=vscode://file/$encodedlocation>$location</>";
    }
}
