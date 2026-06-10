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

namespace local_devtools\local\cli\commands\lint;

use local_devtools\local\api\linter;
use local_devtools\local\lint\linters\base;
use local_devtools\local\utils;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function array_key_exists;

/**
 * Command to lint a directory or file.
 *
 * @package   local_devtools
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class handler {
    /**
     * Invoke
     * @param string[] $paths
     * @param SymfonyStyle $io
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $format
     * @param bool $decorate
     * @param bool $progress
     * @param string[] $linters
     * @return int
     */
    private static function invoke(
        #[Argument('Paths to lint (must be absolute or relative to the Moodle root)')] array $paths,
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
        #[Option('Format to output as (text/json)')] string $format = 'text',
        #[Option('Add file:// links to output')] bool $decorate = true,
        #[Option('Enable/disable the progress bar')] bool $progress = true,
        #[Option('Linters to run')] array $linters = [],
    ): int {
        chdir(utils::get_moodle_root_dir());

        /** @var array<string, class-string<\local_devtools\local\lint\formatters\base>> $formatterclasses */
        $formatterclasses = [
            'json' => \local_devtools\local\lint\formatters\json::class,
            'jsonl' => \local_devtools\local\lint\formatters\jsonl::class,
            'text' => \local_devtools\local\lint\formatters\text::class,
        ];
        if (!array_key_exists($format, $formatterclasses)) {
            $io->writeln('Available format options are:');
            $io->listing(array_keys($formatterclasses));
            $io->error("Unknown format specified");
            return Command::FAILURE;
        }

        $formatterclass = $formatterclasses[$format];
        $formatter = new $formatterclass($io);

        if ($formatter instanceof \local_devtools\local\lint\formatters\text) {
            $formatter->decorate = $decorate;
        }

        $realpaths = [];
        foreach ($paths as $path) {
            $realpath = realpath($path);
            if ($realpath === false) {
                $io->error("Invalid path: $path");
                return Command::FAILURE;
            }
            $realpaths[] = $realpath;
        }

        if ($realpaths === []) {
            $io->error('No paths provided');
            return Command::FAILURE;
        }

        $progressindicator = $progress && $output instanceof ConsoleOutputInterface
            ? new ProgressIndicator($output->getErrorOutput())
            : null;

        // Build an associative array of lintername -> enabled.
        $linternames = array_map(
            fn(/** @var class-string<base> $linter */ $linter) => $linter::get_name(),
            linter::get_linters_classnames(),
        );
        $enabledlinters = array_fill_keys($linternames, false);

        foreach ($linters as $linter) {
            if (!array_key_exists($linter, $enabledlinters)) {
                continue;
            }
            $enabledlinters[$linter] = true;
        }

        $linters = linter::get_linters_classnames(...$enabledlinters);

        $results = linter::run($realpaths, $linters, progress: $progressindicator);

        return $formatter->output($linters, $results);
    }

    /**
     * Builds the command for linters
     * @param string $name
     * @param class-string<base>[] $linters
     * @return Command
     */
    private static function build_command(string $name, array $linters): Command {
        $linter = null;
        if (\count($linters) === 1) {
            $linter = $linters[0];
        }

        $linternames = array_map(
            fn(/** @var class-string<base> $linter */ $linter) => $linter::get_name(),
            $linters,
        );
        $command = new Command($name);
        if ($linter) {
            $description = $linter::get_description();
            if ($description) {
                $command->setDescription($description);
            }
        } else {
            $command->setDescription('Executes linters');
        }
        $command->addArgument('paths', mode: InputArgument::IS_ARRAY)
            ->addOption('format', mode: InputOption::VALUE_REQUIRED, default: 'text')
            ->addOption('decorate', mode: InputOption::VALUE_NEGATABLE, default: true)
            ->addOption('progress', mode: InputOption::VALUE_NEGATABLE, default: true)
            ->addOption('linters', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, default: $linternames)
            ->setCode(self::invoke(...));
        return $command;
    }

    /**
     * Register linter command
     * @param Application $app
     * @return void
     */
    public static function register(Application $app): void {
        $linters = linter::get_linters_classnames();

        // Make the command for running all linters.
        $command = self::build_command('lint', $linters);
        $app->addCommand($command);

        // Make commands for running each individual linter.
        foreach ($linters as $linter) {
            $name = $linter::get_name();
            $command = self::build_command("lint:$name", [$linter]);
            $app->addCommand($command);
        }

        return;
    }
}
