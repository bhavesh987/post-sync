<?php

/**
 * Fired during plugin deactivation
 */
class PostSync_Deactivator {

	/**
	 * Deactivation logic.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Flush rewrite rules to ensure that the site environment is clean.
		flush_rewrite_rules();
	}

}
