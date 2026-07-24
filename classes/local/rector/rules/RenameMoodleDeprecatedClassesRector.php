<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_devkit\local\rector\rules;

use core\context\block;
use core\context\course;
use core\context\coursecat;
use core\context\module;
use core\context\system;
use core\context\user;
use PhpParser\Node;
use Rector\Rector\AbstractRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

use function is_string;
use function strlen;

/**
 * Updates old classnames to new namespaced names.
 *
 * phpcs:disable moodle.NamingConventions.ValidFunctionName
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class RenameMoodleDeprecatedClassesRector extends AbstractRector {
    /**
     * Map of old Moodle class aliases to their new namespaced equivalents.
     * @var array<class-string, class-string>
     */
    private const array CONTEX_CLASS_MAP = [
        // These are not included in legacyclasses.php.
        \context_block::class => block::class,
        \context_course::class => course::class,
        \context_coursecat::class => coursecat::class,
        \context_helper::class => \core\context_helper::class,
        \context_module::class => module::class,
        \context_system::class => system::class,
        \context_user::class => user::class,
        \context::class => \core\context::class,
    ];

    /** @var array<class-string, class-string> $classmap */
    private static array $classmap = [];

    /**
     * Loads the classmap.
     */
    private function construct_classmap(): void {
        global $CFG;
        if (self::$classmap !== []) {
            return;
        }
        self::$classmap = self::CONTEX_CLASS_MAP;

        if (!isset($CFG)) {
            return;
        }

        /** @var mixed $legacyclasses */
        $legacyclasses = null;
        require("$CFG->libdir/db/legacyclasses.php");

        /** @var array<class-string, string|array{string, string}> $legacyclasses */
        $legacyclasses ??= [];

        foreach ($legacyclasses as $oldclassname => $path) {
            try {
                if (!class_exists($oldclassname)) {
                    continue;
                }

                if (is_string($path)) {
                    $subsystem = 'core';
                    $file = $path;
                } else {
                    [$subsystem, $file] = $path;
                }

                $suffix = '.php';
                if (str_ends_with($file, $suffix)) {
                    $file = substr($file, 0, strlen($file) - strlen($suffix));
                }
                $file = str_replace('/', '\\', $file);
                $newclassname = "$subsystem\\$file";

                if (class_exists($newclassname)) {
                    self::$classmap[$oldclassname] = $newclassname;
                }
                // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
            } catch (\Throwable) {
                // Too bad.
            }
        }
    }

    /**
     * Constructor.
     */
    public function __construct(
        /** @var RenameClassRector $renamereactor */
        private readonly RenameClassRector $renamereactor,
    ) {
    }

    /**
     * Rule definition
     */
    public function getRuleDefinition(): RuleDefinition {
        return new RuleDefinition(
            'Automatically renames legacy Moodle class aliases to their modern namespaced equivalents',
            [
                new CodeSample(
                    '$context = context_system::instance();',
                    '$context = \core\context\system::instance();',
                ),
            ],
        );
    }

    /**
     * {@inheritDoc}
     * @return array<class-string<Node>>
    */
    #[\Override]
    public function getNodeTypes(): array {
        return $this->renamereactor->getNodeTypes();
    }

    #[\Override]
    public function refactor(Node $node): ?Node {
        $this->construct_classmap();
        $this->renamereactor->configure(self::$classmap);
        // phpcs:ignore
        // @phpstan-ignore argument.type
        return $this->renamereactor->refactor($node);
    }
}
