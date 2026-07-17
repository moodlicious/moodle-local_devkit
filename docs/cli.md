# CLI Commands

## Entry Points

### Long form

```bash
php local/devkit/cli/run.php
```

### Binary (shorthand)

Symlink or copy `bin/devkit` to your Moodle root:

```bash
ln -s public/local/devkit/bin/devkit devkit
./devkit
```

All examples below use `./devkit` — replace with `php local/devkit/cli/run.php` if you prefer the long form.

Uses Symfony Console (v7.x).

## Commands

### `plugins:list`

List all installed plugins.

```bash
./devkit plugins:list
./devkit plugins:list --json
./devkit plugins:list --include-standard
```

Options: `--json`, `--include-standard`.

### `db:show` / `database:show`

List all database tables, or tables for a specific plugin component.

```bash
./devkit db:show
./devkit db:show mod_forum
./devkit database:show mod_assign --format=json
```

Options: `component` (optional), `--format` (`table`|`json`).

### `db:table` / `database:table`

Get detailed info (fields, indexes, keys) for a specific database table.

```bash
./devkit db:table forum
./devkit database:table assign --format=json
```

Options: `tablename` (required), `--format` (`table`|`json`).

### `db:format` / `database:format`

Format the `db/install.xml` file for a plugin.

```bash
./devkit db:format mod_forum
./devkit database:format mod_forum
```

Options: `component` (required).

### `lint`

Run all linters on paths.

```bash
./devkit lint local/devkit/
./devkit lint mod/assign/ --format=json
./devkit lint local/devkit/ --linters=phpcs --linters=phplint
./devkit lint local/devkit/ --relative
./devkit lint local/devkit/ --rules=/moodle\.Commenting/ --rules=no-unused-vars
./devkit lint local/devkit/ --format=github
```

Options: `paths` (array arg), `--format` (`text`|`json`|`jsonl`|`github`), `--decorate`/`--no-decorate`, `--progress`/`--no-progress`, `--relative`/`--no-relative`, `--rules` (case-insensitive substring or /pattern/flags regex), `--linters` (filter).

### `lint:phpcs`

Moodle coding standards only.

### `lint:phplint`

PHP syntax check only.

### `lint:phpstan`

Static analysis (configurable rule level, default 8) only.

### `lint:eslint`

JS linting only.

### `lint:stylelint`

CSS/SCSS linting only.

### `lint:phpdoc`

PHPDoc checking (uses `local_moodlecheck`).

### `lint:lang`

Language string consistency check only.

### `format`

Format code files with an opinionated code-style.

```bash
./devkit format local/devkit/classes/
./devkit format local/devkit/classes/local/cli/commands/format.php
./devkit format mod/assign/ lang/en/
```

Formatting pipeline:

| Extension | Formatters |
|-----------|------------|
| `.php` | Pint → PHPCBF |
| `.css`, `.scss` | Biome → Stylelint |
| `.js`, `.jsx`, `.ts`, `.tsx` | Biome → ESLint |
| `db/install.xml` | XMLDB |

Ignored paths: `.git/`, `amd/build/`, `js/esm/build/`, `node_modules/`, `tests/fixtures/`, `vendor/`.

Options: `paths` (array arg, files or directories).

### `mcp:serve`

Start MCP server (stdio). See [MCP docs](mcp.md).
