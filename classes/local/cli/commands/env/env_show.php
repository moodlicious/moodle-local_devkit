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

namespace local_devkit\local\cli\commands\env;

use local_devkit\local\api\env;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to show the current Moodle environment.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[AsCommand(name: 'env:show|env', description: 'Shows the current Moodle environment.')]
class env_show extends Command {
    /**
     * Invoke
     * @param SymfonyStyle $io The input/output style interface.
     * @return int
     */
    public function __invoke(
        SymfonyStyle $io,
    ): int {
        $env = env::overview();
        $list = [];
        foreach ($env as $key => $value) {
            $list[] = [$key => $value];
        }

        $io->definitionList(...$list);
        return Command::SUCCESS;
    }
}
