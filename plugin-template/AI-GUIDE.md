# AI-GUIDE — Plugin Template

## معرفی کوتاه (فارسی)

این فایل راهنمای **مشتری** و وایب‌کدرهایی است که می‌خواهند این افزونه را با Cursor، Claude یا ابزار مشابه گسترش بدهند. فایل داخل zip محصول منتشر می‌شود.

این سند جایگزین `AGENTS.md` نیست. `AGENTS.md` (اگر به ریپوی خصوصی دسترسی داشته باشید) برای توسعهٔ داخلی خود افزونه است. شما هستهٔ افزونه را ویرایش نکنید؛ یک **companion plugin** بسازید و از hookها و نقطهٔ گسترش رسمی استفاده کنید تا به‌روزرسانی‌های بعدی کارتان را پاک نکنند.

ادامهٔ این سند به انگلیسی است تا مدل‌های زبانی آن را دقیق‌تر دنبال کنند.

---

## Who this is for

Customers and vibe coders extending **Plugin Template** with an AI coding agent. Read this file first, then inspect the source in the plugin folder. Do not invent hooks, REST routes, or Abilities that are not listed here or present in the code.

## Architecture and key files

After `scripts/init.sh` the folder, namespace, and prefixes below are renamed. Use the names you see in *this* copy of the plugin, never hard-coded `plugin-template` / `PluginTemplate` / `htw_pt_` values from an old memory.

- `plugin-template.php` — bootstrap, constants, hooks the plugin in.
- `src/Plugin.php` — boots modules.
- `src/Admin/SettingsPage.php` — Settings API page.
- `src/Rest/StatusController.php` — REST route.
- `src/Abilities/Abilities.php` — Abilities API (WordPress 6.9+).
- `src/AI/AiFeatures.php` — optional AI Client stub (WordPress 7.0+; no bundled API keys).
- `src/Integrations/WooCommerce.php` — HPOS / checkout-blocks declaration when WooCommerce is present.
- `docs/hooks.md` and `docs/abilities.md` in the *repository* (not always shipped in the zip) — keep this guide in sync with those files when you maintain the product.

## Hooks and filters (real)

All hooks use the `htw_pt_` prefix (renamed by `init.sh`).

| Hook | Type | Arguments | Purpose |
| --- | --- | --- | --- |
| `htw_pt_modules` | filter | `array $classes` | Add, remove or replace module classes before they boot. |
| `htw_pt_before_boot` | action | — | Fires before modules register. |
| `htw_pt_loaded` | action | `Plugin $plugin` | Fires after all modules registered. |

Shortcode: `[htw_pt_hello name="…"]` (theme override: `{theme}/plugin-template/hello.php`).

REST: `GET /wp-json/plugin-template/v1/status` — administrators only.

## Abilities (WordPress 6.9+)

Registered in `src/Abilities/Abilities.php` and skipped silently on WordPress < 6.9.

| Name | Read-only | Permission | Description |
| --- | --- | --- | --- |
| `plugin-template/site-info` | yes | `manage_options` | Site name, WordPress / PHP / plugin versions. |

Do not bundle the MCP Adapter. Follow `docs/abilities.md` if you add new abilities in a companion plugin of your own.

## Conventions

- Prefixes: `htw_pt_` (functions/hooks), `HTW_PT_` (constants), `htw-pt-` (slugs/CSS), namespace `HowToWP\PluginTemplate`.
- Text domain: `plugin-template`. All user-facing strings go through `__()` / `esc_html__()` / `esc_attr__()`.
- Security: sanitise input, escape output, use nonces and real capability checks. Never store PHP in the database and `eval` it. Never commit secrets or API keys.
- **Do not edit this plugin's core files to add a feature.** Put customisations in a companion plugin or, for templates, a theme override.

## Ready-made prompts

Copy one of these into Cursor after this plugin is installed (and after your companion plugin exists or is being created):

1. **Add a settings field** — “Create a companion plugin that adds one Settings API field to Plugin Template without editing its core files. Use only documented hooks. Propose the hook or module filter you will use first; if none fits, say so and stop.”
2. **Call the sample Ability** — “Write a small WP-CLI command or admin-ajax-free admin page that calls the `plugin-template/site-info` Ability when WordPress 6.9+ is available, and fails gracefully otherwise.”
3. **Replace the hello template** — “Override `[htw_pt_hello]` by copying the template into the theme override path documented in AI-GUIDE.md. Do not change the plugin zip.”

When a prompt would require an undocumented hook, stop and tell the user instead of inventing one.
