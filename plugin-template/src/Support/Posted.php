<?php
/**
 * Read a posted array only after its nonce checks out.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Superglobal helpers. Callers still check current_user_can().
 */
final class Posted {

	/**
	 * Sanitized copy of one $_POST array.
	 *
	 * Map_deep runs before any value is returned. Nested values are text.
	 *
	 * @param string $key          Posted array key.
	 * @param string $nonce_action Nonce action checked before the array is read.
	 * @param string $nonce_field  Posted nonce field name.
	 * @return array<int|string, mixed>
	 */
	public static function array( string $key, string $nonce_action, string $nonce_field = '_wpnonce' ): array {
		if ( ! isset( $_POST[ $nonce_field ] ) || ! is_string( $_POST[ $nonce_field ] ) ) {
			return array();
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) );
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return array();
		}
		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
			return array();
		}
		$clean = map_deep( wp_unslash( $_POST[ $key ] ), 'sanitize_text_field' );
		return is_array( $clean ) ? $clean : array();
	}
}
