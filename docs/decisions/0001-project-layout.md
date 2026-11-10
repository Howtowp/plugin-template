# 0001 — Project layout (plugin in a subfolder)

**Status:** accepted

The repo root is a *project*; the shippable plugin lives in `plugin-template/`. Docs, data, research, design and scripts sit beside it and can never leak into the zip because the release packer only reads `plugin_dir`.
Release config: `.release.yml` with `plugin_dir: plugin-template`, `copy_root_files: false`.
The older "simple layout" (plugin at repo root) is still supported by the release workflow.
