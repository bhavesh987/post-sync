<?php

class PostSync_Target {

	public function register_routes() {
		register_rest_route( 'post-sync/v1', '/receive', array(
			'methods' => 'POST',
			'callback' => array( $this, 'receive_post' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	public function check_permission( $request ) {
		$target_key = get_option( 'ps_target_key' );
		if ( empty( $target_key ) ) return false;

		$parsed = PostSync_Auth::parse_key( $target_key );
		if ( ! $parsed ) return false;

		$signature = $request->get_header( 'x_ps_signature' );
		$host_domain = $request->get_header( 'x_ps_domain' );
		$body = $request->get_body();

		if ( ! $signature || ! $host_domain ) return false;

		// Domain Check: We trust the Key's bound domain.
		if ( $host_domain !== $parsed['domain'] ) return false;

		// Signature Verificaton
		return PostSync_Auth::verify_signature( $body, $signature, $parsed['secret'] );
	}

	public function receive_post( $request ) {
		$params = $request->get_json_params();

		$origin_id = isset( $params['origin_id'] ) ? intval( $params['origin_id'] ) : 0;
		$host_domain = isset( $params['host_domain'] ) ? sanitize_text_field( $params['host_domain'] ) : '';
		
		if ( ! $origin_id || ! $host_domain ) {
			return new WP_Error( 'missing_params', 'Missing Origin ID or Domain', array( 'status' => 400 ) );
		}

		// Look for existing post using meta query
		$args = array(
			'post_type' => 'post',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'ps_origin_id',
					'value' => $origin_id,
				),
				array(
					'key' => 'ps_origin_host',
					'value' => $host_domain,
				),
			),
			'post_status' => 'any',
			'posts_per_page' => 1,
		);
		$query = new WP_Query( $args );
		$existing_id = 0;
		if ( $query->have_posts() ) {
			$existing_id = $query->posts[0]->ID;
		}

		// Handle Terms (Create if missing)
		$tags_input = array();
		if ( ! empty( $params['tags'] ) ) {
			foreach ( $params['tags'] as $tag_name ) {
				if ( ! term_exists( $tag_name, 'post_tag' ) ) {
					wp_insert_term( $tag_name, 'post_tag' );
				}
				$tags_input[] = $tag_name;
			}
		}

		$cats_input = array();
		if ( ! empty( $params['categories'] ) ) {
			foreach ( $params['categories'] as $cat_name ) {
				$term = term_exists( $cat_name, 'category' );
				if ( ! $term ) {
					$term_info = wp_insert_term( $cat_name, 'category' );
					if ( ! is_wp_error( $term_info ) ) {
						$cats_input[] = $term_info['term_id'];
					}
				} else {
					$cats_input[] = ( is_array( $term ) ) ? $term['term_id'] : $term;
				}
			}
		}

		$post_data = array(
			'post_title'    => sanitize_text_field( $params['title'] ),
			'post_content'  => wp_kses_post( $params['content'] ), // Safe HTML
			'post_excerpt'  => sanitize_textarea_field( $params['excerpt'] ),
			'post_status'   => 'publish',
			'post_type'     => 'post',
			'tags_input'    => $tags_input,
			'post_category' => $cats_input,
		);

		if ( $existing_id ) {
			$post_data['ID'] = $existing_id;
			$post_id = wp_update_post( $post_data );
			$action = 'update';
		} else {
			$post_id = wp_insert_post( $post_data );
			$action = 'create';
		}

		if ( is_wp_error( $post_id ) ) {
			PostSync_Logger::log( 'target', 'receive_post', 0, $host_domain, 'error', $post_id->get_error_message() );
			return new WP_Error( 'save_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
		}

		// Save Meta
		update_post_meta( $post_id, 'ps_origin_id', $origin_id );
		update_post_meta( $post_id, 'ps_origin_host', $host_domain );

		// Handle Featured Image
		if ( ! empty( $params['featured_image'] ) ) {
			$image_url = esc_url_raw( $params['featured_image'] );
			$this->sideload_image( $post_id, $image_url );
		}

		PostSync_Logger::log( 'target', 'receive_post', $post_id, $host_domain, 'success', "Post $action successful" );

		if ( get_option( 'ps_target_chatgpt_key' ) ) {
			// Schedule single event to run immediately (async)
			wp_schedule_single_event( time(), 'ps_process_translation', array( $post_id ) );
		}

		return new WP_REST_Response( array( 'message' => 'Post synced successfully', 'id' => $post_id ), 200 );
	}

	private function sideload_image( $post_id, $url ) {
		// Prevent re-downloading if same URL
		$attached = get_post_meta( $post_id, 'ps_featured_image_source', true );
		if ( $attached === $url && has_post_thumbnail( $post_id ) ) return; 

		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$desc = "Synced Image";
		$id = media_sideload_image( $url, $post_id, $desc, 'id' );

		if ( ! is_wp_error( $id ) ) {
			set_post_thumbnail( $post_id, $id );
			update_post_meta( $post_id, 'ps_featured_image_source', $url );
		}
	}
	
	public function process_translation_job( $post_id ) {
		// Will call Translator class
		$translator = new PostSync_Translator();
		$translator->translate_post( $post_id );
	}

}
