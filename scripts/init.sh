#!/usr/bin/env bash
# Rename the template after "Use this template".
#
#   ./scripts/init.sh <slug> "<Plugin Name>" <Namespace> <prefix> [--keep-script]
#   ./scripts/init.sh my-plugin "My Plugin" MyPlugin mp
#
#   slug       lowercase, digits, dashes (folder name, text domain, zip root)
#   name       human readable plugin name
#   Namespace  PascalCase; becomes HowToWP\<Namespace>
#   prefix     2-6 lowercase letters/digits; htw_<prefix>_ / HTW_<PREFIX>_ / htw-<prefix>-
#
# Portable bash + perl (macOS and Linux). Run from anywhere; it works on the repo it lives in.
set -euo pipefail

OLD_SLUG="plugin-template"
OLD_NAME="Plugin Template"
OLD_NS="PluginTemplate"
OLD_PREFIX="pt"

usage() {
  sed -n '2,13p' "$0" | sed 's/^# \{0,1\}//'
  exit 1
}

KEEP_SCRIPT=0
ARGS=()
for a in "$@"; do
  case "$a" in
    --keep-script) KEEP_SCRIPT=1 ;;
    -h|--help) usage ;;
    *) ARGS+=("$a") ;;
  esac
done
[ "${#ARGS[@]}" -eq 4 ] || usage

SLUG="${ARGS[0]}"
NAME="${ARGS[1]}"
NS="${ARGS[2]}"
PFX="${ARGS[3]}"

[[ "$SLUG" =~ ^[a-z][a-z0-9-]*$ ]]   || { echo "error: slug must match ^[a-z][a-z0-9-]*\$" >&2; exit 1; }
[[ "$NS"   =~ ^[A-Z][A-Za-z0-9]*$ ]] || { echo "error: Namespace must be PascalCase (e.g. MyPlugin)" >&2; exit 1; }
[[ "$PFX"  =~ ^[a-z][a-z0-9]{1,5}$ ]] || { echo "error: prefix must be 2-6 lowercase letters/digits" >&2; exit 1; }
[ -n "$NAME" ] || { echo "error: plugin name is empty" >&2; exit 1; }
case "$NAME" in *\'*|*\"*|*\\*) echo "error: plugin name must not contain quotes or backslashes" >&2; exit 1 ;; esac
command -v perl >/dev/null 2>&1 || { echo "error: perl is required" >&2; exit 1; }

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
[ -d "$OLD_SLUG" ] || { echo "error: '$OLD_SLUG/' not found; was init.sh already run?" >&2; exit 1; }

UPFX="$(printf '%s' "$PFX" | tr '[:lower:]' '[:upper:]')"
echo "Renaming: $OLD_SLUG -> $SLUG | '$OLD_NAME' -> '$NAME' | $OLD_NS -> $NS | prefix $OLD_PREFIX -> $PFX"

export OLD_SLUG OLD_NAME OLD_NS OLD_PREFIX SLUG NAME NS PFX UPFX

# 1) Replace text inside files (skips .git, dependencies, build output, binaries).
FILES=()
while IFS= read -r -d '' f; do
  FILES+=("$f")
done < <(find . \( -name .git -o -name node_modules -o -name vendor -o -name build -o -name .phpunit.cache \) -prune -o -type f ! -name 'package-lock.json' ! -name '*.zip' -print0)

for f in "${FILES[@]}"; do
  [ "$f" = "./scripts/init.sh" ] && continue
  if grep -Iq . "$f" 2>/dev/null; then
    perl -pi -e '
      s/\Q$ENV{OLD_NAME}\E/$ENV{NAME}/g;
      s/\Q$ENV{OLD_SLUG}\E/$ENV{SLUG}/g;
      s/\Q$ENV{OLD_NS}\E/$ENV{NS}/g;
      s/HTW_PT_/HTW_$ENV{UPFX}_/g;
      s/htw_pt_/htw_$ENV{PFX}_/g;
      s/htw-pt-/htw-$ENV{PFX}-/g;
      s/\bHTW_PT\b/HTW_$ENV{UPFX}/g;
      s/\bhtw_pt\b/htw_$ENV{PFX}/g;
    ' "$f"
  fi
done

# The "adopt this in an old project" prompt in docs/releasing.md fetches files from the TEMPLATE repo:
# keep those URLs pointing at howtowp/plugin-template.
if [ -f docs/releasing.md ]; then
  perl -pi -e 's{raw\.githubusercontent\.com/howtowp/\Q$ENV{SLUG}\E/main/}{raw.githubusercontent.com/howtowp/plugin-template/main/}g' docs/releasing.md
fi

# package-lock.json: only the package name (keep dependency data untouched).
if [ -f "$OLD_SLUG/package-lock.json" ]; then
  perl -pi -e 's/"name": "\Q$ENV{OLD_SLUG}\E"/"name": "$ENV{SLUG}"/g' "$OLD_SLUG/package-lock.json"
fi

# 2) Rename files and folders containing the old slug (deepest first).
while IFS= read -r p; do
  base="$(basename "$p")"
  dir="$(dirname "$p")"
  new="${base//$OLD_SLUG/$SLUG}"
  [ "$base" = "$new" ] || mv "$p" "$dir/$new"
done < <(find . \( -name .git -o -name node_modules -o -name vendor \) -prune -o -name "*$OLD_SLUG*" -print | awk '{ print length($0) " " $0 }' | sort -rn | cut -d' ' -f2-)

# 3) Fresh project README and changelog.
cat > README.md <<README_EOF
# $NAME

افزونه‌ی وردپرس · WordPress plugin by [How to WP](https://howtowp.ir).

- Plugin folder: \`$SLUG/\` (the only thing that ships). Project material: \`docs/\`, \`data/\`, \`design/\`, \`research/\`, \`scripts/\`, \`dev/\`.
- Store copy: [product.json](./product.json) (bilingual; not in the zip). Customer AI guide: \`$SLUG/AI-GUIDE.md\` (ships in the zip).
- Coding agents: read [AGENTS.md](./AGENTS.md).
- Build: \`cd $SLUG && composer install && npm install && npm run build\`.
- Release: bump versions, tag \`vX.Y.Z\` on \`main\` (see AGENTS.md).

License: GPL-2.0-or-later.
README_EOF

cat > CHANGELOG.md <<CHANGELOG_EOF
# Changelog

Format: [Keep a Changelog](https://keepachangelog.com), versioning: [SemVer](https://semver.org).
The WordPress-facing changelog lives in \`$SLUG/readme.txt\`.

## [Unreleased]

## [0.1.0]
- Initial version, created from howtowp/plugin-template.
CHANGELOG_EOF

# 4) Regenerate the .pot with the new names when possible.
if command -v python3 >/dev/null 2>&1 && [ -f scripts/make-pot.py ]; then
  python3 scripts/make-pot.py >/dev/null || true
fi

# 5) Remove this script (one-shot) unless asked to keep it.
if [ "$KEEP_SCRIPT" -eq 0 ]; then
  rm -f "$ROOT/scripts/init.sh"
  echo "Removed scripts/init.sh (use --keep-script to keep it)."
fi

echo "Done. Next:"
echo "  cd $SLUG && composer install && npm install && npm run build"
echo "  git add -A && git commit -m 'chore: initialise $SLUG'"
