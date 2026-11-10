# انتشار نسخه (Release) — راهنمای انسان‌ها و ایجنت‌های کدنویسی

> English summary at the bottom.

نسخه‌ها فقط از مسیر **تگ گیت → GitHub Actions → Release** منتشر می‌شوند. هیچ‌وقت zip را دستی نساز و آپلود نکن، و آپدیت خودکار داخل پیشخوان وردپرس وجود ندارد؛ مشتری از **howtowp.ir** دانلود می‌کند و بخش دانلود سایت همیشه آخرین Release را نشان می‌دهد (لازم نیست چیزی دستی روی سایت عوض شود).

## ۱. قبل از شروع

- روی شاخه‌ی `dev` کار کن (یا شاخه‌های `feat/…` و `fix/…` که به `dev` می‌آیند). `main` همیشه قابل انتشار است.
- نسخه‌گذاری [SemVer](https://semver.org): `MAJOR.MINOR.PATCH` (رفع باگ = patch، قابلیت جدید سازگار = minor، تغییر ناسازگار = major). نسخه‌ی آزمایشی: `1.2.0-beta` (یا `-beta.1`)؛ پسوند دلخواه، Release به‌صورت **prerelease** علامت می‌خورد.
- تغییرهای فقط‌مستندات (docs، README، AGENTS، قوانین Cursor، …) که چیزی از پوشه‌ی افزونه را عوض نمی‌کنند **نیازی به نسخه‌ی جدید ندارند**؛ فقط به `main` برسان.

## ۲. بالا بردن نسخه (همه‌ی جاها با هم)

نسخه‌ی جدید را `X.Y.Z` بنامیم. این‌ها باید **دقیقاً یکی** باشند:

| فایل | چه چیزی |
| --- | --- |
| `plugin-template/plugin-template.php` | هدر `Version:` (تگ باید با این برابر باشد) |
| `plugin-template/plugin-template.php` | ثابت `HTW_PT_VERSION` |
| `plugin-template/readme.txt` | `Stable tag:` و ورودی `== Changelog ==` / `== Upgrade Notice ==` |
| `plugin-template/package.json` (و `package-lock.json` با `npm install --package-lock-only`) | `version` (اگر وجود دارد) |
| `plugin-template/blocks/*/block.json` | `version` (اگر وجود دارد) |
| `dev/blueprint.json` | آدرس zip نسخه (اگر استفاده می‌کنی) |
| `CHANGELOG.md` | ورودی جدید به سبک [Keep a Changelog](https://keepachangelog.com) |

نمونه‌ی `CHANGELOG.md`:

```md
## [Unreleased]

## [X.Y.Z] - YYYY-MM-DD
### Added
- …
### Changed
- …
### Fixed
- …
```

تست `composer test` برابر بودن هدر و `HTW_PT_VERSION` را چک می‌کند. بعد از `scripts/init.sh` نام ثابت‌ها عوض می‌شود (`HTW_<PREFIX>_VERSION`).

## ۳. چک‌ها

داخل پوشه‌ی افزونه:

```bash
composer lint && composer phpcs && composer phpstan && composer test
npm ci && npm run build
```

همه باید سبز باشند. Plugin Check هم روی PR/Push به `dev` در Actions اجرا می‌شود.

## ۴. ادغام و تگ

```bash
git checkout main && git pull
git merge --ff-only dev        # یا PR از dev به main
git push origin main

git tag -a vX.Y.Z -m "vX.Y.Z"  # تگ باید با Version هدر برابر باشد (با v ابتدایی)
git push origin vX.Y.Z
```

- تگ **باید** `vX.Y.Z` و برابر `Version:` هدر باشد (`v1.2.0-beta` برای `Version: 1.2.0-beta`).
- تگ را روی `main` بزن، نه `dev`.
- **push تگ همان انتشار است:** Actions فوراً شروع می‌شود.

## ۵. بعد از push تگ (خودکار)

`.github/workflows/release.yml` workflow مشترک [`howtowp/.github`](https://github.com/howtowp/.github) را صدا می‌زند:

1. تگ را checkout می‌کند و `.release.yml` را می‌خواند (`plugin_dir`، `node`، `composer`، `keep`، …).
2. در صورت نیاز `npm ci && npm run build` (بلوک‌ها → `build/` و `index.php`ها) و Composer را اجرا می‌کند.
3. مطمئن می‌شود تگ با `Version:` هدر برابر است؛ **فقط پوشه‌ی افزونه** را zip می‌کند (ریشه‌ی zip = `plugin-template/`)، فایل‌های dev و `docs/data/design/research/scripts` را بیرون می‌گذارد و zip را بررسی می‌کند.
4. Release را با فایل `plugin-template-X.Y.Z.zip` منتشر می‌کند (پسوند `-beta` → prerelease).
5. بخش دانلود howtowp.ir همین Release را به مشترکین نشان می‌دهد.

وضعیت را در تب **Actions** و صفحه‌ی **Releases** ببین.

### اجرای دستی (dry-run)

تب Actions → «Release» → Run workflow: ورودی `tag` (تگ **موجود**) و `dry_run` (پیش‌فرض **true**) را بده. فقط می‌سازد، بررسی می‌کند و zip را به‌صورت artifact برمی‌گرداند؛ Release نمی‌سازد. چون تگ لازم است، برای تمرین قبل از انتشار از چک‌های بخش ۳ استفاده کن؛ dry-run برای بازبینی تگ‌های موجود است. با `dry_run=false` می‌توان zip یک تگ موجود را دوباره ساخت/جایگزین کرد (تگ جابه‌جا نمی‌شود).

## ۶. اگر انتشار خراب شد

- **خطای «tag does not match Version»** (هماهنگ نبودن نسخه): Release ساخته نشده است. نسخه را در فایل‌ها درست کن (`dev` → `main`)، سپس تگ اشتباه را حذف و دوباره بزن — **فقط اگر Release منتشر نشده باشد**:
  ```bash
  git push origin :refs/tags/vX.Y.Z && git tag -d vX.Y.Z
  git tag -a vX.Y.Z -m "vX.Y.Z" && git push origin vX.Y.Z
  ```
- **خطای دیگر در build/بررسی** (مثلاً `keep` یا اندازه): علت را در لاگ Actions درست کن؛ اگر Release ساخته نشده بود همین روش حذف و تگ مجدد. اگر ساخته شده بود، تگ را جابه‌جا نکن.
- **Release قبلاً منتشر و مشتریان ممکن است دانلود کرده باشند:** تگ را عوض نکن؛ نسخه‌ی patch بعدی (`X.Y.Z+1`) منتشر کن.

## ۷. ممنوع

- ساختن zip دستی و آپلود روی Release یا سایت.
- commit کردن zip، `vendor/` توسعه (dev)، `node_modules/`، `build/`، `.env`، توکن و کلید، داده‌ی مشتری.
- حذف/جابه‌جایی تگی که Release منتشرشده دارد؛ force-push روی `main`.
- ساخت تگ از داخل اتوماسیون/ایجنت بدون دستور صریح صاحب پروژه.

## ۸. افزودن به پروژه‌های قدیمی (پرامپت برای Cursor)

این متن را در Cursor (داخل ریپوی پروژه‌ی قدیمی) بگذار:

```text
این پروژه یک افزونه/قالب وردپرس How to WP است. مستندات و قوانین انتشار نسخه را به همان سبک قالب استاندارد اضافه کن. فقط فایل‌های مستند/قانون را اضافه یا ویرایش کن؛ کد افزونه، .release.yml و workflow را تغییر نده و نسخه را بالا نبر.

۱) فایل‌های مرجع را از ریپوی عمومی قالب بخوان:
   https://raw.githubusercontent.com/howtowp/plugin-template/main/docs/releasing.md
   https://raw.githubusercontent.com/howtowp/plugin-template/main/.cursor/rules/release.mdc
   و بخش «Release» در
   https://raw.githubusercontent.com/howtowp/plugin-template/main/AGENTS.md
۲) ساختار این پروژه را بررسی کن: چیدمان ساده (افزونه در ریشه) یا پروژه (plugin_dir در .release.yml)؛ slug، فایل اصلی، نام ثابت نسخه، وجود package.json/block.json/readme.txt/CHANGELOG.md.
۳) docs/releasing.md را با همان محتوا بساز، ولی نام‌ها و مسیرها را با این پروژه تطبیق بده (slug، مسیر فایل اصلی، نام ثابت نسخه، فقط فایل‌هایی که واقعاً وجود دارند). اگر پوشه‌ی docs نیست بساز.
۴) .cursor/rules/release.mdc را اضافه کن (agent-requestable، alwaysApply: false، description: "when bumping version / releasing") با همان قوانین و مسیرهای این پروژه.
۵) در AGENTS.md (اگر نیست بساز؛ CLAUDE.md هم فقط به آن ارجاع دهد) یک بخش مختصر «Release» اضافه کن که به docs/releasing.md ارجاع می‌دهد و شامل این‌هاست: کار روی dev، بالا بردن همه‌ی جاهای نسخه، CHANGELOG، merge به main، تگ vX.Y.Z برابر Version هدر (پسوند -beta = prerelease) و push تگ، ساخت خودکار Release توسط Actions، دانلود مشتری از howtowp.ir، ممنوعیت zip/vendor dev/node_modules/.env/توکن و آپلود دستی zip، و رفع خطای ناهماهنگی نسخه با حذف تگ اشتباه (فقط اگر Release منتشر نشده) و تگ مجدد.
۶) در CHANGELOG.md زیر [Unreleased] یک خط «docs: release process» بنویس (اگر فایل هست). هیچ توکن/رازی ننویس.
۷) تغییرها را روی شاخه‌ی dev commit کن (پیام: "docs: add release process") و push کن؛ merge به main و تگ نزن، چون تغییر فقط مستندات است.
در پایان بگو چه فایل‌هایی اضافه یا ویرایش شد.
```

---

## English summary

Releases happen only via **git tag → GitHub Actions → Release**. Work on `dev`, bump the plugin header `Version:`, the `HTW_PT_VERSION` constant, `readme.txt` Stable tag, `package.json` / `block.json` (if present) and add a Keep-a-Changelog entry to `CHANGELOG.md`. Run `composer lint/phpcs/phpstan/test` and `npm run build`. Merge `dev` to `main`, then `git tag -a vX.Y.Z` (must equal the header Version; a `-beta` suffix makes a prerelease) and push the tag. The reusable workflow in `howtowp/.github` builds the zip per `.release.yml` (only the plugin folder, root folder `<slug>/`), verifies it and publishes the Release; howtowp.ir serves the latest Release to subscribers. A manual `workflow_dispatch` run in the Actions tab is a dry-run by default (it needs an existing tag). Never commit zips, dev `vendor/`, `node_modules/`, `.env` or tokens, and never upload zips by hand. If a release fails on a version mismatch, fix the version and re-tag; delete the wrong tag only if no Release was published, otherwise ship the next patch version. Docs-only changes need no release.
