# Debug Bar

PHP Debug Bar ([php-debugbar/php-debugbar](https://php-debugbar.com/) v3.7). Shows at bottom of every page.

## Collectors

| Tab | Data |
|-----|------|
| PHP | PHP version, extensions |
| Messages | Custom log messages |
| Request | `$_GET`, `$_POST`, `$_SERVER`, `$_COOKIE`, `$_SESSION` |
| Timeline | Execution time measurements |
| Memory | Memory usage stats |
| Exceptions | Caught exceptions + PHP errors |
| Config | `$CFG` (secrets/passwords excluded) |
| Moodle | Moodle version, PHP version, debug mode, language |
| String Manager | `get_string()` calls (only when custom string manager enabled) |
| Database | SQL query log (if enabled in settings) |

## Logging

```php
\local_devkit\local\debugbar::log('message');
\local_devkit\local\debugbar::log('error happened', \local_devkit\local\log_level::ERROR);
```

Levels: `alert`, `critical`, `debug`, `emergency`, `error`, `info`, `notice`, `success`, `warning`.

## Measuring

```php
$result = \local_devkit\local\debugbar::measure('slow operation', function () {
    // Do stuff.
    return $output;
});
```

Shows in Timeline + Messages tabs.

## Exceptions

```php
try {
    // Risky code.
} catch (\Throwable $e) {
    \local_devkit\local\debugbar::log_exception($e);
}
```

Also auto-catches all PHP errors/exceptions via custom handlers.

## Editor Links

File paths in debug bar are clickable. Choose editor in settings.

23 editors: sublime, textmate, emacs, macvim, codelite, phpstorm, phpstorm-remote, idea, idea-remote, vscode, vscode-insiders, vscode-remote, vscode-insiders-remote, vscodium, nova, xdebug, atom, espresso, netbeans, cursor, cursor-remote, windsurf, zed, antigravity.

## Redirect Stacking

Debug data persists across redirects via SQLite at `$CFG->tempdir/local_devkit/debugbar.sqlite`. Prunes to 1 dataset.

URL: `/local/devkit/debugbar/open.php` (requires login).

## AJAX

Debug bar binds to `fetch` and `XHR` requests. Shows AJAX debug tab.

See [Configuration](configuration.md) for AJAX setup.
