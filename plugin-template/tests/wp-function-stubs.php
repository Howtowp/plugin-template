<?php
/**
 * Tiny WordPress stand-ins for the embedded Bluwp boot test.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Record hook callbacks. Priority -1000 is the bridge resolver.
	 *
	 * @param mixed $callback Hook callback.
	 */
	function add_action( string $hook, mixed $callback, int $priority = 10, int $args = 1 ): void {
		unset( $args );
		$GLOBALS['htw_test_actions'][ $hook ][ $priority ][] = $callback;
	}
}

if ( ! function_exists( 'did_action' ) ) {
	/**
	 * Pretend no hook has run, so boot stays deferred.
	 */
	function did_action( string $hook ): int {
		unset( $hook );
		return 0;
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	/**
	 * Activation is exercised by WordPress, not this process.
	 *
	 * @param mixed $callback Hook callback.
	 */
	function register_activation_hook( string $file, mixed $callback ): void {
		unset( $file, $callback );
	}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	/**
	 * Deactivation is exercised by WordPress, not this process.
	 *
	 * @param mixed $callback Hook callback.
	 */
	function register_deactivation_hook( string $file, mixed $callback ): void {
		unset( $file, $callback );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * WordPress trailing-slash helper.
	 */
	function trailingslashit( string $value ): string {
		return rtrim( $value, '/\\' ) . '/';
	}
}
