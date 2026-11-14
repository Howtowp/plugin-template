<?php
/**
 * Early WooCommerce compatibility.
 *
 * HPOS and checkout-blocks compatibility are declared with HTW_PT_FILE
 * (__FILE__ of the real main file). Never hardcode a filename here.
 * Bluwp itself is started from the main file via BluwpBridgeV1::consume().
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

use HowToWP\PluginTemplate\Text;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'HTW_PT_FILE' ) || ! defined( 'HTW_PT_SLUG' ) ) {
	return;
}

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			return;
		}
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			HTW_PT_FILE,
			true
		);
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'cart_checkout_blocks',
			HTW_PT_FILE,
			true
		);
	}
);

add_action(
	'admin_notices',
	static function (): void {
		if ( empty( $GLOBALS['bluwp_framework_error'] ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( Text::BluwpRequired->text() )
		);
	}
);

// Bluwp boots this plugin on init priority 1. Fire after that.
add_action(
	'init',
	static function (): void {
		if ( ! empty( $GLOBALS['bluwp_framework_error'] ) || empty( $GLOBALS['bluwp_framework_loaded'] ) ) {
			return;
		}
		do_action( 'htw_pt_loaded' );
	},
	2
);
