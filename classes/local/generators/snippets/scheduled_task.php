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

namespace local_devkit\local\generators\snippets;

use Nette\PhpGenerator\ClassManipulator;

/**
 * Class scheduled_task
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduled_task extends base {
    #[\Override]
    public function generate(): string {
        $this->category = 'task';
        [$file, $namespace, $class] = self::php_file_with_namespaced_class();
        $namespace->addUse(\core\task\scheduled_task::class);

        $class->setExtends(\core\task\scheduled_task::class);
        $manipulator = new ClassManipulator($class);

        $getname = $manipulator->inheritMethod('get_name');
        $getname->removeComment();
        $getname->addAttribute('Override');
        $getname->addBody('return get_string(?, ?);', ["task:{$class->getName()}", $this->component]);

        $execute = $manipulator->inheritMethod('execute');
        $execute->removeComment();
        $execute->addAttribute('Override');
        $class->setMethods([$getname, $execute]);

        return $file;
    }
}
