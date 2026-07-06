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
 * Unit tests for the lang linter.
 *
 * @package   local_devkit
 * @covers    \local_devkit\local\lint\linters\lang
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lang_test extends advanced_testcase {
    /** @var string Path to the test fixtures directory */
    private string $fixturedir;

    /** @var lang Linter instance with relaxed exclude patterns for testing */
    private lang $linter;

    protected function setUp(): void {
        parent::setUp();
        $path = realpath(__DIR__ . '/../../../fixtures');
        $this->fixturedir = $path !== false ? $path : __DIR__ . '/../../../fixtures';
        $this->linter = new #[linter(
            name: 'lang',
            description: 'testable lang linter for unit tests',
        )] class extends lang {
            #[\Override]
            public static function get_exclude_patterns(): array {
                return [];
            }
        };
    }

    /**
     * Test that a component with matching identifiers across all locales passes.
     */
    public function test_passing_component(): void {
        $filepath = $this->fixturedir . '/lang/en/mod_assign.php';
        $results = $this->linter->lint_file($filepath);
        // mod_assign has identical identifiers in en and fr, no issues expected.
        $this->assertCount(0, $results);
    }

    /**
     * Test that a component missing the en locale reports linting-requires-en-locale.
     */
    public function test_missing_en_locale(): void {
        $filepath = $this->fixturedir . '/lang/en/missing_en.php';
        $results = $this->linter->lint_file($filepath);
        $this->assertCount(1, $results);
        // array_filter preserves keys, use reset() to get first result.
        $result = reset($results);
        $rules = array_map(fn($i) => $i->rule, $result->issues);
        $this->assertContains('linting-requires-en-locale', $rules);
    }

    /**
     * Test that a file missing identifiers present in en reports identifier-missing.
     */
    public function test_identifier_missing_in_locale(): void {
        $filepath = $this->fixturedir . '/lang/fr/local_devkit.php';
        $results = $this->linter->lint_file($filepath);
        // validate_component creates separate file results per issue,
        // and array_filter preserves keys — collect all issues across results.
        $allissues = array_merge(
            ...array_map(fn($r) => $r->issues, $results),
        );
        $rules = array_map(fn($i) => $i->rule, $allissues);
        $this->assertContains('identifier-missing', $rules);
    }

    /**
     * Test that a file with identifiers missing from en reports identifier-safely-missing.
     */
    public function test_identifier_safely_missing(): void {
        $filepath = $this->fixturedir . '/lang/en/local_devkit.php';
        $results = $this->linter->lint_file($filepath);
        $allissues = array_merge(
            ...array_map(fn($r) => $r->issues, $results),
        );
        $rules = array_map(fn($i) => $i->rule, $allissues);
        $this->assertContains('identifier-safely-missing', $rules);
    }

    /**
     * Test that a file missing placeholders from the en locale reports identifier-placeholders-missing.
     */
    public function test_placeholder_missing(): void {
        $filepath = $this->fixturedir . '/lang/fr/missing_placeholder.php';
        $results = $this->linter->lint_file($filepath);
        $allissues = array_merge(
            ...array_map(fn($r) => $r->issues, $results),
        );
        $rules = array_map(fn($i) => $i->rule, $allissues);
        $this->assertContains('identifier-placeholders-missing', $rules);
    }

    /**
     * Test that a file with extra placeholders not in en reports identifier-placeholders-extra.
     */
    public function test_placeholder_extra(): void {
        $filepath = $this->fixturedir . '/lang/de/extra_placeholder.php';
        $results = $this->linter->lint_file($filepath);
        $allissues = array_merge(
            ...array_map(fn($r) => $r->issues, $results),
        );
        $rules = array_map(fn($i) => $i->rule, $allissues);
        $this->assertContains('identifier-placeholders-extra', $rules);
    }

    /**
     * Test include patterns include the expected lang path.
     */
    public function test_get_include_patterns(): void {
        $patterns = lang::get_include_patterns();
        $this->assertContains('**/lang/*/*.php', $patterns);
    }

    /**
     * Test exclude patterns include vendor directories.
     */
    public function test_get_exclude_patterns(): void {
        $patterns = lang::get_exclude_patterns();
        $this->assertContains('**/vendor/**', $patterns);
    }
}
