#!/usr/bin/env python3
"""Tiny .pot generator (fallback when WP-CLI is not available).

Preferred: `wp i18n make-pot plugin-template plugin-template/languages/plugin-template.pot`.
Usage: python3 scripts/make-pot.py            (run from the project root)
"""
import re
import sys
from pathlib import Path

root = Path(__file__).resolve().parent.parent
plugin_dir = next(p.parent for p in root.glob("*/uninstall.php"))
slug = plugin_dir.name
call = re.compile(r"\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*'((?:[^'\\]|\\.)*)'\s*,\s*'" + re.escape(slug) + r"'")
entries = {}
for f in sorted(plugin_dir.rglob("*.php")):
    if any(part in {"vendor", "node_modules", "build", "tests"} for part in f.parts):
        continue
    for i, line in enumerate(f.read_text(encoding="utf-8").splitlines(), 1):
        for m in call.finditer(line):
            entries.setdefault(m.group(1), []).append(f"{f.relative_to(plugin_dir).as_posix()}:{i}")

out = [
    f'# Copyright (C) How to WP\n# This file is distributed under the GPL-2.0-or-later.\nmsgid ""\nmsgstr ""\n'
    f'"Project-Id-Version: {slug}\\n"\n"Report-Msgid-Bugs-To: https://howtowp.ir\\n"\n'
    '"MIME-Version: 1.0\\n"\n"Content-Type: text/plain; charset=UTF-8\\n"\n"Content-Transfer-Encoding: 8bit\\n"\n'
    '"POT-Creation-Date: 2026-01-01T00:00:00+00:00\\n"\n"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"\n'
    '"Language-Team: How to WP\\n"\n"X-Domain: ' + slug + '\\n"\n'
]
for msgid, refs in entries.items():
    out.append("#: " + " ".join(refs) + f'\nmsgid "{msgid}"\nmsgstr ""\n')
(plugin_dir / "languages" / f"{slug}.pot").write_text("\n".join(out), encoding="utf-8")
print(f"wrote {len(entries)} strings")
