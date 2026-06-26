# Installation

## Requirements

- Moodle 4.5+ (2024100700)
- PHP 8.3+
- Composer dependencies installed

## Install

1. Copy `local/devkit/` to your Moodle `local/` directory.
2. Run `composer install` in `local/devkit/` to install deps.
3. Visit Site admin > Notifications to install.
4. Enable in Settings > Development > DevKit.

## Kill Switch

Set env var to disable the plugin without uninstalling:

```bash
export MDL_LOCAL_DEVKIT_DISABLE=1
```

Plugin also auto-disables during PHPUnit runs.

## Supported Moodle Branches

Tested against 4.05, 5.00, 5.01, 5.02 (see CI config).
