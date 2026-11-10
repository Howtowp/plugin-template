<?php
/**
 * Runs when the plugin is deleted from the WordPress dashboard.
 *
 * @package HowToWP\PluginTemplate
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Only remove data when the site owner opted in (Settings → Plugin Template).
$htw_pt_settings = get_option( 'htw_pt_settings', array() );

if ( ! empty( $htw_pt_settings['delete_data_on_uninstall'] ) ) {
	delete_option( 'htw_pt_settings' );
	delete_option( 'htw_pt_schema_version' );
	// Custom tables: drop them here with $wpdb->query() after $wpdb->prefix . 'htw_pt_<name>'.
}
