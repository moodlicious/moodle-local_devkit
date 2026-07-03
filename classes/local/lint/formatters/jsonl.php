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

namespace local_devkit\local\lint\formatters;

use local_devkit\local\utils;

/**
 * Jsonl formatter.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class jsonl extends base {
    #[\Override]
    public function output(array $linters, array $results): int {
        foreach ($results as $fileresult) {
            $issues = $fileresult->issues;

            $filepath = $this->relative
                ? utils::get_path_relative_to_moodle_root($fileresult->file)
                : $fileresult->file;

            $component = $this->displaycomponent ? $fileresult->get_component() : null;

            foreach ($issues as $issue) {
                $jsonstring = json_encode([
                    ...($component !== null ? ['component' => $component] : []),
                    'file' => $filepath,
                    ...$issue->jsonSerialize(),
                ]);
                if ($jsonstring === false) {
                    $this->io->error('Error encoding linter results JSON');
                    return -1;
                }

                $this->io->writeln($jsonstring);
            }
        }

        return self::exit_code($results);
    }
}
