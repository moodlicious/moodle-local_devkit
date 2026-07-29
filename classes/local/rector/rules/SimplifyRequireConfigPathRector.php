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
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

use function count;
use function str_ends_with;

/**
 * Simplifies require/require_once paths to use __DIR__ relative paths.
 *
 * Converts `require(dirname(dirname(__DIR__)) . '/config.php');`
 * into `require(__DIR__ . '/../../config.php');`
 *
 * Also handles `__FILE__` variants (e.g. `dirname(dirname(__FILE__))`).
 * Works at any nesting depth.
 *
 * phpcs:disable moodle.NamingConventions.ValidFunctionName
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class SimplifyRequireConfigPathRector extends AbstractRector {
    /**
     * Definition
     */
    public function getRuleDefinition(): RuleDefinition {
        return new RuleDefinition(
            'Simplifies require/require_once paths to use __DIR__ relative paths for better IDE navigation',
            [
                new CodeSample(
                    "require(dirname(dirname(__DIR__)) . '/config.php');",
                    "require(__DIR__ . '/../../config.php');",
                ),
                new CodeSample(
                    "require_once(dirname(dirname(dirname(__DIR__))) . '/config.php');",
                    "require_once(__DIR__ . '/../../../config.php');",
                ),
                new CodeSample(
                    "require(dirname(__DIR__, 2) . '/config.php');",
                    "require(__DIR__ . '/../../config.php');",
                ),
                new CodeSample(
                    "require_once(dirname(__FILE__, 3) . '/config.php');",
                    "require_once(__DIR__ . '/../../../config.php');",
                ),
                new CodeSample(
                    "require(dirname(dirname(__FILE__)) . '/config.php');",
                    "require(__DIR__ . '/../../config.php');",
                ),
                new CodeSample(
                    "require_once(dirname(__DIR__) . '/config.php');",
                    "require_once(__DIR__ . '/../config.php');",
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
        return [Include_::class];
    }

    #[\Override]
    public function refactor(Node $node): ?Node {
        if (!$node instanceof Include_) {
            return null;
        }

        if ($node->type !== Include_::TYPE_REQUIRE_ONCE && $node->type !== Include_::TYPE_REQUIRE) {
            return null;
        }

        if (!$node->expr instanceof Concat) {
            return null;
        }

        $concat = $node->expr;

        $depth = $this->resolve_dirname_depth($concat->left);
        if ($depth === null) {
            return null;
        }

        if (!$concat->right instanceof String_) {
            return null;
        }

        $suffix = $concat->right->value;
        if (!str_ends_with($suffix, '/config.php')) {
            return null;
        }

        $prefix = str_repeat('/..', $depth);

        $concat->left = new Dir();
        $concat->right = new String_("$prefix$suffix");

        return $node;
    }

    /**
     * Resolves the dirname depth from a chain of nested dirname() calls
     * or from the dirname(__DIR__, N) multi-arg form.
     *
     * Returns null if the node is not a dirname chain wrapping __DIR__ or __FILE__.
     */
    private function resolve_dirname_depth(Node $node): ?int {
        if ($node instanceof FuncCall && $this->isName($node, 'dirname')) {
            $depth = $this->resolve_multi_arg_dirname($node);
            if ($depth !== null) {
                return $depth;
            }
        }

        return $this->resolve_chain_dirname($node);
    }

    /**
     * Resolves depth from dirname(__DIR__, N) form.
     */
    private function resolve_multi_arg_dirname(FuncCall $node): ?int {
        if (count($node->args) !== 2) {
            return null;
        }

        $arg0 = $node->args[0];
        $arg1 = $node->args[1];

        if (!$arg0 instanceof Arg || !$arg1 instanceof Arg) {
            return null;
        }

        if (!$arg0->value instanceof Dir && !$arg0->value instanceof File) {
            return null;
        }

        if (!$arg1->value instanceof Int_) {
            return null;
        }

        return $arg1->value->value;
    }

    /**
     * Resolves depth from dirname(dirname(...dirname(__DIR__)...)) form.
     */
    private function resolve_chain_dirname(Node $node): ?int {
        $depth = 0;
        $current = $node;
        while ($current instanceof FuncCall && $this->isName($current, 'dirname')) {
            if (count($current->args) !== 1) {
                return null;
            }

            $arg = $current->args[0];
            if (!$arg instanceof Arg) {
                return null;
            }

            $depth++;
            $value = $arg->value;

            if ($value instanceof Dir || $value instanceof File) {
                return $depth;
            }

            $current = $value;
        }

        return null;
    }
}
