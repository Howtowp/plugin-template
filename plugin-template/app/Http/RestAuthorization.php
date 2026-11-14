<?php
/**
 * Capability and REST nonce check shared by state-changing and read routes.
 *
 * Bluwp also receives HTW_PT_CAPABILITY on the route. This class repeats the
 * check in the controller and requires the wp_rest nonce (X-WP-Nonce or _wpnonce)
 * so a route is never left open by a missing router flag.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\App\Http;

defined( 'ABSPATH' ) || exit;

/**
 * Allows the request only when the user holds HTW_PT_CAPABILITY and a valid nonce.
 */
final class RestAuthorization {

	/**
	 * Whether the current user may call a plugin REST route.
	 *
	 * @param \WP_REST_Request|null $request Current REST request, if Bluwp passed one.
	 */
	public static function allows( ?\WP_REST_Request $request ): bool {
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( HTW_PT_CAPABILITY ) ) {
			return false;
		}
		$nonce = self::nonce_from( $request );
		if ( '' === $nonce || ! function_exists( 'wp_verify_nonce' ) ) {
			return false;
		}
		return false !== wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Read the REST nonce from the request or the X-WP-Nonce header.
	 *
	 * @param \WP_REST_Request|null $request Current REST request.
	 */
	private static function nonce_from( ?\WP_REST_Request $request ): string {
		if ( $request instanceof \WP_REST_Request ) {
			$header = $request->get_header( 'X-WP-Nonce' );
			if ( is_string( $header ) && '' !== $header ) {
				return sanitize_text_field( $header );
			}
			$param = $request->get_param( '_wpnonce' );
			if ( is_string( $param ) && '' !== $param ) {
				return sanitize_text_field( wp_unslash( $param ) );
			}
		}
		if ( ! isset( $_SERVER['HTTP_X_WP_NONCE'] ) || ! is_string( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			return '';
		}
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
	}
}
