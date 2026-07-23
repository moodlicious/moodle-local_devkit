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

declare(strict_types=1);

namespace local_devkit\local\cli\commands\seed;

use local_devkit\local\seeders\users as users_seeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to seed a bunch of users.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[AsCommand(name: 'seed:users')]
class users extends Command {
    /**
     * Invoke
     */
    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        #[Option]
        int $count = 1,
    ): int {
        $progress = $output instanceof ConsoleOutputInterface
        ? new ProgressIndicator($output->getErrorOutput())
        : null;
        $progress?->start('Starting...');
        $seeder = new users_seeder($progress, $count);
        $seeder->seed();
        $progress?->finish('Completed!');
        return Command::SUCCESS;
    }
}
