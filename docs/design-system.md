# Design system

How to WP admin UI is not drawn inside each plugin. It comes from two shared pieces, plus a thin JSON schema in the plugin.

| Piece | What it owns | Version |
| --- | --- | --- |
| **Bluwp** (embedded in `lib/bluwp`) | REST router, migrations, cron, autoload. PHP namespace `Howtowp\Bluwp` | `>= 0.3.0` and `< 0.4.0` |
| **VibeFarsi** (copied into `admin/src/kit`, MIT) | Buttons, fields, tabs, table, dialog, toast | source of the React components |
| **This plugin** | Schema, REST, tokens, Vite build in `assets/admin/` | its own header version |

Customers install one plugin. Bluwp is not a separate download. The main file loads `lib/bluwp/bridge.php` and calls `BluwpBridgeV1::consume()`. On `plugins_loaded` at priority -1000 the bridge uses an active standalone Bluwp plugin if one registered itself, otherwise it compares every embedded copy via `metadata.php` and loads the highest version once. `attach()` then refuses a copy outside the consumer's line (for `0.3.0`, that is `>= 0.3.0` and `< 0.4.0`). The loaded classes are `Howtowp\Bluwp\...`.

Replace the embedded tree with `./scripts/update-bluwp.sh <directory-or-tgz>`. Do not hand-edit files under `lib/bluwp/`. CI checks that `bridge.php` exists and that `metadata.php`'s version sits inside `HTW_PT_BLUWP_MIN` / `HTW_PT_BLUWP_BELOW`.

Do not copy compiled `style.css`, `admin.css`, or font files from another plugin into this repo. That freezes an old kit and copies the font into every zip. Build your own app instead.

## Tokens

Set on `#htw-pt-admin` (the id uses the `htw-pt-` prefix, so `scripts/init.sh` renames it with the plugin). Tailwind utilities are prefixed with that same id, and preflight is off, so the rules do not leak into the rest of wp-admin or into another plugin's root.

| Token | Value | Role |
| --- | --- | --- |
| `--htw-color-brand` | `#f97316` | Accent, focus ring |
| `--htw-color-brand-strong` | `#ea580c` | Primary buttons, white text (AA) |
| `--htw-color-brand-hover` | `#c2410c` | Primary hover |
| `--htw-color-brand-active` | `#9a3412` | Primary active |
| `--htw-color-brand-soft` | `#fff7ed` | Soft orange background |
| `--htw-color-canvas` | `#f5f7fb` | Page background |
| `--htw-color-ink` | `#26323e` | Body text |
| `--htw-color-danger` | `#b44555` | Errors, destructive actions |
| `--htw-spacing` | `0.25rem` | Spacing unit |
| `--htw-radius-md` | `0.375rem` | Inputs, chips |
| `--htw-radius-lg` | `0.5rem` | Cards, buttons, dialogs |

Focus ring: `rgba(249, 115, 22, 0.35)`.

The shell is `dir="rtl"`, full width, with 20px padding. There is no max-width. `assets/admin-shell.css` (enqueued outside the React root) sets `#htw-pt-wrap.wrap` margin, padding, and max-width to zero so wp-admin's `.wrap` gutter does not inset the page. Fields with schema `direction: ltr` (URLs, IBAN, mobile, card numbers) use the `htw-ltr` class. The font family is `HowToWP Sans`.

Screens use underline tabs (`Tabs` `variant="underline"`, the default). Settings sections use a second underline tab list. `LoadProvider` draws the top progress bar; `useLoadTask` / `useNavigationProgress` drive it. `FormSkeleton`, `TableSkeleton`, and `ScreenSkeleton` hold the layout while data loads. `Button` `busy` shows `.htw-spin` and disables the control.

### wp-admin reset

`admin/src/styles.css` keeps an **unlayered** block under `#htw-pt-admin` for `button`, `input`, `select`, `textarea`, and links. Preflight is off, so without this block `forms.css`, `common.css`, and `buttons.css` paint gray beveled tabs, a misaligned switch, and a second chevron on `select`. The switch thumb uses logical `inset-inline-*` (not `translateX`). The select sits in `.htw-select` at full width with one chevron. `npm run build` prefixes Tailwind utilities with the same id; selectors that already start with `#htw-pt-admin` are left as they are, so the reset stays one id deep and unlayered.

### Previous brand

`#1767c1` was the brand before the Pado pilot. It is not a current token.

| Old | New |
| --- | --- |
| `--el-color-brand-600` / `--htw-color-brand` `#1767c1` | `--htw-color-brand` `#f97316`, buttons `#ea580c` |
| `--el-color-canvas` `#f5f7fb` | `--htw-color-canvas` |
| `--el-color-ink` `#26323e` | `--htw-color-ink` |
| `--el-color-danger` `#b44555` | `--htw-color-danger` |
| `--el-spacing` `.25rem` | `--htw-spacing` |
| `--el-radius-md` `.375rem` | `--htw-radius-md` |
| `--el-radius-lg` `.5rem` | `--htw-radius-lg` |
| `.bluwp-input--ltr` | `.htw-ltr` / `dir="ltr"` |
| `bluwp-vuejs` compiled `style.css` | `assets/admin/admin.css` from `admin/` |
| admin content `max-width: 1200px` | full width, 20px padding, `.wrap` neutralized |

## How a plugin builds its admin app

Source is `plugin-template/admin/` (Vite, React, Tailwind, copied VibeFarsi components). It is not shipped. `npm run build` writes `assets/admin/admin.js`, `admin.css`, and `manifest.json`. React, ReactDOM, and the JSX runtime are external and resolve to WordPress globals. The script depends on `wp-element`, `wp-api-fetch`, `wp-i18n`, and `react-jsx-runtime`.

There is no `bluwp-vuejs` checkout and no `development/build-admin.mjs`.

```bash
cd plugin-template && npm ci && npm run build
```

CI rebuilds and fails if `assets/admin` does not match the source. The release workflow runs the same build before the zip, so the zip contains `assets/admin` and not `admin/` or `node_modules`.

`src/Admin/AdminPage.php` enqueues those files only when the hook is `toplevel_page_<slug>`. Nothing is enqueued on the front end.

## Bumping Bluwp

The current line is Bluwp 0.3 (`Howtowp\Bluwp`). Stay inside `>= 0.3.0` and `< 0.4.0` until every How to WP plugin moves together again.

1. Run `./scripts/update-bluwp.sh` with the new Bluwp tree. `metadata.php` must stay inside `HTW_PT_BLUWP_MIN` and `HTW_PT_BLUWP_BELOW`.
2. Smoke-test the settings schema (form render, save, `422`, forbidden without the capability). Confirm a site that still has standalone Bluwp 0.3.x active boots the standalone copy, and a site without it boots `lib/bluwp`.

A 0.4 upgrade is a breaking framework bump. Do it in a dedicated change. What an existing plugin must edit to adopt 0.3.0 is listed in [CONTRIBUTING.md](../CONTRIBUTING.md).

## Fonts

`assets/shared-font/fonts.css` is enqueued with the handle `htw-admin-font` and the family name `HowToWP Sans`. Every How to WP plugin must use that same handle and family so WordPress prints the face once.

The files in the template are **Vazirmatn** (SIL OFL). IranSans is a commercial family. Do not commit IranSans files unless How to WP has a license that covers redistribution. To opt in, add `assets/shared-font/IranSans.woff2` and `IranSansBold.woff2` and point extra `@font-face` rules at `HowToWP Sans` with `font-weight` 400 and 700. Until those files exist, Vazirmatn is the face.

## What does not belong in the shared starter

Payment routing, bank drivers, relay/envelope code, and Mana checkout are product-specific. The old compiled Vue kit is not part of this template.
