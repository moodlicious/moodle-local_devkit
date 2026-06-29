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

namespace local_devkit\local\lint\formatters;

use local_devkit\local\lint\schemas\file;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The base formatter.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var bool */
    public bool $relative = false;

    /**
     * Constructor.
     * @param SymfonyStyle $io
     */
    public function __construct(
        /** @var SymfonyStyle $io */
        protected readonly SymfonyStyle $io
    ) {
    }

    /**
     * Return the appropriate exit code based on whether any issues were found.
     * @param file[] $results
     * @return int
     */
    protected static function exit_code(array $results): int {
        foreach ($results as $file) {
            if ($file->issues) {
                return Command::FAILURE;
            }
        }
        return Command::SUCCESS;
    }

    /**
     * Outputs the results.
     * @param class-string<\local_devkit\local\lint\linters\base>[] $linters
     * @param file[] $results
     * @return int
     */
    abstract public function output(array $linters, array $results): int;
}
