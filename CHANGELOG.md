# Changelog

All notable changes to this project. Format: [Keep a Changelog](https://keepachangelog.com), versioning: [SemVer](https://semver.org).
The WordPress-facing changelog lives in `plugin-template/readme.txt`.

## [Unreleased]

### Changed
- Embedded Bluwp is 0.3.0. Its PHP namespace is `Howtowp\Bluwp`. The template pin is `>= 0.3.0 < 0.4.0` (`HTW_PT_BLUWP_MIN` / `HTW_PT_BLUWP_BELOW`).
- Option names, transients, cron hooks, table names, and user meta were not renamed. They already use `bluwp_` or the plugin slug, so existing sites keep their settings without a data migration.

## [0.3.0] - 2026-11-14
### Changed
- The starter now follows the Bluwp layout used by How to WP plugins: `bootstrap/`, `pages/`, `routes/`, `app/`, `src/Text.php`, `hooks/early/`, `crons/`, `database/migrations/`.
- Admin UI is a JSON schema rendered by a React app (VibeFarsi, Tailwind) in `assets/admin`. React is WordPress's copy (`wp-element`). IranSans is not bundled; Vazirmatn (OFL) is the default face.
- Bluwp 0.2.1 is embedded in `lib/bluwp` and booted with `BluwpBridgeV1::consume()`. There is no `Requires Plugins: bluwp`. A standalone Bluwp plugin on the same line is preferred when it is already active.
- The sample settings page and its REST route share `HTW_PT_CAPABILITY` and require a `wp_rest` nonce. WooCommerce HPOS/blocks compatibility uses `HTW_PT_FILE`, not a hardcoded filename.
- The previous sample block, Settings API page, and Abilities sample are no longer in the skeleton. Abilities stay documented as optional in `docs/abilities.md`.
### Added
- `scripts/check-catalog.php` keeps the plugin header, readme Stable tag, `package.json`, and `AI-GUIDE.md` `Plugin-Version` aligned. `product.json` still has no version field.
- CI lints PHP on 8.1 and runs the catalog check, and still runs Plugin Check.
- `docs/design-system.md` and `docs/migrating.md`.

## [0.2.0]
- Added `docs/security-rules.md` (the Persian Security & Standards ruleset, adapted to `src/` PSR-4, PHP 8.1 / WP 6.8, Abilities API, `wp_add_inline_script` / `wp_interactivity_state`), the condensed `.cursor/rules/wordpress.mdc`, and the Definition of Done in `AGENTS.md`.
- Added "Silence is golden" `index.php` to the plugin root and every subfolder; `build/` gets them via the npm `postbuild` script (`bin/add-index.js`).
- PHPCS: WordPress-Extra + WordPress-Docs + PHPCompatibilityWP (`testVersion 8.1-`, `minimum_wp_version 6.8`), warning above 120 characters.
- Templates receive their data as `$args` instead of `extract()`; settings sanitizer and structure tests tightened.

## [0.1.0]
- Initial template: project layout, PSR-4 modules, Settings API, REST, Abilities API sample, AI stub, WooCommerce HPOS declaration, interactive block, release + Plugin Check workflows.
