# Changelog

All notable changes to this project. Format: [Keep a Changelog](https://keepachangelog.com), versioning: [SemVer](https://semver.org).
The WordPress-facing changelog lives in `plugin-template/readme.txt`.

## [Unreleased]

## [0.2.0]
- Added `docs/security-rules.md` (Persian security and standards ruleset, draft pending merge with the owner's original text), `.cursor/rules/wordpress.mdc`, and a Definition of Done in `AGENTS.md`.
- Added "Silence is golden" `index.php` to the plugin root and every subfolder; `build/` gets them via the npm `postbuild` script (`bin/add-index.js`).
- PHPCS: WordPress-Extra + WordPress-Docs + PHPCompatibilityWP (`testVersion 8.1-`, `minimum_wp_version 6.8`), soft line-length warning at 150.
- Templates receive their data as `$args` instead of `extract()`; settings sanitizer and structure tests tightened.

## [0.1.0]
- Initial template: project layout, PSR-4 modules, Settings API, REST, Abilities API sample, AI stub, WooCommerce HPOS declaration, interactive block, release + Plugin Check workflows.
