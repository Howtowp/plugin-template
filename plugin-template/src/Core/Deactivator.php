<?php
/**
 * Deactivation hook.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Deactivation callback. Never delete user data here (that is uninstall.php).
 */
final class Deactivator {

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		// Clear scheduled events here (see wp_clear_scheduled_hook).
	}
}
