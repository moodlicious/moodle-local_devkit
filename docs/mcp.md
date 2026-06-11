# MCP Server

Model Context Protocol server for AI coding agents (like Cursor, Claude, etc.).

Uses `mcp/sdk` (v0.4.0).

## Setup

Add to your `mcp.json`:

```json
{
    "mcpServers": {
        "moodle-devkit": {
            "command": "php",
            "args": [
                "/full/path/to/moodle/public/local/devkit/cli/run.php",
                "mcp:serve"
            ]
        }
    }
}
```

Start via CLI:

```bash
php local/devkit/cli/run.php mcp:serve
```

Runs on stdio (bidirectional JSON-RPC).

## Tools

| Tool | Description | Params |
|------|-------------|--------|
| `list_plugins` | List installed plugins | `includestandardplugins` (bool) |
| `list_plugin_tables` | List DB schema for a plugin | `component` (string, e.g. `mod_assign`) |
| `lint_files` | Run linters on paths | `paths` (string[]) |

## In This Plugin

These same MCP tools back the [CLI commands](cli.md) and are also used by the [internal API](development.md).
