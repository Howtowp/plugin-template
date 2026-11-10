<?php
/**
 * Settings storage.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Repositories;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Core\Installer;

/**
 * Single place that reads/writes the settings option.
 */
final class SettingsRepository {

	/**
	 * Default values. Every setting key must exist here.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'greeting'                 => '',
			'delete_data_on_uninstall' => false,
		);
	}

	/**
	 * All settings merged over defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		$stored = get_option( Installer::OPTION_SETTINGS, array() );
		return array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Get one setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $fallback Fallback when the key is unknown.
	 * @return mixed
	 */
	public function get( string $key, $fallback = null ) {
		$all = $this->all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}
}
