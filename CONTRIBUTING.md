# Contributing

Plugins built from this template embed Bluwp **0.3.0** in `lib/bluwp`. The PHP namespace is `Howtowp\Bluwp`. The accepted line is `>= 0.3.0` and `< 0.4.0` (`HTW_*_BLUWP_MIN` / `HTW_*_BLUWP_BELOW`). `BluwpBridgeV1::consume()` still loads **one** copy for the whole site: the highest version on that line.

PHP stays 8.1 or newer. `readme.txt` stays `Tested up to: 7.1`. Do not add the IRANSansX font. Vazirmatn remains the bundled face. Posted arrays still go through `map_deep` before use.

## New plugin

```bash
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp
cd my-plugin && composer install && composer test
php ../scripts/check-catalog.php
```

`use Howtowp\Bluwp\...` for framework classes. Do not add `class_alias` and do not point those imports at any other vendor prefix.

## What an existing plugin must change

Do this in every plugin that embeds Bluwp (including Pado, Smart Manager, and the payment gateway) and ship those updates together. A plugin left on the 0.2 line will refuse this 0.3.0 tree, and a plugin whose code still names the previous Bluwp vendor prefix will fatal if this tree is the copy that loads.

1. **Replace `lib/bluwp`.** Copy this template's tree, or run `./scripts/update-bluwp.sh` against a 0.3.x tree whose namespace is already `Howtowp\Bluwp`. `metadata.php` must say `0.3.0` (or any `0.3.x` below `0.4.0`). The `Version` header in `bluwp.php` must match `metadata.php`.
2. **Rename the PHP namespace everywhere outside that tree as well.** In the plugin's own PHP, tests, and stubs, change every Bluwp `namespace`, `use`, fully qualified class name, `@package` tag, and string class name so the root is `Howtowp\Bluwp`. The autoload prefix string is `Howtowp\\Bluwp`. Search for the previous PascalCase vendor prefix that used to sit in front of `\Bluwp`, and for its lower-case and upper-case spellings, and delete them. Do not add `class_alias`. Do not keep a second bridge.
3. **Move the version pin.** Set `HTW_*_BLUWP_MIN` to `0.3.0` and `HTW_*_BLUWP_BELOW` to `0.4.0`. Pass that minimum into `BluwpBridgeV1::consume( $file, $slug, $title, $minimum, $embedded_dir )`. `$file` is the real main file (`__FILE__` / the `HTW_*_FILE` constant), never a hardcoded filename.
4. **Update the words around the pin.** README, `readme.txt` description, `AI-GUIDE.md`, product copy, and CI checks that mention the Bluwp line should say `0.3` and `Howtowp\Bluwp`. A user-facing notice that names the compatible line must say `0.3`.
5. **Leave stored keys alone.** This rename does not change option names, transients, cron hooks, database table names, or user meta. In this framework those keys are `bluwp_*` (for example `bluwp_migrations` and `bluwp_oauth_…`) or the plugin slug (`{slug}_version`, `{slug}_migration_version`, and cron hooks such as `{slug}_sample`). They never included the vendor prefix, so existing sites keep their settings. Do not add a migration that rewrites them.
6. **Verify.** From the repo root, `php scripts/check-catalog.php`. Inside the plugin directory, `composer lint && composer phpcs && composer phpstan && composer test`. A case-insensitive search of the plugin must find no copy of the previous Bluwp vendor prefix.

`docs/migrating.md` is the longer layout checklist (admin UI, capability, HPOS). This file is only the namespace and version move.

## Strings and translations

English is the source language. Write every user-facing string as a literal inside a WordPress i18n function. The text domain is the plugin slug (`plugin-template` until `scripts/init.sh` renames it).

- PHP: `__()`, `esc_html__()`, `esc_attr__()`, `_x()`, `_n()`, and `sprintf()`. Put a `translators:` comment on the line above any string that contains a placeholder. Escape at output. Shared strings and the one customer override stay on `src/Text.php`; `catalog()` calls `__()` with a literal, and `text()` applies the override.
- React (`admin/`): `import { __, _x, _n, sprintf } from "@wordpress/i18n"`. The text domain argument is the slug literal, not a variable. Vite marks that package external as `wp.i18n`, and the build keeps the `.__()` calls so `wp i18n make-pot` can see them. The bundle is `assets/admin/screen.js` (a name ending in `admin.js` is mishandled by `make-json`).
- `src/Admin/AdminPage.php` enqueues `wp-i18n` and calls `wp_set_script_translations()`. On `init`, the main file calls `load_plugin_textdomain()` so the bundled `languages/` catalog is found. WordPress 6.7+ then loads the `.mo` just in time.
- The admin root gets `dir="rtl"` or `dir="ltr"` from `is_rtl()`. Prefer logical Tailwind utilities (`ms-`, `me-`, `ps-`, `pe-`, `start`, `end`). LTR uses a Latin system font. RTL uses Vazirmatn, family `HowToWP Sans`. Do not add IRANSansX.

Regenerate after changing a string. WP-CLI must be on `PATH`. From the plugin directory:

```bash
npm run build
npm run i18n
```

`npm run i18n` (and `composer i18n`) writes `languages/{slug}.pot`, updates `languages/{slug}-fa_IR.po`, compiles the `.mo`, and builds the JS JSON. `npm run i18n:check` fails when the pot is stale. CI runs that check.

### Add a language

1. Copy `languages/plugin-template-fa_IR.po` to `languages/plugin-template-{locale}.po` (for example `plugin-template-ar.po`).
2. Set `Language:` and `Plural-Forms:` in the header. Translate every `msgstr`.
3. Run `npm run i18n` so the `.mo` and the JS JSON for that locale are compiled.
4. Commit the `.po`, `.mo`, and JSON. WordPress loads them when the site locale matches.

### Adopt this in an existing plugin

Do this in Pado, Smart Manager, and the payment gateway, then ship those updates together. A plugin that still prints Persian literals will stay Persian on an English site.

1. **Source language.** Replace hardcoded Persian (and any other locale) with English literals in `__()`, `esc_html__()`, `esc_attr__()`, `_x()`, or `_n()`. The text domain is that plugin's slug, a string literal. Keep one catalog for shared strings if the plugin has one; customer overrides stay user content, not translations.
2. **React admin.** Import `__`, `_x`, `_n`, and `sprintf` from `@wordpress/i18n`. Externalize that module as `wp.i18n`. Depend on `wp-i18n` and call `wp_set_script_translations( $handle, $slug, $dir . 'languages' )`. Name the built script so it does not end in `admin.js`.
3. **Catalog.** Add `Text Domain` and `Domain Path: /languages` to the plugin header. On `init`, call `load_plugin_textdomain()` with the bundled `languages` directory. Add `npm run i18n` / `composer i18n` as in this template. Commit `{slug}.pot`, `{slug}-fa_IR.po`, `{slug}-fa_IR.mo`, and the JS JSON. Move the old Persian copy into the `fa_IR` `msgstr` entries.
4. **Direction and type.** Set the admin root `dir` from `is_rtl()`. Use logical CSS (`ms-` / `me-` / `ps-` / `pe-` / `start` / `end`). LTR: a Latin system font. RTL: Vazirmatn as `HowToWP Sans`. Do not bundle IRANSansX.
5. **Bluwp.** If you edit the embedded tree, keep Bluwp's own text domain (`bluwp`) and its `lang/` catalog. Do not put those strings in the product text domain. Prefer `./scripts/update-bluwp.sh` for anything that is not a translation of a hardcoded Persian string.
6. **Verify.** `composer lint && composer phpcs && composer phpstan && composer test`, `php scripts/check-catalog.php`, `npm run build`, and `npm run i18n:check`. Open the screen once in `en_US` and once in `fa_IR`.
