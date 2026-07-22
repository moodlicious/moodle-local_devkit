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

namespace local_devkit\form;

use core\context;
use core\context\system;
use core\url;
use core_form\dynamic_form;

use function is_string;

/**
 * Class linter_config
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class linter_config extends dynamic_form {
    /**
     * Utility function to get the classname.
     * @return class-string<\local_devkit\local\lint\linters\base>|null
     */
    public function get_linter_classname() {
        /** @var class-string<\local_devkit\local\lint\linters\base>|null $classname */
        $classname = $this->optional_param('classname', null, PARAM_TEXT);
        if (is_string($classname) && $classname !== '') {
            return $classname;
        }

        $data = $this->get_data();
        if ($data !== null && property_exists($data, 'classname')) {
            /** @var class-string<\local_devkit\local\lint\linters\base>|null $classname */
            $classname = $data->classname;
            if (is_string($classname) && $classname !== '') {
                return $classname;
            }
        }

        return null;
    }

    #[\Override]
    public function definition(): void {
        $form = $this->_form;
        $form->addElement('hidden', 'classname');

        $classname = $this->get_linter_classname();
        if ($classname !== null) {
            $classname::define_config($form);
        }
    }

    #[\Override]
    protected function get_context_for_dynamic_submission(): context {
        return system::instance();
    }

    #[\Override]
    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/site:config', $this->get_context_for_dynamic_submission());
    }

    #[\Override]
    public function process_dynamic_submission() {
        $data = $this->get_data();
        if ($data === null) {
            return ['success' => false];
        }
        unset($data->classname);
        $linter = $this->get_linter_classname();
        if ($linter === null) {
            return ['success' => false];
        }
        $linter::save_config($data);
        return ['success' => true];
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        $linter = $this->get_linter_classname();
        if ($linter === null) {
            return;
        }

        $config = $linter::get_config();
        $data = (object) [
            ...(array) ($config ?? new \stdClass()),
            'classname' => $linter,
        ];
        $this->set_data($data);
        return;
    }

    #[\Override]
    protected function get_page_url_for_dynamic_submission(): url {
        return new url('/admin/settings.php', ['section' => 'local_devkit']);
    }
}
