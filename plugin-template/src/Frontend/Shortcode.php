<?php
/**
 * Sample shortcode.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Frontend;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Core\Module;
use HowToWP\PluginTemplate\Repositories\SettingsRepository;
use HowToWP\PluginTemplate\Support\Template;

/**
 * [htw_pt_hello name="..."]
 */
final class Shortcode implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_shortcode( 'htw_pt_hello', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 */
	public function render( $atts = array() ): string {
		$atts = shortcode_atts( array( 'name' => '' ), (array) $atts, 'htw_pt_hello' );

		wp_enqueue_style( 'htw-pt-frontend' );

		$greeting = (string) ( new SettingsRepository() )->get( 'greeting', '' );
		if ( '' === $greeting ) {
			$greeting = __( 'Hello', 'plugin-template' );
		}

		return Template::render(
			'hello',
			array(
				'greeting' => $greeting,
				'name'     => sanitize_text_field( $atts['name'] ),
			)
		);
	}
}
