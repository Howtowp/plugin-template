#!/usr/bin/env bash
# Rebuild the gettext catalog from PHP and the Vite bundle.
# English is the source. fa_IR.po keeps its translations; new strings are added empty.
set -euo pipefail

cd "$(dirname "$0")/.."

if ! command -v wp >/dev/null 2>&1; then
  echo "wp (WP-CLI) is required. Install https://wp-cli.org/ and retry." >&2
  exit 1
fi

if [ ! -f assets/admin/screen.js ]; then
  echo "assets/admin/screen.js is missing. Run npm run build first." >&2
  exit 1
fi

# The bundle is screen.js, not admin.js. A file whose name ends in "admin.js"
# is rewritten by wp i18n make-json (its ".min.js" pattern is unescaped) and
# the JSON hash no longer matches the file WordPress loads.
# Do not exclude the path "admin": that also skips assets/admin/screen.js.
wp i18n make-pot . languages/plugin-template.pot \
  --domain=plugin-template \
  --exclude=node_modules,vendor,lib,tests,admin/src,admin/harness,bin \
  --skip-audit \
  --headers='{"Report-Msgid-Bugs-To":"https://howtowp.ir","Language-Team":"How to WP <https://howtowp.ir>"}'

PO=languages/plugin-template-fa_IR.po
if [ ! -f "$PO" ]; then
  wp i18n make-po languages/plugin-template.pot "$PO"
else
  wp i18n update-po languages/plugin-template.pot "$PO"
fi

# update-po copies the pot header and clears Language. The locale is fa_IR.
perl -0pi -e 's/"Language: \\n"\n/"Language: fa_IR\\n"\n"Plural-Forms: nplurals=2; plural=(n > 1);\\n"\n/' "$PO"

wp i18n make-mo languages
wp i18n make-json languages --no-purge --pretty-print

# Jed JSON for the enqueued assets/admin/screen.js file.
find languages -name 'plugin-template-fa_IR-*.json' -print
echo "Updated languages/plugin-template.pot, fa_IR.po, fa_IR.mo, and the JS JSON."
