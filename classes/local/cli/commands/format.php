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

use core\di;
use local_devkit\local\format\base;
use local_devkit\local\format\biome;
use local_devkit\local\format\eslint;
use local_devkit\local\format\phpcbf;
use local_devkit\local\format\pint;
use local_devkit\local\format\stylelint;
use local_devkit\local\format\xmldb;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

use function count;

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
     * Ignorable path patterns.
     * @var string[]
     */
    public const IGNORE_PATTERNS = [
        '*/.git/*',
        '*/amd/build/*',
        '*/js/esm/build/*',
        '*/node_modules/*',
        '*/tests/fixtures/*',
        '*/vendor/*',
    ];
    /**
     * Configure arguments.
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
        OutputInterface $output,
    ): int {
        $paths = $input->getArgument('paths');
        $progress = $output instanceof ConsoleOutputInterface
            ? new ProgressIndicator($output->getErrorOutput())
            : null;

        $progress?->start('Starting...');
        $this->format_run($paths, $progress);
        $progress?->finish('All done.');

        return Command::SUCCESS;
    }

    /**
     * Format files in the given paths.
     * @param string[] $paths
     */
    private function format_run(array $paths, ?ProgressIndicator $progress): void {
        $allfiles = $this->collect_files($paths);

        $formattermap = $this->build_formatter_map($allfiles);

        foreach ($formattermap as $formatterclass => $files) {
            $name = $formatterclass::get_name();
            $batches = array_chunk($files, 10);
            foreach ($batches as $batch) {
                $progress?->setMessage("Running $name on " . count($batch) . " files...");
                $formatterclass::format_batch($batch);
            }
        }
    }

    /**
     * Collect all files from the given paths.
     * @param string[] $paths
     * @return string[]
     */
    private function collect_files(array $paths): array {
        $files = [];

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            if (is_dir($path)) {
                $finder = new Finder();
                $finder
                    ->files()
                    ->in($path)
                    ->ignoreVCSIgnored(true);

                $finder->filter(function (\SplFileInfo $file): bool {
                    $realpath = $file->getRealPath();
                    if ($realpath === false) {
                        return false;
                    }
                    $normalisedpath = str_replace('\\', '/', $realpath);
                    foreach (self::IGNORE_PATTERNS as $pattern) {
                        if (fnmatch($pattern, $normalisedpath)) {
                            return false;
                        }
                    }
                    return true;
                });

                foreach ($finder as $file) {
                    $realpath = $file->getRealPath();
                    if ($realpath !== false) {
                        $files[] = $realpath;
                    }
                }
            } else {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Build a map of formatter class to file paths, preserving pick_formatters order.
     *
     * Formatters are emitted in the order they first appear across all files,
     * which respects the per-file ordering defined by pick_formatters.
     *
     * @param string[] $files
     * @return array<class-string<base>, string[]>
     */
    private function build_formatter_map(array $files): array {
        $formatterorder = [];
        $formattermap = [];

        foreach ($files as $file) {
            foreach ($this->pick_formatters($file) as $formatter) {
                $class = $formatter::class;
                if (!isset($formattermap[$class])) {
                    $formatterorder[] = $class;
                    $formattermap[$class] = [];
                }
                $formattermap[$class][] = $file;
            }
        }

        $ordered = [];
        foreach ($formatterorder as $class) {
            $ordered[$class] = $formattermap[$class];
        }

        return $ordered;
    }

    /**
     * Picks formatters.
     * @return base[]
     */
    private function pick_formatters(string $path): array {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        $formatters = match ($ext) {
            'php' => [
                di::get(pint::class),
                di::get(phpcbf::class),
            ],
            'css', 'scss' => [
                di::get(biome::class),
                di::get(stylelint::class),
            ],
            'js', 'jsx', 'ts', 'tsx' => [
                di::get(biome::class),
                di::get(eslint::class),
            ],
            default => null,
        };

        if ($formatters !== null) {
            return $formatters;
        }

        if ($ext === 'xml' && str_ends_with(str_replace('\\', '/', $path), '/db/install.xml')) {
            return [di::get(xmldb::class)];
        }

        return [];
    }
}
