<?php
/**
 * Template loader with theme override.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Renders files from /templates. A theme can override any of them by copying
 * the file to `{theme}/plugin-template/{name}.php`.
 */
final class Template {

	/**
	 * Render a template and return its HTML.
	 *
	 * @param string               $name Template name without extension (e.g. "hello").
	 * @param array<string, mixed> $vars Variables available inside the template.
	 */
	public static function render( string $name, array $vars = array() ): string {
		$name = preg_replace( '/[^a-z0-9\-_\/]/i', '', $name );
		$file = locate_template( 'plugin-template/' . $name . '.php' );
		if ( ! $file ) {
			$file = HTW_PT_DIR . 'templates/' . $name . '.php';
		}
		if ( ! is_readable( $file ) ) {
			return '';
		}
		ob_start();
		( static function ( string $htw_pt_file, array $htw_pt_vars ): void {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			extract( $htw_pt_vars, EXTR_SKIP );
			include $htw_pt_file;
		} )( $file, $vars );
		return (string) ob_get_clean();
	}
}
