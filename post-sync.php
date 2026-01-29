<?php
/**
 * Plugin Name: Post Sync + On site Translation
 * Plugin URI: https://example.com/post-sync
 * Description: Syncs posts from a Host site to one or more Target sites using REST APIs with key-based auth. Translation happens on the Target.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'POST_SYNC_VERSION', '1.0.0' );
define( 'POST_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'POST_SYNC_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ps-activator.php
 */
function activate_post_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ps-activator.php';
	PostSync_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ps-deactivator.php
 */
function deactivate_post_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ps-deactivator.php';
	PostSync_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_post_sync' );
register_deactivation_hook( __FILE__, 'deactivate_post_sync' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-post-sync.php';

/**
 * Begins execution of the plugin.
 */
function run_post_sync() {
	$plugin = new PostSync();
	$plugin->run();
}
run_post_sync();
