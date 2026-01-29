<?php

/**
 * Fired during plugin activation
 */
class PostSync_Activator {

	/**
	 * Create the logs table on activation.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ps_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			site_role varchar(20) NOT NULL,
			action varchar(50) NOT NULL,
			post_id bigint(20) NOT NULL,
			target_url varchar(255) DEFAULT '' NOT NULL,
			status varchar(20) NOT NULL,
			message text DEFAULT '' NOT NULL,
			time_taken float DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

}
