# Database Tracing

PDO-based query tracing. Wraps Moodle's `$DB` to log every query.

## How It Works

1. `after_config` hook calls `helper::wrap_database($DB)`.
2. Detects DB driver (MySQL/MariaDB only currently).
3. Wraps driver in devkit version that creates `TraceablePDO`.
4. Every `query_start()`/`query_end()` creates traced statements.
5. `begin_transaction()`, `commit_transaction()`, `rollback_transaction()` also mirrored.

## Backtraces

Each SQL query gets a backtrace showing WHERE it was called from. Internal Moodle DB classes filtered out.

## Supported Drivers

| Driver | Status |
|--------|--------|
| MySQL (`mysqli_native`) | Full support |
| MariaDB (`mariadb_native`) | Full support |
| PostgreSQL | Pass-through (no tracing) |
| Others | Pass-through |

## Getting PDO

```php
$pdo = \local_devkit\local\databases\helper::get_pdo($DB);
```

Returns `TraceablePDO` or null.

## Enable

Enabled by default when debug bar is on and query collection is enabled in settings.

See [Configuration](configuration.md) for settings.
