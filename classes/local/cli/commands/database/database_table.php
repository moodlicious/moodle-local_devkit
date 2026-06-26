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

namespace local_devkit\local\cli\commands\database;

use Exception;
use local_devkit\local\api\database;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Get information about a specific database table.
 *
 * // phpcs:disable moodle.Commenting.ValidTags.Invalid
 * @phpstan-import-type DatabaseTable from database
 * // phpcs:enable moodle.Commenting.ValidTags.Invalid
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[AsCommand(name: 'db:table|database:table', description: 'Get information about a specific database table.')]
class database_table extends Command {
    /**
     * Invoke
     * @param string $tablename
     * @param SymfonyStyle $io
     * @return int
     */
    public function __invoke(
        #[Argument('The table name.')] string $tablename,
        SymfonyStyle $io,
        #[Option('What format to display (table/json)', suggestedValues: ['table', 'json'])] string $format = 'table',
    ): int {
        try {
            $table = self::get_data($tablename);

            match ($format) {
                'table' => database_show::display_table_table($io, $table),
                'json' => self::display_json($io, $table),
                default => throw new Exception('Unknown format, available formats are table,json'),
            };

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $io->error($th->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Helper function to get required data for this command.
     * @param string $tablename
     * @throws Exception
     * @return DatabaseTable
     */
    public static function get_data(string $tablename) {
        $table = database::find_table($tablename);
        if (!$table) {
            throw new Exception("Table with name '$tablename' not found.");
        }
        return $table;
    }

    /**
     * Displays table as JSON.
     * @param SymfonyStyle $io
     * @param DatabaseTable $data
     * @return void
     */
    public static function display_json(SymfonyStyle $io, array $data): void {
        $io->writeln(json_encode(self::process_json($data), JSON_THROW_ON_ERROR));
    }

    /**
     * Processes json for output.
     * @param DatabaseTable $data
     * @return DatabaseTable
     */
    public static function process_json(array $data) {
        return $data;
    }
}
