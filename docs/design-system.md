# Design system

How to WP admin UI is not drawn inside each plugin. It comes from two shared pieces, plus a thin JSON schema in the plugin.

| Piece | What it owns | Version |
| --- | --- | --- |
| **Bluwp** (embedded in `lib/bluwp`) | Menus, page registry, REST router, migrations, cron, autoload | `>= 0.2.1` and `< 0.3.0` |
| **bluwp-vuejs** (build-time kit, source not in this repo yet) | Tokens, IranSans, `bluwp-*` components, WordPress admin chrome, RTL | same 0.2 line, when the checkout exists |
| **This plugin** | Schemas, REST, domain code, optional kit build in `assets/` | its own header version |

Customers install one plugin. Bluwp is not a separate download. The main file loads `lib/bluwp/bridge.php` and calls `BluwpBridgeV1::consume()`. On `plugins_loaded` at priority -1000 the bridge uses an active standalone Bluwp plugin if one registered itself, otherwise it compares every embedded copy via `metadata.php` and loads the highest version once. `attach()` then refuses a copy outside the consumer's line (for `0.2.1`, that is `>= 0.2.1` and `< 0.3.0`).

Replace the embedded tree with `./scripts/update-bluwp.sh <directory-or-tgz>`. Do not hand-edit files under `lib/bluwp/`. CI checks that `bridge.php` exists and that `metadata.php`'s version sits inside `HTW_PT_BLUWP_MIN` / `HTW_PT_BLUWP_BELOW`.

Do not copy compiled `style.css`, `admin.css`, or font files from another plugin into this repo. That freezes an old kit and copies the font into every zip. Build your own app instead.

## Tokens

Defined by the kit on `:root` / `:host` as `--el-*`. Plugins do not redeclare them.

| Token | Value | Role |
| --- | --- | --- |
| `--el-color-brand-600` | `#1767c1` | Primary actions, links, focus |
| `--el-color-canvas` | `#f5f7fb` | Page background |
| `--el-color-ink` | `#26323e` | Body text |
| `--el-color-danger` | `#b44555` | Errors, destructive actions |
| `--el-font-sans` | IranSans, then `"Segoe UI", Tahoma, sans-serif` | UI font |

Also used by the kit: brand steps `#edf5ff` / `#dcecff` / `#b9d8fa` / `#2477d4` / `#12539d`, muted `#667585`, line `#dce4ed`, surface `#fff`. Radius `--el-radius-md` .375rem and `--el-radius-lg` .5rem. Spacing unit `--el-spacing` .25rem.

The admin shell is mounted with `{ locale: 'fa-IR', direction: 'rtl', navigationPlacement: 'header' }`.

**LTR exceptions.** URLs, tokens, and numbers stay left-to-right inside the RTL shell. The sample settings field sets `'direction' => 'ltr'`. Table columns use the same key. The kit’s class is `bluwp-input--ltr`.

## How a plugin builds its admin app

The bluwp-vuejs kit source is **not available in this repository yet**. Until a checkout exists, skip the build. `npm run build` warns and exits 0. The PHP admin (schemas, REST, embedded Bluwp) still runs; the Vue shell appears after a real kit build writes `assets/`.

1. Pin the kit in `plugin-template/development/kit.json` (`bluwpVuejs`, currently `0.2.x`). Pin the embedded Bluwp in the main file (`HTW_PT_BLUWP_MIN` / `HTW_PT_BLUWP_BELOW`) to the same minor line.
2. In the **bluwp-vuejs** checkout, add `apps/<slug>/`. A plugin that only uses table, form, and service schemas does not register a custom Vue page. `development/app/main.js` is a sketch of the mount options, not the build input.
3. From the plugin directory:

   ```bash
   npm run build
   # or, with an explicit checkout:
   node development/build-admin.mjs /path/to/bluwp-vuejs
   ```

   The script calls `bluwp-vuejs/tools/build-plugin.mjs` and writes into `assets/` (`js/`, `css/`, `fonts/`, `build-manifest.json`). Those outputs are gitignored.
4. The asset handle in `bootstrap/app.php` is `<slug>-vue-admin`. `kit.json` `assetHandle` must match. `scripts/check-catalog.php` checks that.
5. `npm run build` exits 0 with a warning when the kit is not on disk, so CI can run. Before a release that should ship the admin UI, run `npm run build:strict` (or pass `--strict`) against the pinned kit and confirm `assets/` is what you expect.

Do not hand-edit the compiled CSS. When the kit’s minor version changes, bump `kit.json`, rebuild, and commit only if this product intentionally vendors the build output (the template itself does not).

## Bumping Bluwp or the kit

Stay on Bluwp 0.2 until every How to WP plugin moves together.

1. Run `./scripts/update-bluwp.sh` with the new Bluwp tree. `metadata.php` must stay inside `HTW_PT_BLUWP_MIN` and `HTW_PT_BLUWP_BELOW` (the bridge enforces that line: a `0.2.x` minimum rejects `0.3.0` and newer).
2. Change `bluwp` and `bluwpVuejs` in `development/kit.json` to the same line.
3. Rebuild admin assets with `--strict` when the kit source exists.
4. Smoke-test the settings schema (form render, save, `422`, forbidden without the capability). Confirm a site that still has standalone Bluwp 0.2.x active boots the standalone copy, and a site without it boots `lib/bluwp`.

A 0.3 upgrade is a breaking framework bump. Do it in a dedicated change, not as a side effect of a product feature.

## IranSans

IranSans (400 and 700, WOFF2 and WOFF, `font-display: swap`) is part of the kit build, not a file in this template.

**Open question:** IranSans is generally a commercial family. Before any plugin zip redistributes the font files, confirm that the How to WP / Bluwp license covers shipping them inside every product zip. Until that is confirmed, do not copy the font into a new plugin by hand, and do not commit the built `assets/fonts/` tree to this template.

## What does not belong in the shared starter

Payment routing, bank drivers, relay/envelope code, Mana checkout, and the teal storefront skin are product-specific. Storefront UI, if a plugin needs it, is a separate surface: logical CSS properties, the active theme’s font, not the admin `--el-*` tokens, unless a product explicitly decides otherwise.
