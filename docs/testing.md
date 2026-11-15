# Testing

- **Syntax (PHP 8.1):** from the repo root, `bash scripts/lint-php.sh`. CI runs this on PHP 8.1.
- **Catalog:** `php scripts/check-catalog.php` — header version, `HTW_PT_VERSION`, readme Stable tag, `package.json`, `AI-GUIDE.md` `Plugin-Version`, hook names, Bluwp pin, embedded `lib/bluwp/bridge.php` and `metadata.php` version, no `Requires Plugins: bluwp`, no hardcoded main filename, page/REST capability constant. `product.json` must not carry a conflicting `version` (the schema forbids the field).
- **Unit** (no WordPress): `cd plugin-template && composer install && composer test`. Bluwp symbols used by PHPStan live in `tests/stubs/bluwp.php`. Do not load that stub in a process that also loads the real Bluwp plugin.
- **Static analysis / style:** `composer phpstan`, `composer phpcs` (WPCS 3.4).
- **Admin bundle:** `cd plugin-template && npm ci && npm run build`. CI runs that build, `node admin/harness/check-reset.mjs`, and `git diff --exit-code -- assets/admin`.
- **wp-admin style harness:** `plugin-template/admin/harness/preview.html` loads WordPress 7.1.4 `common.css`, `forms.css`, `buttons.css`, and the RTL twins, then the built admin bundle. Serve the plugin directory (`python3 -m http.server` inside `plugin-template/`) and open `/admin/harness/preview.html`. The check script fails CI if the orange tokens or the unlayered reset disappear from `assets/admin/admin.css`. The CSS fixtures are GPL copies for this harness only; `.distignore` keeps `admin/` out of the zip.
- **Embedded boot:** `composer test` includes a case that loads `lib/bluwp` with WordPress function stubs and no standalone provider. `bluwp_framework_loaded` must be `0.2.1`.
- **In a real WordPress:** from the repo root `npx @wordpress/env start` (uses `.wp-env.json`). The embedded copy is enough; do not install a separate Bluwp unless you are testing the standalone-provider path. Admin / password on http://localhost:8888.
- **Plugin Check:** runs on PRs to `dev`/`main` (`.github/workflows/plugin-check.yml`) together with the PHP 8.1 lint and catalog check.
- **Demo in the browser:** `dev/blueprint.json` for WordPress Playground (replace the zip URL with a public release zip). The playground also needs Bluwp.
- Integration tests: add the WordPress test suite via wp-env and a second PHPUnit suite. Do not boot those tests with `tests/stubs/bluwp.php` loaded.
