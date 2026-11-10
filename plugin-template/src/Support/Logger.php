<?php
/**
 * Minimal logger.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Writes to the PHP error log only when WP_DEBUG_LOG is on. Never log secrets.
 */
final class Logger {

	/**
	 * Log a message.
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Extra data (no secrets).
	 */
	public static function log( string $message, array $context = array() ): void {
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}
		$line = '[plugin-template] ' . $message;
		if ( $context ) {
			$line .= ' ' . wp_json_encode( $context );
		}
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $line );
	}
}
