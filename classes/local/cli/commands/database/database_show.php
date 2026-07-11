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
 * Command to list all installed plugins.
 *
 * // phpcs:disable moodle.Commenting.ValidTags.Invalid
 * @phpstan-import-type PluginDatabase from database
 * @phpstan-import-type DatabaseField from database
 * @phpstan-import-type DatabaseKey from database
 * @phpstan-import-type DatabaseIndex from database
 * @phpstan-import-type DatabaseTable from database
 * @phpstan-import-type DatabaseKeyReferences from database
 * // phpcs:enable moodle.Commenting.ValidTags.Invalid
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[AsCommand(name: 'db:show|database:show', description: 'List all database tables or only tables of a specific component.')]
class database_show extends Command {
    /**
     * Invoke
     * @param string|null $component
     * @param SymfonyStyle $io
     * @return int
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The component name of the plugin.')] ?string $component = null,
        #[Option('What format to display (table/json)', suggestedValues: ['table', 'json'])] string $format = 'table',
    ): int {
        $plugintables = [];
        try {
            $plugintables = self::get_data($component);

            match ($format) {
                'table' => self::display_table($io, $plugintables),
                'json' => self::display_json($io, $plugintables),
                default => throw new Exception('Unknown format, available formats are table,json'),
            };

            return 0;
        } catch (\Throwable $th) {
            $io->error($th->getMessage());
            return 1;
        }
    }

    /**
     * Helper function to get the data for this command.
     * @param string|null $component
     * @throws Exception
     * @return PluginDatabase[]
     */
    public static function get_data(?string $component) {
        if ($component) {
            $plugintable = database::list_plugin_tables($component);
            if (!$plugintable) {
                throw new Exception("Component '$component' does not define db/install.xml.");
            }
            $plugintables[] = $plugintable;
        } else {
            $plugintables = database::list_tables();
        }

        return $plugintables;
    }

    /**
     * Displays tables as a table.
     * @param SymfonyStyle $io
     * @param PluginDatabase[] $data
     * @return void
     */
    public static function display_table(SymfonyStyle $io, array $data): void {
        foreach ($data as $database) {
            $io->title($database['name']);
            $io->comment($database['comment']);

            $io->text('Tables');
            $io->listing(
                array_map(
                    fn(/** @var DatabaseTable $table */ $table) => "{$table['name']}: {$table['comment']}",
                    $database['tables'],
                ),
            );
        }
    }

    /**
     * Displays table
     * @param SymfonyStyle $io
     * @param DatabaseTable $table
     * @return void
     */
    public static function display_table_table(SymfonyStyle $io, array $table): void {
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

    /**
     * Displays tables as JSON.
     * @param SymfonyStyle $io
     * @param PluginDatabase[] $data
     * @return void
     */
    public static function display_json(SymfonyStyle $io, array $data): void {
        $io->writeln(json_encode(self::process_json($data), JSON_THROW_ON_ERROR));
    }

    /**
     * Process json for display.
     * @param PluginDatabase[] $data
     * @return array{name: string, tables: array{name: string, comment: string}[]}[]
     */
    public static function process_json(array $data): array {
        $json = [];
        foreach ($data as $database) {
            $json[] = [
                'name' => $database['name'],
                'comment' => $database['comment'],
                'tables' => array_map(fn(/** @var DatabaseTable $table */ $table) => [
                    'name' => $table['name'],
                    'comment' => $table['comment'],
                ], $database['tables']),
            ];
        }
        return $json;
    }
}
