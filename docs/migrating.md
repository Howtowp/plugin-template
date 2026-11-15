# Migrating a plugin onto this starter

Use this when an existing How to WP plugin should adopt the Bluwp admin and the layout in `docs/architecture.md`. Read `AGENTS.md` and `docs/design-system.md` first. Do not copy payment-gateway code into the template or into an unrelated plugin.

## Checklist

1. **Branch from this template** (new plugin) or copy only the skeleton files you still lack (existing plugin). Run `./scripts/init.sh <slug> "<Plugin Name>" <Namespace> <prefix>` for a new repo so the slug, `HowToWP\<Namespace>`, and `htw_<prefix>_` prefixes move together.
2. **Main file.** Header: `Requires PHP: 8.1`, `declare(strict_types=1);`. Do **not** set `Requires Plugins: bluwp`. Add `Requires Plugins: woocommerce` only when the plugin really needs WooCommerce. Define `HTW_*_FILE` as `__FILE__`, plus version, slug, Bluwp minimum, and one capability constant.
3. **No hardcoded main filename.** WooCommerce `declare_compatibility` and `PluginBootstrap::register` receive the file constant. Delete stale names such as `forouzandea-smart-gateway.php`. `plugins_url()` is not a substitute: HPOS compatibility is keyed by the real plugin file.
4. **One capability.** The admin page, the menu, and every REST route that serves that page use the same constant (`HTW_PT_CAPABILITY` here). A screen limited to `manage_options` must not be writable with `manage_woocommerce`. Re-check the nonce (`wp_rest`) in the controller on state-changing routes.
5. **Folders.** `bootstrap/app.php` (autoload, features, REST namespace, asset handle), `pages/`, `routes/api.php`, `app/Controllers`, `app/Schemas`, `src/` for domain code, `hooks/early/` for boot only, `crons/`, `database/migrations/`. Domain rules do not live in the page registry.
6. **Admin UI.** Replace hand-built settings HTML with a form or table schema (`type`, `fields` or `columns`, `submit.endpoint`). Mount options stay `locale: 'fa-IR'`, `direction: 'rtl'`, `navigationPlacement: 'header'`. Mark URL and number fields `direction: ltr`.
7. **Replace the Vue admin.** Delete `development/build-admin.mjs` and any sibling `bluwp-vuejs` build. Copy `admin/` from this template (or keep using it), run `npm run build`, and enqueue `assets/admin` only on the plugin's own screen. Map `--el-*` tokens to the `--htw-*` table in `docs/design-system.md`. Do not ship IranSans unless it is licensed; Vazirmatn is the default face.
8. **Embed Bluwp.** `./scripts/update-bluwp.sh <bluwp-tree-or-tgz>` writes `lib/bluwp`. Require `lib/bluwp/bridge.php` and call `BluwpBridgeV1::consume( $file, $slug, $title, $minimum, $embedded_dir )`. Delete the old “Bluwp is missing, stop” branch that called `PluginBootstrap::register` only when a standalone plugin was active. The bridge loads the embedded copy unless a standalone provider is already active. Pin `>= 0.2.1 < 0.3.0` (`HTW_*_BLUWP_MIN` / `HTW_*_BLUWP_BELOW`). `php scripts/check-catalog.php` checks `bridge.php` and `metadata.php`.
9. **Strings.** Put Persian UI copy in `src/Text.php` (backed enum). Customer-facing cases can be overridden in an option; admin cases cannot. Keep gettext only for the pre-boot environment notice if you still need it. Escape at output.
10. **Catalog honesty.** `Plugin-Version` in `AI-GUIDE.md` equals the plugin header. `product.json` has no `version` key (the schema forbids it). Hooks in the guide exist in PHP. `readme.txt` Stable tag matches the header. `php scripts/check-catalog.php` must pass.
11. **Leave product code behind.** Do not import routing, drivers, relay, or Mana from «درگاه پرداخت هوشمند». Do not copy its bugs: hardcoded plugin filename, REST capability wider than the screen, a second checkout palette treated as the admin system.
12. **Directory guards.** `index.php` (“Silence is golden”) in every folder Bluwp does not glob. `crons/` and `database/migrations/` are globbed: use `index.html` plus `.htaccess`, not `index.php`, or Bluwp will try to load the guard as a cron or migration.
13. **Verify.** `php -l` on PHP 8.1, `composer lint && composer phpcs && composer phpstan && composer test`, and `npm run build:strict` when the kit checkout is available.

## From the required-plugin model (wc-iranian-smart-gateways)

That plugin requires standalone Bluwp and calls `PluginBootstrap::register` from `hooks/early/woocommerce.php` after a version check. To sell it as one install:

1. Vendor Bluwp 0.2.1 with `./scripts/update-bluwp.sh` into `lib/bluwp`. Keep `bridge.php` and `metadata.php`.
2. In the main file, after the PHP/WordPress gate: `require lib/bluwp/bridge.php`, then `BluwpBridgeV1::consume( REAL_MAIN_FILE, slug, title, '0.2.1', __DIR__ . '/lib/bluwp' )`. Pass the real main file (`__FILE__`), not a leftover name such as `forouzandea-smart-gateway.php`.
3. Remove `bluwp` from `Requires Plugins`. Leave `woocommerce` if the product is a WooCommerce gateway.
4. Delete the early return that aborted when `$GLOBALS['bluwp_framework_loaded']` was empty. The bridge fills that global from the embedded copy, or from a standalone Bluwp plugin when one is active.
5. Do not call `PluginBootstrap::register` yourself. `consume()` registers activation and deactivation (migrations and cron cleanup) and attaches the plugin on `plugins_loaded` priority -1000. Keep your own activation hook if you need a product action, and register it after `consume()` so Bluwp's hook runs first.
6. Sites that already have standalone Bluwp 0.2.x keep using it. The embedded copy is the fallback, not a second loaded framework. A standalone 0.3.x fails the compatibility line on purpose.
7. Leave payment code in the product. Do not copy it into this template.
8. Point the admin at `admin/` instead of `bluwp-vuejs/apps/<slug>`. The PHP schema can stay; the React app reads `GET .../admin/settings/schema` and posts to the schema's `submit.endpoint` with `wp.apiFetch` (the `wp_rest` nonce). Rebuild `assets/admin` and drop the compiled Vue `style.css` / `script.js`.

## Starting a new plugin (short)

```bash
# GitHub: Use this template → new repository
git clone git@github.com:howtowp/my-plugin.git && cd my-plugin
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp
cd my-plugin && composer install && composer test
php ../scripts/check-catalog.php
```

Install Bluwp `>= 0.2.1 < 0.3` on the site. Build admin assets when you are ready to open the settings screen (`docs/design-system.md`). Then replace the sample settings fields, the sample cron, and the sample migration with the product’s own.
