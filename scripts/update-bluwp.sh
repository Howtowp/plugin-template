#!/usr/bin/env bash
# Replace the embedded Bluwp copy shipped inside the plugin.
#
#   ./scripts/update-bluwp.sh /path/to/bluwp
#   ./scripts/update-bluwp.sh /path/to/bluwp.tgz
#
# The argument is a Bluwp tree (bridge.php and metadata.php) or a .tgz/.tar.gz
# that contains one. Junk from macOS archives is dropped. The destination is
# <plugin_dir>/lib/bluwp from .release.yml, so the path still works after init.sh.
set -euo pipefail

if [[ $# -ne 1 || "$1" == "-h" || "$1" == "--help" ]]; then
	echo "usage: $0 <bluwp-directory-or-tgz>" >&2
	exit 1
fi

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
INPUT=$1
PLUGIN_DIR="$(awk '
	/^plugin_dir:/ {
		sub(/^plugin_dir:[[:space:]]*/, "")
		sub(/[[:space:]]+#.*$/, "")
		gsub(/["'\'']/, "")
		print
		exit
	}
' "$ROOT/.release.yml")"
if [[ -z "$PLUGIN_DIR" ]]; then
	echo "error: .release.yml has no plugin_dir" >&2
	exit 1
fi
DEST="$ROOT/$PLUGIN_DIR/lib/bluwp"

TMP=""
cleanup() {
	if [[ -n "$TMP" && -d "$TMP" ]]; then
		rm -rf "$TMP"
	fi
}
trap cleanup EXIT

SRC=$INPUT
if [[ -f "$INPUT" ]]; then
	case "$INPUT" in
		*.tgz | *.tar.gz) ;;
		*)
			echo "error: $INPUT is not a directory or a .tgz/.tar.gz archive" >&2
			exit 1
			;;
	esac
	TMP="$(mktemp -d)"
	tar -xzf "$INPUT" -C "$TMP"
	SRC="$(find "$TMP" -type f -name bridge.php -exec grep -l 'class BluwpBridgeV1' {} + | head -n 1 || true)"
	SRC="$(dirname "$SRC")"
fi

if [[ ! -f "$SRC/bridge.php" || ! -f "$SRC/metadata.php" ]]; then
	echo "error: $INPUT does not contain Bluwp bridge.php and metadata.php" >&2
	exit 1
fi

mkdir -p "$(dirname "$DEST")"
rm -rf "$DEST"
mkdir -p "$DEST"
cp -a "$SRC"/. "$DEST"/
find "$DEST" \( -name '.DS_Store' -o -name '._*' -o -name 'vitrin-local-domain.tmp.py' \) -delete
printf '%s\n' '<?php' '// Silence is golden.' > "$DEST/index.php"

VERSION="$(php -r '
	$source = file_get_contents($argv[1]);
	if (!preg_match("/['"'"'"]version['"'"'"]\\s*=>\\s*['"'"'\"](\\d+\\.\\d+\\.\\d+)['"'"'\"]/", $source, $match)) {
		fwrite(STDERR, "metadata.php has no version\n");
		exit(1);
	}
	echo $match[1];
' "$DEST/metadata.php")"

echo "Embedded Bluwp $VERSION at ${PLUGIN_DIR}/lib/bluwp"
echo "Confirm HTW_*_BLUWP_MIN / HTW_*_BLUWP_BELOW still accept $VERSION, then run php scripts/check-catalog.php"
