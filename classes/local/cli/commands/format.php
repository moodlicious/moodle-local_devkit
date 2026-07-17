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

use local_devkit\local\format\phpcbf;
use local_devkit\local\format\pint;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Format command.
 *
 * Formats code with an opinionated code-style.
 * PHP: pint, then phpcbf
 * JS: biome, then eslint --fix
 * CSS: biome, then stylelint --fix
 * XMLDB: moodle formatter
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[AsCommand(
    name: 'format',
    description: 'Formats code with a highly opinionated code-style',
)]
class format extends Command {
    /**
     * Configure arguments.
     * @return void
     */
    protected function configure(): void {
        $this->addArgument('paths', InputArgument::IS_ARRAY);
    }

    /**
     * Invoke.
     */
    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
    ): int {
        $paths = $input->getArgument('paths');

        self::format_run($paths);

        return Command::SUCCESS;
    }

    /**
     * Summary of format
     * @param string[] $paths
     */
    private static function format_run(array $paths): void {
        foreach ($paths as $path) {
            match (true) {
                is_dir($path) => self::format_directory($path),
                is_file($path) => self::format_file($path),
                default => null,
            };
        }
    }

    /**
     * Run formatter.
     */
    private static function format_directory(string $directory): void {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $path) {
            self::format_run([$path]);
        }
    }

    /**
     * Run formatter.
     */
    private static function format_file(string $path): void {
        echo "$path... ";
        $formatters = self::pick_formatters($path);
        foreach ($formatters as $formatter) {
            echo PHP_EOL;
            $name = $formatter::get_name();
            echo "  $name: ";
            $error = $formatter::format($path);
            echo $error ? 'error' : 'success';
        }
        echo PHP_EOL;
        return;
    }

    /**
     * Picks formatters.
     * @return \local_devkit\local\format\base[]
     */
    private static function pick_formatters(string $path): array {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        if ($ext === 'php') {
            return [
                \core\di::get(pint::class),
                \core\di::get(phpcbf::class),
            ];
        }

        return [];
    }
}
