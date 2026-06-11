# Hooks & Events

## Moodle 4.5+ Hooks

Three hooks registered in `db/hooks.php`. All gated by `devkit::is_enabled() && debugbar::is_enabled()`.

| Hook | Callback | What it does |
|------|----------|-------------|
| `core\hook\after_config` | `hook_callbacks::after_config()` | Loads Composer autoloader. Wraps `$DB` with PDO tracing. Adds PDO to debug bar database collector. |
| `core\hook\output\before_standard_head_html_generation` | `hook_callbacks::before_standard_head_html_generation()` | Injects debug bar CSS/JS in `<head>`. |
| `core\hook\output\before_footer_html_generation` | `hook_callbacks::before_footer_html_generation()` | Injects debug bar HTML at page bottom. |

## Event Observer

Wildcard observer registered in `db/events.php`. Catches EVERY Moodle event.

```php
$observers = [
    [
        'eventname' => '*',
        'callback' => '\local_devkit\local\observer::observe_all_events',
        'priority' => 999999,
    ],
];
```

Logs each event to debug bar Timeline tab: event name, class, description, data, context, URL.

Handy for seeing what events fire during any action.
