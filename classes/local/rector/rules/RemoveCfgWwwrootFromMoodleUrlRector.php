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

use core\url;
use moodle_url;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Removes unneeded $CFG->wwwroot from moodle_url usage.
 *
 * phpcs:disable moodle.NamingConventions.ValidFunctionName
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class RemoveCfgWwwrootFromMoodleUrlRector extends AbstractRector {
    /**
     * Definition
     */
    public function getRuleDefinition(): RuleDefinition {
        return new RuleDefinition(
            'Removes $CFG->wwwroot concatenation from moodle_url instantiation and upgrades to \core\url',
            [
                new CodeSample(
                    'new moodle_url($CFG->wwwroot . \'/mod/assign/view.php\');',
                    'new \core\url(\'/mod/assign/view.php\');',
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
        return [New_::class];
    }

    #[\Override]
    public function refactor(Node $node): ?Node {
        if (!$node instanceof New_) {
            return null;
        }

        if (!$this->is_moodle_url($node)) {
            return null;
        }

        if ($node->args === []) {
            return null;
        }

        $arg0 = $node->args[0];

        if (!$arg0 instanceof Arg) {
            return null;
        }

        if (!$arg0->value instanceof Concat) {
            return null;
        }

        if (!$this->is_cfg_wwwroot_property($arg0->value->left)) {
            return null;
        }

        $arg0->value = $arg0->value->right;

        return $node;
    }

    /**
     * Checks if the given node's class is a moodle url class.
     */
    private function is_moodle_url(New_ $node): bool {
        $classes = [
            moodle_url::class,
            url::class,
        ];
        return $this->isNames($node->class, $classes);
    }

    /**
     * Checks if an AST node matches `$CFG->wwwroot`
     */
    private function is_cfg_wwwroot_property(Node $node): bool {
        if (!$node instanceof PropertyFetch) {
            return false;
        }

        // Check if the variable is named `$CFG` and property is `wwwroot`.
        return $node->var instanceof Variable
            && $this->isName($node->var, 'CFG')
            && $this->isName($node->name, 'wwwroot');
    }
}
