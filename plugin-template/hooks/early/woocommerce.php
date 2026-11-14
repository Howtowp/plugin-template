<?php
/**
 * Early WooCommerce compatibility and Bluwp boot.
 *
 * Loaded from the main plugin file before plugins_loaded so HPOS and
 * checkout-blocks compatibility are declared in time. The plugin file path is
 * HTW_PT_FILE (__FILE__ of the real main file). Never hardcode a filename here.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

use Forouzandea\Bluwp\Foundation\Bootstrap\PluginBootstrap;
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
	'plugins_loaded',
	static function (): void {
		$version = '';
		$loaded  = $GLOBALS['bluwp_framework_loaded'] ?? null;
		if ( is_array( $loaded ) && isset( $loaded['version'] ) && is_string( $loaded['version'] ) ) {
			$version = $loaded['version'];
		}
		$compatible = empty( $GLOBALS['bluwp_framework_error'] )
			&& '' !== $version
			&& version_compare( $version, HTW_PT_BLUWP_MIN, '>=' )
			&& version_compare( $version, HTW_PT_BLUWP_BELOW, '<' );
		if ( ! $compatible ) {
			add_action(
				'admin_notices',
				static function (): void {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html( Text::BluwpRequired->text() )
					);
				}
			);
			return;
		}

		$plugin = PluginBootstrap::register(
			HTW_PT_FILE,
			HTW_PT_SLUG,
			Text::PluginName->text(),
			false
		);
		$plugin->app();

		do_action( 'htw_pt_loaded' );
	},
	20
);
