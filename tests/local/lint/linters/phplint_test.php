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

/**
 * Unit tests for the phplint linter.
 *
 * @package   local_devkit
 * @covers    \local_devkit\local\lint\linters\phplint
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class phplint_test extends advanced_testcase {
    /** @var string Path to the test fixtures directory */
    private string $fixturedir;

    /** @var phplint Linter instance with relaxed exclude patterns for testing */
    private phplint $linter;

    protected function setUp(): void {
        parent::setUp();
        $path = realpath(__DIR__ . '/../../../fixtures');
        $this->fixturedir = $path !== false ? $path : __DIR__ . '/../../../fixtures';
        $this->linter = new #[linter(
            name: 'phplint',
            description: 'testable phplint linter for unit tests',
        )] class extends phplint {
            #[\Override]
            public static function get_exclude_patterns(): array {
                return [];
            }
        };
    }

    /**
     * Test that a valid PHP file passes with no issues.
     */
    public function test_passing_php_file(): void {
        $filepath = $this->fixturedir . '/php/passing.php';
        $results = $this->linter->lint_file($filepath);
        // Lint_file returns base result (file exists) + phplint result.
        $last = $results[count($results) - 1];
        $this->assertCount(0, $last->issues);
    }

    /**
     * Test that a file with a syntax error reports a linting error.
     */
    public function test_syntax_error(): void {
        $filepath = $this->fixturedir . '/php/syntax-error.php';
        $results = $this->linter->lint_file($filepath);
        $last = $results[count($results) - 1];
        $rules = array_map(fn($i) => $i->rule, $last->issues);
        $this->assertContains('php-file-must-parse-successfully', $rules);
    }

    /**
     * Test include patterns include the expected PHP path.
     */
    public function test_get_include_patterns(): void {
        $patterns = phplint::get_include_patterns();
        $this->assertContains('*.php', $patterns);
    }
}
