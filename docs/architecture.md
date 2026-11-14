# Architecture

The shippable plugin lives in `plugin-template/`. Bluwp (the required plugin) boots it. The Vue kit is a build-time dependency, not a runtime Composer package.

```
plugin-template.php          Headers, constants, BluwpBridgeV1::consume. No business logic.
lib/bluwp/                   Embedded Bluwp. bridge.php selects one copy per request.
bootstrap/app.php            Bluwp name, version constant, autoload, feature flags.
hooks/early/woocommerce.php  HPOS / blocks via HTW_PT_FILE, then PluginBootstrap.
pages/                       admin.php and admin-menu.php (closures). index.php there is only a directory guard.
routes/api.php               REST map. Bluwp loads the files named in bootstrap `routing.files`, not every PHP file.
app/Controllers              HTTP: capability, nonce, validate.
app/Schemas                  JSON the admin kit renders.
app/Repositories             Options.
app/Http                     REST capability + nonce helper.
src/Text.php                 Persian string enum + customer overrides.
crons/                       Bluwp cron definitions (glob; no index.php).
database/migrations          Ordered migrations (glob; no index.php).
assets/                      Build output of bluwp-vuejs. Not hand-written CSS.
development/                 build-admin.mjs + kit pin. Not in the zip.
```

Boot:

1. The main file exits unless `ABSPATH` is defined, then refuses PHP older than 8.1 or WordPress older than 6.8 with a gettext notice.
2. It loads `src/Text.php` and `hooks/early/woocommerce.php`.
3. `before_woocommerce_init` declares `custom_order_tables` and `cart_checkout_blocks` for `HTW_PT_FILE` when WooCommerce is present. WooCommerce is not a required plugin.
4. `BluwpBridgeV1::consume()` registers this plugin. On `plugins_loaded` priority -1000 the bridge loads either the active standalone Bluwp or the highest embedded copy whose `metadata.php` version is inside `0.2.1`–`0.3.0`. It then registers `PluginBootstrap`, which boots the app on `init` priority 1.
5. `htw_pt_loaded` fires on `init` priority 2. An incompatible or missing framework sets `bluwp_framework_error` and the plugin shows an admin notice instead of booting.

Autoload (in `bootstrap/app.php`, not Composer at runtime):

- `HowToWP\PluginTemplate\App\` → `app/`
- `HowToWP\PluginTemplate\` → `src/`

Composer’s PSR-4 map mirrors that for PHPStan and PHPUnit only. The zip does not ship `vendor/`.

`features.hooks` is false. The only early hook file is required from the main file so WooCommerce compatibility is registered in time.

Record decisions in `docs/decisions/`.
