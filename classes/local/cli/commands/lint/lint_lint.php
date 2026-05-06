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
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
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
#[AsCommand(name: 'lint:lint', description: 'All linters are enabled by default unless explicitly selected.')]
class lint_lint extends Command {
    /**
     * Invoke
     * @param string[] $paths
     * @return int
     */
    public function __invoke(
        #[Argument('Paths to lint (must be absolute or relative to the Moodle root)')] array $paths,
        SymfonyStyle $io,
        OutputInterface $output,
        #[Option('Enable/disable the eslint linter')] bool $eslint = true,
        #[Option('Enable/disable the lang dir linter')] bool $lang = true,
        #[Option('Enable/disable the php-codesniffer linter')] bool $phpcs = true,
        #[Option('Enable/disable the php -l linter')] bool $phplint = true,
        #[Option('Enable/disable the phpdoc linter')] bool $phpdoc = true,
        #[Option('Enable/disable the phpstan linter')] bool $phpstan = true,
        #[Option('Enable/disable the stylelint linter')] bool $stylelint = true,
        #[Option('Format to output as (text/json)')] string $format = 'text',
        #[Option('Add file:// links to output')] bool $decorate = true,
        #[Option('Enable/disable the progress bar')] bool $progress = true,
    ): int {
        global $CFG;
        $moodleroot = isset($CFG->root) ? $CFG->root : $CFG->dirroot;
        chdir($moodleroot);

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
            return -1;
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
                return -1;
            }
            $realpaths[] = $realpath;
        }

        if ($realpaths === []) {
            $io->error('No paths provided');
            return -1;
        }

        $progressindicator = $progress && $output instanceof ConsoleOutputInterface
            ? new ProgressIndicator($output->getErrorOutput())
            : null;

        $linters = linter::get_linters_classnames(
            eslint: $eslint,
            lang: $lang,
            phpcs: $phpcs,
            phplint: $phplint,
            phpdoc: $phpdoc,
            phpstan: $phpstan,
            stylelint: $stylelint
        );

        $results = linter::run($realpaths, $linters, progress: $progressindicator);

        return $formatter->output($linters, $results);
    }
}
