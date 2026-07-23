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

namespace local_devkit\local\cli\commands\purge;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;

/**
 * Command to purge Moodle caches.
 * Same as admin/cli/purge_cache.php.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class handler {
    /**
     * Invoke
     * @param string[] $caches
     */
    private static function invoke(
        SymfonyStyle $io,
        #[Option('Caches to purge')] array $caches = [],
    ): int {
        $allcaches = array_fill_keys(array_keys(self::get_caches()), false);
        $purgecaches = array_fill_keys($caches, true);

        $purgeoptions = [...$allcaches, ...$purgecaches];

        $io->text('Caches to purge:');
        $io->listing($caches);

        purge_caches($purgeoptions);

        $message = match (count($allcaches) === count($purgecaches)) {
            true => get_string('purgecachesfinished', 'admin'),
            false => get_string('purgeselectedcachesfinished', 'admin'),
        };

        $io->success($message);

        return Command::SUCCESS;
    }

    /**
     * Builds the command for purging caches
     * @param string[] $cachekeys
     */
    private static function build_command(string $name, array $cachekeys): Command {
        $caches = self::get_caches();
        $cachekey = null;

        if (count($cachekeys) === 1) {
            $cachekey = $cachekeys[0];
        }

        $command = new Command($name);
        if ($cachekey !== null) {
            $description = $caches[$cachekey];
            $command->setDescription($description);
        } else {
            $command->setDescription(get_string('purgecaches', 'admin'));
        }
        $command
            ->addOption(
                'caches',
                mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                default: $cachekey !== null ? [$cachekey] : array_keys($caches),
            )
            ->setCode(self::invoke(...));
        return $command;
    }

    /**
     * Gets a list of purgable caches.
     * @return array<string, string>
     */
    public static function get_caches(): array {
        return [
            'courses' => get_string('purgecoursecache', 'admin'),
            'filter' => get_string('purgefiltercache', 'admin'),
            'js' => get_string('purgejscache', 'admin'),
            'lang' => get_string('purgelangcache', 'admin'),
            'muc' => get_string('purgemuc', 'admin'),
            'template' => get_string('purgetemplates', 'admin'),
            'theme' => get_string('purgethemecache', 'admin'),
            'other' => get_string('purgeothercaches', 'admin'),
        ];
    }

    /**
     * Register purge and purge:* commands
     */
    public static function register(Application $app): void {
        $caches = self::get_caches();
        // Make the command for purging all caches.
        $command = self::build_command('purge', array_keys($caches));
        $app->addCommand($command);

        // Make commands for running each individual cache.
        foreach (array_keys($caches) as $cachekey) {
            $command = self::build_command("purge:$cachekey", [$cachekey]);
            $app->addCommand($command);
        }
    }
}
