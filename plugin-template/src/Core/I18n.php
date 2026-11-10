<?php
/**
 * Translations.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the text domain on `init` (loading earlier triggers notices since WP 6.7).
 */
final class I18n implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load translations from /languages.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'plugin-template', false, dirname( HTW_PT_BASENAME ) . '/languages' );
	}
}
