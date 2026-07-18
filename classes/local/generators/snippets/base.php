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

use local_devkit\local\component;
use local_devkit\local\utils;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;

/**
 * Class base
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var string */
    protected readonly string $filepath;
    /** @var string */
    protected readonly string $component;
    /** @var string */
    protected readonly string $copyright;
    /** @var string|null */
    protected ?string $category = null;

    /**
     * Constructor.
     */
    public function __construct(string $filepath) {
        $this->filepath = utils::get_path_relative_to_moodle_root($filepath);
        $this->component = component::resolve_component_from_path($this->filepath);

        $year = date("Y");
        $this->copyright = "$year Your name";
    }

    /**
     * Generates the snippet.
     */
    abstract public function generate(): string;

    /**
     * Standard PHP File.
     */
    protected function php_file(): PhpFile {
        return new PhpFile();
    }

    /**
     * Standard PHP File with class.
     * @return array{PhpFile, ClassType}
     */
    protected function php_file_with_class(): array {
        $file = $this->php_file();
        [$namespacename, $classname] = $this->php_class_info();
        $namespace = $file->addNamespace($namespacename);
        $class = $namespace->addClass($classname);
        $this->php_class_add_docblock_tags($class, $this->category);
        return [$file, $class];
    }

    /**
     * Adds docblock to class.
     */
    protected function php_class_add_docblock_tags(
        ClassType $class,
        ?string $category = null,
    ): ClassType {
        $license = 'https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later';

        if (!$class->getComment()) {
            $class->addComment("Class {$class->getName()}.");
        }

        $class->addComment('');
        $class->addComment("@package   $this->component");

        if ($category) {
            $class->addComment("@category  $category");
        }

        $class->addComment("@copyright $this->copyright");
        $class->addComment("@license   $license");
        return $class;
    }

    /**
     * Deduces the namespace and classname for a given path.
     * @return array{string, string}
     */
    protected function php_class_info(): array {
        $dirpath = dirname($this->filepath);
        $classname = basename($this->filepath, '.php');
        $componentpath = utils::get_path_relative_to_moodle_root(
            \core_component::get_component_directory($this->component),
        );

        $classesdir = "$componentpath/classes/";
        $namespace = str_replace($classesdir, '', $dirpath);
        $namespace = str_replace('/', '\\', $namespace);
        $namespace = "$this->component\\$namespace";

        return [$namespace, $classname];
    }
}
