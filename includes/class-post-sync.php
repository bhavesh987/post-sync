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
	}

	private function define_admin_hooks() {
		// Admin hooks will be defined here
	}

	private function define_public_hooks() {
		// Public hooks will be defined here
	}

	public function run() {
		// Hooks are added in constructor
	}

}
