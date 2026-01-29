<?php

class PostSync {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'post-sync';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once POST_SYNC_PATH . 'includes/class-ps-logger.php';
		require_once POST_SYNC_PATH . 'includes/class-ps-auth.php';
		require_once POST_SYNC_PATH . 'admin/class-ps-admin.php';
	}

	private function define_admin_hooks() {
		$plugin_admin = new PostSync_Admin( $this->plugin_name, $this->version );
		add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $plugin_admin, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
	}

	private function define_public_hooks() {
		// Public hooks will be defined here
	}

	public function run() {
		// Hooks are added in constructor
	}

}
