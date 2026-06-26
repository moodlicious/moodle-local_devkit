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

import ModalForm from "core_form/modalform";

/**
 * Finds all config form buttons and initialises it.
 *
 * @module     local_devkit/linter_config
 * @copyright  2026 Felix
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    const configButtons = document.querySelectorAll(
        "[data-linter-config-form][data-linter-classname]",
    );
    configButtons.forEach((button) =>
        button.addEventListener("click", (e) => {
            const element = e.target;
            if (!(element instanceof HTMLAnchorElement)) {
                return;
            }

            const {linterName, linterClassname} = element.dataset;
            if (!linterName || !linterClassname) {
                return;
            }

            e.preventDefault();

            const modalForm = new ModalForm({
                formClass: "local_devkit\\form\\linter_config",
                args: {classname: linterClassname},
                modalConfig: {
                    title: `Configuring ${linterName}`,
                },
                returnFocus: element,
            });
            // @ts-ignore
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
                window.location.reload();
            });

            modalForm.show();
        }),
    );
};
