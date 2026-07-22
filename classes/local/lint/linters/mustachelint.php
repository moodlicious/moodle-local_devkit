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

use function strlen;

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
    public function lint_file(string $filepath): array {
        $results = parent::lint_file($filepath);
        if (!$this->can_lint_file($filepath)) {
            return $results;
        }

        $templatename = static::resolve_template_name($filepath);
        if ($templatename === null) {
            return [self::create_file_with_fatal_issue($filepath, "Unable to resolve template name.")];
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            return [self::create_file_with_fatal_issue($filepath, "Unable to read template file.")];
        }

        $issues = [
            ...self::get_issues_for_boilerplate($content),
        ];

        $comments = self::extract_comments_from_template($content);
        $documentation = self::get_documentation_comment($comments);

        $issues = [
            ...$issues,
            ...static::get_issues_for_documentation_comment($documentation, $templatename),
        ];

        return [new file($filepath, $issues)];
    }

    /**
     * Returns the template name in the format of componentname/templatename.
     */
    protected static function resolve_template_name(string $filepath): ?string {
        $directoriespath = self::parse_template_path($filepath);
        if ($directoriespath === null) {
            return null;
        }

        [$pluginpath, $templatepath] = $directoriespath;

        $component = component::resolve_component_from_path($pluginpath);
        if ($component === null) {
            return null;
        }

        return "$component/$templatepath";
    }

    /**
     * Gets the plugin path and mustache path.
     * @return array{string, string}|null
     */
    private static function parse_template_path(string $filepath): ?array {
        $filepath = utils::get_path_relative_to_moodle_root($filepath);
        if (!str_contains($filepath, '/templates/')) {
            return null;
        }

        [$dirpath, $mustachepath] = explode('/templates/', $filepath, 2);
        if ($mustachepath === '') {
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
     * @return string[]
     */
    private static function extract_comments_from_template(string $content): array {
        preg_match_all('/^\{\{!$[\s\S]*?^\}\}$/m', $content, $matches);
        $comments = $matches[0];

        return array_filter(
            array_map(function (string $comment): string|null {
                $comment = preg_replace('/^\{\{!\R?/', '', $comment); // Remove opening line.
                if ($comment !== null && $comment !== '') {
                    $comment = preg_replace('/^\}\}$/m', '', $comment);   // Remove closing line.
                }
                return $comment;
            }, $comments),
            fn(?string $comment): bool => $comment !== null && $comment !== '',
        );
    }

    /**
     * Check for the presence of GPL boilerplate in the file.
     * @return issue[]
     */
    private static function get_issues_for_boilerplate(string $content): array {
        if (boilerplate::check_has_boilerplate($content, 'mustache')) {
            return [];
        }

        return [
            issue::simple(
                'Template must contain GPL License',
                'missing-boilerplate',
                self::get_name(),
                severity::warning,
            ),
        ];
    }

    /**
     * Finds the documentation comment.
     * @param string[] $comments
     */
    private static function get_documentation_comment(array $comments): ?string {
        foreach ($comments as $comment) {
            $trimmed = trim($comment);

            if (str_starts_with($trimmed, '@template')) {
                return $comment;
            }
        }

        return null;
    }

    /**
     * Gets all issues related to the documentation comment.
     *
     * @see \core\output\mustache_template_finder::get_template_filepath() for disabling theme overrides.
     * @return issue[]
     */
    protected static function get_issues_for_documentation_comment(
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
                // Phpstan can not resolve the dynamic PHPUNIT_TEST constant.
                // phpcs:ignore moodle.Commenting.InlineComment
                // @phpstan-ignore booleanNot.alwaysTrue, booleanOr.alwaysTrue
            } else if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
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
                } catch (\Throwable) {
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
     */
    protected static function get_template_from_comment(string $comment): ?string {
        $result = preg_match('/@template ([A-Za-z0-9_\/-]+)/', $comment, $match);
        if ($result === 0 || $result === false) {
            return null;
        }

        return $match[1];
    }

    /**
     * Get the example json from the documentation comment.
     */
    protected static function get_example_from_comment(string $comment): ?string {
        $result = preg_match('/Example context \(json\):\R\s*([\s\S]*})/', $comment, $match);
        if ($result === 0 || $result === false) {
            return null;
        }

        return $match[1];
    }
}
