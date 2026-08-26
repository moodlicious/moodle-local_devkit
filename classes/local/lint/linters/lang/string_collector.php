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

namespace local_devkit\local\lint\linters\lang;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

/**
 * AST visitor that collects $string['key'] = 'value' assignments from lang files.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class string_collector extends NodeVisitorAbstract {
    /** @var array<string, string> */
    public array $strings = [];

    #[\Override]
    public function enterNode(Node $node): null {
        if (
            $node instanceof Assign &&
            $node->var instanceof ArrayDimFetch &&
            $node->var->var instanceof Variable &&
            $node->var->var->name === 'string' &&
            $node->var->dim instanceof String_ &&
            $node->expr instanceof String_
        ) {
            $this->strings[$node->var->dim->value] = $node->expr->value;
        }

        return null;
    }
}
