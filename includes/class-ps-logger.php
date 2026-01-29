<?php

class PostSync_Logger {

	public static function log( $site_role, $action, $post_id, $target_url, $status, $message, $time_taken = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ps_logs';

		$wpdb->insert(
			$table_name,
			array(
				'site_role' => $site_role,
				'action' => $action,
				'post_id' => $post_id,
				'target_url' => $target_url,
				'status' => $status,
				'message' => substr( $message, 0, 65535 ), // limit text size
				'time_taken' => $time_taken,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%f', '%s' )
		);
	}
}
