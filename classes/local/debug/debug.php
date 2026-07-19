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

namespace local_devkit\local\debug;

use InvalidArgumentException;
use IteratorAggregate;
use local_devkit\local\api\database;
use Traversable;

/**
 * Utilities for debugging.
 *
 * // phpcs:disable moodle.Commenting.ValidTags
 * @template TKey of array-key
 * @template-covariant TValue
 * @implements IteratorAggregate<TKey, TValue>
 * // phpcs:enable moodle.Commenting.ValidTags
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debug implements IteratorAggregate {
    /**
     * Any data associated with this debug instance.
     * @var array<TKey, TValue>
     */
    private array $payload;

    /**
     * Constructor.
     * @param array<TKey, TValue> $payload
     */
    public function __construct(array $payload) {
        $this->payload = $payload;
    }

    /**
     * Dump payload.
     * @return self<TKey, TValue>
     */
    public function dump(): self {
        return $this->payload_each(function ($item, $key) {
            $this->payload_print_key($key);
            var_dump($item);
        });
    }

    /**
     * Dump payload and die.
     */
    public function dd(): never {
        $this->dump()->die();
    }

    /**
     * Dump payload as json.
     * @return self<TKey, TValue>
     */
    public function json(bool $pretty = true): self {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        return $this->payload_each(function ($item, $key) use ($flags) {
            $this->payload_print_key($key);
            echo json_encode($item, $flags), PHP_EOL;
        });
    }

    /**
     * Dump payload as json and die.
     */
    public function jsond(bool $pretty = true): never {
        $this->json($pretty)->die();
    }

    /**
     * Export payload.
     * @return self<TKey, TValue>
     */
    public function export(): self {
        return $this->payload_each(function ($item, $key) {
            $this->payload_print_key($key);
            var_export($item);
            echo PHP_EOL;
        });
    }

    /**
     * Export payload and die.
     */
    public function exportd(): never {
        $this->export()->die();
    }

    /**
     * Measures execution time in milliseconds.
     * @param int $iterations
     * @return self<TKey, float|null>
     */
    public function measure(int $iterations = 1): self {
        if ($iterations < 1) {
            throw new InvalidArgumentException('Internations must be at least 1.');
        }

        $payload = [];
        $this->payload_each(function ($value, $key) use (&$payload, $iterations) {
            if (!is_callable($value)) {
                $payload[$key] = null;
                return;
            }

            $totalduration = 0;
            foreach (range(0, $iterations - 1) as $i) {
                $start = microtime(true);
                $value();
                $end = microtime(true);
                $totalduration += $end - $start;
            }

            $payload[$key] = (float) $totalduration / $iterations * 1_000;
        });
        return new self($payload);
    }

    /**
     * Gets information about a database table.
     *
     * Examples:
     * - `debug()->table('user', 'notfound', 'config')->dd()`.
     *
     * @return self<string, mixed>
     */
    public function table(string $tablename, string ...$tablenames): self {
        $tablenames = [$tablename, ...$tablenames];
        $tables = [];

        foreach ($tablenames as $name) {
            $tables[$name] = database::find_table($name);
        }

        return new self($tables);
    }

    /**
     * Gets performance information.
     *
     * Examples:
     * - `debug()->performance()->json();`
     * - `debug()->performance('dbqueries')->dump();`
     *
     * @param (
     *     'txt'|'html'|'realtime'|'memory_total'|'memory_growth'|'memory_peak'|'includecount'|
     *     'dbqueries'|'dbreads_replica'|'dbtime'|'serverload'|'sessionsize'|
     *     'cachesused'|'cachesused'|'html'
     * )|null $metric
     * @return self<int, mixed[]|null>
     */
    public function performance(?string $metric = null): self {
        $info = get_performance_info();

        if ($metric !== null) {
            $info = isset($info[$metric]) ? [$metric => $info[$metric]] : null;
            return new self([$info]);
        }

        // We are only interested in the raw numbers.
        unset($info['html']);
        unset($info['txt']);

        return new self([$info]);
    }

    /**
     * Dies.
     */
    public function die(string|int $status = 0): never {
        die($status);
    }

    /**
     * Utility function to loop through each layload.
     * @param callable(mixed,int|string):mixed $callback
     * @return self<TKey, TValue>
     */
    private function payload_each(callable $callback): self {
        foreach ($this as $key => $item) {
            $callback($item, $key);
        }
        return $this;
    }

    /**
     * Utility function print a payload key.
     * @param array-key $key
     * @return self<TKey, TValue>
     */
    private function payload_print_key(int|string $key): self {
        if (is_numeric($key)) {
            return $this;
        }

        echo "$key: ";
        return $this;
    }

    #[\Override]
    public function getIterator(): Traversable {
        yield from $this->payload;
    }
}
