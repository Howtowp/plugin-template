# Plugin Template — قالب افزونه‌ی وردپرس

**فارسی** · [English](#english)

قالب استاندارد و آماده‌ی ساخت افزونه‌های [How to WP](https://howtowp.ir). برای هر افزونه‌ی جدید روی «Use this template» بزنید، اسکریپت `init.sh` را اجرا کنید و شروع کنید.

## شروع سریع

```bash
# ۱) در گیت‌هاب: Use this template → ریپوی جدید (برای محصول‌های فروشی: Private)
git clone git@github.com:howtowp/my-plugin.git && cd my-plugin

# ۲) تغییر نام‌ها: slug، نام افزونه، namespace، پیشوند کوتاه
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp

# ۳) بیلد و بررسی
cd my-plugin && composer install && npm install && npm run build
composer lint && composer phpcs && composer phpstan && composer test
```

## ساختار پروژه (پیشنهادی)

افزونه‌ی قابل‌انتشار فقط داخل **یک زیرپوشه** است؛ بقیه‌ی پروژه (مستندات، دیتا، طراحی، تحقیق، اسکریپت) کنارش می‌ماند و **هیچ‌وقت داخل zip نمی‌رود**.

```
my-plugin/                  ریشه‌ی ریپو = پروژه
├── my-plugin/              خود افزونه (نام پوشه = slug = ریشه‌ی zip)
│   ├── my-plugin.php  AI-GUIDE.md  src/  blocks/  templates/  assets/  languages/  tests/
│   └── composer.json  package.json  readme.txt  LICENSE  uninstall.php  …
├── docs/  data/  design/  research/  scripts/  dev/
├── AGENTS.md  CLAUDE.md  .cursor/rules/
├── product.json            bilingual store copy (not in the zip)
├── product.schema.json     convenience copy; canonical schema is in howtowp/.github
├── .release.yml            plugin_dir: my-plugin
└── .github/workflows/      release.yml · plugin-check.yml
```

اگر فقط خود افزونه را دارید (بدون پروژه)، **چیدمان ساده** هم پشتیبانی می‌شود: فایل‌های افزونه مستقیم در ریشه‌ی ریپو باشند و در `.release.yml` کلید `plugin_dir` را حذف کنید. جزئیات: [docs/wordpress-release.md](https://github.com/howtowp/.github/blob/main/docs/wordpress-release.md).


## محتوای محصول (`product.json` و `AI-GUIDE.md`)

- `product.json` در ریشهٔ ریپو: متن دوزبانهٔ صفحهٔ محصول (ویژگی‌ها، کاربردها، FAQ و غیره). داخل zip نمی‌رود؛ بعداً به‌عنوان asset به Release وصل می‌شود. اسکیمای مرجع: [Howtowp/.github/schemas/product.schema.json](https://github.com/Howtowp/.github/blob/main/schemas/product.schema.json).
- `<slug>/AI-GUIDE.md`: راهنمای مشتری و وایب‌کدر برای گسترش افزونه با Cursor یا Claude. این فایل **باید** داخل zip باشد. با `AGENTS.md` (توسعهٔ داخلی) فرق دارد.

قیمت، نسخه، changelog و Requires از این فایل‌ها خوانده نمی‌شوند.

## ریلیز

نسخه را در هدر افزونه، `HTW_*_VERSION`، `readme.txt`، `package.json` و `CHANGELOG.md` بالا ببرید، روی `main` تگ `vX.Y.Z` بزنید. GitHub Actions فایل `<slug>-X.Y.Z.zip` را می‌سازد و به Release اضافه می‌کند. آپدیت خودکار داخل پیشخوان وجود ندارد؛ مشتری از howtowp.ir دانلود می‌کند. راهنمای کامل: [docs/releasing.md](docs/releasing.md).

## ویژگی‌ها

PSR-4 و ماژول‌ها · صفحه‌ی تنظیمات (Settings API) · REST · Abilities API (برای ایجنت‌های AI/MCP) · AI Client اختیاری · سازگاری HPOS ووکامرس · بلاک تعاملی (Interactivity API) · Plugin Check و PHPCS و PHPStan · نمونه‌ی Playground · `AGENTS.md` برای ایجنت‌های کدنویسی.

---

## English

Standard, ready-to-use skeleton for [How to WP](https://howtowp.ir) plugins. Click **Use this template**, run `scripts/init.sh`, start building.

### Quick start

```bash
git clone git@github.com:howtowp/my-plugin.git && cd my-plugin
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp   # slug, name, namespace, short prefix
cd my-plugin && composer install && npm install && npm run build
composer lint && composer phpcs && composer phpstan && composer test
```

### Layouts

- **Project layout (default):** the shippable plugin is a subfolder (`<slug>/`); `docs/`, `data/`, `design/`, `research/`, `scripts/`, `dev/` live beside it and never ship. `.release.yml` sets `plugin_dir: <slug>` and `copy_root_files: false`.
- **Simple layout:** plugin files at the repo root; omit `plugin_dir` in `.release.yml`.

See [docs/wordpress-release.md](https://github.com/howtowp/.github/blob/main/docs/wordpress-release.md) for the full schema.

### What is inside

PSR-4 modules (`Core`, `Admin`, `Frontend`, `Rest`, `Abilities`, `AI`, `Integrations`, `Services`, `Repositories`, `Support`), Settings API page, REST route, Abilities API sample (WP 6.9+), optional AI Client stub (WP 7.0+, no API keys), WooCommerce HPOS/Checkout-blocks declaration, Interactivity-API block built with `@wordpress/scripts`, PHPCS (WPCS 3.4), PHPStan, PHPUnit, Plugin Check CI, Playground blueprint, `.wp-env.json`, and `AGENTS.md` for AI coding agents.


### Product content (`product.json` and `AI-GUIDE.md`)

- `product.json` at the repo root: bilingual store copy (features, use cases, FAQ, …). It does **not** ship in the plugin zip; it is attached to the GitHub Release later. Canonical schema: [Howtowp/.github/schemas/product.schema.json](https://github.com/Howtowp/.github/blob/main/schemas/product.schema.json).
- `<slug>/AI-GUIDE.md`: customer / vibe-coder guide for extending the plugin with Cursor or Claude. It **must** ship in the zip. This is not `AGENTS.md` (internal development).

Price, version, changelog and requirement headers do not belong in `product.json`.

### Release

Bump the version everywhere, tag `vX.Y.Z` on `main`; the reusable workflow in [`howtowp/.github`](https://github.com/howtowp/.github) builds `<slug>-X.Y.Z.zip` and publishes a Release. There is no in-dashboard auto-updater. Full guide: [docs/releasing.md](docs/releasing.md).

License: GPL-2.0-or-later. © How to WP.
