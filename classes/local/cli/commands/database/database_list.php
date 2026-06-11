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

namespace local_devtools\local\cli\commands\database;

use Exception;
use local_devtools\local\api\database;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to list all installed plugins.
 *
 * // phpcs:disable moodle.Commenting.ValidTags.Invalid
 * @phpstan-import-type PluginDatabase from database
 * @phpstan-import-type DatabaseField from database
 * @phpstan-import-type DatabaseKey from database
 * @phpstan-import-type DatabaseIndex from database
 * @phpstan-import-type DatabaseKeyReferences from database
 * // phpcs:enable moodle.Commenting.ValidTags.Invalid
 *
 * @package   local_devtools
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[AsCommand(name: 'database:list', description: 'List all database tables of a specific component.')]
class database_list extends Command {
    /**
     * Invoke
     * @param string $component
     * @param SymfonyStyle $io
     * @return int
     */
    public function __invoke(
        #[Argument('The component name of the plugin.')] string $component,
        SymfonyStyle $io,
        #[Option('What format to display (table/json)', suggestedValues: ['table', 'json'])] string $format = 'table',
    ): int {
        try {
            $result = database::list_plugin_tables($component);

            match ($format) {
                'table' => self::display_table($io, $result),
                'json' => self::display_json($io, $result),
                default => throw new Exception('Unknown format, available formats are table,json'),
            };

            return 0;
        } catch (\Throwable $th) {
            $io->error($th->getMessage());
            return 1;
        }
    }

    /**
     * Displays tables as a table.
     * @param SymfonyStyle $io
     * @param PluginDatabase $data
     * @return void
     */
    public static function display_table(SymfonyStyle $io, array $data): void {
        $io->title($data['name']);
        $io->comment($data['comment']);

        foreach ($data['tables'] as $table) {
            $io->section("Table: {$table['name']}");
            $io->comment($table['comment']);

            $io->text('Fields');
            $io->table(
                ['name', 'type', 'comment'],
                array_map(fn(/** @var DatabaseField $field */ $field) => [
                    $field['name'],
                    $field['type'],
                    $field['comment'],
                ], $table['fields']),
            );

            $io->text('Indexes');
            $io->table(
                ['name', 'fields', 'unique', 'comment'],
                array_map(fn(/** @var DatabaseIndex $index */ $index) => [
                    $index['name'],
                    implode(',', $index['fields']),
                    $index['unique'],
                    $index['comment'],
                ], $table['indexes']),
            );

            $io->text('Keys');
            $io->table(
                ['name', 'type', 'fields', 'references', 'comment'],
                array_map(fn(/** @var DatabaseKey $key */ $key) => [
                    $key['name'],
                    $key['type'],
                    implode(',', $key['fields']),
                    $key['references']['table']
                    ? $key['references']['table'] . '.' . implode(',', $key['references']['fields'])
                    : '',
                    $key['comment'],
                ], $table['keys']),
            );
        }
    }

    /**
     * Displays tables as JSON.
     * @param SymfonyStyle $io
     * @param PluginDatabase $data
     * @return void
     */
    public static function display_json(SymfonyStyle $io, array $data): void {
        $io->writeln(json_encode($data, JSON_THROW_ON_ERROR));
    }
}
