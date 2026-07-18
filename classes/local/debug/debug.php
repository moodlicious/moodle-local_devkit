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

/**
 * Utilities for debugging.
 *
 * @package    local_devkit
 * @copyright  2026 Felix
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debug {
    /**
     * Any data associated with this debug instance.
     * @var mixed[]
     */
    private array $payload;

    /**
     * Constructor.
     * @param mixed[] $payload
     */
    public function __construct(array $payload) {
        $this->payload = $payload;
    }

    /**
     * Dump payload.
     */
    public function dump(): self {
        return $this->payload_each(function ($item) {
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
     */
    public function json(bool $pretty = true): self {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        return $this->payload_each(function ($item) use ($flags) {
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
     */
    public function export(): self {
        return $this->payload_each(function ($item) {
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
     * @return self
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

            $payload[$key] = $totalduration / $iterations * 1_000;
        });
        return new self($payload);
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
     */
    private function payload_each(callable $callback): self {
        foreach ($this->payload as $key => $item) {
            $callback($item, $key);
        }
        return $this;
    }
}
