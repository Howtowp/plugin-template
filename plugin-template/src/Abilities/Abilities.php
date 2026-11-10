<?php
/**
 * Abilities API registration (WordPress 6.9+).
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Abilities;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Core\Module;
use HowToWP\PluginTemplate\Services\SiteInfoService;

/**
 * Registers an ability category and one sample read-only ability.
 *
 * Abilities are machine-readable capabilities of the plugin (JSON Schema in/out
 * + permission check). Core exposes them over REST; the MCP Adapter plugin can
 * expose them to AI agents. See docs/abilities.md.
 */
final class Abilities implements Module {

	public const CATEGORY = 'plugin-template';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return; // WordPress < 6.9.
		}
		// Categories first (separate, earlier hook), then abilities.
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the plugin's category.
	 */
	public function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Plugin Template', 'plugin-template' ),
				'description' => __( 'Abilities provided by Plugin Template.', 'plugin-template' ),
			)
		);
	}

	/**
	 * Register abilities. Name format: "{plugin-slug}/{ability-name}".
	 */
	public function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'plugin-template/site-info',
			array(
				'label'               => __( 'Get site info', 'plugin-template' ),
				'description'         => __( 'Returns site name, WordPress, PHP and plugin versions. Read-only.', 'plugin-template' ),
				'category'            => self::CATEGORY,
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'           => array( 'type' => 'string' ),
						'wp_version'     => array( 'type' => 'string' ),
						'php_version'    => array( 'type' => 'string' ),
						'plugin_version' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_site_info' ),
				'permission_callback' => array( $this, 'can_read_site_info' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true ),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Permission check: never return true blindly.
	 */
	public function can_read_site_info(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Execute the ability.
	 *
	 * @return array<string, string>
	 */
	public function execute_site_info(): array {
		return ( new SiteInfoService() )->get();
	}
}
