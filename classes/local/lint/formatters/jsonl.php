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

            foreach ($issues as $issue) {
                $jsonstring = json_encode([
                    'file' => $fileresult->file,
                    ...$issue->jsonSerialize(),
                ]);
                if ($jsonstring === false) {
                    $this->io->error('Error encoding linter results JSON');
                    return -1;
                }

                $this->io->writeln($jsonstring);
            }
        }

        return 0;
    }
}
