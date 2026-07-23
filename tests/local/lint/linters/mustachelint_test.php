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

declare(strict_types=1);

namespace local_devkit\local\lint\linters;

use advanced_testcase;
use local_devkit\local\attributes\linter;
use local_devkit\local\lint\schemas\issue;

/**
 * Unit tests for the mustachelint linter.
 *
 * @package   local_devkit
 * @covers    \local_devkit\local\lint\linters\mustachelint
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mustachelint_test extends advanced_testcase {
    /** @var string Path to the test fixtures directory */
    private string $fixturedir;

    /** @var mustachelint Linter instance with relaxed exclude patterns for testing */
    private mustachelint $linter;

    protected function setUp(): void {
        parent::setUp();
        $path = realpath(__DIR__ . '/../../../fixtures');
        $this->fixturedir = $path !== false ? $path : __DIR__ . '/../../../fixtures';
        // phpcs:ignore moodle.Files.LineLength.TooLong
        $this->linter = new #[linter(name: 'mustachelint', description: 'testable mustachelint linter for unit tests')] class extends mustachelint {
            /** @var string */
            public static string $mocktemplatename = 'local_devkit/test';

            #[\Override]
            public static function get_exclude_patterns(bool $includethirdparty = true): array {
                return [];
            }

            #[\Override]
            protected static function resolve_template_name(string $filepath): string {
                return self::$mocktemplatename;
            }
        };
    }

    /**
     * Test that a valid template with boilerplate and doc comment passes.
     */
    public function test_passing_template(): void {
        $filepath = $this->fixturedir . '/templates/passing.mustache';
        $results = $this->linter->lint_file($filepath);
        self::assertCount(1, $results);
        self::assertCount(0, $results[0]->issues);
    }

    /**
     * Test that a template without GPL boilerplate reports missing-boilerplate.
     */
    public function test_missing_boilerplate(): void {
        $filepath = $this->fixturedir . '/templates/missing-boilerplate.mustache';
        $results = $this->linter->lint_file($filepath);
        self::assertCount(1, $results);
        $rules = array_map(fn(issue $i): ?string => $i->rule, $results[0]->issues);
        self::assertContains('missing-boilerplate', $rules);
    }

    /**
     * Test that a template without a documentation comment reports documentation-required.
     */
    public function test_no_doc_comment(): void {
        $filepath = $this->fixturedir . '/templates/no-doc-comment.mustache';
        $results = $this->linter->lint_file($filepath);
        self::assertCount(1, $results);
        $rules = array_map(fn(issue $i): ?string => $i->rule, $results[0]->issues);
        self::assertContains('documentation-required', $rules);
    }

    /**
     * Test that a template with wrong @template name reports template-name-incorrect.
     */
    public function test_wrong_template_name(): void {
        $filepath = $this->fixturedir . '/templates/wrong-template-name.mustache';
        $results = $this->linter->lint_file($filepath);
        self::assertCount(1, $results);
        $rules = array_map(fn(issue $i): ?string => $i->rule, $results[0]->issues);
        self::assertContains('template-name-incorrect', $rules);
    }

    /**
     * Test that a template with mixed-case template name reports template-name-casing.
     */
    public function test_mixed_case_name(): void {
        $class = $this->linter::class;
        $original = $class::$mocktemplatename;
        $class::$mocktemplatename = 'local_devkit/Test';

        $filepath = $this->fixturedir . '/templates/mixed-case-name.mustache';
        $results = $this->linter->lint_file($filepath);
        self::assertCount(1, $results);
        $rules = array_map(fn(issue $i): ?string => $i->rule, $results[0]->issues);
        self::assertContains('template-name-casing', $rules);

        $class::$mocktemplatename = $original;
    }

    /**
     * Test that a template without Example context reports documentation-example-context-required.
     */
    public function test_no_example_context(): void {
        $filepath = $this->fixturedir . '/templates/no-example-context.mustache';
        $results = $this->linter->lint_file($filepath);
        self::assertCount(1, $results);
        $rules = array_map(fn(issue $i): ?string => $i->rule, $results[0]->issues);
        self::assertContains('documentation-example-context-required', $rules);
    }

    /**
     * Test that a template with invalid JSON example reports documentation-example-context-decode.
     */
    public function test_invalid_json_example(): void {
        $filepath = $this->fixturedir . '/templates/invalid-json-example.mustache';
        $results = $this->linter->lint_file($filepath);
        self::assertCount(1, $results);
        $rules = array_map(fn(issue $i): ?string => $i->rule, $results[0]->issues);
        self::assertContains('documentation-example-context-decode', $rules);
    }

    /**
     * Test include patterns include the expected mustache path.
     */
    public function test_get_include_patterns(): void {
        $patterns = mustachelint::get_include_patterns();
        self::assertContains('*.mustache', $patterns);
    }

    /**
     * Test exclude patterns include tests directories.
     */
    public function test_get_exclude_patterns(): void {
        $patterns = mustachelint::get_exclude_patterns();
        self::assertContains('*/tests/fixtures/*', $patterns);
    }
}
