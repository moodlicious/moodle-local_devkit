# Configuration

## Admin Settings

Site admin > Development > DevKit:

| Setting | Key | Type | Description |
|---------|-----|------|-------------|
| Debug Bar Enabled | `debugbar_enabled` | checkbox | Show debug bar at page bottom. |
| Collect Database Queries | `debugbar_collect_queries` | checkbox | Show DB queries in debug bar. |
| Editor | `debugbar_editor` | select | Code editor for file links in debug bar (23 editors). |
| Linter Configuration | `local_devkit/linter_config` | table | Per-linter settings via dynamic modal form. |

## Linter Configuration

Each linter can be configured individually via Site administration > Development > DevKit > Linter Configuration. Settings are stored in the `config_plugins` table and include:

- **Status**: enable/disable each linter independently.
- **Include patterns**: glob patterns for files the linter should process (disabled by default — uses linter's built-in defaults).
- **Exclude patterns**: glob patterns to skip (disabled by default — uses built-in defaults like `.git/`, `node_modules/`, `vendor/`).
- **Per-linter extras**:
  - `phpcs`: excluded sniffs list.
  - `phpstan`: rule level (0–10, default 8), result cache mode (`per_component` default or `normal`).

## AJAX Support

Edit `/lib/ajax/service.php`. Add this line before `echo`:

```php
\local_devkit\local\debugbar::instance()->sendDataInHeaders();
```

## Custom String Manager

Add to `config.php`:

```php
$CFG->customstringmanager = '\local_devkit\local\string_manager';
```

See [String Manager](string-manager.md) for details.
