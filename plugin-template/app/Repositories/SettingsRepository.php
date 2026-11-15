<?php
/**
 * Option storage for the sample settings screen.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\App\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the htw_pt_settings option. No HTML, no REST.
 */
final class SettingsRepository {

	public const OPTION = 'htw_pt_settings';

	/**
	 * Default values. Unknown keys are never stored.
	 *
	 * @return array{enabled: bool, notice: string, site_url: string, delete_data_on_uninstall: bool}
	 */
	public static function defaults(): array {
		return array(
			'enabled'                  => false,
			'notice'                   => '',
			'site_url'                 => '',
			'delete_data_on_uninstall' => false,
		);
	}

	/**
	 * Stored settings merged onto the defaults.
	 *
	 * @return array{enabled: bool, notice: string, site_url: string, delete_data_on_uninstall: bool}
	 */
	public static function all(): array {
		$defaults = self::defaults();
		if ( ! function_exists( 'get_option' ) ) {
			return $defaults;
		}
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return $defaults;
		}
		return array(
			'enabled'                  => self::is_on( $stored['enabled'] ?? false ),
			'notice'                   => self::text_value( $stored, 'notice' ),
			'site_url'                 => self::text_value( $stored, 'site_url' ),
			'delete_data_on_uninstall' => self::is_on( $stored['delete_data_on_uninstall'] ?? false ),
		);
	}

	/**
	 * A stored string, or an empty string when the key is missing.
	 *
	 * @param array<string, mixed> $stored Option payload.
	 * @param string               $key    Key to read.
	 */
	private static function text_value( array $stored, string $key ): string {
		$value = $stored[ $key ] ?? '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Normalize a loose JSON boolean to a real bool.
	 *
	 * @param mixed $value Raw JSON value.
	 */
	public static function is_on( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_int( $value ) ) {
			return 1 === $value;
		}
		if ( ! is_string( $value ) ) {
			return false;
		}
		return in_array( strtolower( $value ), array( '1', 'true', 'yes', 'on' ), true );
	}

	/**
	 * Persist known keys. An unchanged value is success.
	 *
	 * @param array<string, mixed> $settings Sanitized settings.
	 */
	public static function save( array $settings ): bool {
		$clean    = array(
			'enabled'                  => self::is_on( $settings['enabled'] ?? false ),
			'notice'                   => self::text_value( $settings, 'notice' ),
			'site_url'                 => self::text_value( $settings, 'site_url' ),
			'delete_data_on_uninstall' => self::is_on( $settings['delete_data_on_uninstall'] ?? false ),
		);
		$previous = self::all();
		if ( $clean === $previous ) {
			return true;
		}
		if ( ! function_exists( 'update_option' ) ) {
			return false;
		}
		return false !== update_option( self::OPTION, $clean, true );
	}
}
