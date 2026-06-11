# Development

## Add New Debug Bar Collector

Create class in `classes/local/debugbar/` extending `DebugBar\DataCollector\DataCollector`. Register it in `debugbar::init_collectors()`.

## Add New Linter

1. Create class in `classes/local/lint/linters/` extending `lint\linters\base`.
2. Add `#[linter(name, description)]` attribute.
3. Implement `get_name()`, `get_description()`, `get_include_patterns()`, `can_lint_file()`, `lint_file()`.

## Add New MCP Tool

1. Create class in `classes/local/mcp/tools/`.
2. Add `#[McpTool(name, description)]` attribute.
3. Register in MCP server tool list.

## Tests

Run PHPUnit tests:

```bash
php admin/tool/phpunit/cli/init.php  # if not done
php vendor/bin/phpunit --testsuite local_devkit_testsuite
```

Test files in `tests/`.

## Build

Create distributable zip:

```bash
bash scripts/build.sh
```

Output: `dist/local_devkit.zip`. Includes: `classes/`, `db/`, `demo/`, `lang/`, `version.php`, `vendor/`, `README.md`.

## CI

GitHub Actions workflow (`.github/workflows/moodle-ci.yaml`) tests:

- PHP 8.3, 8.4
- Moodle 4.05, 5.00, 5.01, 5.02
- MariaDB
- PHP Lint, PHPMD, PHPCS, PHPDoc, Validate, Savepoints, Mustache, Grunt, PHPUnit, Behat
- Builds + uploads zip on push to `main`.

## PHPStan

```bash
phpstan analyze -c phpstan.neon
```

Level 8. Config at `phpstan.neon.dist` (extend in `phpstan.neon` with `rootDirectory` set to Moodle root).

## Code Style

Moodle coding standards enforced by PHPCS linter.
