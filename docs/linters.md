# Lint Framework

Extensible lint system. Runs external tools and parses output.

## 7 Linters

| Linter | Tool | Files |
|--------|------|-------|
| phplint | `php -l` | `*.php` |
| phpcs | `phpcs --report=json` | `*.php` |
| phpstan | `phpstan analyze` | `*.php` |
| eslint | `bunx eslint --format json` | `*.js` (excludes `amd/build/`, `yui/build/`) |
| stylelint | `bunx stylelint --formatter json` | `*.css`, `*.scss` |
| phpdoc | `local_moodlecheck` | `*.php` |
| lang | Custom PHP logic | `**/lang/*/*.php` |

## Configuration

Linters can be configured per-linter via the admin UI (Development > DevKit > Linter Configuration) or programmatically. See [Configuration docs](configuration.md).

Each linter supports:
- **Status**: enable/disable.
- **Include patterns**: glob patterns to narrow which files to lint.
- **Exclude patterns**: glob patterns to skip (defaults: `.git/`, `node_modules/`, `vendor/`).
- **Per-linter options**: e.g. PHPStan rule level (0–10), PHPCS excluded sniffs.

## Output Formats

| Format | Description |
|--------|-------------|
| `text` | Human-readable: `file:line:col: severity: message (source/rule)` |
| `json` | All results as one JSON object `{linters, files}` |
| `jsonl` | One JSON line per issue |

All formats support `--relative` to output paths relative to the Moodle root directory.

## Lang Linter

Validates language string consistency:

- `en` locale must exist for each component.
- Every string in `en` must exist in all other locales.
- No orphaned strings in translations.
- `{$a}` and `{$a->key}` placeholders match between `en` and translations.
- Issues include line numbers pointing to the relevant string definition.

## Issue Schema

Each issue: `line`, `column`, `message`, `rule`, `source`, `severity` (`info`|`warning`|`error`|`unknown`).

## Usage

```bash
./devkit lint path/to/code
./devkit lint:phpcs path/to/code --format=json
./devkit lint path/to/code --relative
```

See [CLI docs](cli.md).
