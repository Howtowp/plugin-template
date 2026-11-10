<?php
/**
 * Plugin Name:       Plugin Template
 * Plugin URI:        https://howtowp.ir/plugins/plugin-template
 * Description:       Starter skeleton for How to WP plugins: PSR-4, modules, Abilities API, blocks, WooCommerce HPOS-ready.
 * Version:           0.2.0
 * Requires at least: 6.8
 * Requires PHP:      8.1
 * Author:            How to WP
 * Author URI:        https://howtowp.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       plugin-template
 * Domain Path:       /languages
 * Update URI:        https://howtowp.ir
 *
 * Optional headers, add them above when the plugin needs them (do not leave a
 * half-commented header line in this block, WordPress would still parse it):
 *   - "Requires Plugins" with a comma separated list of plugin slugs (e.g. woocommerce)
 *   - "WC requires at least" and "WC tested up to" for WooCommerce plugins
 *
 * @package HowToWP\PluginTemplate
 */

defined( 'ABSPATH' ) || exit;

define( 'HTW_PT_VERSION', '0.2.0' ); // Keep equal to the Version header above (release check + tests).
define( 'HTW_PT_FILE', __FILE__ );
define( 'HTW_PT_DIR', plugin_dir_path( __FILE__ ) );
define( 'HTW_PT_URL', plugin_dir_url( __FILE__ ) );
define( 'HTW_PT_BASENAME', plugin_basename( __FILE__ ) );
define( 'HTW_PT_MIN_PHP', '8.1' );
define( 'HTW_PT_MIN_WP', '6.8' );

/*
 * Environment check. Keep this part free of namespaces and typed
 * syntax above this line, so an old PHP shows a notice instead of a fatal error.
 */
if ( version_compare( PHP_VERSION, HTW_PT_MIN_PHP, '<' ) || version_compare( $GLOBALS['wp_version'], HTW_PT_MIN_WP, '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: plugin name, 2: PHP version, 3: WordPress version */
						__( '%1$s requires PHP %2$s+ and WordPress %3$s+.', 'plugin-template' ),
						'Plugin Template',
						HTW_PT_MIN_PHP,
						HTW_PT_MIN_WP
					)
				)
			);
		}
	);
	return;
}

// Autoload: Composer if present, otherwise a tiny PSR-4 fallback (no vendor/ needed).
if ( is_readable( HTW_PT_DIR . 'vendor/autoload.php' ) ) {
	require_once HTW_PT_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( $class_name ) {
			$prefix = 'HowToWP\\PluginTemplate\\';
			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}
			$relative = str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) );
			$path     = HTW_PT_DIR . 'src/' . $relative . '.php';
			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}
	);
}

register_activation_hook( HTW_PT_FILE, array( \HowToWP\PluginTemplate\Core\Activator::class, 'activate' ) );
register_deactivation_hook( HTW_PT_FILE, array( \HowToWP\PluginTemplate\Core\Deactivator::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\HowToWP\PluginTemplate\Plugin::instance()->boot();
	}
);
