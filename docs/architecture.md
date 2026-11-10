# Architecture

- `plugin-template.php` — headers, constants, environment check, autoloader (Composer, or the built-in PSR-4 fallback), activation hooks, boot on `plugins_loaded`.
- `Plugin` — composition root: builds the module list (`htw_pt_modules` filter) and calls `register()` on each.
- Modules (`Core\Module`): `Core\I18n`, `Core\Upgrader`, `Admin\SettingsPage`, `Frontend\{Assets,Shortcode,Blocks}`, `Rest\StatusController`, `Abilities\Abilities`, `AI\AiFeatures`, `Integrations\WooCommerce`.
- `Services\*` — business logic, no output. `Repositories\*` — options/DB access. `Support\*` — helpers (Logger, Template).
- Data: options `htw_pt_settings`, `htw_pt_schema_version`; migrations via `Core\Upgrader`.

Record significant decisions in `docs/decisions/` (one short ADR per file).
