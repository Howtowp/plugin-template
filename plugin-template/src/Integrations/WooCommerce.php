<?php
/**
 * WooCommerce integration.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Integrations;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Core\Module;

/**
 * Declares compatibility with HPOS and the Cart/Checkout blocks. Does nothing
 * when WooCommerce is not installed. Read orders with wc_get_order(), never
 * with get_post_meta().
 */
final class WooCommerce implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
	}

	/**
	 * Declare feature compatibility (only if WooCommerce is active).
	 */
	public function declare_compatibility(): void {
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', HTW_PT_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', HTW_PT_FILE, true );
	}
}
