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

namespace local_devkit\local\api;

use Exception;
use local_devkit\local\schema\database as database_schema;
use xmldb_file;
use xmldb_structure;

/**
 * Plugins API.
 *
 * @package   local_devkit
 * @copyright 2026 Felix Yeung
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class database {
    /**
     * List database tables for a given plugin.
     * @param string $component
     * @return database_schema|null
     */
    public static function list_plugin_tables(string $component): ?database_schema {
        $targetplugin = plugins::get_by_component($component);

        if ($targetplugin === null) {
            throw new Exception("Plugin with component '$component' not found.");
        }

        $xmlpath = $targetplugin['directory'] . '/db/install.xml';
        if (!file_exists($xmlpath)) {
            return null;
        }

        return self::list_tables_from_xml($xmlpath);
    }

    /**
     * Gets database tables from a specific db/install.xml file.
     * @param string $xmlpath
     * @return database_schema|null
     */
    public static function list_tables_from_xml(string $xmlpath) {
        $structure = self::get_xmldb_structure($xmlpath);

        /** @var \xmldb_table[] $tables */
        $tables = $structure->getTables();

        $tableresults = [];

        foreach ($tables as $table) {
            $fields = $table->getFields();
            $keys = $table->getKeys();
            $indexes = $table->getIndexes();

            $fieldsresults = [];
            $keysresults = [];
            $indexesresults = [];

            foreach ($fields as $field) {
                $fieldsresults[] = new database_schema\field(
                    name: $field->getName(),
                    comment: $field->getComment(),
                    type: self::field_type_to_string($field->getType()),
                );
            }

            foreach ($keys as $key) {
                $keysresults[] = new database_schema\key(
                    name: $key->getName(),
                    comment: $key->getComment(),
                    type: self::key_type_to_string($key->getType()),
                    fields: $key->getFields(),
                    references: new database_schema\reference(
                        table: $key->getRefTable(),
                        fields: $key->getRefFields(),
                    ),
                );
            }

            foreach ($indexes as $index) {
                $indexesresults[] = new database_schema\index(
                    name: $index->getName(),
                    comment: $index->getComment(),
                    unique: $index->getUnique(),
                    fields: $index->getFields(),
                );
            }

            $tableresults[] = new database_schema\table(
                name: $table->getName(),
                comment: $table->getComment(),
                fields: $fieldsresults,
                keys: $keysresults,
                indexes: $indexesresults,
            );
        }

        $result = new database_schema(
            name: $structure->getName(),
            comment: $structure->getComment(),
            tables: $tableresults,
        );

        return $result;
    }

    /**
     * Returns every single plugin tables.
     * @return database_schema[]
     */
    public static function list_tables(): array {
        global $CFG;

        $plugins = plugins::list(true);
        $plugintables = [];
        foreach ($plugins as $plugin) {
            $tables = self::list_plugin_tables($plugin['component']);
            if ($tables === null) {
                continue;
            }
            $plugintables[] = $tables;
        }

        $corexmlpath = $CFG->libdir . '/db/install.xml';
        $coretables = self::list_tables_from_xml($corexmlpath);
        if ($coretables === null) {
            throw new Exception('Unable to load tables from core moodle /lib/db/install.xml');
        }
        $plugintables[] = $coretables;

        return $plugintables;
    }

    /**
     * Find a specific table.
     * @param string $tablename
     * @return database_schema\table|null
     */
    public static function find_table(string $tablename): ?database_schema\table {
        $result = self::list_tables();

        $foundtable = null;
        foreach ($result as $plugintables) {
            foreach ($plugintables->tables as $table) {
                if ($table->name !== $tablename) {
                    continue;
                }
                $foundtable = $table;
                break;
            }
        }

        return $foundtable;
    }

    /**
     * Converts the XMLDB_TYPE_XXX to human readable string.
     * @param int $type
     * @return string
     */
    public static function field_type_to_string(int $type) {
        return match ($type) {
            XMLDB_TYPE_INCORRECT => 'incorrect',
            XMLDB_TYPE_INTEGER => 'integer',
            XMLDB_TYPE_NUMBER => 'number',
            XMLDB_TYPE_FLOAT => 'float',
            XMLDB_TYPE_CHAR => 'char',
            XMLDB_TYPE_TEXT => 'text',
            XMLDB_TYPE_BINARY => 'binary',
            XMLDB_TYPE_DATETIME => 'datetime',
            XMLDB_TYPE_TIMESTAMP => 'timestamp',
            default => 'unknown',
        };
    }

    /**
     * Converts the XMLDB_KEY_XXX to human readable string.
     * @param int $type
     * @return string
     */
    public static function key_type_to_string(int $type) {
        return match ($type) {
            XMLDB_KEY_INCORRECT => 'incorrect',
            XMLDB_KEY_PRIMARY => 'primary',
            XMLDB_KEY_UNIQUE => 'unique',
            XMLDB_KEY_FOREIGN => 'foreign',
            XMLDB_KEY_CHECK => 'check',
            XMLDB_KEY_FOREIGN_UNIQUE => 'foreign_and_unique',
            default => 'unknown',
        };
    }

    /**
     * Gets the xmldb_structure for a given xmlpath.
     * @param string $xmlpath
     * @return xmldb_structure
     */
    public static function get_xmldb_structure(string $xmlpath): xmldb_structure {
        global $CFG;

        if (!file_exists($xmlpath)) {
            throw new Exception("XMLDB file not found: $xmlpath");
        }

        $xml = new xmldb_file($xmlpath);
        $xml->setDTD("$CFG->libdir/xmldb/xmldb.dtd");
        $xml->setSchema("$CFG->libdir/xmldb/xmldb.xsd");

        if (!$xml->loadXMLStructure()) {
            throw new Exception("Failed to load XMLDB structure from: $xmlpath");
        }

        $structure = $xml->getStructure();
        if (!$structure) {
            throw new Exception("Failed to retrieve XMLDB structure from: $xmlpath");
        }

        return $structure;
    }
}
