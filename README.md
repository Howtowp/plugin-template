# Plugin Template — قالب افزونه‌ی وردپرس

**فارسی** · [English](#english)

قالب مشترک افزونه‌های [How to WP](https://howtowp.ir). ظاهر پیشخوان از **Bluwp** و کیت **bluwp-vuejs** می‌آید (همان سیستم «درگاه پرداخت هوشمند»)، نه از CSS کپی‌شده داخل هر افزونه. برای افزونه‌ی جدید: Use this template، سپس `scripts/init.sh`.

## شروع سریع

```bash
git clone git@github.com:howtowp/my-plugin.git && cd my-plugin
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp
cd my-plugin && composer install
composer lint && composer phpcs && composer phpstan && composer test
php ../scripts/check-catalog.php
```

روی سایت، افزونه‌ی Bluwp با نسخه‌ی `>= 0.2.1` و `< 0.3` باید فعال باشد. ساخت فایل‌های پیشخوان (وقتی صفحه‌ی تنظیمات را باز می‌کنید) در [docs/design-system.md](docs/design-system.md) است. `npm run build` بدون checkout کیت فقط هشدار می‌دهد و خارج می‌شود.

## ساختار

افزونه‌ی قابل‌انتشار فقط داخل **یک زیرپوشه** است. `docs/`، `data/`، `design/`، `research/`، `scripts/` و `dev/` داخل zip نمی‌روند.

```
my-plugin/
├── my-plugin/                 خود افزونه (نام پوشه = slug)
│   ├── my-plugin.php          هدر، ثابت‌ها، HTW_*_FILE = __FILE__
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

مجوز بازتوزیع فونت IranSans هنوز باز است. جزئیات: [docs/design-system.md](docs/design-system.md). چک‌لیست مهاجرت افزونه‌های قدیمی: [docs/migrating.md](docs/migrating.md).

## ریلیز

نسخه را در هدر، `HTW_*_VERSION`، `readme.txt`، `AI-GUIDE.md` و `package.json` بالا ببرید و روی `main` تگ `vX.Y.Z` بزنید. راهنما: [docs/releasing.md](docs/releasing.md).

---

## English

Shared starter for [How to WP](https://howtowp.ir) plugins. Admin UI comes from **Bluwp** and the **bluwp-vuejs** kit (the same system as the smart-gateway plugin). Plugins do not vendor a copy of the kit CSS.

### Quick start

```bash
git clone git@github.com:howtowp/my-plugin.git && cd my-plugin
./scripts/init.sh my-plugin "My Plugin" MyPlugin mp   # slug, name, namespace, short prefix
cd my-plugin && composer install
composer lint && composer phpcs && composer phpstan && composer test
php ../scripts/check-catalog.php
```

The site needs Bluwp `>= 0.2.1` and `< 0.3`. Building the admin bundle is documented in [docs/design-system.md](docs/design-system.md). `npm run build` exits 0 with a warning when the kit checkout is absent.

### What you get

- Bluwp layout: `bootstrap/`, `pages/`, `routes/`, `app/`, `src/Text.php`, `hooks/early/`, `crons/`, `database/migrations/`, `assets/`.
- One schema-driven settings page, one REST route with the **same** capability constant and a `wp_rest` nonce.
- HPOS and checkout-blocks compatibility declared with `HTW_PT_FILE` (the real main file, not a hardcoded name).
- `scripts/init.sh` renames slug, namespace, and prefix. `scripts/check-catalog.php` keeps the header, `readme.txt`, `package.json`, and `AI-GUIDE.md` `Plugin-Version` in lockstep. `product.json` has no version field.
- Release workflow, Plugin Check, security rules, and `product.schema.json` stay.

IranSans redistribution is an open licensing question. Migration checklist: [docs/migrating.md](docs/migrating.md). Release guide: [docs/releasing.md](docs/releasing.md).

License: GPL-2.0-or-later. © How to WP.
