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

namespace local_devkit\local\lint\linters;

use local_devkit\local\attributes\linter;
use local_devkit\local\component;
use local_devkit\local\generators\boilerplate;
use local_devkit\local\lint\schemas\file;
use local_devkit\local\lint\schemas\issue;
use local_devkit\local\lint\severity;
use local_devkit\local\utils;

/**
 * The jsdoc linter.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[linter(
    name: 'jsdoc',
    description: 'lints js/ts/tsx files for missing boilerplate and docblock',
)]
class jsdoc extends base {
    #[\Override]
    public static function get_include_patterns(): array {
        return [
            ...parent::get_include_patterns(),
            ...['**/amd/src/*.js', '**/js/esm/src/*.ts', '**/js/esm/src/*.tsx'],
        ];
    }

    #[\Override]
    public function lint_file(string $filepath): array {
        $results = parent::lint_file($filepath);
        if (!$this->can_lint_file($filepath)) {
            return $results;
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            return [self::create_file_with_fatal_issue($filepath, "Unable to read file.")];
        }

        $modulename = self::resolve_module_name($filepath);
        if ($modulename === null) {
            return [self::create_file_with_fatal_issue($filepath, "Unable to resolve module name from file path.")];
        }

        $issues = [
            ...self::get_issues_for_boilerplate($content),
            ...self::get_issues_for_docblock($content, $modulename),
        ];

        return [new file($filepath, $issues)];
    }

    /**
     * Resolve the expected module name from a file path.
     * @param string $filepath
     * @return string|null
     */
    private static function resolve_module_name(string $filepath): ?string {
        $relative = utils::get_path_relative_to_moodle_root($filepath);
        $component = component::resolve_component_from_path($relative);
        if ($component === null) {
            return null;
        }

        $modulepath = null;
        if (preg_match('#amd/src/(.+)$#', $relative, $match) === 1) {
            $modulepath = $match[1];
        } else if (preg_match('#js/esm/src/(.+)$#', $relative, $match) === 1) {
            $modulepath = $match[1];
        }

        if ($modulepath === null) {
            return null;
        }

        $modulepath = preg_replace('/\.(js|ts|tsx)$/', '', $modulepath);
        return "$component/$modulepath";
    }

    /**
     * Check for the presence of GPL boilerplate in the file.
     * @param string $content
     * @return issue[]
     */
    private static function get_issues_for_boilerplate(string $content): array {
        if (boilerplate::check_has_boilerplate($content, 'js')) {
            return [];
        }

        return [
            issue::simple(
                'File is missing the GPL boilerplate.',
                'missing-boilerplate',
                self::get_name(),
                severity::warning,
            ),
        ];
    }

    /**
     * Check for the presence and correctness of the docblock.
     * @param string $content
     * @param string $expectedmodule
     * @return issue[]
     */
    private static function get_issues_for_docblock(string $content, string $expectedmodule): array {
        preg_match_all('/\/\*\*[\s\S]*?\*\//', $content, $matches);
        $docblocks = $matches[0];

        $docblock = null;
        foreach ($docblocks as $db) {
            if (str_contains($db, '@module')) {
                $docblock = $db;
                break;
            }
        }

        if ($docblock === null) {
            return [
                issue::simple(
                    'File is missing the required docblock with @module tag',
                    'missing-docblock',
                    self::get_name(),
                    severity::warning,
                ),
            ];
        }

        $issues = [];

        if (!str_contains($docblock, '@copyright')) {
            $issues[] = issue::simple(
                'Docblock is missing @copyright tag',
                'missing-copyright',
                self::get_name(),
                severity::warning,
            );
        }

        if (preg_match('/@license\s+\S+/', $docblock) === 0) {
            $issues[] = issue::simple(
                'Docblock is missing @license tag',
                'missing-license',
                self::get_name(),
                severity::warning,
            );
        }

        if (preg_match('/@module\s+(\S+)/', $docblock, $match) === 1) {
            $declaredmodule = $match[1];
            if ($declaredmodule !== $expectedmodule) {
                $issues[] = issue::simple(
                    "Incorrect @module, expected '$expectedmodule', found '$declaredmodule'",
                    'module-name-incorrect',
                    self::get_name(),
                    severity::error,
                );
            }
        }

        return $issues;
    }
}
