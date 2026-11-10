# قوانین توسعه‌ی افزونه‌ی وردپرس (Security & Standards Ruleset)

> این فایل مجموعه‌قوانین اجباری برای هر کد PHP/JS است که در این پلاگین نوشته می‌شود.
> برای **Cursor**: نسخه‌ی خلاصه‌ی این قوانین در `.cursor/rules/wordpress.mdc` است (همیشه فعال).
> برای **Claude Code** و بقیه‌ی ایجنت‌ها: `CLAUDE.md` و `AGENTS.md` در ریشه‌ی پروژه به این فایل ارجاع می‌دهند.
>
> **پایه‌ی فنی:** PHP ≥ 8.1 و WordPress ≥ 6.8. کد افزونه در `src/` با PSR-4 و namespace مثل `HowToWP\PluginTemplate\` است (نه `includes/`). پیشوند همه‌چیز `htw_pt_` / `HTW_PT_` و text domain برابر slug (`plugin-template`) است؛ بعد از `scripts/init.sh` این نام‌ها عوض می‌شوند.
>
> **قانون طلایی**: هیچ کدی «تمام‌شده» محسوب نمی‌شود مگر اینکه از فیلتر ۴ لایه عبور کند:
> **Validate → Sanitize (ورودی) → Prepare (کوئری) → Escape (خروجی)**. اگر هر لایه جا افتاد، کد ناقص است.

---

## 0) اصول غیرقابل‌مذاکره (Non‑Negotiable)

این‌ها هرگز نباید نقض شوند. اگر رخ دادند، کد باید بازنویسی شود، نه patch:

1. **هیچ ورودی کاربر بدون sanitize** استفاده نمی‌شود (`$_POST`, `$_GET`, `$_REQUEST`, `$_SERVER`, `$_COOKIE`, بدنه‌ی REST/AJAX).
2. **هیچ خروجی بدون escape** چاپ نمی‌شود (حتی داده‌ای که «به نظر امن» است).
3. **هیچ کوئری SQL بدون `$wpdb->prepare()`** با ورودی متغیر ساخته نمی‌شود.
4. **هر فایل PHP** با ABSPATH guard شروع می‌شود.
5. **هر action تغییردهنده‌ی state** (POST/AJAX/REST) هم **nonce** و هم **capability check** دارد.
6. **هرگز `die`/`exit`/`wp_die`** برای کنترل جریان معمول استفاده نمی‌شود. `exit` فقط در پنج حالت مجاز است: (۱) ABSPATH guard، (۲) `WP_UNINSTALL_PLUGIN` guard در `uninstall.php`، (۳) بلافاصله بعد از `wp_safe_redirect()`، (۴) بعد از پاسخ AJAX با `wp_send_json_*`، (۵) بعد از استریم یک فایل دانلودی. بقیه‌ی خطاها با `WP_Error` یا exception برمی‌گردند.
7. **هر REST route و هر Ability** یک `permission_callback` واقعی دارد؛ برای عملیات نوشتن/حذف هرگز «کاملاً باز» نیست.

---

## 1) امنیت ورودی — Sanitization & Validation

هرگز به Superglobal مستقیم دست نزن. همیشه اول existence check، بعد unslash، بعد sanitize، بعد validate.

**❌ بد:**
```php
$name  = $_POST['name'];
$id    = $_GET['invoice_id'];
$email = $_POST['email'];
update_option( 'htw_pt_setting', $_POST['value'] );
```

**✅ خوب:**
```php
$name  = isset( $_POST['name'] )
    ? sanitize_text_field( wp_unslash( $_POST['name'] ) )
    : '';

$id    = isset( $_GET['invoice_id'] ) ? absint( $_GET['invoice_id'] ) : 0;

$email = isset( $_POST['email'] )
    ? sanitize_email( wp_unslash( $_POST['email'] ) )
    : '';
if ( ! is_email( $email ) ) {
    return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'plugin-template' ) );
}
```

**جدول تابع درست برای هر نوع داده:**

| نوع داده | تابع sanitize |
|---|---|
| متن تک‌خطی | `sanitize_text_field()` |
| متن چندخطی (textarea) | `sanitize_textarea_field()` |
| ایمیل | `sanitize_email()` |
| عدد صحیح مثبت (ID) | `absint()` |
| عدد صحیح | `intval()` |
| عدد اعشاری | `floatval()` |
| URL | `esc_url_raw()` |
| کلید/slug | `sanitize_key()` / `sanitize_title()` |
| نام فایل | `sanitize_file_name()` |
| HTML مجاز (محتوا) | `wp_kses_post()` |
| HTML با تگ‌های سفارشی | `wp_kses( $val, $allowed )` |
| آرایه | حلقه + sanitize روی هر عضو، یا `map_deep()` |
| رنگ hex | `sanitize_hex_color()` |
| JSON | `json_decode()` + اعتبارسنجی ساختار |

**قوانین تکمیلی:**
- همیشه `wp_unslash()` قبل از sanitize روی داده‌ی Superglobal، چون وردپرس به‌صورت خودکار slash می‌زند.
- **Validate ≠ Sanitize**. sanitize فرمت را تمیز می‌کند؛ validate منطق را چک می‌کند (مثلاً «این ID واقعاً در دیتابیس هست؟»، «این مقدار در whitelist مجاز است؟»).
- برای مقادیر با گزینه‌های محدود (dropdown/status)، از **whitelist** استفاده کن، نه sanitize:
  ```php
  $status = $_POST['status'] ?? '';
  $allowed = array( 'paid', 'pending', 'failed' );
  if ( ! in_array( $status, $allowed, true ) ) {
      $status = 'pending';
  }
  ```

---

## 2) امنیت خروجی — Output Escaping

**همیشه در آخرین لحظه‌ی چاپ escape کن (Late Escaping)**، حتی اگر مطمئنی داده امن است.

**❌ بد:**
```php
echo $invoice->title;
echo '<input value="' . $value . '">';
echo '<a href="' . $url . '">link</a>';
_e( $dynamic_text, 'plugin-template' ); // متن متغیر داخل ترجمه = ممنوع
```

**✅ خوب:**
```php
echo esc_html( $invoice->title );
echo '<input value="' . esc_attr( $value ) . '">';
echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'link', 'plugin-template' ) . '</a>';
echo wp_kses_post( $rich_content ); // فقط جایی که HTML مجاز لازم است
```

**جدول context درست:**

| کجا چاپ می‌شود | تابع escape |
|---|---|
| متن داخل HTML | `esc_html()` |
| مقدار attribute | `esc_attr()` |
| URL (href/src) | `esc_url()` |
| داخل تگ `<textarea>` | `esc_textarea()` |
| داخل JavaScript | `esc_js()` (ترجیحاً `wp_json_encode()`) |
| HTML مجاز و امن | `wp_kses_post()` یا `wp_kses()` |
| ترجمه + escape | `esc_html__()`, `esc_attr__()`, `esc_html_e()` |

**قوانین تکمیلی:**
- حتی خروجی `get_option()`, `$wpdb->get_var()`, و مقادیر از دیتابیس هم escape می‌شوند (دیتابیس منبع «امن» نیست).
- برای چاپ URL در attribute هم `esc_url()` کافی است.
- هرگز متن متغیر را به‌عنوان string اول `__()` / `_e()` نده؛ فقط literal.

---

## 3) امنیت دیتابیس — SQL & `$wpdb`

هر کوئری با ورودی متغیر **باید** از `prepare()` عبور کند. Concatenation ممنوع مطلق.

**❌ بد:**
```php
$wpdb->query( "DELETE FROM {$wpdb->prefix}invoices WHERE id = " . $id );
$results = $wpdb->get_results( "SELECT * FROM my_table WHERE status = '$status'" );
```

**✅ خوب:**
```php
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}invoices WHERE id = %d",
        $id
    )
);

$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}invoices WHERE status = %s",
        $status
    )
);
```

**قوانین تکمیلی:**
- placeholderها: `%d` عدد صحیح، `%f` اعشاری، `%s` رشته. دور `%s` علامت نقل‌قول **نگذار**؛ `prepare` خودش می‌گذارد.
- نام جدول و ستون قابل prepare نیست؛ آن‌ها را از whitelist بساز، هرگز از ورودی کاربر.
- برای `IN (...)` تعداد placeholder را داینامیک بساز:
  ```php
  $ids          = array_map( 'absint', $ids );
  $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
  $sql          = $wpdb->prepare( "SELECT * FROM $table WHERE id IN ($placeholders)", $ids );
  ```
- تا جای ممکن به‌جای کوئری خام از API وردپرس استفاده کن (`WP_Query`, `get_posts`, `get_option`, `update_post_meta`).
- برای `LIKE` از `$wpdb->esc_like()` استفاده کن: `'%' . $wpdb->esc_like( $term ) . '%'`.

---

## 4) CSRF و مجوز — Nonce & Capability

**هر** عملیات تغییردهنده (submit فرم، AJAX، REST، لینک حذف/ویرایش) باید **هم nonce و هم capability** داشته باشد. این دو جدا از هم‌اند: nonce می‌گوید «درخواست از فرم ما آمده»، capability می‌گوید «این کاربر اجازه دارد».

**✅ فرم ادمین:**
```php
// موقع رندر فرم:
wp_nonce_field( 'save_invoice_action', 'invoice_nonce' );

// موقع پردازش:
if (
    ! isset( $_POST['invoice_nonce'] ) ||
    ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['invoice_nonce'] ) ), 'save_invoice_action' )
) {
    return new WP_Error( 'bad_nonce', __( 'Security check failed.', 'plugin-template' ) );
}

if ( ! current_user_can( 'manage_options' ) ) {
    return new WP_Error( 'forbidden', __( 'You are not allowed to do this.', 'plugin-template' ) );
}
// خطا را با WP_Error برگردان و در لایه‌ی نمایش با wp_admin_notice() نشان بده (نه wp_die / die).
```

**قوانین تکمیلی:**
- capability را متناسب انتخاب کن: `manage_options` (تنظیمات)، `edit_posts`، `edit_others_posts`، یا capability اختصاصی. هرگز فقط `is_admin()` (که صرفاً می‌گوید در ناحیه‌ی ادمینیم، نه اینکه کاربر مجاز است).
- لینک‌های action در URL را با `wp_nonce_url()` بساز و با `check_admin_referer()` بررسی کن.
- هرگز به این تکیه نکن که «این صفحه فقط برای ادمین لود می‌شود» → همیشه صریح چک کن.

---

## 5) محافظ دسترسی مستقیم — ABSPATH Guard

اولین دستور **هر** فایل PHP (فایل اصلی پلاگین و فایل‌های دارای docblock هدر، بعد از آن docblock) این است. تنها استثناها: `uninstall.php` که guard آن `WP_UNINSTALL_PLUGIN` است، و فایل‌های `index.php` سکوت:

```php
<?php
defined( 'ABSPATH' ) || exit;
```

- این یکی از پنج استثنای مجاز برای `exit` است (بخش ۰ و ۸).
- در فایل‌های template/partial و `render.php` بلوک‌ها هم لازم است.
- هیچ فایلی نباید مستقیم از طریق URL قابل اجرا باشد.

---

## 6) مدیریت Asset — Enqueue

هرگز `<script>` یا `<link>` یا `<style>` را دستی در HTML چاپ نکن. همیشه از enqueue API استفاده کن.

**❌ بد:**
```php
echo '<link rel="stylesheet" href="' . plugins_url( 'css/admin.css', __FILE__ ) . '">';
echo '<script src="..."></script>';
```

**✅ خوب:**
```php
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    // فقط در صفحه‌ی خودمان لود کن، نه همه‌جا:
    if ( 'toplevel_page_plugin-template' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'plugin-template-admin',
        plugins_url( 'assets/css/admin.css', HTW_PT_FILE ),
        array(),
        HTW_PT_VERSION // نه false → برای cache busting
    );

    wp_enqueue_script(
        'plugin-template-admin',
        plugins_url( 'assets/js/admin.js', HTW_PT_FILE ),
        array( 'jquery' ),
        HTW_PT_VERSION,
        true // در footer
    );

    // انتقال داده به JS: wp_add_inline_script به‌جای wp_localize_script و به‌جای echo دستی.
    wp_add_inline_script(
        'plugin-template-admin',
        'window.htwPtData = ' . wp_json_encode(
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'htw_pt_ajax' ),
            )
        ) . ';',
        'before'
    );
} );
```

**قوانین تکمیلی:**
- version را همیشه از ثابت پلاگین بده (نه `false` و نه hardcode)، برای cache busting.
- asset را فقط در صفحه‌ی مربوطه لود کن (شرط `$hook`)، نه در کل ادمین.
- CSS/JS داخلی را با `wp_add_inline_style()` / `wp_add_inline_script()` اضافه کن، نه echo مستقیم.
- **`wp_localize_script()` برای انتقال داده ممنوع است** (فقط برای ترجمه طراحی شده بود). انتقال داده به جاوااسکریپت: `wp_add_inline_script()` با `wp_json_encode()`؛ در بلوک‌های تعاملی **`wp_interactivity_state()`** (state سراسری) و `wp_interactivity_data_wp_context()` (context هر بلوک)؛ برای script moduleها داده‌ی module یا state اینتراکتیویتی.
- بلوک‌ها در `blocks/<name>/` نوشته و با `npm run build` به `build/` کامپایل می‌شوند؛ ثبت آن‌ها با `block.json` (`render`, `viewScriptModule`) است، نه echo دستی.

---

## 7) امنیت AJAX و REST API

**AJAX:**
```php
add_action( 'wp_ajax_htw_pt_save', 'htw_pt_ajax_save' );

function htw_pt_ajax_save() {
    check_ajax_referer( 'htw_pt_ajax', 'nonce' ); // nonce
    if ( ! current_user_can( 'manage_options' ) ) {   // capability
        wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
    }

    $value = isset( $_POST['value'] )
        ? sanitize_text_field( wp_unslash( $_POST['value'] ) )
        : '';

    // ... کار ...

    wp_send_json_success( array( 'saved' => true ) ); // نه die/echo دستی
}
```

**REST API** — هر route باید `permission_callback` واقعی داشته باشد:
```php
register_rest_route( 'plugin-template/v1', '/invoice', array(
    'methods'             => 'POST',
    'callback'            => 'htw_pt_rest_create',
    'permission_callback' => function () {
        return current_user_can( 'manage_options' );
    },
    'args'                => array(
        'amount' => array(
            'required'          => true,
            'sanitize_callback' => 'absint',
            'validate_callback' => function ( $v ) { return $v > 0; },
        ),
    ),
) );
```

- هرگز `'permission_callback' => '__return_true'` برای عملیات حساس.

**Abilities API (وردپرس 6.9+)** — هر ability هم‌ردیف یک route REST است و همان سخت‌گیری را می‌خواهد:
```php
wp_register_ability(
    'plugin-template/site-info',
    array(
        'label'               => __( 'Get site info', 'plugin-template' ),
        'description'         => __( 'Read-only site information.', 'plugin-template' ),
        'category'            => 'plugin-template', // قبلاً روی wp_abilities_api_categories_init ثبت شده
        'output_schema'       => array( 'type' => 'object' ),
        'execute_callback'    => array( $this, 'execute_site_info' ),
        'permission_callback' => function () {
            return current_user_can( 'manage_options' ); // واقعی؛ نه __return_true
        },
        'meta'                => array(
            'show_in_rest' => true,
            'mcp'          => array( 'public' => true ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    )
);
```
- `permission_callback` واقعی با capability دقیق؛ برای ability که **می‌نویسد یا حذف می‌کند** هرگز کاملاً باز نیست و در لایه‌ی `Services/` هم دوباره capability چک می‌شود.
- `input_schema` / `output_schema` را کامل بنویس (JSON Schema)، ورودی را مثل ورودی REST sanitize/validate کن و `meta.annotations` را درست بگذار (`readonly` / `destructive` / `idempotent`).
- ثبت فقط روی `wp_abilities_api_init` و با guard `function_exists( 'wp_register_ability' )`؛ `show_in_rest` و `mcp.public` فقط برای چیزی که واقعاً باید به ابزارها و ایجنت‌ها نشان داده شود.
- برای action تفکیک کن: `wp_ajax_` (کاربر لاگین) و `wp_ajax_nopriv_` (مهمان). فقط جایی nopriv بده که واقعاً لازم است.

---

## 8) مدیریت خطا و پایداری — Error Handling

`die`/`exit` وسط منطق = ممنوع. کد باید graceful باشد.

**❌ بد:**
```php
if ( ! $invoice ) {
    die( 'Invoice not found' );
}
```

**✅ خوب:**
```php
if ( ! $invoice ) {
    return new WP_Error(
        'invoice_not_found',
        __( 'Invoice not found.', 'plugin-template' )
    );
}
// یا در context نمایش:
if ( ! $invoice ) {
    wp_admin_notice( __( 'Invoice not found.', 'plugin-template' ), array( 'type' => 'error' ) );
    return;
}
```

**قوانین تکمیلی:**
- برای خطاهای قابل‌بازیابی از `WP_Error` استفاده کن؛ همیشه `is_wp_error()` را چک کن.
- `try/catch` برای عملیاتی که ممکن است exception بدهند (I/O، parse، API خارجی).
- برای debug از `error_log()` استفاده کن، نه `var_dump`/`print_r` در خروجی. هرگز خروجی دیباگ در محیط production چاپ نشود.
- `exit` فقط در این پنج حالت مجاز است: (۱) ABSPATH guard، (۲) `WP_UNINSTALL_PLUGIN` guard در `uninstall.php`، (۳) redirect با `wp_safe_redirect()` که بعدش `exit;` استاندارد است، (۴) پایان AJAX با `wp_send_json_*`، (۵) بعد از استریم یک فایل دانلودی (هدرها + خروجی فایل، سپس `exit;`).

```php
wp_safe_redirect( admin_url( 'admin.php?page=plugin-template' ) );
exit; // این exit مجاز و لازم است
```

دانلود فایل (بعد از nonce + capability و مسیر امن):
```php
header( 'Content-Type: application/octet-stream' );
header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $name ) . '"' );
readfile( $safe_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
exit; // بعد از استریم فایل مجاز است
```

---

## 9) درخواست‌های خارجی و File I/O

**HTTP خارجی** — همیشه HTTP API وردپرس، نه cURL/`file_get_contents` خام:
```php
$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
if ( is_wp_error( $response ) ) {
    return $response;
}
$code = wp_remote_retrieve_response_code( $response );
$body = wp_remote_retrieve_body( $response );
```

**File I/O** — از `WP_Filesystem` استفاده کن، نه `fopen`/`fwrite` مستقیم؛ و مسیرها را sanitize کن تا path traversal رخ ندهد:
```php
$filename = sanitize_file_name( $user_input ); // جلوگیری از ../
```
- هرگز مسیر فایل مستقیماً از ورودی کاربر ساخته نشود.
- آپلود فقط با `wp_handle_upload()` و بررسی نوع فایل (`wp_check_filetype`).

---

## 10) استانداردهای وردپرس — Naming & i18n

**Prefix اجباری** روی همه‌ی موارد global برای جلوگیری از collision:
- توابع، کلاس‌ها، ثابت‌ها، option keys، meta keys، hookها، متغیرهای global.
- پیشوند این قالب `htw_pt_` (توابع، option، hook، transient، جدول، shortcode)، `HTW_PT_` (ثابت) و `htw-pt-` (کلاس CSS و handle) است. کلاس‌ها با **namespace** PSR-4 (`HowToWP\PluginTemplate\…`) در `src/` هستند، یک کلاس در هر فایل و نام فایل = نام کلاس.

**i18n** — همه‌ی رشته‌های قابل‌نمایش قابل ترجمه باشند، با text domain ثابت و برابر با slug پلاگین:
```php
__( 'Save', 'plugin-template' );
esc_html_e( 'Settings', 'plugin-template' );
// text domain همیشه literal، هرگز متغیر.
```
```php
// فقط روی init (زودتر صدا زدنش از وردپرس 6.7 هشدار می‌دهد):
add_action( 'init', function () {
    load_plugin_textdomain( 'plugin-template', false, dirname( plugin_basename( HTW_PT_FILE ) ) . '/languages' );
} );
```

**Hookها**: از افعال/اسم‌های prefix‌دار استفاده کن؛ فیلترها مقدار return، اکشن‌ها side effect.

---

## 11) ساختار و فایل‌های پایه‌ی انتشار

هر پلاگین (چیدمان پروژه: خود پلاگین در زیرپوشه‌ی `<slug>/` است؛ `docs/`، `data/`، `design/`، `research/`، `scripts/`، `dev/` کنارش می‌مانند و هرگز در zip نمی‌روند):

```
my-plugin/                  # ریشه‌ی ریپو = پروژه
├── my-plugin/              # خود پلاگین (نام پوشه = slug = ریشه‌ی zip)
│   ├── my-plugin.php       # فایل اصلی + هدر پلاگین + ABSPATH guard + تعریف ثابت‌ها
│   ├── uninstall.php       # پاکسازی هنگام حذف (بدون die؛ با WP_UNINSTALL_PLUGIN guard)
│   ├── readme.txt          # فرمت استاندارد وردپرس (== نه README.md ==)
│   ├── index.php           # فایل silence: <?php // Silence is golden.
│   ├── src/                # کلاس‌های PSR-4 (به‌جای includes/)
│   │   ├── index.php       # silence در هر پوشه و زیرپوشه
│   │   └── ...
│   ├── blocks/             # سورس بلوک‌ها  ->  build/ (بیلد؛ index.php را postbuild می‌سازد)
│   ├── templates/          # index.php
│   ├── assets/             # src/ (منتشر نمی‌شود) و dist/ (منتشر می‌شود)
│   ├── languages/
│   └── bin/add-index.js    # postbuild: index.php در build/ (bin/ منتشر نمی‌شود)
├── docs/  data/  design/  research/  scripts/  dev/
├── .release.yml            # plugin_dir: my-plugin
└── AGENTS.md  CLAUDE.md  .cursor/rules/
```

**قوانین:**
- در ریشه‌ی پلاگین و **هر پوشه‌ی داخل آن** (`src` و زیرپوشه‌ها، `templates`، `assets/*`، `languages`، `blocks/*`) یک `index.php` با محتوای `<?php // Silence is golden.` بگذار (جلوگیری از directory listing). پوشه‌ی `build/` را `wp-scripts` در هر بیلد خالی می‌کند؛ پس اسکریپت npm `postbuild` (`bin/add-index.js`) بعد از `npm run build` خودکار در `build/` و زیرپوشه‌هایش `index.php` می‌سازد. تست `StructureTest` نبودن index را در پوشه‌های ارسالی گزارش می‌دهد.
- هدر `Update URI: https://howtowp.ir` را نگه دار.
- ثابت‌های اصلی را در فایل اصلی تعریف کن:
  ```php
  define( 'HTW_PT_VERSION', '1.0.0' );
  define( 'HTW_PT_FILE', __FILE__ );
  define( 'HTW_PT_DIR', plugin_dir_path( __FILE__ ) );
  define( 'HTW_PT_URL', plugin_dir_url( __FILE__ ) );
  ```
- autoload با Composer و در غیاب `vendor/` با autoloader ساده‌ی PSR-4 داخل فایل اصلی (پلاگین بدون vendor هم کار کند).
- `uninstall.php` با guard شروع شود:
  ```php
  <?php
  defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
  ```
- Activation/Deactivation hook تعریف شود؛ ساخت جدول با `dbDelta()`، نه کوئری خام.

---

## 12) کیفیت کد — WPCS & Formatting

- طول خط را زیر ~۱۰۰ کاراکتر نگه دار (سقف سخت ۱۲۰؛ در `phpcs.xml.dist` بالای ۱۲۰ هشدار می‌دهد). خطوط طولانی را با شکستن آرایه/آرگومان‌ها refactor کن.
- indent با **tab** (استاندارد وردپرس)، نه space.
- Yoda conditions برای مقایسه: `if ( 'paid' === $status )`.
- هر تابع/کلاس docblock داشته باشد (`@param`, `@return`).
- توابع را کوچک و single-responsibility نگه دار؛ منطق تکراری را استخراج کن.
- هیچ warning/notice در PHP تولید نشود (isset/null coalescing قبل از دسترسی به آرایه).

**PHPCS** — پیکربندی و اجرا قبل از هر تحویل (داخل پوشه‌ی پلاگین؛ ابزارها در `composer.json` هستند):
```bash
composer install
composer phpcs      # = phpcs  (از phpcs.xml.dist می‌خواند)
composer phpcbf     # اصلاح خودکار موارد قابل‌اصلاح
composer phpstan
composer test
```

`phpcs.xml.dist` (خلاصه؛ نسخه‌ی کامل در پوشه‌ی پلاگین):
```xml
<?xml version="1.0"?>
<ruleset name="Plugin Template">
    <rule ref="WordPress-Extra" />
    <rule ref="WordPress-Docs" />
    <rule ref="PHPCompatibilityWP" />
    <config name="minimum_wp_version" value="6.8" />
    <config name="testVersion" value="8.1-" />
    <arg name="extensions" value="php" />
    <exclude-pattern>*/vendor/*</exclude-pattern>
    <exclude-pattern>*/node_modules/*</exclude-pattern>
</ruleset>
```
(در WPCS 3 نام گزینه `minimum_wp_version` است؛ نام قدیمی `minimum_supported_wp_version` منسوخ شده.)

---

## 13) چک‌لیست نهایی قبل از انتشار (Definition of Done)

قبل از هر commit/release این‌ها باید ✅ باشند:

- [ ] هیچ `$_POST/$_GET/$_REQUEST/$_SERVER` بدون `wp_unslash` + sanitize نمانده.
- [ ] هیچ `echo`/`print` بدون escape مناسب context نمانده.
- [ ] هر کوئری با ورودی متغیر از `$wpdb->prepare()` عبور کرده.
- [ ] هر فایل PHP ABSPATH guard دارد (`uninstall.php`: guard `WP_UNINSTALL_PLUGIN`).
- [ ] هر action تغییردهنده هم nonce و هم `current_user_can()` دارد.
- [ ] هیچ `die`/`exit` غیرمجاز نمانده (فقط پنج استثنا: ABSPATH، WP_UNINSTALL_PLUGIN، بعد از `wp_safe_redirect`، بعد از `wp_send_json_*`، بعد از استریم دانلود).
- [ ] همه‌ی CSS/JS با `wp_enqueue_*` ثبت شده، نه echo دستی؛ داده به JS با `wp_add_inline_script` / `wp_interactivity_state` (نه `wp_localize_script`).
- [ ] همه‌ی route های REST و همه‌ی Abilityها دارای `permission_callback` واقعی‌اند (نوشتن/حذف هرگز کاملاً باز نیست).
- [ ] `readme.txt`، `uninstall.php` و `index.php` (silence) در ریشه‌ی پلاگین و هر زیرپوشه (و `build/` پس از `npm run build`) موجودند.
- [ ] همه‌ی رشته‌ها i18n شده‌اند با text domain برابر slug.
- [ ] همه‌ی نام‌های global (تابع/کلاس/hook/option) prefix دارند؛ کلاس‌ها در `src/` با namespace PSR-4.
- [ ] `composer phpcs` (WordPress-Extra + WordPress-Docs + PHPCompatibilityWP، `testVersion 8.1-`، `minimum_wp_version 6.8`) بدون error و بدون warning پاس می‌شود؛ `composer phpstan` و `composer test` هم سبز.
- [ ] هیچ خط بیش از ۱۲۰ کاراکتر، هیچ warning/notice در PHP.
- [ ] هیچ API key / secret در کد hardcode نشده (از option/constant استفاده شده)؛ ویژگی‌های AI فقط با AI Client هسته و feature-detect، بدون ذخیره‌ی کلید.
- [ ] `npm run build` کار می‌کند و Plugin Check در CI سبز است.

---

## راهنمای اولویت هنگام رفع مشکل

اگر AI با گزارش خطا مواجه شد، به این ترتیب رفع کند:
1. **بلاک‌کننده‌های امنیتی** (SQL Injection, XSS, nonce/capability, ABSPATH) — اول.
2. **استانداردهای انتشار** (enqueue, readme.txt/index.php, i18n) — دوم.
3. **کیفیت و پایداری** (die/exit, error handling, طول خط, WPCS warnings) — سوم.

هر رفع باید **ریشه‌ای** باشد، نه patch سطحی؛ اگر یک الگوی ناامن در چند فایل تکرار شده، همه‌ی نمونه‌ها اصلاح شوند، نه فقط موردی که در گزارش آمده.
