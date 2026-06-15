# DevKit

> **WARNING: Dev use only!**  
> This plugin is for dev environments. Do NOT use in production.  
> Leaks data. Slows things down. You've been warned.

## What This

Collection of tools for Moodle devs. Debug bar, CLI commands, linter framework, MCP server, database tracing, and more.

## Components

| Feature | Description |
|---------|-------------|
| [Debug Bar](debugbar.md) | PHP Debug Bar with 9 collectors. Log messages, measure timings, see DB queries, track events. |
| [CLI Commands](cli.md) | Symfony Console app. Lint, list plugins, inspect DB schema, start MCP server. |
| [Lint Framework](linters.md) | 7 linters. PHPCS, PHPStan, ESLint, Stylelint, PHPDoc, PHP syntax, lang strings. |
| [MCP Server](mcp.md) | Model Context Protocol server for AI coding agents. |
| [Database Tracing](database.md) | PDO-based query tracing with backtraces. |
| [String Manager](string-manager.md) | Log all `get_string()` calls to debug bar. |
| [Hooks & Events](hooks-events.md) | Moodle 4.5+ hooks + wildcard event observer. |

## Setup

1. [Installation](installation.md)
2. [Configuration](configuration.md)

## For Devs

- [Extending](development.md) - New collectors, linters, MCP tools, tests
