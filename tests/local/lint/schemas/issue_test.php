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

namespace local_devkit\local\lint\schemas;

use advanced_testcase;
use local_devkit\local\lint\severity;

/**
 * Unit tests for the issue class.
 *
 * @package   local_devkit
 * @covers    \local_devkit\local\lint\schemas\issue
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class issue_test extends advanced_testcase {
    /**
     * Test that constructor sets properties correctly.
     */
    public function test_constructor_sets_properties(): void {
        $issue = new issue(10, 20, 'Test message', 'rule1', 'test', severity::error);

        self::assertSame(10, $issue->line);
        self::assertSame(20, $issue->column);
        self::assertSame('Test message', $issue->message);
        self::assertSame('rule1', $issue->rule);
        self::assertSame('test', $issue->source);
        self::assertSame(severity::error, $issue->severity);
    }

    /**
     * Test that constructor allows null rule.
     */
    public function test_constructor_allows_null_rule(): void {
        $issue = new issue(1, 1, 'Message', null, 'source', severity::warning);
        self::assertNull($issue->rule);
    }

    /**
     * Test that base from_object returns null.
     */
    public function test_from_object_returns_null(): void {
        $obj = (object) ['some' => 'data'];
        $result = issue::from_object($obj);
        self::assertNull($result);
    }

    /**
     * Test that simple creates issue with default values.
     */
    public function test_simple_creates_issue_with_defaults(): void {
        $issue = issue::simple('Simple error');

        self::assertSame(0, $issue->line);
        self::assertSame(0, $issue->column);
        self::assertSame('Simple error', $issue->message);
        self::assertNull($issue->rule);
        self::assertSame('unknown', $issue->source);
        self::assertSame(severity::error, $issue->severity);
    }

    /**
     * Test that simple creates issue with custom severity.
     */
    public function test_simple_creates_issue_with_custom_severity(): void {
        $issue = issue::simple('Warning message', null, 'test', severity::warning);

        self::assertSame(severity::warning, $issue->severity);
        self::assertSame('test', $issue->source);
    }

    /**
     * Test that jsonSerialize returns correct structure.
     */
    public function test_json_serialize_returns_correct_structure(): void {
        $issue = new issue(5, 10, 'Error message', 'rule1', 'test', severity::error);
        $serialized = $issue->jsonSerialize();

        self::assertArrayHasKey('line', $serialized);
        self::assertArrayHasKey('column', $serialized);
        self::assertArrayHasKey('message', $serialized);
        self::assertArrayHasKey('rule', $serialized);
        self::assertArrayHasKey('source', $serialized);
        self::assertArrayHasKey('severity', $serialized);
        self::assertSame(5, $serialized['line']);
        self::assertSame(10, $serialized['column']);
        self::assertSame('Error message', $serialized['message']);
        self::assertSame('rule1', $serialized['rule']);
        self::assertSame('test', $serialized['source']);
        self::assertSame(severity::error, $serialized['severity']);
    }

    /**
     * Test that jsonSerialize handles null rule.
     */
    public function test_json_serialize_handles_null_rule(): void {
        $issue = new issue(1, 1, 'Message', null, 'source', severity::warning);
        $serialized = $issue->jsonSerialize();

        self::assertNull($serialized['rule']);
    }
}
