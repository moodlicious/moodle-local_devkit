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

use function count;

/**
 * Text formatter.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text extends base {
    /** @var bool */
    public bool $decorate = true;

    #[\Override]
    public function output(array $linters, array $results): int {
        $decorateoutput = $this->decorate && $this->io->isDecorated();

        $filecount = count($results);
        $issuecount = 0;

        foreach ($results as $fileresult) {
            $issues = $fileresult->issues;
            $issuecount += count($issues);

            foreach ($issues as $issue) {
                $severity = $issue->severity->value;
                $message = $issue->message;
                $rule = "$issue->source/$issue->rule";
                $filelink = $fileresult->format_path($issue->line, $issue->column, $decorateoutput);
                $out = "$filelink: $severity: $message ($rule)";
                $this->io->writeln($out);
            }
        }

        $this->io->writeln('');
        $this->io->writeln("Linted $filecount files with $issuecount issues.");
        return 0;
    }
}
