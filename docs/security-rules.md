# قوانین امنیت و استاندارد کدنویسی افزونه‌های How to WP

> **پیش‌نویس:** این سند بر پایه‌ی راهنمای رسمی وردپرس (Plugin Handbook، Security، WPCS) و چارچوبی که برای قالب توافق شد نوشته شده است. متن اصلی «قوانین امنیت و استاندارد» کاربر هنگام نوشتن این نسخه در دسترس نبود؛ **قبل از انتشار نهایی باید با متن اصلی ادغام شود** (جدول‌ها و اولویت‌ها را با نسخه‌ی اصلی تطبیق دهید).

مبنا: **PHP ≥ 8.1** و **WordPress ≥ 6.8**. ساختار کد: `src/` با PSR-4 و namespace مثل `HowToWP\PluginTemplate\` (نه `includes/`). پیشوند همه‌چیز: `htw_pt_` / `HTW_PT_`.

## ۱. قانون طلایی ۴ لایه

هر داده‌ای که از بیرون می‌آید (فرم، URL، REST، AJAX، Ability، option، API خارجی) از این مسیر می‌گذرد، به همین ترتیب:

| لایه | سؤال | ابزار |
| --- | --- | --- |
| ۱. **Validate** (اعتبارسنجی) | آیا اصلاً مجاز و درست است؟ | `isset`، `in_array( …, true )`، `is_email`، `absint`، `wp_verify_nonce`، `current_user_can` |
| ۲. **Sanitize** (پاک‌سازی) | قبل از ذخیره/استفاده تمیزش کن | `sanitize_text_field( wp_unslash( … ) )` و هم‌خانواده‌ها |
| ۳. **Prepare** (آماده‌سازی) | قبل از رفتن به دیتابیس/فایل/پوسته | `$wpdb->prepare()`، `$wpdb->insert/update` با فرمت، `wp_json_encode` |
| ۴. **Escape** (خروجی) | در لحظه‌ی چاپ، نه زودتر | `esc_html`، `esc_attr`، `esc_url`، `esc_js`، `wp_kses_post` |

قاعده‌ها: ورودی را **هیچ‌وقت** مستقیم از `$_GET/$_POST/$_REQUEST/$_SERVER` استفاده نکن (اول `isset` و `wp_unslash` سپس sanitize). خروجی را **دیر** escape کن (در همان خطی که چاپ می‌شود). هیچ لایه‌ای جای لایه‌ی دیگر را نمی‌گیرد.

## ۲. جدول Sanitize / Validate

| نوع داده | تابع |
| --- | --- |
| متن تک‌خطی | `sanitize_text_field( wp_unslash( $v ) )` |
| متن چندخطی | `sanitize_textarea_field( wp_unslash( $v ) )` |
| ایمیل | `sanitize_email()` + `is_email()` |
| URL | `esc_url_raw()` برای ذخیره |
| عدد صحیح | `absint()` / `(int)` |
| کلید/اسلاگ | `sanitize_key()` / `sanitize_title()` |
| نام فایل | `sanitize_file_name()` |
| HTML مجاز | `wp_kses( $v, $allowed )` / `wp_kses_post()` |
| انتخاب از فهرست | `in_array( $v, $allowed, true )` |
| JSON | `json_decode` + اعتبارسنجی ساختار + sanitize تک‌تک مقدارها |

## ۳. جدول Escape

| کجا چاپ می‌شود | تابع |
| --- | --- |
| متن در HTML | `esc_html()` / `esc_html__()` / `esc_html_e()` |
| مقدار attribute | `esc_attr()` |
| لینک/src | `esc_url()` |
| داخل JavaScript | `wp_json_encode()` یا `esc_js()` |
| HTML مجاز | `wp_kses_post()` / `wp_kses()` |
| textarea | `esc_textarea()` |

استثنا فقط با `// phpcs:ignore <Code> -- دلیل` و وقتی تابع خودش خروجی امن می‌دهد (مثل `get_block_wrapper_attributes()`).

## ۴. nonce و دسترسی

- هر عملی که چیزی را **تغییر می‌دهد**: هم `nonce` (`wp_nonce_field` / `check_admin_referer` / `check_ajax_referer`) و هم `current_user_can( '<capability مشخص>' )`. nonce جای capability را نمی‌گیرد.
- از `manage_options` فقط برای تنظیمات سایت استفاده کن؛ برای بقیه capability دقیق‌تر.
- Settings API (`register_setting` + `settings_fields`) خودش nonce و capability را مدیریت می‌کند؛ تابع `sanitize_callback` را حتماً بنویس.

## ۵. دیتابیس

- همیشه `$wpdb->prepare()` با placeholderهای `%s %d %f`؛ نام جدول/ستون هرگز از ورودی کاربر نیاید (فهرست سفید).
- برای `LIKE` از `$wpdb->esc_like()` سپس `prepare`.
- ترجیحاً APIهای وردپرس (`WP_Query`، `get_option`، `update_post_meta`) به‌جای SQL خام. سفارش‌های ووکامرس: `wc_get_order()` (سازگار با HPOS).
- جدول سفارشی فقط با `dbDelta` و نسخه‌ی اسکیما (`Installer` / `Upgrader`).

## ۶. REST API و Abilities API

**REST:** هر route یک `permission_callback` واقعی دارد. `__return_true` فقط برای داده‌ی واقعاً عمومی و فقط‌خواندنی. `args` با `validate_callback` و `sanitize_callback`. خروجی را فقط از فیلدهای مجاز بساز.

**Abilities API (وردپرس 6.9+):** `permission_callback` واقعی و سخت‌گیرانه (هرگز برای نوشتن/حذف «کاملاً باز» نباشد)؛ `input_schema`/`output_schema` کامل؛ `meta.annotations` (`readonly` / `destructive` / `idempotent`) را درست تعیین کن؛ `meta.show_in_rest` و `meta.mcp.public` فقط برای چیزی که واقعاً باید به ایجنت‌ها نشان داده شود. توانایی‌های نوشتنی را به `Services/` بسپار و در آن‌ها هم دوباره capability را چک کن.

## ۷. AJAX و فرم‌ها

- `wp_ajax_{action}` با `check_ajax_referer` + capability. نسخه‌ی `nopriv` فقط وقتی لازم است و با محدودیت و اعتبارسنجی بیشتر.
- پاسخ با `wp_send_json_success/error` (آن‌ها خودشان خاتمه می‌دهند).
- ریدایرکت با `wp_safe_redirect()` سپس `exit`.

## ۸. `exit` / `die`

`die`، `wp_die` و `exit` برای کنترل جریان در کد عادی ممنوع است. فقط این موارد مجاز است:

1. محافظ ابتدای فایل: `defined( 'ABSPATH' ) || exit;`
2. محافظ `uninstall.php`: `defined( 'WP_UNINSTALL_PLUGIN' ) || exit;`
3. بلافاصله بعد از `wp_safe_redirect()`
4. بعد از پاسخ AJAX با `wp_send_json_*` (اگر خودکار خاتمه نداده باشد)
5. بعد از استریم یک فایل دانلودی

خطاها را با `WP_Error` یا exception برگردان، نه با `die`.

## ۹. اسکریپت و داده‌ی سمت کلاینت

- ترجیحاً `wp_add_inline_script()` با داده‌ی `wp_json_encode()` شده؛ برای بلوک‌های تعاملی `wp_interactivity_state()` / `wp_interactivity_data_wp_context()`؛ برای ماژول‌های اسکریپت داده‌ی `script module` — به‌جای `wp_localize_script()` که برای ترجمه طراحی شده بود.
- هیچ‌وقت راز، کلید API یا داده‌ی خصوصی کاربر دیگر در خروجی صفحه نیاید.
- اسکریپت/استایل فقط جایی enqueue شود که استفاده می‌شود؛ نسخه‌ی افزونه را برای cache-busting بده.

## ۱۰. فایل‌ها، آپلود و دانلود

- آپلود: `wp_handle_upload` / `wp_check_filetype_and_ext`، فهرست سفید پسوند و MIME، اندازه‌ی مجاز.
- مسیرها: هیچ‌وقت مسیر خام کاربر؛ `realpath` + بررسی اینکه داخل پوشه‌ی مجاز است.
- دانلود: capability + nonce/امضا، هدرهای درست، بعد از استریم `exit`.
- `file_get_contents` روی URL ممنوع؛ از `wp_remote_get/post` با timeout و بررسی `is_wp_error` استفاده کن.

## ۱۱. ساختار و فایل‌های محافظ

- هر فایل PHP: `defined( 'ABSPATH' ) || exit;` (به‌جز `uninstall.php` که گاردش `WP_UNINSTALL_PLUGIN` است و `index.php`های سکوت).
- در ریشه‌ی افزونه و هر زیرپوشه‌ی داخل آن یک `index.php` با محتوای `<?php // Silence is golden.` است (پوشه‌ی `build/` را `npm run build` از طریق `postbuild` پر می‌کند).
- `uninstall.php` اولین دستورش `defined( 'WP_UNINSTALL_PLUGIN' ) || exit;` است و فقط در صورت انتخاب کاربر داده را پاک می‌کند.
- هدر `Update URI: https://howtowp.ir` را نگه دار تا افزونه‌ی هم‌نام از مخزن وردپرس رویش نوشته نشود.
- کلاس‌ها در `src/` با PSR-4؛ یک کلاس در هر فایل؛ `final` مگر دلیل برای ارث‌بری باشد.

## ۱۲. سایر قوانین

- `extract()`، `eval()`، `create_function`، `unserialize()` روی ورودی کاربر، `$_REQUEST` و `error_reporting()` ممنوع.
- لاگ: فقط با `Support\Logger` و بدون راز/داده‌ی شخصی.
- وابستگی Composer در زیپ: فقط runtime، با پیشوند (Strauss) اگر ممکن است تداخل ایجاد کند.
- هوش مصنوعی: فقط `wp_ai_client_prompt()` با feature-detect؛ **هیچ کلید API‌ای ذخیره یا درخواست نشود**؛ خروجی مدل مثل ورودی نامطمئن است (sanitize/escape).
- هیچ توکن، `.env`، کلید خصوصی یا داده‌ی مشتری وارد گیت نشود.

## ۱۳. راهنمای اولویت رفع مشکل

| اولویت | نوع | مثال | اقدام |
| --- | --- | --- | --- |
| **P0 — فوری** | اجرای کد/تزریق/دور زدن دسترسی | SQL بدون `prepare`، route نوشتنی بدون دسترسی، آپلود بدون بررسی، افشای راز | قبل از هر کار دیگر؛ انتشار متوقف |
| **P1 — بالا** | XSS/CSRF، نبود nonce یا capability | چاپ بدون escape، فرم بدون nonce | همین نسخه |
| **P2 — متوسط** | ورودی بدون sanitize، `permission_callback` ضعیف روی خواندن | `$_POST` بدون `wp_unslash` | نسخه‌ی بعدی |
| **P3 — استاندارد** | WPCS، docblock، Yoda، نام‌گذاری، i18n | خطاهای phpcs | در جریان کار عادی |
| **P4 — بهبود** | خوانایی، تست، مستندات | طول خط > ۱۲۰ | هر وقت فرصت بود |

## ۱۴. ابزارها

`composer phpcs` (WPCS 3.4 + PHPCompatibilityWP، `testVersion 8.1-`)، `composer phpstan`، `composer test`، Plugin Check در CI. نتیجه‌ی سبز این‌ها شرط «Definition of Done» در `AGENTS.md` است.
