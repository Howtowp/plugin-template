# Migrating a plugin onto this starter

Use this when an existing How to WP plugin should adopt the Bluwp admin and the layout in `docs/architecture.md`. Read `AGENTS.md` and `docs/design-system.md` first. Do not copy payment-gateway code into the template or into an unrelated plugin.

## Checklist

1. **Branch from this template** (new plugin) or copy only the skeleton files you still lack (existing plugin). Run `./scripts/init.sh <slug> "<Plugin Name>" <Namespace> <prefix>` for a new repo so the slug, `HowToWP\<Namespace>`, and `htw_<prefix>_` prefixes move together.
2. **Main file.** Header: `Requires PHP: 8.1`, `Requires Plugins: bluwp` (add `woocommerce` only when the plugin really needs it), `declare(strict_types=1);`. Define `HTW_*_FILE` as `__FILE__`, plus version, slug, and one capability constant.
3. **No hardcoded main filename.** WooCommerce `declare_compatibility` and `PluginBootstrap::register` receive the file constant. Delete stale names such as `forouzandea-smart-gateway.php`. `plugins_url()` is not a substitute: HPOS compatibility is keyed by the real plugin file.
4. **One capability.** The admin page, the menu, and every REST route that serves that page use the same constant (`HTW_PT_CAPABILITY` here). A screen limited to `manage_options` must not be writable with `manage_woocommerce`. Re-check the nonce (`wp_rest`) in the controller on state-changing routes.
5. **Folders.** `bootstrap/app.php` (autoload, features, REST namespace, asset handle), `pages/`, `routes/api.php`, `app/Controllers`, `app/Schemas`, `src/` for domain code, `hooks/early/` for boot only, `crons/`, `database/migrations/`. Domain rules do not live in the page registry.
6. **Admin UI.** Replace hand-built settings HTML with a form or table schema (`type`, `fields` or `columns`, `submit.endpoint`). Mount options stay `locale: 'fa-IR'`, `direction: 'rtl'`, `navigationPlacement: 'header'`. Mark URL and number fields `direction: ltr`.
7. **Do not vendor the kit.** Remove any checked-in copy of bluwp-vuejs `style.css` / IranSans. Add `development/build-admin.mjs` and `development/kit.json`. Build `apps/<slug>` from a pinned `0.2.x` kit into `assets/`. See the IranSans license note in `docs/design-system.md` before shipping fonts.
8. **Pin Bluwp** to `>= 0.2.1 < 0.3.0` at runtime (`HTW_*_BLUWP_MIN` / `HTW_*_BLUWP_BELOW`) and in `kit.json`. Show an admin notice when the range does not match; do not boot half the plugin.
9. **Strings.** Put Persian UI copy in `src/Text.php` (backed enum). Customer-facing cases can be overridden in an option; admin cases cannot. Keep gettext only for the pre-boot environment notice if you still need it. Escape at output.
10. **Catalog honesty.** `Plugin-Version` in `AI-GUIDE.md` equals the plugin header. `product.json` has no `version` key (the schema forbids it). Hooks in the guide exist in PHP. `readme.txt` Stable tag matches the header. `php scripts/check-catalog.php` must pass.
11. **Leave product code behind.** Do not import routing, drivers, relay, or Mana from «درگاه پرداخت هوشمند». Do not copy its bugs: hardcoded plugin filename, REST capability wider than the screen, a second checkout palette treated as the admin system.
12. **Directory guards.** `index.php` (“Silence is golden”) in every folder Bluwp does not glob. `crons/` and `database/migrations/` are globbed: use `index.html` plus `.htaccess`, not `index.php`, or Bluwp will try to load the guard as a cron or migration.
13. **Verify.** `php -l` on PHP 8.1, `composer lint && composer phpcs && composer phpstan && composer test`, and `npm run build:strict` when the kit checkout is available.

## Starting a new plugin (short)

```bash
# GitHub: Use this template → new repository
git clone git@github.com:howtowp/my-plugin.git && cd my-plugin
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp
cd my-plugin && composer install && composer test
php ../scripts/check-catalog.php
```

Install Bluwp `>= 0.2.1 < 0.3` on the site. Build admin assets when you are ready to open the settings screen (`docs/design-system.md`). Then replace the sample settings fields, the sample cron, and the sample migration with the product’s own.
