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
$post_sync_logs_table = $wpdb->prefix . 'ps_logs';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
$wpdb->query( 'DROP TABLE IF EXISTS ' . $post_sync_logs_table );

// Delete Options
delete_option( 'ps_mode' );
delete_option( 'ps_targets' );
delete_option( 'ps_target_key' );
delete_option( 'ps_target_lang' );
delete_option( 'ps_target_chatgpt_key' );
