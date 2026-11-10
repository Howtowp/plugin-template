<?php
/**
 * Front-end assets.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Frontend;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Core\Module;

/**
 * Registers (does not enqueue) shared assets. Enqueue only where they are used.
 */
final class Assets implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register the stylesheet; Shortcode enqueues it when rendered.
	 */
	public function register_assets(): void {
		wp_register_style(
			'htw-pt-frontend',
			HTW_PT_URL . 'assets/dist/frontend.css',
			array(),
			HTW_PT_VERSION
		);
	}
}
