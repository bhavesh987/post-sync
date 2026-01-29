<?php
/**
 * Plugin Name: Post Sync + On site Translation
 * Plugin URI: https://postsync.com
 * Description: Syncs posts from a Host site to one or more Target sites using REST APIs with key-based auth. Translation happens on the Target.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0+
 * Text Domain: post-sync
 * Domain Path: /languages
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
function post_sync_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ps-activator.php';
	PostSync_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ps-deactivator.php
 */
function post_sync_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ps-deactivator.php';
	PostSync_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'post_sync_activate' );
register_deactivation_hook( __FILE__, 'post_sync_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-post-sync.php';

/**
 * Begins execution of the plugin.
 */
function post_sync_run() {
	new PostSync();
}
post_sync_run();
