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

namespace local_devkit\local\lint\api;

use advanced_testcase;
use local_devkit\local\api\linter;
use local_devkit\local\lint\linters\base;
use local_devkit\local\lint\linters\eslint;
use local_devkit\local\lint\linters\lang;
use local_devkit\local\lint\linters\phpcs;
use local_devkit\local\lint\linters\phpdoc;
use local_devkit\local\lint\linters\phplint;
use local_devkit\local\lint\linters\phpstan;
use local_devkit\local\lint\linters\stylelint;

/**
 * Unit tests for the linter API class.
 *
 * @package   local_devkit
 * @covers    \local_devkit\local\api\linter
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class linter_test extends advanced_testcase {
    /**
     * Test that get_linters_classnames returns all linters when all are enabled.
     */
    public function test_get_linters_classnames_returns_all_when_enabled(): void {
        $linters = linter::get_linters_classnames([
            'eslint',
            'lang',
            'phpcs',
            'phplint',
            'phpdoc',
            'phpstan',
            'stylelint',
        ]);

        self::assertSame([
            '\\' . eslint::class,
            '\\' . lang::class,
            '\\' . phpcs::class,
            '\\' . phpdoc::class,
            '\\' . phplint::class,
            '\\' . phpstan::class,
            '\\' . stylelint::class,
        ], $linters);
    }

    /**
     * Test that get_linters_classnames returns empty when all are disabled.
     */
    public function test_get_linters_classnames_returns_empty_when_all_disabled(): void {
        $linters = linter::get_linters_classnames([]);

        self::assertSame([], $linters);
    }

    /**
     * Test that get_linters_classnames excludes specific linters when disabled.
     */
    public function test_get_linters_classnames_excludes_specific_linters(): void {
        $linters = linter::get_linters_classnames([
            'eslint',
            'phpcs',
            'phplint',
            'stylelint',
        ]);

        self::assertSame([
            '\\' . eslint::class,
            '\\' . phpcs::class,
            '\\' . phplint::class,
            '\\' . stylelint::class,
        ], $linters);
    }

    /**
     * Test that get_linters_info returns linter names with descriptions.
     */
    public function test_get_linters_info_returns_linter_names(): void {
        $linters = [phplint::class];
        $info = linter::get_linters_info($linters);

        self::assertSame(['phplint: executes "php -l" for syntax checking'], $info);
    }

    /**
     * Test that get_linters_info handles null description gracefully.
     */
    public function test_get_linters_info_handles_null_description(): void {
        $linter = new #[\local_devkit\local\attributes\linter('base')] class extends base {
        };

        $info = linter::get_linters_info([$linter::class]);

        self::assertSame(['base'], $info);
    }
}
