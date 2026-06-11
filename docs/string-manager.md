# String Manager

Custom string manager that logs every `get_string()` call.

## Enable

Add to `config.php`:

```php
$CFG->customstringmanager = '\local_devkit\local\string_manager';
```

## What It Does

Extends `core_string_manager_standard`. Every `get_string()` call gets logged to the `string_manager_collector` in the debug bar.

Shows:
- String identifier
- Component
- Arguments
- File/line where called

## Why

Find unused strings. Debug slow string loading. Understand what Moodle loads on each page.

## Disable

Remove the `$CFG->customstringmanager` line from `config.php`.
