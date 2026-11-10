# Hooks reference

All hooks use the `htw_pt_` prefix. Keep this list in sync when adding hooks.

| Hook | Type | Arguments | Purpose |
| --- | --- | --- | --- |
| `htw_pt_modules` | filter | `array $classes` | Add, remove or replace module classes before they boot. |
| `htw_pt_before_boot` | action | — | Fires before modules register. |
| `htw_pt_loaded` | action | `Plugin $plugin` | Fires after all modules registered. |

## Shortcode

`[htw_pt_hello name="…"]` — renders `templates/hello.php` (theme override: `{theme}/plugin-template/hello.php`).

## REST

`GET /wp-json/plugin-template/v1/status` — administrators only.
