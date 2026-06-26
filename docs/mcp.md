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
| `db_show_tables` | Show database tables, all or for a specific plugin | `component` (string, optional, e.g. `mod_assign`) |
| `db_get_table` | Get fields, indexes and keys of a specific table | `tablename` (string, e.g. `forum`) |
| `lint_files` | Run linters on paths | `paths` (string[]) |

## In This Plugin

These same MCP tools back the [CLI commands](cli.md) and are also used by the [internal API](development.md).
