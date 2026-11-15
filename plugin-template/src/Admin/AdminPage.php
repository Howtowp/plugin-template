<?php
/**
 * React admin screen. Assets load only on this menu page.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Admin;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Text;

/**
 * Settings UI mounted on #htw-pt-admin.
 */
final class AdminPage {

	/**
	 * Hook the menu and the screen-only enqueue.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Top-level menu. Capability matches the REST routes.
	 */
	public static function menu(): void {
		add_menu_page(
			Text::PluginName->text(),
			Text::PluginName->text(),
			HTW_PT_CAPABILITY,
			HTW_PT_SLUG,
			array( self::class, 'render' ),
			'dashicons-admin-generic',
			58
		);
	}

	/**
	 * Mount point for the VibeFarsi app. No frontend output.
	 */
	public static function render(): void {
		if ( ! current_user_can( HTW_PT_CAPABILITY ) ) {
			return;
		}
		echo '<div id="htw-pt-wrap" class="wrap">';
		echo '<div id="htw-pt-admin" class="htw-pt-admin" dir="rtl"></div>';
		echo '</div>';
	}

	/**
	 * Scripts and styles for this screen only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public static function enqueue( string $hook ): void {
		if ( 'toplevel_page_' . HTW_PT_SLUG !== $hook ) {
			return;
		}
		$manifest_path = HTW_PT_DIR . 'assets/admin/manifest.json';
		if ( ! is_readable( $manifest_path ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					if ( ! current_user_can( HTW_PT_CAPABILITY ) ) {
						return;
					}
					echo '<div class="notice notice-warning"><p>';
					echo esc_html__( 'Admin assets are missing. Run npm run build in the plugin.', 'plugin-template' );
					echo '</p></div>';
				}
			);
			return;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local build manifest, not a remote URL.
		$manifest_json = file_get_contents( $manifest_path );
		$manifest      = json_decode( (string) $manifest_json, true );
		$entry         = null;
		$style         = '';
		if ( is_array( $manifest ) ) {
			foreach ( $manifest as $item ) {
				if ( ! is_array( $item ) || ! isset( $item['file'] ) || ! is_string( $item['file'] ) ) {
					continue;
				}
				if ( ! empty( $item['isEntry'] ) ) {
					$entry = $item['file'];
				}
				if ( str_ends_with( $item['file'], '.css' ) ) {
					$style = $item['file'];
				}
			}
		}
		if ( ! is_string( $entry ) || '' === $entry ) {
			return;
		}

		wp_enqueue_style(
			'htw-admin-font',
			HTW_PT_URL . 'assets/shared-font/fonts.css',
			array(),
			HTW_PT_VERSION
		);
		wp_enqueue_style(
			HTW_PT_SLUG . '-shell',
			HTW_PT_URL . 'assets/admin-shell.css',
			array( 'htw-admin-font' ),
			HTW_PT_VERSION
		);

		if ( '' !== $style ) {
			wp_enqueue_style(
				HTW_PT_SLUG . '-admin',
				HTW_PT_URL . 'assets/admin/' . $style,
				array( 'htw-admin-font', HTW_PT_SLUG . '-shell' ),
				HTW_PT_VERSION
			);
		}

		wp_enqueue_script(
			HTW_PT_SLUG . '-admin',
			HTW_PT_URL . 'assets/admin/' . $entry,
			array( 'wp-element', 'wp-api-fetch', 'wp-i18n', 'react-jsx-runtime' ),
			HTW_PT_VERSION,
			true
		);
		wp_add_inline_script(
			HTW_PT_SLUG . '-admin',
			'window.htwPtAdmin = ' . wp_json_encode(
				array(
					'namespace' => HTW_PT_SLUG . '/v1',
					'nonce'     => wp_create_nonce( 'wp_rest' ),
				)
			) . ';',
			'before'
		);
	}
}
