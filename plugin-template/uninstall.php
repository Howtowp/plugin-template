<?php
/**
 * Runs when the plugin is deleted from the WordPress dashboard.
 *
 * Data is removed only when the settings screen opted in.
 *
 * @package HowToWP\PluginTemplate
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$htw_pt_settings = get_option( 'htw_pt_settings', array() );

if ( is_array( $htw_pt_settings ) && ! empty( $htw_pt_settings['delete_data_on_uninstall'] ) ) {
	delete_option( 'htw_pt_settings' );
	delete_option( 'htw_pt_texts' );

	global $wpdb;

	$htw_pt_notes = $wpdb->prefix . 'htw_pt_notes';
	// Prefix plus a fixed suffix. Not user input, so it is not prepared.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$htw_pt_notes}`" );
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
