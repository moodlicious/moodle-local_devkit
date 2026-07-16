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

namespace local_devkit\local\lint\linters;

use local_devkit\local\attributes\linter;
use local_devkit\local\lint\schemas\file;
use local_devkit\local\lint\schemas\issue\stylelint as stylelint_issue;
use Symfony\Component\Process\Process;

/**
 * The stylelint linter.
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[linter(
    name: 'stylelint',
    description: 'executes "stylelint" on stylesheets against project coding standards',
)]
class stylelint extends base {
    #[\Override]
    public static function get_include_patterns(): array {
        return [
            ...parent::get_include_patterns(),
            ...['*.css', '*.scss'],
        ];
    }

    #[\Override]
    public function lint_file(string $filepath): array {
        $results = parent::lint_file($filepath);
        if (!$this->can_lint_file($filepath)) {
            return $results;
        }

        $filepath = realpath($filepath);
        if ($filepath === false) {
            return [];
        }

        $process = new Process(['bunx', 'stylelint', '--formatter', 'json', $filepath]);
        $process->run();

        $output = $process->getOutput();
        $jsonoutput = json_decode($output);
        if ($jsonoutput === null) {
            $output = $output ?: $process->getErrorOutput();
            $results[] = self::create_file_with_fatal_issue($filepath, "Error decoding stylelint output: '$output'");
            return $results;
        }

        foreach ($jsonoutput as $lintedfile) {
            $issues = [];
            $warnings = $lintedfile->warnings;
            foreach ($warnings as $warning) {
                $issue = stylelint_issue::from_object($warning);
                if ($issue === null) {
                    continue;
                }
                $issues[] = $issue;
            }

            $results[] = new file((string) $lintedfile->source, $issues);
        }

        return $results;
    }
}
