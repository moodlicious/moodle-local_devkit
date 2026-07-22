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
                        $this->format_file($realpath, $progress);
                    }
                }
            } else {
                $this->format_file($path, $progress);
            }
        }
    }

    /**
     * Run formatters on a single file.
     */
    private function format_file(string $path, ?ProgressIndicator $progress): void {
        $progress?->setMessage("Formatting $path...");
        $formatters = $this->pick_formatters($path);
        foreach ($formatters as $formatter) {
            $name = $formatter::get_name();
            $progress?->setMessage("Formatting $path with $name");
            $formatter::format($path);
        }
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
