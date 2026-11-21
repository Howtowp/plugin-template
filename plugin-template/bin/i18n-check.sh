#!/usr/bin/env bash
# Fail when languages/plugin-template.pot does not match a fresh extraction.
set -euo pipefail

cd "$(dirname "$0")/.."

if ! command -v wp >/dev/null 2>&1; then
  echo "wp (WP-CLI) is required." >&2
  exit 1
fi

if [ ! -f assets/admin/screen.js ]; then
  echo "assets/admin/screen.js is missing. Run npm run build first." >&2
  exit 1
fi

tmp="$(mktemp)"
wp i18n make-pot . "$tmp" \
  --domain=plugin-template \
  --exclude=node_modules,vendor,lib,tests,admin/src,admin/harness,bin \
  --skip-audit \
  --headers='{"Report-Msgid-Bugs-To":"https://howtowp.ir","Language-Team":"How to WP <https://howtowp.ir>"}' \
  --quiet

normalize() {
  sed -E '/^"POT-Creation-Date:/d; /^"PO-Revision-Date:/d; /^"X-Generator:/d'
}

if ! diff -u <(normalize < languages/plugin-template.pot) <(normalize < "$tmp"); then
  echo "languages/plugin-template.pot is stale. Run npm run i18n." >&2
  rm -f "$tmp"
  exit 1
fi

rm -f "$tmp"
echo "languages/plugin-template.pot is up to date."
