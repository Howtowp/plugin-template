<?php
/**
 * Settings page (Settings API).
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Admin;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Core\Installer;
use HowToWP\PluginTemplate\Core\Module;
use HowToWP\PluginTemplate\Repositories\SettingsRepository;

/**
 * Settings → Plugin Template.
 */
final class SettingsPage implements Module {

	private const PAGE_SLUG = 'plugin-template';
	private const GROUP     = 'htw_pt_settings_group';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . HTW_PT_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Add the options page.
	 */
	public function add_menu(): void {
		add_options_page(
			__( 'Plugin Template', 'plugin-template' ),
			__( 'Plugin Template', 'plugin-template' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register setting, section and fields.
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			Installer::OPTION_SETTINGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => SettingsRepository::defaults(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section( 'htw_pt_main', __( 'General', 'plugin-template' ), '__return_false', self::PAGE_SLUG );

		add_settings_field( 'greeting', __( 'Greeting', 'plugin-template' ), array( $this, 'field_greeting' ), self::PAGE_SLUG, 'htw_pt_main' );
		add_settings_field( 'delete_data_on_uninstall', __( 'Remove data on uninstall', 'plugin-template' ), array( $this, 'field_delete' ), self::PAGE_SLUG, 'htw_pt_main' );
	}

	/**
	 * Sanitize submitted settings. Whitelist keys, cast types.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();
		return array(
			'greeting'                 => isset( $input['greeting'] ) ? sanitize_text_field( (string) $input['greeting'] ) : '',
			'delete_data_on_uninstall' => ! empty( $input['delete_data_on_uninstall'] ),
		);
	}

	/**
	 * Greeting field.
	 */
	public function field_greeting(): void {
		$value = (string) ( new SettingsRepository() )->get( 'greeting', '' );
		printf(
			'<input type="text" class="regular-text" name="%1$s[greeting]" value="%2$s" />',
			esc_attr( Installer::OPTION_SETTINGS ),
			esc_attr( $value )
		);
	}

	/**
	 * Delete-on-uninstall checkbox.
	 */
	public function field_delete(): void {
		$checked = (bool) ( new SettingsRepository() )->get( 'delete_data_on_uninstall', false );
		printf(
			'<label><input type="checkbox" name="%1$s[delete_data_on_uninstall]" value="1" %2$s /> %3$s</label>',
			esc_attr( Installer::OPTION_SETTINGS ),
			checked( $checked, true, false ),
			esc_html__( 'Delete all plugin data when the plugin is deleted.', 'plugin-template' )
		);
	}

	/**
	 * Render the page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '<form action="options.php" method="post">';
		settings_fields( self::GROUP );
		do_settings_sections( self::PAGE_SLUG );
		submit_button();
		echo '</form></div>';
	}

	/**
	 * Add a Settings link on the Plugins screen.
	 *
	 * @param array<int, string> $links Existing links.
	 * @return array<int, string>
	 */
	public function action_links( array $links ): array {
		$url = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'plugin-template' ) . '</a>' );
		return $links;
	}
}
