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
 * Unit tests for the jsdoc linter.
 *
 * @package   local_devkit
 * @covers    \local_devkit\local\lint\linters\jsdoc
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class jsdoc_test extends advanced_testcase {
    /** @var string Path to the test fixtures directory */
    private string $fixturedir;

    /** @var jsdoc Linter instance with relaxed exclude patterns for testing */
    private jsdoc $linter;

    protected function setUp(): void {
        parent::setUp();
        $path = realpath(__DIR__ . '/../../../fixtures');
        $this->fixturedir = $path !== false ? $path : __DIR__ . '/../../../fixtures';
        $this->linter = new #[linter(
            name: 'jsdoc',
            description: 'testable jsdoc linter for unit tests',
        )] class extends jsdoc {
            #[\Override]
            public static function get_exclude_patterns(): array {
                return [];
            }
        };
    }

    /**
     * Test that a valid AMD file with boilerplate and docblock passes.
     */
    public function test_passing_amd_file(): void {
        $filepath = $this->fixturedir . '/amd/src/passing.js';
        $results = $this->linter->lint_file($filepath);
        $this->assertCount(1, $results);
        $this->assertCount(0, $results[0]->issues);
    }

    /**
     * Test that a valid ESM file with boilerplate and docblock passes.
     */
    public function test_passing_esm_file(): void {
        $filepath = $this->fixturedir . '/js/esm/src/passing.tsx';
        $results = $this->linter->lint_file($filepath);
        $this->assertCount(1, $results);
        $this->assertCount(0, $results[0]->issues);
    }

    /**
     * Test that a file missing the GPL boilerplate reports missing-boilerplate.
     */
    public function test_missing_boilerplate(): void {
        $filepath = $this->fixturedir . '/amd/src/missing-boilerplate.js';
        $results = $this->linter->lint_file($filepath);
        $this->assertCount(1, $results);
        $rules = array_map(fn($i) => $i->rule, $results[0]->issues);
        $this->assertContains('missing-boilerplate', $rules);
    }

    /**
     * Test that a file missing the docblock reports missing-docblock.
     */
    public function test_missing_docblock(): void {
        $filepath = $this->fixturedir . '/amd/src/missing-docblock.js';
        $results = $this->linter->lint_file($filepath);
        $this->assertCount(1, $results);
        $rules = array_map(fn($i) => $i->rule, $results[0]->issues);
        $this->assertContains('missing-docblock', $rules);
    }

    /**
     * Test that a file with incomplete docblock (missing @copyright and @license) reports the missing tags.
     */
    public function test_incomplete_docblock(): void {
        $filepath = $this->fixturedir . '/amd/src/incomplete-docblock.js';
        $results = $this->linter->lint_file($filepath);
        $this->assertCount(1, $results);
        $rules = array_map(fn($i) => $i->rule, $results[0]->issues);
        $this->assertContains('missing-copyright', $rules);
        $this->assertContains('missing-license', $rules);
    }

    /**
     * Test that a file with wrong @module name reports module-name-incorrect.
     */
    public function test_wrong_module_name(): void {
        $filepath = $this->fixturedir . '/amd/src/wrong-module-name.js';
        $results = $this->linter->lint_file($filepath);
        $this->assertCount(1, $results);
        $rules = array_map(fn($i) => $i->rule, $results[0]->issues);
        $this->assertContains('module-name-incorrect', $rules);
    }

    /**
     * Test include patterns include the expected AMD and ESM paths.
     */
    public function test_get_include_patterns(): void {
        $patterns = jsdoc::get_include_patterns();
        $this->assertContains('**/amd/src/*.js', $patterns);
        $this->assertContains('**/js/esm/src/*.ts', $patterns);
        $this->assertContains('**/js/esm/src/*.tsx', $patterns);
        $this->assertNotContains('**/js/esm/src/*.js', $patterns);
    }

    /**
     * Test exclude patterns include tests directories.
     */
    public function test_get_exclude_patterns(): void {
        $patterns = jsdoc::get_exclude_patterns();
        $this->assertContains('**/tests/fixtures/*', $patterns);
    }
}
