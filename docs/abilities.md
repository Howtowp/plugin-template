# Abilities (Abilities API, WordPress 6.9+)

Abilities make plugin features discoverable and callable by AI agents and tools, with JSON-Schema input/output and a permission check. They are registered in `src/Abilities/Abilities.php` and are skipped silently on WordPress < 6.9 (`function_exists( 'wp_register_ability' )`).

| Name | Category | Read-only | Permission | Description |
| --- | --- | --- | --- | --- |
| `plugin-template/site-info` | `plugin-template` | yes | `manage_options` | Site name, WordPress/PHP/plugin versions. |

## Rules

1. Register the **category** on `wp_abilities_api_categories_init`; register abilities on `wp_abilities_api_init` (a later hook). An unknown category makes registration fail silently.
2. Name = `plugin-slug/ability-name`, lowercase letters, digits and dashes.
3. Always a real `permission_callback`. Describe side effects with `meta.annotations` (`readonly`, `destructive`, `idempotent`).
4. `meta.show_in_rest => true` exposes it at `/wp-json/wp-abilities/v1/…`; `meta.mcp.public => true` lets the (optional, separate) MCP Adapter plugin expose it to agents. Do not bundle the MCP Adapter; declare it with a "Requires Plugins" header only if the plugin truly needs it.
5. Put logic in `Services/`; the ability callback only validates and delegates.
