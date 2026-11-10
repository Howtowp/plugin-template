<?php
/**
 * Sample REST route.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Rest;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Core\Module;
use HowToWP\PluginTemplate\Services\SiteInfoService;

/**
 * GET /wp-json/plugin-template/v1/status
 */
final class StatusController implements Module {

	public const API_NAMESPACE = 'plugin-template/v1';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes. Always set a real permission_callback.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::API_NAMESPACE,
			'/status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'can_view' ),
			)
		);
	}

	/**
	 * Only administrators.
	 */
	public function can_view(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Return site info.
	 */
	public function get_status(): \WP_REST_Response {
		return rest_ensure_response( ( new SiteInfoService() )->get() );
	}
}
