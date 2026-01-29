<?php

class PostSync_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function set_locale() {
		load_plugin_textdomain( // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
			'post-sync',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	public function add_plugin_admin_menu() {
		add_options_page(
			__( 'Post Sync Settings', 'post-sync' ),
			__( 'Post Sync', 'post-sync' ),
			'manage_options',
			'post-sync',
			array( $this, 'display_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'post_sync_options', 'ps_mode', array( 'sanitize_callback' => array( $this, 'sanitize_mode' ) ) );
		register_setting( 'post_sync_options', 'ps_targets', array( 'sanitize_callback' => array( $this, 'sanitize_targets' ) ) );
		register_setting( 'post_sync_options', 'ps_target_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'post_sync_options', 'ps_target_lang', array( 'sanitize_callback' => array( $this, 'sanitize_lang' ) ) );
		register_setting( 'post_sync_options', 'ps_target_chatgpt_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	}

	public function sanitize_mode( $input ) {
		$valid_modes = array( 'host', 'target' );
		return in_array( $input, $valid_modes, true ) ? $input : 'host';
	}

	public function sanitize_lang( $input ) {
		$valid_langs = array( 'French', 'Spanish', 'Hindi' );
		return in_array( $input, $valid_langs, true ) ? $input : 'French';
	}

	public function sanitize_targets( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		$clean = array();
		$host_domain = wp_parse_url( get_site_url(), PHP_URL_HOST );

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

		wp_localize_script( 'post-sync-admin', 'post_sync_l10n', array(
			'save_to_generate' => __( 'Save to generate key', 'post-sync' ),
			'remove'           => __( 'Remove', 'post-sync' ),
		) );
	}

	public function display_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Post Sync Settings', 'post-sync' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'post_sync_options' );
				do_settings_sections( 'post_sync_options' );
				$mode = get_option( 'ps_mode', 'host' );
				?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Mode', 'post-sync' ); ?></th>
						<td>
							<label><input type="radio" name="ps_mode" value="host" <?php checked( $mode, 'host' ); ?>> <?php esc_html_e( 'Host', 'post-sync' ); ?></label>
							<br>
							<label><input type="radio" name="ps_mode" value="target" <?php checked( $mode, 'target' ); ?>> <?php esc_html_e( 'Target', 'post-sync' ); ?></label>
						</td>
					</tr>
				</table>

				<hr>

				<!-- Host Settings -->
				<div id="host-settings" style="display: <?php echo ( $mode === 'host' ) ? 'block' : 'none'; ?>;">
					<h2><?php esc_html_e( 'Host Configuration', 'post-sync' ); ?></h2>
					<p><?php esc_html_e( 'Configure the target sites to push content to.', 'post-sync' ); ?></p>
					
					<table class="widefat" id="ps-targets-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Target URL', 'post-sync' ); ?></th>
								<th><?php esc_html_e( 'Key (Copy to Target)', 'post-sync' ); ?></th>
								<th><?php esc_html_e( 'Action', 'post-sync' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$targets = get_option( 'ps_targets', array() );
							if ( ! empty( $targets ) ) {
								foreach ( $targets as $index => $target ) {
									?>
									<tr>
										<td><input type="url" name="ps_targets[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $target['url'] ); ?>" class="regular-text"></td>
										<td>
											<input type="text" name="ps_targets[<?php echo esc_attr( $index ); ?>][key]" value="<?php echo esc_attr( $target['key'] ); ?>" class="regular-text" readonly>
										</td>
										<td><button type="button" class="button ps-remove-row"><?php esc_html_e( 'Remove', 'post-sync' ); ?></button></td>
									</tr>
									<?php
								}
							}
							?>
						</tbody>
					</table>
					<p><button type="button" class="button" id="ps-add-target"><?php esc_html_e( 'Add New Target', 'post-sync' ); ?></button></p>
				</div>

				<!-- Target Settings -->
				<div id="target-settings" style="display: <?php echo ( $mode === 'target' ) ? 'block' : 'none'; ?>;">
					<h2><?php esc_html_e( 'Target Configuration', 'post-sync' ); ?></h2>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Connection Key', 'post-sync' ); ?></th>
							<td>
								<input type="text" name="ps_target_key" value="<?php echo esc_attr( get_option( 'ps_target_key' ) ); ?>" class="large-text">
								<p class="description"><?php esc_html_e( 'Paste the key generated on the Host site.', 'post-sync' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Translation Language', 'post-sync' ); ?></th>
							<td>
								<select name="ps_target_lang">
									<?php $lang = get_option( 'ps_target_lang', 'French' ); ?>
									<option value="French" <?php selected( $lang, 'French' ); ?>><?php esc_html_e( 'French', 'post-sync' ); ?></option>
									<option value="Spanish" <?php selected( $lang, 'Spanish' ); ?>><?php esc_html_e( 'Spanish', 'post-sync' ); ?></option>
									<option value="Hindi" <?php selected( $lang, 'Hindi' ); ?>><?php esc_html_e( 'Hindi', 'post-sync' ); ?></option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'ChatGPT API Key', 'post-sync' ); ?></th>
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
