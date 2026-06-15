# CLI Commands

Entry point:

```bash
php local/devkit/cli/run.php
```

Uses Symfony Console (v7.x).

## Commands

### `plugins:list`

List all installed plugins.

```bash
php local/devkit/cli/run.php plugins:list
php local/devkit/cli/run.php plugins:list --json
php local/devkit/cli/run.php plugins:list --include-standard
```

Options: `--json`, `--include-standard`.

### `database:list <component>`

List database tables for a plugin.

```bash
php local/devkit/cli/run.php database:list mod_forum
php local/devkit/cli/run.php database:list mod_assign --format=json
```

Options: `component` (required), `--format` (`table`|`json`).

### `database:format <component>`

Format the `db/install.xml` file for a plugin.

```bash
php local/devkit/cli/run.php database:format mod_forum
```

Options: `component` (required).

### `lint`

Run all linters on paths.

```bash
php local/devkit/cli/run.php lint local/devkit/
php local/devkit/cli/run.php lint mod/assign/ --format=json
php local/devkit/cli/run.php lint local/devkit/ --linters=phpcs --linters=phplint
```

Options: `paths` (array arg), `--format` (`text`|`json`|`jsonl`), `--decorate`/`--no-decorate`, `--progress`/`--no-progress`, `--linters` (filter).

### `lint:phpcs`

Moodle coding standards only.

### `lint:phplint`

PHP syntax check only.

### `lint:phpstan`

Static analysis (level 8) only.

### `lint:eslint`

JS linting only.

### `lint:stylelint`

CSS/SCSS linting only.

### `lint:phpdoc`

PHPDoc checking (uses `local_moodlecheck`).

### `lint:lang`

Language string consistency check only.

### `mcp:serve`

Start MCP server (stdio). See [MCP docs](mcp.md).
