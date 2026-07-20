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
use local_devkit\local\lint\schemas\issue;
use local_devkit\local\lint\severity;
use xmldb_file;

use function strlen;

/**
 * The xmldb linter.
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[linter(
    name: 'xmldb',
    description: 'validates db/install.xml files',
)]
class xmldb extends base {
    #[\Override]
    public static function get_include_patterns(): array {
        return [
            ...parent::get_include_patterns(),
            ...['*/install.xml'],
        ];
    }

    #[\Override]
    public function lint_file(string $filepath): array {
        global $CFG;

        $results = parent::lint_file($filepath);
        if (!$this->can_lint_file($filepath)) {
            return $results;
        }

        $file = new file($filepath);
        $results[] = $file;

        $xml = new xmldb_file($filepath);
        $xml->setDTD("$CFG->libdir/xmldb/xmldb.dtd");
        $xml->setSchema("$CFG->libdir/xmldb/xmldb.xsd");

        if (!$xml->validateXMLStructure()) {
            // Oops, got some errors, so return them.
            $errormessage = $xml->getStructure()->getError();
            foreach ($this->parse_error_string($errormessage) as $error) {
                $file->add_issue(new issue(
                    $error['line'],
                    0,
                    $error['message'],
                    'invalid-structure',
                    self::get_name(),
                    severity::error,
                ));
            }

            return $results;
        }

        // No errors, now check formatting.
        $xml->loadXMLStructure();
        $expected = $xml->getStructure()->xmlOutput();
        $actual = file_get_contents($filepath);

        if ($expected !== $actual) {
            $file->add_issue(new issue(
                0,
                0,
                'XML has incorrect formatting.',
                'incorrect-format',
                self::get_name(),
                severity::warning,
                ['Use the xmldb editor to reformat the file.'],
            ));
        }

        return $results;
    }

    /**
     * Parses the error from xmldb back into a neat format.
     * Reverses the error formatting from {@see xmldb_file::validateXMLStructure()}
     * @param string $text
     * @return array{line: int, message: string}[]
     */
    public function parse_error_string(string $text): array {
        $prefix = 'XML Error: ';
        $text = substr($text, strlen($prefix));

        preg_match_all(
            '/(.*?)\s+at\s+line\s+(\d+)\./',
            $text,
            $matches,
            PREG_SET_ORDER,
        );

        $result = [];

        foreach ($matches as $match) {
            $result[] = [
                'line' => (int) $match[2],
                'message' => trim($match[1]),
            ];
        }

        return $result;
    }
}
