# Hooks reference

All hooks use the `htw_pt_` prefix. Keep this list in sync with `plugin-template/AI-GUIDE.md` and the code. `scripts/check-catalog.php` fails if the guide mentions a `htw_pt_*` name the PHP does not contain.

| Hook | Type | Arguments | Purpose |
| --- | --- | --- | --- |
| `htw_pt_activated` | action | — | Fires on activation, after the PHP / WordPress version gate. |
| `htw_pt_deactivated` | action | — | Fires on deactivation. |
| `htw_pt_loaded` | action | — | Fires on `init` priority 2, after Bluwp boots the plugin. |
| `htw_pt_settings_schema` | filter | `array $schema` | Settings form schema before the REST response. |
| `htw_pt_settings` | filter | `array $settings` | Sanitized settings before they are saved. |
| `htw_pt_sample_cron` | action | — | Fired by `crons/sample.php`. Delete that file if the product has no use for it. |

## REST

Namespace `plugin-template/v1` (the slug plus `/v1`).

- `GET /wp-json/plugin-template/v1/admin/settings/schema` — form schema. Capability `HTW_PT_CAPABILITY` and a `wp_rest` nonce.
- `POST /wp-json/plugin-template/v1/admin/settings` — save. Same capability and nonce. Validation errors are HTTP 422.

## Options

- `htw_pt_settings` — sample form (`enabled`, `notice`, `site_url`, `delete_data_on_uninstall`).
- `htw_pt_texts` — overrides for customer-facing `Text` cases.

## Removed in 0.3.0

`htw_pt_modules`, `htw_pt_before_boot`, and the `[htw_pt_hello]` shortcode belonged to the previous PSR-4 sample. They are not registered.
