<?php

class PostSync_Host {

	public function handle_post_save( $post_id, $post, $update ) {
		// Checks
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;
		if ( 'post' !== $post->post_type ) return;
		if ( 'publish' !== $post->post_status ) return;

		// Get Targets
		$targets = get_option( 'ps_targets', array() );
		if ( empty( $targets ) ) return;

		// Prepare Payload
		$categories = get_the_terms( $post_id, 'category' );
		$tags = get_the_terms( $post_id, 'post_tag' );
		
		$cat_names = $categories ? wp_list_pluck( $categories, 'name' ) : array();
		$tag_names = $tags ? wp_list_pluck( $tags, 'name' ) : array();

		$featured_image_url = '';
		if ( has_post_thumbnail( $post_id ) ) {
			$featured_image_url = get_the_post_thumbnail_url( $post_id, 'full' );
		}

		$payload = array(
			'origin_id' => $post_id,
			'title'     => $post->post_title,
			'content'   => $post->post_content,
			'excerpt'   => $post->post_excerpt,
			'categories'=> $cat_names,
			'tags'      => $tag_names,
			'featured_image' => $featured_image_url,
			'host_domain' => parse_url( get_site_url(), PHP_URL_HOST ),
		);
		$body = json_encode( $payload );

		// Push to each target
		foreach ( $targets as $target ) {
			if ( empty( $target['url'] ) || empty( $target['key'] ) ) continue;

			$this->push_to_target( $target, $payload, $body, $post_id );
		}
	}

	private function push_to_target( $target, $payload, $body, $post_id ) {
		// The Host has the generated key, which is encoded.
		// We need to parse it to get the 'secret' to sign the request.
		$parsed = PostSync_Auth::parse_key( $target['key'] );
		
		if ( ! $parsed ) {
			PostSync_Logger::log( 'host', 'sync_push', $post_id, $target['url'], 'error', 'Invalid Key Format' );
			return;
		}

		$secret = $parsed['secret'];
		$signature = PostSync_Auth::sign_request( $body, $secret );

		$endpoint = trailingslashit( $target['url'] ) . 'wp-json/post-sync/v1/receive';
		
		$args = array(
			'body'        => $body,
			'headers'     => array(
				'Content-Type' => 'application/json',
				'X-PS-Signature' => $signature,
				'X-PS-Domain' => $payload['host_domain'],
			),
			'timeout'     => 15,
			'blocking'    => true, 
			'sslverify'   => apply_filters( 'ps_ssl_verify', true ), // Allow overriding for local dev
		);

		$start_time = microtime( true );
		$response = wp_remote_post( $endpoint, $args );
		$end_time = microtime( true );
		$duration = $end_time - $start_time;

		if ( is_wp_error( $response ) ) {
			PostSync_Logger::log( 'host', 'sync_push', $post_id, $target['url'], 'error', $response->get_error_message(), $duration );
		} else {
			$code = wp_remote_retrieve_response_code( $response );
			$msg = wp_remote_retrieve_body( $response );
			$status = ( $code >= 200 && $code < 300 ) ? 'success' : 'error';
			
			// Try to get clean message from JSON
			$json_msg = json_decode( $msg, true );
			if ( $json_msg && isset( $json_msg['message'] ) ) {
				$msg = $json_msg['message'];
			} elseif ( $json_msg && isset( $json_msg['code'] ) ) {
				$msg = $json_msg['code'] . ': ' . ( isset($json_msg['message']) ? $json_msg['message'] : '' );
			} else {
				$msg = substr( strip_tags( $msg ), 0, 200 ); // fallback truncate
			}

			PostSync_Logger::log( 'host', 'sync_push', $post_id, $target['url'], $status, "HTTP $code: $msg", $duration );
		}
	}

}
