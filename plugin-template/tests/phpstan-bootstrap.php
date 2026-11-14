<?php
/**
 * PHPStan bootstrap: constants defined in the main plugin file.
 *
 * @package HowToWP\PluginTemplate
 */

define( 'HTW_PT_VERSION', '0.0.0' );
define( 'HTW_PT_FILE', __DIR__ . '/../plugin-template.php' );
define( 'HTW_PT_DIR', __DIR__ . '/../' );
define( 'HTW_PT_URL', 'http://example.org/' );
define( 'HTW_PT_BASENAME', 'plugin-template/plugin-template.php' );
define( 'HTW_PT_SLUG', 'plugin-template' );
define( 'HTW_PT_CAPABILITY', 'manage_options' );
define( 'HTW_PT_MIN_PHP', '8.1' );
define( 'HTW_PT_MIN_WP', '6.8' );
define( 'HTW_PT_BLUWP_MIN', '0.2.1' );
define( 'HTW_PT_BLUWP_BELOW', '0.3.0' );

if ( ! class_exists( 'BluwpBridgeV1', false ) ) {
	/**
	 * Stand-in so PHPStan can see the embedded bridge call.
	 */
	class BluwpBridgeV1 {
		/**
		 * @param string $file     Plugin main file.
		 * @param string $name     Bluwp app name.
		 * @param string $title    Admin title.
		 * @param string $minimum  Minimum Bluwp version.
		 * @param string $embedded Embedded Bluwp directory.
		 */
		public static function consume( string $file, string $name, string $title, string $minimum, string $embedded ): void {
			unset( $file, $name, $title, $minimum, $embedded );
		}
	}
}
