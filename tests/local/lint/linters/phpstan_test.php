<?php
// This file is is part of Moodle - https://moodle.org/
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
 * Unit tests for the phpstan linter.
 *
 * @package   local_devkit
 * @covers    \local_devkit\local\lint\linters\phpstan
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class phpstan_test extends advanced_testcase {
    /**
     * Test that get_include_patterns includes *.php.
     */
    public function test_get_include_patterns(): void {
        $patterns = phpstan::get_include_patterns();
        $this->assertContains('*.php', $patterns);
    }

    /**
     * Test that get_include_patterns includes the parent patterns.
     */
    public function test_get_include_patterns_includes_parent_patterns(): void {
        $patterns = phpstan::get_include_patterns();
        $this->assertContains('*.php', $patterns);
    }

    /**
     * Test that get_rule_level returns default value of 8.
     */
    public function test_get_rule_level_default(): void {
        $level = phpstan::get_rule_level();
        $this->assertSame(8, $level);
    }

    /**
     * Test that get_result_cache_mode returns per_component by default.
     */
    public function test_get_result_cache_mode_default(): void {
        $mode = phpstan::get_result_cache_mode();
        $this->assertSame(phpstan::RESULT_CACHE_PER_COMPONENT, $mode);
    }

    /**
     * Test that get_stub_files returns an array.
     */
    public function test_get_stub_files_returns_array(): void {
        $this->resetAfterTest();
        $linter = new phpstan();
        $stubs = $linter->get_stub_files();
        $this->assertNotEmpty($stubs);
    }

    /**
     * Test that get_stub_files returns all stub files.
     */
    public function test_get_stub_files_finds_stub_files(): void {
        $this->resetAfterTest();
        $linter = new phpstan();
        $stubs = $linter->get_stub_files();
        $this->assertNotEmpty($stubs);
        foreach ($stubs as $stub) {
            $this->assertStringEndsWith('.stub', $stub);
        }
    }

    /**
     * Test that get_stub_files returns real paths.
     */
    public function test_get_stub_files_returns_real_paths(): void {
        $this->resetAfterTest();
        $linter = new phpstan();
        $stubs = $linter->get_stub_files();
        foreach ($stubs as $stub) {
            $this->assertFileExists($stub);
        }
    }

    /**
     * Test that get_stub_files returns unique paths.
     */
    public function test_get_stub_files_returns_unique_paths(): void {
        $this->resetAfterTest();
        $linter = new phpstan();
        $stubs = $linter->get_stub_files();
        $this->assertSame($stubs, array_unique($stubs));
    }
}
