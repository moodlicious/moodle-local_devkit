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

namespace local_devkit\local\format;

use Symfony\Component\Process\Process;

/**
 * Class pint
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pint extends base {
    #[\Override]
    public static function get_name(): string {
        return 'Laravel Pint';
    }

    #[\Override]
    public static function format(string $path): ?int {
        global $CFG;
        $bin = realpath("$CFG->dirroot/local/devkit/vendor/bin/pint");
        $config = realpath("$CFG->dirroot/local/devkit/pint.json");

        if ($bin === false || $config === false) {
            return null;
        }

        $process = new Process([
            'php',
            $bin,
            '--no-interaction',
            '--quiet',
            '--config',
            $config,
            $path,
        ], timeout: MINSECS);
        $process->run();

        return $process->getExitCode();
    }
}
