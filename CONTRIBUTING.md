# Contributing

Plugins built from this template embed Bluwp **0.3.0** in `lib/bluwp`. The PHP namespace is `Howtowp\Bluwp`. The accepted line is `>= 0.3.0` and `< 0.4.0` (`HTW_*_BLUWP_MIN` / `HTW_*_BLUWP_BELOW`). `BluwpBridgeV1::consume()` still loads **one** copy for the whole site: the highest version on that line.

PHP stays 8.1 or newer. `readme.txt` stays `Tested up to: 7.1`. Do not add the IRANSansX font. Vazirmatn remains the bundled face. Posted arrays still go through `map_deep` before use.

## New plugin

```bash
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp
cd my-plugin && composer install && composer test
php ../scripts/check-catalog.php
```

`use Howtowp\Bluwp\...` for framework classes. Do not add `class_alias` and do not point those imports at any other vendor prefix.

## What an existing plugin must change

Do this in every plugin that embeds Bluwp (including Pado, Smart Manager, and the payment gateway) and ship those updates together. A plugin left on the 0.2 line will refuse this 0.3.0 tree, and a plugin whose code still names the previous Bluwp vendor prefix will fatal if this tree is the copy that loads.

1. **Replace `lib/bluwp`.** Copy this template's tree, or run `./scripts/update-bluwp.sh` against a 0.3.x tree whose namespace is already `Howtowp\Bluwp`. `metadata.php` must say `0.3.0` (or any `0.3.x` below `0.4.0`). The `Version` header in `bluwp.php` must match `metadata.php`.
2. **Rename the PHP namespace everywhere outside that tree as well.** In the plugin's own PHP, tests, and stubs, change every Bluwp `namespace`, `use`, fully qualified class name, `@package` tag, and string class name so the root is `Howtowp\Bluwp`. The autoload prefix string is `Howtowp\\Bluwp`. Search for the previous PascalCase vendor prefix that used to sit in front of `\Bluwp`, and for its lower-case and upper-case spellings, and delete them. Do not add `class_alias`. Do not keep a second bridge.
3. **Move the version pin.** Set `HTW_*_BLUWP_MIN` to `0.3.0` and `HTW_*_BLUWP_BELOW` to `0.4.0`. Pass that minimum into `BluwpBridgeV1::consume( $file, $slug, $title, $minimum, $embedded_dir )`. `$file` is the real main file (`__FILE__` / the `HTW_*_FILE` constant), never a hardcoded filename.
4. **Update the words around the pin.** README, `readme.txt` description, `AI-GUIDE.md`, product copy, and CI checks that mention the Bluwp line should say `0.3` and `Howtowp\Bluwp`. A user-facing notice that names the compatible line must say `0.3`.
5. **Leave stored keys alone.** This rename does not change option names, transients, cron hooks, database table names, or user meta. In this framework those keys are `bluwp_*` (for example `bluwp_migrations` and `bluwp_oauth_…`) or the plugin slug (`{slug}_version`, `{slug}_migration_version`, and cron hooks such as `{slug}_sample`). They never included the vendor prefix, so existing sites keep their settings. Do not add a migration that rewrites them.
6. **Verify.** From the repo root, `php scripts/check-catalog.php`. Inside the plugin directory, `composer lint && composer phpcs && composer phpstan && composer test`. A case-insensitive search of the plugin must find no copy of the previous Bluwp vendor prefix.

`docs/migrating.md` is the longer layout checklist (admin UI, capability, HPOS). This file is only the namespace and version move.
