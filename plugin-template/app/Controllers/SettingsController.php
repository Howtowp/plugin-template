<?php
/**
 * Settings schema and save endpoint.
 *
 * Thin HTTP layer: capability, nonce, validate, then the repository.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\App\Controllers;

defined( 'ABSPATH' ) || exit;

use Howtowp\Bluwp\Http\API\Controller;
use Howtowp\Bluwp\Http\API\Responses\ResponseFactory;
use HowToWP\PluginTemplate\App\Http\RestAuthorization;
use HowToWP\PluginTemplate\App\Repositories\SettingsRepository;
use HowToWP\PluginTemplate\App\Schemas\SettingsSchema;
use HowToWP\PluginTemplate\Text;

/**
 * GET admin/settings/schema and POST admin/settings.
 */
final class SettingsController extends Controller {

	/**
	 * Form schema for the admin kit.
	 *
	 * The request argument is optional so Bluwp can call schema() or schema( $request ).
	 *
	 * @param \WP_REST_Request|null $request Current REST request, when Bluwp passes it.
	 */
	public function schema( ?\WP_REST_Request $request = null ): \WP_REST_Response {
		$denied = $this->denied( $request );
		if ( null !== $denied ) {
			return $denied;
		}
		return ResponseFactory::make(
			array(
				'schema' => SettingsSchema::make(),
			)
		);
	}

	/**
	 * Validate and store the settings form.
	 *
	 * @param \WP_REST_Request $request JSON body for the form.
	 */
	public function save( \WP_REST_Request $request ): \WP_REST_Response {
		$denied = $this->denied( $request );
		if ( null !== $denied ) {
			return $denied;
		}

		$input = $request->get_json_params();
		if ( ! is_array( $input ) ) {
			return ResponseFactory::make(
				null,
				422,
				messages: array( Text::InvalidSettings->text() )
			);
		}

		$errors   = array();
		$settings = array(
			'enabled'                  => SettingsRepository::is_on( $input['enabled'] ?? false ),
			'delete_data_on_uninstall' => SettingsRepository::is_on( $input['delete_data_on_uninstall'] ?? false ),
			'notice'                   => '',
			'site_url'                 => '',
		);

		$notice = $input['notice'] ?? '';
		if ( ! is_string( $notice ) ) {
			$errors['notice'] = Text::InvalidSettings->text();
		} else {
			$notice = wp_strip_all_tags( $notice );
			if ( 1 !== preg_match( '/\A.{0,500}\z/us', $notice ) ) {
				$errors['notice'] = Text::InvalidSettings->text();
			} else {
				$settings['notice'] = $notice;
			}
		}

		$site_url = $input['site_url'] ?? '';
		if ( ! is_string( $site_url ) ) {
			$errors['site_url'] = Text::InvalidUrl->text();
		} else {
			$site_url = esc_url_raw( $site_url );
			if ( '' !== $site_url && false === wp_http_validate_url( $site_url ) ) {
				$errors['site_url'] = Text::InvalidUrl->text();
			} else {
				$settings['site_url'] = $site_url;
			}
		}

		$customer = null;
		if ( array_key_exists( 'customer_hello', $input ) ) {
			if ( ! is_string( $input['customer_hello'] ) ) {
				$errors['customer_hello'] = Text::InvalidSettings->text();
			} else {
				$customer = wp_strip_all_tags( $input['customer_hello'] );
				if ( 1 !== preg_match( '/\A.{0,500}\z/us', $customer ) ) {
					$errors['customer_hello'] = Text::InvalidSettings->text();
					$customer                 = null;
				}
			}
		}

		if ( array() !== $errors ) {
			return ResponseFactory::validation( $errors );
		}

		$filtered = apply_filters( 'htw_pt_settings', $settings );
		if ( ! is_array( $filtered ) || ! SettingsRepository::save( $filtered ) ) {
			return ResponseFactory::make(
				null,
				500,
				messages: array( Text::SaveFailed->text() )
			);
		}
		if ( is_string( $customer ) && ! Text::save_customer( $customer ) ) {
			return ResponseFactory::make(
				null,
				500,
				messages: array( Text::SaveFailed->text() )
			);
		}

		return ResponseFactory::make(
			messages: array( Text::Saved->text() )
		);
	}

	/**
	 * 403 when the capability or the REST nonce is missing.
	 *
	 * @param \WP_REST_Request|null $request Current REST request.
	 */
	private function denied( ?\WP_REST_Request $request ): ?\WP_REST_Response {
		if ( RestAuthorization::allows( $request ) ) {
			return null;
		}
		return ResponseFactory::make(
			null,
			403,
			messages: array( Text::Forbidden->text() )
		);
	}
}
