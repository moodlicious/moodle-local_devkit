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

namespace local_devkit\local\cli\commands\database;

use local_devkit\local\api\database;
use local_devkit\local\api\plugins;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to format the db/install.xml file for a specific plugin.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[AsCommand(name: 'db:format|database:format', description: 'Format the db/install.xml file for a specific component.')]
class database_format extends Command {
    /**
     * Invoke
     * @param string $component The component name of the plugin.
     * @param SymfonyStyle $io The input/output style interface.
     * @return int
     */
    public function __invoke(
        #[Argument('The component name of the plugin.')] string $component,
        SymfonyStyle $io,
    ): int {
        $plugin = plugins::get_by_component($component);
        if ($plugin === null) {
            $io->error("Component $component not found.");
            return Command::FAILURE;
        }

        $xmlpath = $plugin['directory'] . '/db/install.xml';
        if (!file_exists($xmlpath)) {
            $io->error("install.xml not found at $xmlpath.");
            return Command::FAILURE;
        }

        $structure = database::get_xmldb_structure($xmlpath);
        $result = file_put_contents($xmlpath, $structure->xmlOutput());
        if ($result === false) {
            $io->error("Failed to write install.xml at $xmlpath.");
            return Command::FAILURE;
        }

        $io->success("Formatted $component db/install.xml at $xmlpath.");

        return Command::SUCCESS;
    }
}
