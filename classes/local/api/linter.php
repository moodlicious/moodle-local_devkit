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

namespace local_devkit\local\api;

use core\component;
use local_devkit\local\lint\linters\base;
use local_devkit\local\lint\schemas\file;
use Symfony\Component\Console\Helper\ProgressIndicator;

use function in_array;

/**
 * Linter API.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class linter {
    /**
     * Utility function to get enabled linters.
     * @param string[]|null $linternames list of linters to return, if null then return all available linters
     * @return class-string<base>[]
     */
    public static function get_linters_classnames(?array $linternames = null): array {
        /** @var class-string<base>[] $linters */
        $linters = array_map(
            fn(string $linter): string => "\\$linter",
            array_keys(component::get_component_classes_in_namespace('local_devkit', 'local\lint\linters')),
        );

        $linters = array_filter(
            $linters,
            function (string $linter) use ($linternames): bool {
                if (!is_subclass_of($linter, base::class)) {
                    return false;
                }

                if ($linternames === null) {
                    return true;
                }

                return in_array($linter::get_name(), $linternames, true);
            },
        );

        sort($linters);
        return [...$linters];
    }

    /**
     * Utility function to get enabled linters.
     * @param class-string<base>[] $linters
     * @return string[]
     */
    public static function get_linters_info(array $linters): array {
        return array_values(array_map(
            function (string /** @var class-string<base> $linter */ $linter) {
                $name = $linter::get_name();
                $description = $linter::get_description();
                return $description !== null ? "$name: $description" : $name;
            },
            $linters,
        ));
    }

    /**
     * Executes linters on paths
     * @param string[] $paths
     * @param class-string<base>[] $linterclasses
     * @param ProgressIndicator $progress
     * @return file[]
     */
    public static function run(array $paths, array $linterclasses, ?ProgressIndicator $progress = null): array {
        $linters = array_map(
            fn(string /** @var class-string<base> $linterclass */ $linterclass): object => new $linterclass($progress),
            $linterclasses,
        );

        $progress?->start('Starting.');
        $resultsitems = array_map(
            fn(string $path): array => array_map(
                fn(base $linter): array => $linter->lint($path),
                $linters,
            ),
            $paths,
        );
        $results = array_merge(...$resultsitems);
        $progress?->finish('Done.');

        return base::flatten_results($results);
    }
}
