# Admin app

The admin pixels live in **bluwp-vuejs**, not in this plugin. That kit source is not available in this repository yet, so building the admin shell is optional. The embedded PHP framework is `lib/bluwp` and does not depend on this build.

1. Check out `bluwp-vuejs` on the same minor line as `development/kit.json` (`bluwpVuejs`, currently `0.2.x`).
2. Create `bluwp-vuejs/apps/<slug>/` for this plugin. `main.js` in this folder is only a sketch of the mount options (`fa-IR`, `rtl`, header navigation).
3. From the plugin root, run `npm run build` (or `node development/build-admin.mjs /path/to/bluwp-vuejs`). The kit writes `assets/js`, `assets/css`, and fonts. Those files are gitignored. Do not commit a copied `style.css`.
4. The asset handle must stay `<slug>-vue-admin`, matching `bootstrap/app.php`.

`--strict` makes the build fail when the kit checkout is missing. CI does not pass `--strict`, because this repository does not vendor the kit.
