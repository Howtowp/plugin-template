<?php
/**
 * Install routine: schema + default options.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Creates tables (dbDelta) and stores the schema version.
 */
final class Installer {

	public const SCHEMA_VERSION  = 1;
	public const OPTION_SCHEMA   = 'htw_pt_schema_version';
	public const OPTION_SETTINGS = 'htw_pt_settings';

	/**
	 * Run on activation (and when the Upgrader finds an older schema).
	 */
	public static function install(): void {
		self::create_tables();

		if ( false === get_option( self::OPTION_SETTINGS ) ) {
			add_option(
				self::OPTION_SETTINGS,
				\HowToWP\PluginTemplate\Repositories\SettingsRepository::defaults(),
				'',
				false
			);
		}

		update_option( self::OPTION_SCHEMA, self::SCHEMA_VERSION, false );
	}

	/**
	 * Create or update custom tables. Empty by default: keep data in options/meta
	 * unless you really need a table.
	 */
	private static function create_tables(): void {
		// To add a table: build the CREATE TABLE SQL with $wpdb->prefix . 'htw_pt_<name>'
		// and $wpdb->get_charset_collate(), then pass it to dbDelta()
		// (require ABSPATH . 'wp-admin/includes/upgrade.php' first).
	}
}
