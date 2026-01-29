<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package    PostSync
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop Logs Table
$table_name = $wpdb->prefix . 'ps_logs';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Delete Options
delete_option( 'ps_mode' );
delete_option( 'ps_targets' );
delete_option( 'ps_target_key' );
delete_option( 'ps_target_lang' );
delete_option( 'ps_target_chatgpt_key' );
