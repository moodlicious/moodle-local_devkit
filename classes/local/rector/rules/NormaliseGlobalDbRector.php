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

namespace local_devkit\local\rector\rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Global_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

use function count;

/**
 * Replaces `global $DB;` with `$DB = \core\di::get(\moodle_database::class);`.
 *
 * phpcs:disable moodle.NamingConventions.ValidFunctionName
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class NormaliseGlobalDbRector extends AbstractRector {
    /**
     * Definition
     */
    public function getRuleDefinition(): RuleDefinition {
        return new RuleDefinition(
            'Replaces global $DB with $DB = \core\di::get(\moodle_database::class)',
            [
                new CodeSample(
                    "global \$DB;\n\$DB->get_record('user', ['id' => 1]);",
                    "\$DB = \\core\\di::get(\\moodle_database::class);\n\$DB->get_record('user', ['id' => 1]);",
                ),
                new CodeSample(
                    "global \$CFG, \$DB, \$USER;\n\$DB->get_records('course');",
                    "global \$CFG, \$USER;\n\$DB = \\core\\di::get(\\moodle_database::class);\n\$DB->get_records('course');",
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
        return [Global_::class];
    }

    #[\Override]
    public function refactor(Node $node): Node|array|null {
        if (!$node instanceof Global_) {
            return null;
        }

        $dbvar = null;
        foreach ($node->vars as $var) {
            if ($var instanceof Variable && $var->name === 'DB') {
                $dbvar = $var;
                break;
            }
        }

        if ($dbvar === null) {
            return null;
        }

        $assignment = new Expression($this->createDbAssignment());

        if (count($node->vars) === 1) {
            return $assignment;
        }

        $node->vars = array_values(
            array_filter(
                $node->vars,
                fn($var) => $var !== $dbvar,
            ),
        );

        return [$node, $assignment];
    }

    /**
     * Creates the assignment expression for $DB = \core\di::get(\moodle_database::class)
     */
    private function createDbAssignment(): Assign {
        return new Assign(
            new Variable('DB'),
            new StaticCall(
                new Name('\\core\\di'),
                'get',
                [
                    new Arg(
                        new ClassConstFetch(
                            new Name('\\moodle_database'),
                            'class',
                        ),
                    ),
                ],
            ),
        );
    }
}
