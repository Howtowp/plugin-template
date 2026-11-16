=== Plugin Template ===
Contributors: howtowp
Tags: howtowp, starter, bluwp
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shared starter for How to WP plugins. The admin screen is React (VibeFarsi) on an orange full-width layout.

== Description ==

Plugin Template is the starting point for new How to WP plugins. Bluwp 0.3.0 is included
in lib/bluwp (PHP namespace Howtowp\Bluwp), so customers install this plugin only. A site
that already runs standalone Bluwp keeps that copy when it is on the 0.3 line. The settings screen is a JSON schema
rendered by the bluwp-vuejs admin kit once that kit has been built.
WooCommerce HPOS and checkout-blocks compatibility is declared against this plugin's real main file.

Build the admin with `npm run build` inside this folder. The zip ships `assets/admin`, not the `admin/` source.

== Installation ==

1. Upload this plugin folder to `/wp-content/plugins/`, or install the zip.
2. Activate it from the Plugins screen. Bluwp is inside the plugin.
3. Open the Plugin Template menu (capability: manage_options).

== Frequently Asked Questions ==

= Does this plugin update itself? =

No. Download new versions from https://howtowp.ir and upload them.

= Where is the admin CSS? =

`npm run build` writes `assets/admin`. Open `admin/harness/preview.html` to view it under real WordPress admin CSS.

== Changelog ==

= 0.3.0 =
* Bluwp skeleton: schema settings page, REST route with matching capability and nonce, Text enum, HPOS declaration via the real main file.
* Bluwp 0.2.1 is embedded in lib/bluwp. Customers install one plugin. A standalone Bluwp on the same line is still used when it is active.
* Admin UI is the VibeFarsi kit: orange brand, full-width shell, unlayered wp-admin reset, underline tabs, skeletons and busy buttons.

= 0.2.0 =
* Security and standards ruleset, "Silence is golden" index.php files, stricter PHPCS.

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 0.3.0 =
Bluwp is included. Do not install a separate Bluwp unless you already run one on the 0.3 line (namespace Howtowp\Bluwp).
