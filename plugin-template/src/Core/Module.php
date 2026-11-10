<?php
/**
 * Module contract.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Core;

defined( 'ABSPATH' ) || exit;

/**
 * A module registers its own hooks. Plugin.php only lists modules.
 */
interface Module {

	/**
	 * Register WordPress hooks. Must be cheap: no queries, no remote calls.
	 */
	public function register(): void;
}
