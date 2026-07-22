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
 * Class biome
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class biome extends base {
    /** @var string */
    private static string|false|null $configpath = null;

    #[\Override]
    public static function get_name(): string {
        return 'Biome format';
    }

    #[\Override]
    public static function format(string $path): ?int {
        $config = self::get_config_path();
        if ($config === null) {
            return null;
        }

        $process = new Process([
            'bunx',
            '--bun',
            '@biomejs/biome',
            'format',
            '--config-path',
            $config,
            '--write',
            $path,
        ]);
        $process->run();

        return $process->getExitCode();
    }

    /**
     * Get the biome.jsonc file.
     */
    private static function get_config_path(): ?string {
        global $CFG;

        if (self::$configpath === false) {
            return null;
        }

        if (self::$configpath !== null) {
            return self::$configpath;
        }

        self::$configpath = realpath("$CFG->dirroot/local/devkit/biome.jsonc");

        if (self::$configpath === false) {
            return null;
        }

        return self::$configpath;
    }
}
