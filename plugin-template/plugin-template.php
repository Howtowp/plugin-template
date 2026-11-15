<?php
/**
 * Plugin Name:       Plugin Template
 * Plugin URI:        https://howtowp.ir/plugins/plugin-template
 * Description:       Shared starter for How to WP plugins. Admin UI is React and VibeFarsi.
 * Version:           0.3.0
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
 * Bluwp is embedded in lib/bluwp. A site that also has the standalone Bluwp
 * plugin keeps using that copy when it is active (see BluwpBridgeV1).
 * WooCommerce plugins add "Requires Plugins: woocommerce" and the WC headers.
 * Do not hardcode this file's name anywhere else: pass HTW_PT_FILE.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'HTW_PT_VERSION', '0.3.0' ); // Keep equal to the Version header (release check + tests).
define( 'HTW_PT_FILE', __FILE__ );
define( 'HTW_PT_DIR', plugin_dir_path( __FILE__ ) );
define( 'HTW_PT_URL', plugin_dir_url( __FILE__ ) );
define( 'HTW_PT_BASENAME', plugin_basename( __FILE__ ) );
define( 'HTW_PT_SLUG', 'plugin-template' );
define( 'HTW_PT_CAPABILITY', 'manage_options' ); // Admin page and REST must both use this.
define( 'HTW_PT_MIN_PHP', '8.1' );
define( 'HTW_PT_MIN_WP', '6.8' );
define( 'HTW_PT_BLUWP_MIN', '0.2.1' );
define( 'HTW_PT_BLUWP_BELOW', '0.3.0' ); // Exclusive upper bound. Stay on Bluwp 0.2.x.

/*
 * Keep this block free of namespaces, return types, and enums so an old PHP
 * parses it and shows a notice instead of a fatal error. Typed code is loaded
 * only after the check passes.
 */
if (
	version_compare( PHP_VERSION, HTW_PT_MIN_PHP, '<' )
	|| version_compare( $GLOBALS['wp_version'], HTW_PT_MIN_WP, '<' )
) {
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

require_once HTW_PT_DIR . 'src/Text.php';
require_once HTW_PT_DIR . 'lib/bluwp/bridge.php';

// $embedded is this plugin's own Bluwp tree. consume() also registers the
// Bluwp activation and deactivation hooks (migrations, cron cleanup).
BluwpBridgeV1::consume(
	HTW_PT_FILE,
	HTW_PT_SLUG,
	\HowToWP\PluginTemplate\Text::PluginName->text(),
	HTW_PT_BLUWP_MIN,
	HTW_PT_DIR . 'lib/bluwp'
);

require_once HTW_PT_DIR . 'hooks/early/woocommerce.php';

register_activation_hook(
	HTW_PT_FILE,
	static function () {
		do_action( 'htw_pt_activated' );
	}
);
register_deactivation_hook(
	HTW_PT_FILE,
	static function () {
		do_action( 'htw_pt_deactivated' );
	}
);
