# AGENTS.md — rules for coding agents (Cursor, Claude Code, Codex, …)

This repo is the **shared starter** for How to WP WordPress plugins. It is a project that contains one shippable plugin. Read this file first.

The admin UI is a VibeFarsi React app in `admin/`, built with Vite into `assets/admin`. WordPress supplies React (`wp-element`). Bluwp is **embedded** in each plugin (`lib/bluwp`) for REST, cron, and migrations, so a customer installs one zip. Product-specific payment code is not part of the starter. Details: [docs/design-system.md](docs/design-system.md) and [docs/architecture.md](docs/architecture.md).

## Layout (project layout)

```
./ repo root = project (never shipped as a whole)
├── plugin-template/ THE PLUGIN. Folder name = slug = zip root. Only this folder ships.
│ ├── plugin-template.php   headers, constants, BluwpBridgeV1::consume (no business logic)
│ ├── lib/bluwp/            embedded Bluwp 0.3.x, namespace Howtowp\Bluwp (upstream; do not hand-edit)
│ ├── bootstrap/app.php     Bluwp config: autoload, REST namespace, asset handle
│ ├── hooks/early/          WooCommerce HPOS/blocks + Bluwp boot (uses HTW_PT_FILE)
│ ├── pages/                admin menu + schema page registry
│ ├── routes/api.php        REST map
│ ├── app/                  Controllers, Schemas, Repositories, Http
│ ├── src/Text.php          English string catalog (PSR-4 HowToWP\PluginTemplate\)
│ ├── crons/  database/     sample cron and migration (Bluwp globs these)
│ ├── admin/                VibeFarsi React source (not in the zip)
│ ├── assets/admin/         Vite build (manifest, JS, CSS). React is external.
│ ├── AI-GUIDE.md  readme.txt  uninstall.php  languages/  tests/
│ └── composer.json  package.json  phpcs.xml.dist  phpstan.neon.dist  phpunit.xml.dist
├── docs/ data/ design/ research/ scripts/ dev/   project material, NEVER ships
├── product.json  product.schema.json
├── .release.yml            plugin_dir: plugin-template
└── .github/workflows/      release.yml · plugin-check.yml (includes PHP 8.1 lint + catalog check)
```

`docs/`, `data/`, `design/`, `research/`, `scripts/`, `dev/` never ship. Simple layout (plugin at repo root) is still supported by the release workflow for old product repos.

## Security & standards

Full ruleset (Persian, authoritative): [`docs/security-rules.md`](docs/security-rules.md). Condensed Cursor rule: `.cursor/rules/wordpress.mdc`.

Golden rule: **Validate -> Sanitize (input) -> Prepare (query) -> Escape (output)**. Nonce **and** capability on every state change. The settings page and its REST routes share `HTW_PT_CAPABILITY`. `exit` only for the `ABSPATH` / `WP_UNINSTALL_PLUGIN` guards and right after `wp_safe_redirect()`, `wp_send_json_*`, or a streamed download.

English is the source language. User-facing strings are literals in `__()`, `esc_html__()`, `esc_attr__()`, `_x()`, or `_n()` with the text domain equal to the slug. `src/Text.php` catalogs the shared ones (`catalog()` calls `__()`; `text()` applies a customer override). Persian (`fa_IR`) ships in `languages/`. Escape at output (`esc_html( Text::Save->text() )`). The pre-boot notice in the main file uses `__()` directly so an old PHP can show it before the enum loads.

The plugin main file is `HTW_PT_FILE` (`__FILE__` in the main file). Never hardcode a previous filename in `declare_compatibility` or `PluginBootstrap::register`.

Bluwp is embedded: `>= 0.3.0` and `< 0.4.0` (`HTW_PT_BLUWP_MIN`, `HTW_PT_BLUWP_BELOW`). The PHP namespace is `Howtowp\Bluwp`. The main file requires `lib/bluwp/bridge.php` and calls `BluwpBridgeV1::consume()`. Do not set `Requires Plugins: bluwp`. If a standalone Bluwp plugin is active on the same line, the bridge uses that copy instead of the embedded one. Replace the embedded tree with `./scripts/update-bluwp.sh <path-or-tgz>`. Admin CSS/JS come from `npm run build` (Vite). Do not add a bluwp-vuejs dependency. See [docs/design-system.md](docs/design-system.md).

## Naming (after `scripts/init.sh` these are renamed; use the current names)

- Slug / text domain / zip folder: `plugin-template`. Namespace: `HowToWP\PluginTemplate\`.
- Prefix **`htw_pt_`** for options, hooks, tables; **`HTW_PT_`** for constants; `htw-pt-` for CSS handles.
- One class per file; file name = class name. Every PHP file starts with `defined( 'ABSPATH' ) || exit;` except `uninstall.php` (`WP_UNINSTALL_PLUGIN`).
- `crons/` and `database/migrations/` have **no** `index.php` (Bluwp loads every PHP file there). They use `index.html` and `.htaccess` instead.

## How to start a new plugin

```bash
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp
php scripts/check-catalog.php
cd my-plugin && composer install && composer test
```

Then install Bluwp on the site and, when the settings screen should render, build admin assets ([docs/design-system.md](docs/design-system.md)). Replace the sample settings, cron, and migration. Migration checklist for an existing product: [docs/migrating.md](docs/migrating.md).

## How to…

- **Add a settings screen:** register it in `pages/admin.php` with `->capability( HTW_PT_CAPABILITY )`, return a schema from `app/Schemas`, map GET/POST in `routes/api.php` with the **same** capability constant, and check capability plus the `wp_rest` nonce in the controller. Document new hooks in `docs/hooks.md` and `AI-GUIDE.md`.
- **Add domain code:** `src/<Area>/<Name>.php` under `HowToWP\PluginTemplate\` (not `App`). Controllers stay thin.
- **Add a cron:** `crons/<name>.php` returning a Bluwp `CronDefinition`. No `index.php` in that folder.
- **Add a migration:** `database/migrations/NNN_*.php` extending Bluwp's migration. `down()` must not drop live data.
- **Add a string:** an English literal in `__()`, `esc_html__()`, or `@wordpress/i18n`. Shared or overridable copy is also a case on `src/Text.php` whose `catalog()` match arm calls `__()` with that same literal. Then `npm run build && npm run i18n` and translate the new `msgstr` in `languages/{slug}-fa_IR.po`.
- **Add an ability (optional, not in the skeleton):** [docs/abilities.md](docs/abilities.md). Register it from something that runs on `htw_pt_loaded`. A real `permission_callback` is required.
- **Build admin CSS/JS:** `cd plugin-template && npm run build`. Commit `assets/admin` when the source changes. CI fails if the build is stale. IranSans needs a commercial license; Vazirmatn (OFL) is the bundled face. See the design-system doc.
- **Update embedded Bluwp:** `./scripts/update-bluwp.sh <directory-or-tgz>`, then `php scripts/check-catalog.php`. Stay inside `HTW_PT_BLUWP_MIN` / `HTW_PT_BLUWP_BELOW` (`>= 0.3.0 < 0.4.0`). A jump to 0.4 is a breaking framework upgrade, not a drive-by edit. The namespace stays `Howtowp\Bluwp`.
- **Extensibility:** `do_action` / `apply_filters` with the `htw_pt_` prefix, listed in `docs/hooks.md` and `AI-GUIDE.md`.

## Quality rules

- PHP ≥ 8.1, WordPress ≥ 6.8, Bluwp `>= 0.3.0 < 0.4.0` (`Howtowp\Bluwp`). Escape late, sanitize input, nonce + capability on every state change.
- WooCommerce compatibility is declared with `HTW_PT_FILE`. Read orders with `wc_get_order()` (HPOS) when you add order code. Do not require WooCommerce unless the product is a Woo plugin (`Requires Plugins` and the WC headers).
- AI features, if a product adds them, use the core AI Client (`wp_ai_client_prompt`), feature-detected. Never store or request API keys.
- Checks (run inside `plugin-template/`): `composer install && composer lint && composer phpcs && composer phpstan && composer test`.
- From the repo root: `bash scripts/lint-php.sh` (PHP 8.1 in CI) and `php scripts/check-catalog.php`.
- `npm run build` compiles `admin/` into `assets/admin`. Do not point it at a bluwp-vuejs checkout.

## Definition of Done

Same list as section 13 of `docs/security-rules.md`, with these starter-specific checks:

- [ ] No `$_POST/$_GET/$_REQUEST/$_SERVER` without `wp_unslash` + sanitize; no output without context-correct escaping.
- [ ] Every query with variable input uses `$wpdb->prepare()` (or a WP / Bluwp API). Table names are not user input.
- [ ] Every PHP file has the `ABSPATH` guard (`uninstall.php`: `WP_UNINSTALL_PLUGIN`).
- [ ] Every state-changing action checks nonce **and** `current_user_can()`. Page capability and REST capability are the same constant.
- [ ] WooCommerce / Bluwp receive `HTW_PT_FILE`, never a hardcoded plugin filename.
- [ ] No unauthorised `die`/`exit`; errors via `WP_Error` or `ResponseFactory`.
- [ ] Admin CSS/JS is the Vite build in `assets/admin`, enqueued only on the plugin screen, with `wp-element` / `wp-api-fetch` / `wp-i18n`. Data to JS via `wp_add_inline_script`, not `wp_localize_script`.
- [ ] Every REST route has a real capability check. Write/delete is never wide open.
- [ ] `readme.txt`, `uninstall.php`, and directory guards exist (`index.php`, or `index.html` where Bluwp globs PHP).
- [ ] User-facing strings are English inside i18n functions with text domain = slug. `languages/{slug}.pot` is current (`npm run i18n:check`). Persian lives in the `fa_IR` catalog.
- [ ] `composer phpcs` (0 errors, 0 warnings), `composer phpstan`, `composer test` green; no line over 120 characters.
- [ ] `php scripts/check-catalog.php` is green (header, constant, readme Stable tag, package.json, AI-GUIDE `Plugin-Version`).
- [ ] No API key or secret in code. AI only via the core AI Client.
- [ ] Plugin Check is green in CI. `npm run build:strict` when a release ships admin assets.
- [ ] Release: version bumped together (see below). No secrets, zips, `.env`, `node_modules`, dev `vendor`, or `assets/css` committed.

## Release

Guide: [`docs/releasing.md`](docs/releasing.md). Agent rule: `.cursor/rules/release.mdc`. Release = **tag push**; never build or upload zips by hand. Only tag when the owner asks.

1. Work on `dev`. Docs-only changes need no release (merge to `main` only).
2. Bump the same `X.Y.Z` everywhere: header `Version:` + `HTW_PT_VERSION` in `plugin-template/plugin-template.php` (bootstrap reads the constant), `readme.txt` Stable tag + changelog, `AI-GUIDE.md` `Plugin-Version`, `package.json` / `package-lock.json`. Add a Keep a Changelog entry to `CHANGELOG.md`. Do not put the version in `product.json`.
3. Run `composer lint && composer phpcs && composer phpstan && composer test`, `php scripts/check-catalog.php`, and `npm run build:strict` when assets ship. Merge `dev` -> `main`, push.
4. Tag on `main`: `git tag -a vX.Y.Z -m vX.Y.Z && git push origin vX.Y.Z`. **The tag must equal the header `Version:`**. A `-beta` suffix makes a prerelease.
5. Actions builds per `.release.yml` (only the plugin folder), verifies, and publishes the zip. howtowp.ir serves the latest Release. There is no in-dashboard auto-updater.
6. Version-mismatch failure: fix the version and re-tag; delete the wrong tag only if no Release was published, otherwise ship the next patch.
7. Never commit zips, dev `vendor/`, `node_modules/`, `.env`, tokens, or compiled kit CSS. Never force-push `main`. Never move a tag with a published Release.

## Product content

- `product.json` lives at the **repository root** and must validate against `product.schema.json`. Every text field is `{ "fa": "…", "en": "…" }`. Do not put version, changelog, Requires, Tested up to, price, or club policy in this file. The catalog version check is `AI-GUIDE.md` `Plugin-Version` plus `product.json` `ai_guide` pointing at that file.
- `plugin-template/AI-GUIDE.md` ships in the zip. It is for customers and vibe coders. Keep the hook list honest — only hooks that exist in the code. `scripts/check-catalog.php` enforces that.
- After filling or updating those files, commit to `dev`. Do not tag a release unless the owner asked for one.

## Never commit

Tokens, API keys, `.env*`, private keys, customer data, zip files, `node_modules/`, dev `vendor/`, `build/` output, compiled `assets/css`, `assets/js`, `assets/fonts` (IranSans), large/private datasets. `docs/`, `data/`, `design/`, `research/`, `scripts/`, `dev/` must never end up in the plugin folder or the zip.
