<?php

class PostSync_Auth {

	public static function generate_key( $host_domain ) {
		// Generate a strong random secret
		$secret = bin2hex( random_bytes( 16 ) );
		// Combine secret and host domain
		$raw_key = $secret . '|' . $host_domain;
		// Encode to make it copy-paste friendly
		return base64_encode( $raw_key );
	}

	public static function parse_key( $key ) {
		$decoded = base64_decode( $key );
		if ( ! $decoded || strpos( $decoded, '|' ) === false ) {
			return false;
		}
		list( $secret, $domain ) = explode( '|', $decoded, 2 );
		return array(
			'secret' => $secret,
			'domain' => $domain,
		);
	}

	public static function sign_request( $body, $secret ) {
		return hash_hmac( 'sha256', $body, $secret );
	}

	public static function verify_signature( $body, $signature, $secret ) {
		$computed = self::sign_request( $body, $secret );
		return hash_equals( $computed, $signature );
	}
}
