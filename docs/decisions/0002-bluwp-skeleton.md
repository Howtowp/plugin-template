# 0002 — Bluwp skeleton is the shared starter

**Status:** accepted

How to WP plugins share one admin design system: the Bluwp PHP framework (`Howtowp\Bluwp`, `>= 0.3.0 < 0.4.0`). This repository is that starter. The admin UI is the React app in `admin/`, not a Vue kit.

The plugin folder follows Bluwp’s layout (`bootstrap`, `pages`, `routes`, `app`, `src`, `hooks/early`, `crons`, `database`, `assets`). Admin pages are JSON schemas. Persian copy lives in `src/Text.php`. The compiled kit is built per plugin into `assets/` and is not copied from another product.

The previous sample (Settings API page, sample block, Abilities sample, hand-rolled PSR-4 boot) is retired so a new plugin has one architecture. Release config, `product.json`, `AI-GUIDE.md`, security rules, and `scripts/init.sh` stay. The main plugin file is always passed as `HTW_PT_FILE`. The settings screen and its REST routes share `HTW_PT_CAPABILITY`.

IranSans redistribution is unresolved; see `docs/design-system.md`.
