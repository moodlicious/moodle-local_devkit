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

namespace local_devkit\local\cli\commands;

use local_devkit\local\format\base as format_base;
use local_devkit\local\format\phpcbf;
use local_devkit\local\format\pint;
use local_devkit\local\generators\snippets\base as snippet_base;
use local_devkit\local\generators\snippets\task;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to generate boilerplates.
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[AsCommand(
    name: 'make',
    description: 'Make boilerplate.',
)]
class make extends Command {
    /**
     * Invoke.
     */
    public function __invoke(
        #[Argument('Path to new file.')]
        string $path,
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
    ): int {
        [$generatorclass, $formatterclassnames] = $this->get_generator_and_formatter_classnames();

        // The generators need the file to actually exist.
        @mkdir(dirname($path));
        file_put_contents($path, '');

        $generator = new $generatorclass($path);
        $contents = $generator->generate();
        file_put_contents($path, $contents);

        foreach ($formatterclassnames as $formatterclassname) {
            \core\di::get($formatterclassname)::format($path);
        }

        return Command::SUCCESS;
    }

    /**
     * Summary of get_generator_and_formatters
     * @return array{class-string<snippet_base>, class-string<format_base>[]}
     */
    private function get_generator_and_formatter_classnames() {
        return [task::class, [pint::class, phpcbf::class]];
    }
}
