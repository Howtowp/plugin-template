<?php
/**
 * Schema upgrades.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs migrations when the stored schema version is older than the code.
 *
 * To add a migration: bump Installer::SCHEMA_VERSION and add a
 * `migrate_to_N()` method below.
 */
final class Upgrader implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ), 20 );
	}

	/**
	 * Run pending migrations.
	 */
	public function maybe_upgrade(): void {
		$stored = (int) get_option( Installer::OPTION_SCHEMA, 0 );
		if ( $stored >= Installer::SCHEMA_VERSION ) {
			return;
		}

		for ( $version = $stored + 1; $version <= Installer::SCHEMA_VERSION; $version++ ) {
			$method = 'migrate_to_' . $version;
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			}
		}

		Installer::install(); // Re-run dbDelta and store the new schema version.
	}

	/**
	 * Schema 1 is the initial schema: nothing to migrate.
	 */
	private function migrate_to_1(): void {}
}
