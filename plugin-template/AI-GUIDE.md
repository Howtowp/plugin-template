# AI-GUIDE — Plugin Template

## معرفی کوتاه (فارسی)

این فایل راهنمای **مشتری** و وایب‌کدرهایی است که می‌خواهند این افزونه را با Cursor، Claude یا ابزار مشابه گسترش بدهند. فایل داخل zip محصول منتشر می‌شود.

این سند جایگزین `AGENTS.md` نیست. `AGENTS.md` برای توسعهٔ داخلی خود افزونه است. هستهٔ افزونه را ویرایش نکنید؛ یک **companion plugin** بسازید و فقط از hookها و مسیرهای REST که پایین فهرست شده‌اند استفاده کنید.

ادامهٔ این سند به انگلیسی است تا مدل‌های زبانی آن را دقیق‌تر دنبال کنند.

---

Plugin-Version: 0.4.0

## Who this is for

Customers and vibe coders extending **Plugin Template** with an AI coding agent. Read this file first, then inspect the source in the plugin folder. Do not invent hooks, REST routes, or admin pages that are not listed here or present in the code.

After `scripts/init.sh` the folder, namespace, and prefixes below are renamed. Use the names you see in *this* copy of the plugin.

## Architecture and key files

Admin screens are JSON schemas rendered by a React app (VibeFarsi components, Tailwind) built into `assets/admin`. React comes from WordPress (`wp-element`), not from this plugin. Bluwp itself ships inside this plugin at `lib/bluwp` (customers do not install a second plugin). Its PHP namespace is `Howtowp\Bluwp`. If the site already has the standalone Bluwp plugin active, that copy is used instead, as long as it is on the same 0.3 line (`>= 0.3.0` and `< 0.4.0`). The screen direction follows the site locale (`is_rtl()`). LTR uses a Latin system font. RTL uses Vazirmatn (`HowToWP Sans`). IranSans is not bundled.

- `plugin-template.php` — headers, constants (`HTW_PT_FILE`, `HTW_PT_VERSION`, `HTW_PT_SLUG`, `HTW_PT_CAPABILITY`), then `BluwpBridgeV1::consume()` for the embedded Bluwp. The main filename is always `HTW_PT_FILE` (`__FILE__`). Do not hardcode it. Do not add `Requires Plugins: bluwp`.
- `lib/bluwp/` — embedded Bluwp. Do not edit it. A newer copy is installed by the product author with `scripts/update-bluwp.sh`.
- `bootstrap/app.php` — Bluwp app name, autoload, REST namespace `plugin-template/v1`, admin asset handle.
- `hooks/early/woocommerce.php` — declares WooCommerce HPOS and checkout-blocks compatibility with `HTW_PT_FILE`. `htw_pt_loaded` fires on `init` priority 2, after Bluwp boots the plugin.
- `pages/admin.php` — one settings screen. Capability is `HTW_PT_CAPABILITY` (`manage_options`).
- `routes/api.php` — REST map. Settings routes use the same `HTW_PT_CAPABILITY`.
- `app/Controllers/SettingsController.php` — checks that capability again and a `wp_rest` nonce.
- `app/Schemas/SettingsSchema.php` — the form the kit renders. The URL field sets `direction` to `ltr`.
- `src/Text.php` — English source strings. `catalog()` wraps each one in `__()`. Customer text can still be overridden in the option `htw_pt_texts`. Persian ships in `languages/plugin-template-fa_IR.po`.
- `crons/sample.php` — sample schedule that fires `htw_pt_sample_cron`.
- `database/migrations/001_create_notes.php` — example table `htw_pt_notes` (WordPress prefix + that name).

A compatible Bluwp (embedded, or a standalone plugin on the 0.3 line, namespace `Howtowp\Bluwp`) must load. Otherwise the plugin shows an admin notice and does not boot.

## Hooks (real)

| Hook | Type | Arguments | Purpose |
| --- | --- | --- | --- |
| `htw_pt_activated` | action | — | Plugin activation, after the PHP and WordPress version gate. |
| `htw_pt_deactivated` | action | — | Plugin deactivation. |
| `htw_pt_loaded` | action | — | On `init` priority 2, after Bluwp has booted this plugin. |
| `htw_pt_settings_schema` | filter | `array $schema` | Change the settings form schema before it is sent to the admin kit. |
| `htw_pt_settings` | filter | `array $settings` | Change sanitized settings immediately before they are saved. |
| `htw_pt_sample_cron` | action | — | Fired by the sample cron in `crons/sample.php`. |

Options: `htw_pt_settings` (the form) and `htw_pt_texts` (customer-string overrides). Deleting the plugin removes them, and drops `htw_pt_notes`, only when “delete data” was enabled.

## REST

Namespace: `plugin-template/v1`. Both routes require `HTW_PT_CAPABILITY` and a valid `wp_rest` nonce (`X-WP-Nonce` or `_wpnonce`).

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/wp-json/plugin-template/v1/admin/settings/schema` | Settings form schema. |
| POST | `/wp-json/plugin-template/v1/admin/settings` | Save the form. JSON body. `422` with field messages when validation fails. |

The admin shell also calls Bluwp’s own pages endpoint. That route is Bluwp’s, not this plugin’s.

## Conventions

- Prefixes: `htw_pt_` (options, hooks, tables), `HTW_PT_` (constants), `htw-pt-` (CSS handles), namespace `HowToWP\PluginTemplate`.
- User-facing copy is English inside `__()`, `esc_html__()`, or `@wordpress/i18n`, with the text domain equal to the plugin slug. Escape at output. Add a `Text` case when the string is shared or can be overridden, and put the Persian in `languages/`.
- The settings page capability and the settings REST routes are the same constant, `HTW_PT_CAPABILITY`. Do not give the route a wider capability than the screen.
- Pass `HTW_PT_FILE` into WooCommerce and Bluwp. Never paste an old main filename.
- Nonce and capability on every state change. Do not store secrets in the sample settings.
- **Do not edit this plugin's core files to add a feature.** Use a companion plugin and the hooks above.

## Ready-made prompts

1. **Add a settings field** — “Create a companion plugin that adds one field to the settings form using only `htw_pt_settings_schema` and `htw_pt_settings`. If the saved value is not in the sanitized array those hooks receive, say so and stop.”
2. **Run something on the sample schedule** — “Hook `htw_pt_sample_cron` from a companion plugin. Do not edit `crons/sample.php`.”
3. **Read the schema** — “Call `GET /wp-json/plugin-template/v1/admin/settings/schema` as an administrator with the `wp_rest` nonce. Do not add a new route.”

When a prompt would require an undocumented hook, stop and tell the user instead of inventing one.
