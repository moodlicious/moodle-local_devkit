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

namespace local_devkit\local\seeders;

use Faker\Factory;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressIndicator;

/**
 * Class users
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users extends base {
    /**
     * Constructor.
     */
    public function __construct(
        ?ProgressIndicator $progress,
        /** @var int */
        private readonly int $count = 1,
    ) {
        parent::__construct($progress);
        if ($count <= 0) {
            throw new InvalidArgumentException('count must be positive integer');
        }
    }

    #[\Override]
    public function seed(): void {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $faker = Factory::create();

        // This is to ensure the password passes Moodle's password policy.
        $passwordsuffix = '@aA1!';

        foreach (range(1, $this->count) as $index) {
            $this->progress?->setMessage("Seeding $index/$this->count");
            $user = [
                'firstname' => $faker->firstName(),
                'lastname' => $faker->lastName(),
                'username' => $faker->userName(),
                'email' => $faker->safeEmail(),
                'password' => $faker->password() . $passwordsuffix,
                'confirmed' => 1,
                'mnethostid' => get_config('', 'mnet_localhost_id'),
            ];
            user_create_user($user);
        }
    }
}
