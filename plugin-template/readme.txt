=== Plugin Template ===
Contributors: howtowp
Tags: howtowp, starter, bluwp
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shared starter for How to WP plugins. Admin UI is rendered by Bluwp from a JSON schema.

== Description ==

Plugin Template is the starting point for new How to WP plugins. It requires the Bluwp plugin
(>= 0.2.1 and < 0.3). The settings screen is a JSON schema rendered by the bluwp-vuejs admin kit.
WooCommerce HPOS and checkout-blocks compatibility is declared against this plugin's real main file.

The compiled kit CSS is not stored in the template. Build it from a pinned bluwp-vuejs checkout
into assets/ before you ship a product that opens the settings screen.

== Installation ==

1. Install and activate Bluwp (>= 0.2.1 and < 0.3).
2. Upload this plugin folder to `/wp-content/plugins/`, or install the zip.
3. Activate it from the Plugins screen.
4. Open the Plugin Template menu (capability: manage_options).

== Frequently Asked Questions ==

= Does this plugin update itself? =

No. Download new versions from https://howtowp.ir and upload them.

= Where is the admin CSS? =

It is built from the shared bluwp-vuejs kit into assets/. The template repository does not vendor a copy.

== Changelog ==

= 0.3.0 =
* Bluwp skeleton: schema settings page, REST route with matching capability and nonce, Text enum, HPOS declaration via the real main file.
* Admin assets build from bluwp-vuejs. Compiled kit CSS is not vendored.

= 0.2.0 =
* Security and standards ruleset, "Silence is golden" index.php files, stricter PHPCS.

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 0.3.0 =
Requires Bluwp >= 0.2.1 and < 0.3. The previous sample block and Settings API page are gone.
