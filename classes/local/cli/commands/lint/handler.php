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

namespace local_devkit\local\cli\commands\lint;

use local_devkit\local\api\linter;
use local_devkit\local\lint\linters\base;
use local_devkit\local\lint\schemas\file;
use local_devkit\local\utils;
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
use function count;

/**
 * Command to lint a directory or file.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class handler {
    /**
     * Pattern to detect explicitly-delimited regex (e.g. /pattern/flags).
     * @var string
     */
    private const string REGEX_PATTERN = '/^\/.+\/[a-z]*$/i';

    /**
     * Invoke
     * @param string[] $paths
     * @param SymfonyStyle $io
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $format
     * @param bool $decorate
     * @param bool $progress
     * @param bool $relative
     * @param string[] $rules
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
        #[Option('Output relative paths')] bool $relative = false,
        #[Option('Output component names')] bool $displaycomponent = false,
        #[Option('Filter by rule name (case-insensitive substring, or /pattern/flags for regex)')] array $rules = [],
        #[Option('Linters to run')] array $linters = [],
    ): int {
        chdir(utils::get_moodle_root_dir());
        /** @var array<string, class-string<\local_devkit\local\lint\formatters\base>> $formatterclasses */
        $formatterclasses = [
            'json' => \local_devkit\local\lint\formatters\json::class,
            'jsonl' => \local_devkit\local\lint\formatters\jsonl::class,
            'text' => \local_devkit\local\lint\formatters\text::class,
            'github' => \local_devkit\local\lint\formatters\github::class,
        ];
        if (!array_key_exists($format, $formatterclasses)) {
            $io->writeln('Available format options are:');
            $io->listing(array_keys($formatterclasses));
            $io->error("Unknown format specified");
            return Command::FAILURE;
        }

        $formatterclass = $formatterclasses[$format];
        $formatter = new $formatterclass($io);

        $formatter->relative = $relative;
        $formatter->displaycomponent = $displaycomponent;

        if ($formatter instanceof \local_devkit\local\lint\formatters\text) {
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

        if (count($realpaths) === 1 && $formatter instanceof \local_devkit\local\lint\formatters\github) {
            $formatter->set_plugin_root($realpaths[0]);
        }

        $progressindicator = $progress && $output instanceof ConsoleOutputInterface
            ? new ProgressIndicator($output->getErrorOutput())
            : null;

        $linters = linter::get_linters_classnames($linters);

        $results = linter::run($realpaths, $linters, progress: $progressindicator);

        if (count($rules) > 0) {
            $error = self::validate_rules($rules);
            if ($error !== null) {
                $io->error($error);
                return Command::FAILURE;
            }
            $results = self::filter_results_by_rules($results, $rules);
        }

        return $formatter->output($linters, $results);
    }

    /**
     * Validates rule filter patterns.
     * @param string[] $rules
     * @return string|null Error message, or null if all patterns are valid.
     */
    private static function validate_rules(array $rules): ?string {
        foreach ($rules as $rule) {
            if (preg_match(self::REGEX_PATTERN, $rule) === 1) {
                if (@preg_match($rule, '') === false) {
                    return "Invalid regex pattern \"{$rule}\": " . preg_last_error_msg();
                }
            } else {
                $pattern = '#' . $rule . '#i';
                if (@preg_match($pattern, '') === false) {
                    return "Invalid regex pattern \"{$rule}\": " . preg_last_error_msg();
                }
            }
        }
        return null;
    }

    /**
     * Filters results by rule names using case-insensitive substring or regex patterns.
     * @param file[] $results
     * @param string[] $rules
     * @return file[]
     */
    private static function filter_results_by_rules(array $results, array $rules): array {
        $patterns = array_map(function (string $rule): string {
            if (preg_match(self::REGEX_PATTERN, $rule) === 1) {
                return $rule;
            }
            return '#' . $rule . '#i';
        }, $rules);

        return array_map(function (file $file) use ($patterns): file {
            $filtered = array_filter(
                $file->issues,
                fn($issue) => $issue->rule !== null && self::matches_any_pattern($issue->rule, $patterns),
            );
            $file->issues = array_values($filtered);
            return $file;
        }, $results);
    }

    /**
     * Checks if a value matches any of the given regex patterns.
     * @param string $value
     * @param string[] $patterns
     * @return bool
     */
    private static function matches_any_pattern(string $value, array $patterns): bool {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }
        return false;
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
        if ($linter !== null) {
            $description = $linter::get_description();
            if ($description !== null) {
                $command->setDescription($description);
            }
        } else {
            $command->setDescription('Executes linters');
        }
        $command->addArgument('paths', mode: InputArgument::IS_ARRAY)
            ->addOption('format', mode: InputOption::VALUE_REQUIRED, default: 'text')
            ->addOption('decorate', mode: InputOption::VALUE_NEGATABLE, default: true)
            ->addOption('progress', mode: InputOption::VALUE_NEGATABLE, default: true)
            ->addOption(
                'relative',
                mode: InputOption::VALUE_NEGATABLE,
                description: 'Output paths relative to Moodle root directory',
                default: false,
            )
            ->addOption(
                'displaycomponent',
                mode: InputOption::VALUE_NEGATABLE,
                description: 'Output component names',
                default: false,
            )
            ->addOption(
                'rules',
                mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                default: [],
                description: 'Filter by rule name (case-insensitive substring, or /pattern/flags for regex)',
            )
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
