# Plugin Template — قالب افزونه‌ی وردپرس

**فارسی** · [English](#english)

قالب مشترک افزونه‌های [How to WP](https://howtowp.ir). هر محصول **یک** افزونه است: هستهٔ Bluwp داخل `lib/bluwp` قرار دارد و مشتری افزونهٔ جداگانه‌ای نصب نمی‌کند. اگر Bluwp مستقل روی سایت فعال باشد، همان نسخه استفاده می‌شود. ظاهر پیشخوان با React و کامپوننت‌های VibeFarsi ساخته می‌شود (`npm run build` → `assets/admin`). React از خود وردپرس می‌آید.

## شروع سریع

```bash
git clone git@github.com:howtowp/my-plugin.git && cd my-plugin
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp
cd my-plugin && composer install
composer lint && composer phpcs && composer phpstan && composer test
php ../scripts/check-catalog.php
```

Bluwp `0.3.0` داخل افزونه است (`lib/bluwp`، namespace `Howtowp\Bluwp`، بازهٔ `>= 0.3.0 < 0.4.0`). به‌روزرسانی آن: `./scripts/update-bluwp.sh <path-or-tgz>`. ساخت پیشخوان: `cd plugin-template && npm ci && npm run build`. جزئیات در [docs/design-system.md](docs/design-system.md). افزونه‌ای که از این قالب ساخته شده باید همان namespace را به کار ببرد؛ فهرست دقیق تغییرها در [CONTRIBUTING.md](CONTRIBUTING.md).

## ساختار

افزونه‌ی قابل‌انتشار فقط داخل **یک زیرپوشه** است. `docs/`، `data/`، `design/`، `research/`، `scripts/` و `dev/` داخل zip نمی‌روند.

```
my-plugin/
├── my-plugin/                 خود افزونه (نام پوشه = slug)
│   ├── my-plugin.php          هدر، ثابت‌ها، HTW_*_FILE = __FILE__
│   ├── lib/bluwp/             هستهٔ Bluwp؛ مشتری جدا نصب نمی‌کند
│   ├── bootstrap/  pages/  routes/  app/  src/Text.php
│   ├── hooks/early/           سازگاری HPOS با همان فایل اصلی
│   ├── crons/  database/  assets/  development/
│   └── AI-GUIDE.md  readme.txt
├── product.json  product.schema.json
├── docs/  AGENTS.md  .release.yml  .github/
└── scripts/init.sh  scripts/check-catalog.php
```

## نسخه‌ها و کاتالوگ

`product.json` فیلد version ندارد (اسکیما اجازه نمی‌دهد). خط `Plugin-Version` در `AI-GUIDE.md` باید با هدر افزونه یکی باشد. `php scripts/check-catalog.php` این را، به‌علاوه‌ی capability یکسان صفحه و REST و استفاده‌نکردن از نام فایل سخت‌کدشده، چک می‌کند.

زبان مبدأ انگلیسی است و ترجمهٔ فارسی (`fa_IR`) داخل `languages/` منتشر می‌شود. پیشخوان جهت را از زبان سایت می‌گیرد. جزئیات و ساخت دوبارهٔ فایل‌های ترجمه: [CONTRIBUTING.md](CONTRIBUTING.md).

مجوز بازتوزیع فونت IranSans هنوز باز است. جزئیات: [docs/design-system.md](docs/design-system.md). چک‌لیست مهاجرت افزونه‌های قدیمی: [docs/migrating.md](docs/migrating.md).

## ریلیز

نسخه را در هدر، `HTW_*_VERSION`، `readme.txt`، `AI-GUIDE.md` و `package.json` بالا ببرید و روی `main` تگ `vX.Y.Z` بزنید. راهنما: [docs/releasing.md](docs/releasing.md).

---

## English

Shared starter for [How to WP](https://howtowp.ir) plugins. Each product is **one** install: Bluwp 0.3.0 is embedded in `lib/bluwp` under the PHP namespace `Howtowp\Bluwp` (`>= 0.3.0` and `< 0.4.0`). A site that already runs the standalone Bluwp plugin keeps using that copy when it is active and on the same line. The admin UI is React + [VibeFarsi](https://vibefarsi.ir/components) (MIT), built with Vite. WordPress's React is reused via `wp-element`.

### Quick start

```bash
git clone git@github.com:howtowp/my-plugin.git && cd my-plugin
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp   # slug, name, namespace, short prefix
cd my-plugin && composer install
composer lint && composer phpcs && composer phpstan && composer test
php ../scripts/check-catalog.php
```

Bluwp `0.3.0` ships in `lib/bluwp` (`Howtowp\Bluwp`). Refresh it with `./scripts/update-bluwp.sh <path-or-tgz>`. Build the admin with `cd plugin-template && npm ci && npm run build`. See [docs/design-system.md](docs/design-system.md). A plugin that already consumes this template must follow [CONTRIBUTING.md](CONTRIBUTING.md).

### What you get

- Embedded Bluwp 0.3.0 in `lib/bluwp`, namespace `Howtowp\Bluwp`, booted with `BluwpBridgeV1::consume()`. No `Requires Plugins: bluwp`.
- Bluwp layout: `bootstrap/`, `pages/`, `routes/`, `app/`, `src/Text.php`, `hooks/early/`, `crons/`, `database/migrations/`, `assets/`.
- One schema-driven settings page, one REST route with the **same** capability constant and a `wp_rest` nonce.
- HPOS and checkout-blocks compatibility declared with `HTW_PT_FILE` (the real main file, not a hardcoded name).
- `scripts/init.sh` renames slug, namespace, and prefix. `scripts/check-catalog.php` keeps the header, `readme.txt`, `package.json`, and `AI-GUIDE.md` `Plugin-Version` in lockstep. `product.json` has no version field.
- Release workflow, Plugin Check, security rules, and `product.schema.json` stay.

English is the source language. Persian (`fa_IR`) ships in `languages/`. The admin `dir` follows the site locale: a Latin system font in LTR, Vazirmatn in RTL. Rebuild catalogs with `cd plugin-template && npm run i18n` (WP-CLI required). How to add a language, and the checklist for plugins that already use this template: [CONTRIBUTING.md](CONTRIBUTING.md).

IranSans redistribution is an open licensing question. Migration checklist: [docs/migrating.md](docs/migrating.md). Release guide: [docs/releasing.md](docs/releasing.md).

License: GPL-2.0-or-later. © How to WP.
