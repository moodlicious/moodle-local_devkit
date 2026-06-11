# Lint Framework

Extensible lint system. Runs external tools and parses output.

## 7 Linters

| Linter | Tool | Files | Config key |
|--------|------|-------|------------|
| phplint | `php -l` | `*.php` | `phplint` |
| phpcs | `phpcs --report=json` | `*.php` | `phpcs` |
| phpstan | `phpstan analyze` | `*.php` | `phpstan` |
| eslint | `bunx eslint --format json` | `*.js` (excludes `amd/build/`, `yui/build/`) | `eslint` |
| stylelint | `bunx stylelint --formatter json` | `*.css`, `*.scss` | `stylelint` |
| phpdoc | `local_moodlecheck` | `*.php` | `phpdoc` |
| lang | Custom PHP logic | `**/lang/*/*.php` | `lang` |

## Exclude Patterns

Default excludes: `.git/`, `node_modules/`, `vendor/`.

Override via `$CFG->devkit`:

```php
$CFG->devkit = [
    'linters' => [
        'base' => ['exclude_patterns' => ['*/.venv/*']],
        'phpcs' => ['exclude_patterns' => ['*/classes/*']],
    ],
];
```

## Output Formats

| Format | Description |
|--------|-------------|
| `text` | Human-readable: `file:line:col: severity: message (source/rule)` |
| `json` | All results as one JSON object `{linters, files}` |
| `jsonl` | One JSON line per issue |

## Lang Linter

Validates language string consistency:

- `en` locale must exist for each component.
- Every string in `en` must exist in all other locales.
- No orphaned strings in translations.
- `{$a}` and `{$a->key}` placeholders match between `en` and translations.

## Issue Schema

Each issue: `line`, `column`, `message`, `rule`, `source`, `severity` (`info`|`warning`|`error`|`unknown`).

## Usage

```bash
php local/devkit/cli/run.php lint path/to/code
php local/devkit/cli/run.php lint:phpcs path/to/code --format=json
```

See [CLI docs](cli.md).
