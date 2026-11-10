<?php
/**
 * Sample service.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Business logic lives in services so REST, Abilities, shortcodes and blocks
 * can all reuse it. Services never print output.
 */
final class SiteInfoService {

	/**
	 * Basic, non-sensitive site information.
	 *
	 * @return array{name: string, wp_version: string, php_version: string, plugin_version: string}
	 */
	public function get(): array {
		return array(
			'name'           => (string) get_bloginfo( 'name' ),
			'wp_version'     => (string) get_bloginfo( 'version' ),
			'php_version'    => PHP_VERSION,
			'plugin_version' => HTW_PT_VERSION,
		);
	}
}
