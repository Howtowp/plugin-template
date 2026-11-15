#!/usr/bin/env bash
# Syntax-check every PHP file in the repo with the current php binary.
# CI runs this on PHP 8.1. Skips dependency and build trees.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
status=0
while IFS= read -r -d '' file; do
	if ! php -l "$file" >/dev/null; then
		status=1
	fi
done < <(find "$ROOT" \
	\( -path '*/vendor' -o -path '*/node_modules' -o -path '*/.git' -o -path '*/build' \) -prune \
	-o -name '*.php' -print0)

exit "$status"
