# Lint Framework

Extensible lint system. Runs external tools and parses output.

## 10 Linters

| Linter | Tool | Files |
|--------|------|-------|
| phplint | `php -l` | `*.php` |
| phpcs | `phpcs --report=json` | `*.php` |
| phpstan | `phpstan analyze` | `*.php` (+ stubs) |
| eslint | `bunx eslint --format json` | `*.js` (excludes `amd/build/`, `yui/build/`) |
| stylelint | `bunx stylelint --formatter json` | `*.css`, `*.scss` |
| phpdoc | `local_moodlecheck` | `*.php` |
| lang | Custom PHP logic | `**/lang/*/*.php` |
| mustachelint | Custom PHP logic | `*.mustache` |
| jsdoc | Custom PHP logic | `**/amd/src/*.js`, `**/js/esm/src/*.ts`, `**/js/esm/src/*.tsx` |
| xmldb | `xmldb_file` | `*/install.xml` |

## Configuration

Linters can be configured per-linter via the admin UI (Development > DevKit > Linter Configuration) or programmatically. See [Configuration docs](configuration.md).

Each linter supports:
- **Status**: enable/disable.
- **Include patterns**: glob patterns to narrow which files to lint.
- **Exclude patterns**: glob patterns to skip (defaults: `.git/`, `node_modules/`, `vendor/`).
- **Per-linter options**: e.g. PHPStan rule level (0–10) and result cache mode (normal / per-component), PHPCS excluded sniffs.

## Output Formats

| Format | Description |
|--------|-------------|
| `text` | Human-readable: `file:line:col: severity: message (source/rule)` |
| `json` | All results as one JSON object `{linters, files}` |
| `jsonl` | One JSON line per issue |
| `github` | GitHub Actions workflow commands (`::error`, `::warning`, `::notice`) with file, line, column, and title — for inline PR annotations |

All formats support `--relative` to output paths relative to the Moodle root directory.

The `--displaycomponent` flag includes a `"component"` key in JSON/JSONL output (set to `null` when unresolved) and prepends `[componentname]` to text output lines (omitted when unresolved). Component resolution uses prefix matching against known plugin directories, sorted longest-first.

## Lang Linter

Validates language string consistency:

- `en` locale must exist for each component.
- Every string in `en` must exist in all other locales.
- No orphaned strings in translations.
- `{$a}` and `{$a->key}` placeholders match between `en` and translations.
- Issues include line numbers pointing to the relevant string definition.

## Mustachelint Linter

Validates mustache templates against Moodle conventions:

- **GPL license**: each template must include the standard GPL header as a mustache comment.
- **Documentation comment**: requires a `@template component/templatename` doc comment.
- **Template name validation**: declared `@template` must match the actual file path; names must be lowercase.
- **Example context**: expects JSON example context and renders the template via `$OUTPUT->render_from_template()` to verify it produces non-empty output.

Known limitations:
- Core plugins (e.g. `public/lib/`) are not lintable since they lack a standard component directory structure.
- Theme-overridden templates may produce false positives for `template-name-incorrect`.

## Jsdoc Linter

Validates JS/TS files in `amd/src/` and `js/esm/src/` for required boilerplate:

- **missing-boilerplate**: each file must include the standard GPL license header as `//` comments.
- **missing-docblock**: each file must have a JSDoc docblock with `@module`, `@copyright`, and `@license` tags.
- **module-name-incorrect**: the `@module` value must match the component and path (e.g. `local_devkit/linter_config`).
- **missing-copyright** / **missing-license**: the docblock must include these tags.

## PHPStan Stubs

PHPStan stubs provide type information for Moodle APIs that lack proper type declarations. Stubs are located in `phpstan/stubs/` and mirror Moodle's directory structure.

Stubs are automatically discovered by the PHPStan linter and added to the config's `stubFiles` parameter.

See `phpstan/README.md` for structure and adding stubs.

## Xmldb Linter

Validates Moodle XML database files (`db/install.xml`):

- **xml-structure-must-be-valid**: validates XML structure against the XMDB DTD/XSD.
- **format-must-match-canonical**: checks formatting matches the canonical XMDB output. Use the XMDB editor to reformat.

## Issue Schema

Each issue: `line`, `column`, `message`, `rule`, `source`, `severity` (`info`|`warning`|`error`|`unknown`).

## Usage

```bash
./devkit lint path/to/code
./devkit lint:phpcs path/to/code --format=json
./devkit lint path/to/code --relative
./devkit lint path/to/code --displaycomponent
./devkit lint path/to/code --format=github
```

See [CLI docs](cli.md).
