<?php

class PostSync_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function add_plugin_admin_menu() {
		add_options_page(
			'Post Sync Settings',
			'Post Sync',
			'manage_options',
			'post-sync',
			array( $this, 'display_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'post_sync_options', 'ps_mode' );
		register_setting( 'post_sync_options', 'ps_targets', array( 'sanitize_callback' => array( $this, 'sanitize_targets' ) ) );
		register_setting( 'post_sync_options', 'ps_target_key' );
		register_setting( 'post_sync_options', 'ps_target_lang' );
		register_setting( 'post_sync_options', 'ps_target_chatgpt_key' );
	}

	public function sanitize_targets( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		$clean = array();
		$host_domain = parse_url( get_site_url(), PHP_URL_HOST );

		foreach ( $input as $row ) {
			if ( empty( $row['url'] ) ) {
				continue;
			}
			$url = esc_url_raw( $row['url'] );
			$key = isset( $row['key'] ) ? sanitize_text_field( $row['key'] ) : '';

			if ( empty( $key ) ) {
				// Generate new key
				$key = PostSync_Auth::generate_key( $host_domain );
			}

			$clean[] = array(
				'url' => $url,
				'key' => $key,
			);
		}
		return $clean;
	}

	public function enqueue_scripts( $hook ) {
		if ( 'settings_page_post-sync' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'post-sync-admin',
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/post-sync-admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);
	}

	public function display_settings_page() {
		?>
		<div class="wrap">
			<h1>Post Sync Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'post_sync_options' );
				do_settings_sections( 'post_sync_options' );
				$mode = get_option( 'ps_mode', 'host' );
				?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row">Mode</th>
						<td>
							<label><input type="radio" name="ps_mode" value="host" <?php checked( $mode, 'host' ); ?>> Host</label>
							<br>
							<label><input type="radio" name="ps_mode" value="target" <?php checked( $mode, 'target' ); ?>> Target</label>
						</td>
					</tr>
				</table>

				<hr>

				<!-- Host Settings -->
				<div id="host-settings" style="display: <?php echo ( $mode === 'host' ) ? 'block' : 'none'; ?>;">
					<h2>Host Configuration</h2>
					<p>Configure the target sites to push content to.</p>
					
					<table class="widefat" id="ps-targets-table">
						<thead>
							<tr>
								<th>Target URL</th>
								<th>Key (Copy to Target)</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$targets = get_option( 'ps_targets', array() );
							if ( ! empty( $targets ) ) {
								foreach ( $targets as $index => $target ) {
									?>
									<tr>
										<td><input type="url" name="ps_targets[<?php echo $index; ?>][url]" value="<?php echo esc_attr( $target['url'] ); ?>" class="regular-text"></td>
										<td>
											<input type="text" name="ps_targets[<?php echo $index; ?>][key]" value="<?php echo esc_attr( $target['key'] ); ?>" class="regular-text" readonly>
										</td>
										<td><button type="button" class="button ps-remove-row">Remove</button></td>
									</tr>
									<?php
								}
							}
							?>
						</tbody>
					</table>
					<p><button type="button" class="button" id="ps-add-target">Add New Target</button></p>
				</div>

				<!-- Target Settings -->
				<div id="target-settings" style="display: <?php echo ( $mode === 'target' ) ? 'block' : 'none'; ?>;">
					<h2>Target Configuration</h2>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Connection Key</th>
							<td>
								<input type="text" name="ps_target_key" value="<?php echo esc_attr( get_option( 'ps_target_key' ) ); ?>" class="large-text">
								<p class="description">Paste the key generated on the Host site.</p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Translation Language</th>
							<td>
								<select name="ps_target_lang">
									<?php $lang = get_option( 'ps_target_lang', 'French' ); ?>
									<option value="French" <?php selected( $lang, 'French' ); ?>>French</option>
									<option value="Spanish" <?php selected( $lang, 'Spanish' ); ?>>Spanish</option>
									<option value="Hindi" <?php selected( $lang, 'Hindi' ); ?>>Hindi</option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">ChatGPT API Key</th>
							<td>
								<input type="password" name="ps_target_chatgpt_key" value="<?php echo esc_attr( get_option( 'ps_target_chatgpt_key' ) ); ?>" class="regular-text">
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
