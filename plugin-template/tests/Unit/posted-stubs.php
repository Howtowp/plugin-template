<?php
/**
 * WordPress stand-ins for PostedTest. Global namespace on purpose.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Strip slashes from a posted string or walk an array.
	 *
	 * @param mixed $value Posted value.
	 * @return mixed
	 */
	function wp_unslash( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}
		return stripslashes( (string) $value );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Drop tags.
	 */
	function wp_strip_all_tags( string $value ): string {
		return strip_tags( $value );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Trim and drop tags, close enough for this unit.
	 *
	 * @param mixed $value Posted scalar.
	 */
	function sanitize_text_field( mixed $value ): string {
		return trim( wp_strip_all_tags( (string) $value ) );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	/**
	 * Accept the fixture nonce for the sample action.
	 */
	function wp_verify_nonce( string $nonce, string $action ): int|false {
		return ( 'ok' === $nonce && 'save_sample' === $action ) ? 1 : false;
	}
}

if ( ! function_exists( 'map_deep' ) ) {
	/**
	 * WordPress map_deep.
	 *
	 * @param mixed $value Value or list.
	 * @return mixed
	 */
	function map_deep( mixed $value, callable $callback ): mixed {
		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				$value[ $index ] = map_deep( $item, $callback );
			}
			return $value;
		}
		return $callback( $value );
	}
}
