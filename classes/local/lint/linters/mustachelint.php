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
use local_devkit\local\lint\schemas\file;
use local_devkit\local\lint\schemas\issue;
use local_devkit\local\lint\severity;
use local_devkit\local\utils;

/**
 * The mustachelint linter.
 *
 * Known issues:
 * - Unable to lint moodle core plugins (e.g. public/lib or public/lib/form).
 * - Theme overridden templates might not get linted properly (might raise template-name-incorrect unexpectedly).
 *
 * It may be best to implement our own mustache engine for better control.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[linter(
    name: 'mustachelint',
    description: 'lints mustache templates against Moodle standards',
)]
class mustachelint extends base {
    #[\Override]
    public static function get_include_patterns(): array {
        return [
            ...parent::get_include_patterns(),
            ...['*.mustache'],
        ];
    }

    #[\Override]
    public static function get_exclude_patterns(): array {
        return [
            ...parent::get_exclude_patterns(),
            // Exclude files like
            // ./public/mod/bigbluebuttonbn/tests/fixtures/extension/complex/templates/view_page_addons.mustache.
            ...['*/tests/*'],
        ];
    }

    #[\Override]
    public function lint_file(string $filepath): array {
        $results = parent::lint_file($filepath);
        if (!$this->can_lint_file($filepath)) {
            return $results;
        }

        $templatename = self::resolve_template_name($filepath);
        if (!$templatename) {
            return [self::create_file_with_fatal_issue($filepath, "Unable to resolve template name.")];
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            return [self::create_file_with_fatal_issue($filepath, "Unable to read template file.")];
        }

        $comments = self::extract_comments_from_template($content);
        [$license, $documentation] = self::get_license_and_documentation_comments($comments);

        $issues = [
            ...self::get_issues_for_license_comment($license),
            ...self::get_issues_for_documentation_comment($documentation, $templatename),
        ];

        return [new file($filepath, $issues)];
    }

    /**
     * Returns the template name in the format of componentname/templatename.
     * @return string|null
     */
    private static function resolve_template_name(string $filepath): ?string {
        $directoriespath = self::parse_template_path($filepath);
        if (!$directoriespath) {
            return null;
        }

        [$pluginpath, $templatepath] = $directoriespath;

        $component = component::resolve_component_from_path($pluginpath);
        if (!$component) {
            return null;
        }

        return "$component/$templatepath";
    }

    /**
     * Gets the plugin path and mustache path.
     * @param string $filepath
     * @return array{string, string}|null
     */
    private static function parse_template_path(string $filepath): ?array {
        $filepath = utils::get_path_relative_to_moodle_root($filepath);
        if (!str_contains($filepath, '/templates/')) {
            return null;
        }

        [$dirpath, $mustachepath] = explode('/templates/', $filepath, 2);
        if (!$mustachepath) {
            return null;
        }

        $mustacheext = '.mustache';
        if (!str_ends_with($mustachepath, $mustacheext)) {
            return null;
        }

        $mustachepath = substr($mustachepath, 0, strlen($mustachepath) - strlen($mustacheext));
        return [$dirpath, $mustachepath];
    }

    /**
     * Match any mustache comments and return them.
     * @param string $content
     * @return string[]
     */
    private static function extract_comments_from_template(string $content): array {
        preg_match_all('/^\{\{!$[\s\S]*?^\}\}$/m', $content, $matches);
        $comments = $matches[0];

        return array_filter(array_map(function (string $comment) {
            $comment = preg_replace('/^\{\{!\R?/', '', $comment); // Remove opening line.
            if ($comment) {
                $comment = preg_replace('/^\}\}$/m', '', $comment);   // Remove closing line.
            }
            return $comment;
        }, $comments));
    }

    /**
     * Finds the GPL license comment and the documentation comment.
     * @param string[] $comments
     * @return array{string|null, string|null}
     */
    private static function get_license_and_documentation_comments(array $comments): array {
        $license = null;
        $documentation = null;

        foreach ($comments as $comment) {
            if ($license !== null && $documentation !== null) {
                break;
            }

            $trimmed = trim($comment);

            // Find the comment that looks like a license.
            if ($license === null) {
                if (
                    str_starts_with($trimmed, 'This file is part of Moodle')
                    && str_contains($trimmed, 'GNU General Public License')
                    && str_ends_with($trimmed, '//www.gnu.org/licenses/>.')
                ) {
                    $license = $comment;
                    continue;
                }
            }

            // Assumes the template that contains '@template' is the documentation comment.
            if ($documentation === null) {
                if (str_starts_with($trimmed, '@template')) {
                    $documentation = $comment;
                    continue;
                }
            }
        }

        return [$license, $documentation];
    }

    /**
     * Gets all issues related to the license comment.
     * @param string|null $license
     * @return issue[]
     */
    private static function get_issues_for_license_comment(?string $license): array {
        // Templates must contain GPL License.
        // See https://moodledev.io/docs/5.3/guides/templates#include-gpl-at-the-top-of-each-template.
        if ($license === null) {
            return [
                issue::simple(
                    'Template must contain GPL License',
                    'include-gpl-license',
                    self::get_name(),
                    severity::warning,
                ),
            ];
        }

        return [];
    }

    /**
     * Gets all issues related to the documentation comment.
     *
     * @see \core\output\mustache_template_finder::get_template_filepath() for disabling theme overrides.
     * @param string|null $documentation
     * @param string $templatename
     * @return issue[]
     */
    private static function get_issues_for_documentation_comment(
        ?string $documentation,
        string $templatename,
    ): array {
        $issues = [];

        // Documentation is required.
        // See https://moodledev.io/docs/5.3/guides/templates#include-a-documentation-comment-for-each-template.
        if ($documentation === null) {
            return [
                issue::simple(
                    'Template should contain a documentation comment',
                    'documentation-required',
                    self::get_name(),
                    severity::warning,
                ),
            ];
        }

        $declaredtemplatename = self::get_template_from_comment($documentation);
        if ($declaredtemplatename !== $templatename) {
            $issues[] = issue::simple(
                "Incorrect @template, expected $templatename",
                'template-name-incorrect',
                self::get_name(),
                severity::error,
            );
        }

        if (strtolower($templatename) !== $templatename) {
            $issues[] = issue::simple(
                'Template name should be in all lowercase',
                'template-name-casing',
                self::get_name(),
                severity::warning,
            );
        }

        $examplejson = self::get_example_from_comment($documentation);
        if ($examplejson === null) {
            $issues[] = issue::simple(
                'Template documentation missing example context',
                'documentation-example-context-required',
                self::get_name(),
                severity::warning,
            );
        } else {
            $example = json_decode($examplejson);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $issues[] = issue::simple(
                    'Invalid example context, json decoding error',
                    'documentation-example-context-decode',
                    self::get_name(),
                    severity::error,
                );
            } else if ($example === null) {
                $issues[] = issue::simple(
                    'Invalid example context, expected a JSON object, got null',
                    'documentation-example-context-null',
                    self::get_name(),
                    severity::warning,
                );
            } else {
                try {
                    global $OUTPUT;
                    // Append '!' to end of template name to disable theme override.
                    $rendered = $OUTPUT->render_from_template("$templatename!", $example);
                    if ($rendered === '') {
                        $issues[] = issue::simple(
                            'Template rendered as empty string with json example',
                            'template-render-empty',
                            self::get_name(),
                            severity::warning,
                        );
                    }
                } catch (\Throwable $th) {
                    $issues[] = issue::simple(
                        'Unable to render template with json example',
                        'template-render-error',
                        self::get_name(),
                        severity::error,
                    );
                }
            }
        }

        return $issues;
    }

    /**
     * Get the declared template name (@template xxx) from the documentation comment.
     * @param string $comment
     */
    private static function get_template_from_comment(string $comment): ?string {
        if (!preg_match('/@template ([A-Za-z0-9_\/-]+)/', $comment, $match)) {
            return null;
        }

        return $match[1];
    }

    /**
     * Get the example json from the documentation comment.
     * @param string $comment
     */
    private static function get_example_from_comment(string $comment): ?string {
        if (!preg_match('/Example context \(json\):\R\s*([\s\S]*})/', $comment, $match)) {
            return null;
        }

        return $match[1];
    }
}
