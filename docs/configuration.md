# Configuration

## Admin Settings

Site admin > Plugins > Local plugins > DevKit:

| Setting | Key | Type | Description |
|---------|-----|------|-------------|
| Debug Bar Enabled | `debugbar_enabled` | checkbox | Show debug bar at page bottom. |
| Collect Database Queries | `debugbar_collect_queries` | checkbox | Show DB queries in debug bar. |
| Editor | `debugbar_editor` | select | Code editor for file links in debug bar (23 editors). |

## $CFG Config

Override linter settings in `config.php`:

```php
$CFG->devkit = [
    'linters' => [
        'base' => ['exclude_patterns' => ['*/.venv/*']],
        'phpcs' => ['exclude_patterns' => ['*/classes/*']],
    ],
];
```

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
