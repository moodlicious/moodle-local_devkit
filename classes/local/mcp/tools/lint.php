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

namespace local_devkit\local\mcp\tools;

use Exception;
use local_devkit\local\api\linter;
use local_devkit\local\utils;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;

/**
 * Lints files.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lint {
    /**
     * Lists available linters with their names, descriptions, and enabled status.
     * Only disabled linters include a "disabled" field.
     * @return object{
     *     linters: array{name: string, description: string|null, disabled?: true}[],
     * }
     */
    #[McpTool(
        name: 'list_linters',
        description: 'Lists available linters with their names and descriptions',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true),
    )]
    public static function list_linters(): object {
        $linterclasses = linter::get_linters_classnames();
        $info = array_map(
            function (/** @var class-string<\local_devkit\local\lint\linters\base> $linter */ $linter) {
                $entry = [
                    'name' => $linter::get_name(),
                    'description' => $linter::get_description(),
                ];
                if (!$linter::is_enabled()) {
                    $entry['disabled'] = true;
                }
                return $entry;
            },
            $linterclasses,
        );
        return (object) ['linters' => array_values($info)];
    }

    /**
     * Runs project coding standard linters against files or directories.
     * @param string[] $paths absolute paths to files or directories that needs linting
     * @param string[]|null $linters list of linter names to run (e.g. phpcs, phpstan), or null to run all
     * @return object{
     *     linters: string[], // list of linters that have run
     *     files: \local_devkit\local\lint\schemas\file[], // list of files and their issues
     * }
     */
    #[McpTool(
        name: 'lint_files',
        description: 'Runs project coding standard linters against files or directories',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true),
    )]
    public static function lint_files(array $paths, ?array $linters = null): object {
        global $CFG;
        $cwd = getcwd();
        if ($cwd === false) {
            throw new Exception('Unknown current working directory.');
        }

        chdir(utils::get_moodle_root_dir());
        $linters = linter::get_linters_classnames($linters);
        $results = linter::run($paths, $linters);
        chdir($cwd);

        return (object) [
            'linters' => linter::get_linters_info($linters),
            'files' => $results,
        ];
    }
}
