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
		// Typically we don't drop tables on deactivation to preserve data.
		// If strict cleanup was requested, we would drop it here.
	}

}
