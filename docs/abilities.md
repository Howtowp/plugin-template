# Abilities (Abilities API, WordPress 6.9+)

The Bluwp skeleton does **not** ship a sample ability. Add one only when a product needs it. Register the category on `wp_abilities_api_categories_init` and the ability on `wp_abilities_api_init`, from code that runs on `htw_pt_loaded` (Bluwp has booted by then). Skip silently when `function_exists( 'wp_register_ability' )` is false.

There is no `plugin-template/site-info` ability in this template.

## Rules

1. Register the **category** on `wp_abilities_api_categories_init`; register abilities on `wp_abilities_api_init` (a later hook). An unknown category makes registration fail silently.
2. Name = `plugin-slug/ability-name`, lowercase letters, digits and dashes.
3. Always a real `permission_callback`. Describe side effects with `meta.annotations` (`readonly`, `destructive`, `idempotent`).
4. `meta.show_in_rest => true` exposes it at `/wp-json/wp-abilities/v1/…`; `meta.mcp.public => true` lets the (optional, separate) MCP Adapter plugin expose it to agents. Do not bundle the MCP Adapter; declare it with a "Requires Plugins" header only if the plugin truly needs it.
5. Put logic in `Services/`; the ability callback only validates and delegates.
