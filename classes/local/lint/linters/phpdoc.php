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
use local_devkit\local\lint\schemas\file;

/**
 * The moodle-local_moodlecheck phpdoc linter.
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[linter(
    name: 'phpdoc',
    description: 'executes moodle-local_moodlecheck phpdoc linter',
)]
class phpdoc extends base {
    #[\Override]
    public static function get_include_patterns(): array {
        return [
            ...parent::get_include_patterns(),
            '*.php',
        ];
    }

    #[\Override]
    public function lint_file(string $filepath): array {
        $results = parent::lint_file($filepath);
        if (!$this->can_lint_file($filepath)) {
            return $results;
        }

        $filepath = realpath($filepath);
        if ($filepath === false) {
            return [];
        }

        $fileresult = new file($filepath);

        $installedpath = self::get_installed_path();
        if ($installedpath === null) {
            return [];
        }

        $checker = new \local_moodlecheck_file($filepath);
        $errors = $checker->validate();

        foreach ($errors as $error) {
            $issue = \local_devkit\local\lint\schemas\issue\phpdoc::from_object((object) $error);
            if (!$issue instanceof \local_devkit\local\lint\schemas\issue\phpdoc) {
                continue;
            }
            $fileresult->add_issue($issue);
        }

        $results[] = $fileresult;
        return $results;
    }

    /**
     * Gets the installed path.
     */
    private static function get_installed_path(): ?string {
        /** @var string|null $cache */
        static $cache;
        if (isset($cache)) {
            return $cache;
        }

        global $CFG;
        $path = $CFG->dirroot . '/local/moodlecheck/cli/moodlecheck.php';
        $realpath = realpath($path);
        $cache = $realpath !== false ? $realpath : null;

        if ($cache !== null) {
            require_once($CFG->dirroot . '/local/moodlecheck/locallib.php');
            require_once($CFG->dirroot . '/local/moodlecheck/rules/phpdocs_basic.php');
            \local_moodlecheck_registry::enable_all_rules();
        }

        return $cache;
    }

    #[\Override]
    public static function is_installed(): bool {
        return self::get_installed_path() !== null;
    }
}
