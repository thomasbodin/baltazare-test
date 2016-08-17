<?php

/*
Provides an easy to use interface for communicating with the iThemes Sync server.
Written by Chris Jean for iThemes.com
Version 1.2.0

Version History
	1.0.0 - 2013-10-01 - Chris Jean
		Initial version
	1.0.1 - 2013-02-14 - Chris Jean
		Changed the request timeout to 30 seconds to accomodate sites that have longer delays when communicating with the Sync server.
	1.1.0 - 2014-03-28 - Chris Jean
		Added ping() and send_urgent_notices() function.
	1.1.1 - 2014-04-15 - Chris Jean
		Changed the reported WordPress version from using $wp_version to Ithemes_Sync_Functions::get_wordpress_version().
	1.1.2 - 2014-06-26 - Chris Jean
		Changed the WordPress version used to generate the salted password from using $wp_version to Ithemes_Sync_Functions::get_wordpress_version().
	1.2.0 - 2014-09-30 - Chris Jean
		Added new password hashing via the iThemes_Credentials class.
*/


class Ithemes_Sync_Server {
	private static $secure_server_url = 'https://sync-api.ithemes.com/';
	private static $insecure_server_url = 'http://sync-api.ithemes.com/';
	
	private static $password_iterations = 8;
	
	
	public static function authenticate( $username, $password ) {
		$query = array(
			'user' => $username,
		);
		
		$data = array(
			'auth_token' => self::get_password_hash( $username, $password ),
		);
		
		return self::request( 'authenticate-user', $query, $data );
	}
	
	public static function deauthenticate( $user_id, $username, $private_key ) {
		$query = array(
			'user_id' => $user_id,
			'user'    => $username,
		);
		
		$salt = hash( 'sha256', uniqid( '', true ) );
		
		$data = array(
			'hash' => hash( 'sha256', $user_id . $username . $private_key . $salt ),
			'salt' => $salt,
		);
		
		return self::request( 'deauthenticate-user', $query, $data );
	}
	
	public static function validate( $user_id, $username, $private_key ) {
		$query = array(
			'user_id' => $user_id,
			'user'    => $username,
		);
		
		$salt = hash( 'sha256', uniqid( '', true ) );
		
		$data = array(
			'hash' => hash( 'sha256', $user_id . $username . $private_key . $salt ),
			'salt' => $salt,
		);
		
		return self::request( 'validate-user', $query, $data );
	}
	
	public static function ping( $user_id, $username, $private_key ) {
		$query = array(
			'user_id' => $user_id,
			'user'    => $username,
		);
		
		$salt = hash( 'sha256', uniqid( '', true ) );
		
		$data = array(
			'hash' => hash( 'sha256', $user_id . $username . $private_key . $salt ),
			'salt' => $salt,
		);
		
		return self::request( 'ping-site', $query, $data );
	}
	
	public static function send_urgent_notices( $user_id, $username, $private_key, $notices ) {
		$query = array(
			'user_id' => $user_id,
			'user'    => $username,
		);
		
		$salt = hash( 'sha256', uniqid( '', true ) );
		
		$data = array(
			'hash'    => hash( 'sha256', $user_id . $username . $private_key . $salt ),
			'salt'    => $salt,
			'notices' => $notices,
		);
		
		return self::request( 'send-urgent-notices', $query, $data );
	}
	
	public static function request( $action, $query = array(), $data = array() ) {
		if ( isset( $data['auth_token'] ) ) {
			$data['iterations'] = self::$password_iterations;
		}
		
		require_once(dirname(__FILE__) . '/functions.php');
		$default_query = array(
			'wp'           => Ithemes_Sync_Functions::get_wordpress_version(),
			'site'         => get_bloginfo( 'url' ),
			'timestamp'    => time(),
			'auth_version' => '2',
		);
		
		if ( is_multisite() ) {
			$default_query['ms'] = 1;
		}
		
		$query = array_merge( $default_query, $query );
		$query['action'] = $action;
		
		$request = '?' . http_build_query( $query, '', '&' );
		
		$post_data = array(
			'request' => json_encode( $data ),
		);
		
		$remote_post_args = array(
			'timeout' => 30,
			'body'    => $post_data,
		);
		
		
		$options = array(
			'use_ca_patch' => false,
			'use_ssl'      => true,
		);
		
		$patch_enabled = $GLOBALS['ithemes-sync-settings']->get_option( 'use_ca_patch' );
		
		
		if ( $patch_enabled ) {
			$response = self::do_patched_post( $request, $remote_post_args );
			
			if ( is_wp_error( $response ) ) {
				$response = wp_remote_post( self::$secure_server_url . $request, $remote_post_args );
			} else {
				$options['use_ca_patch'] = true;
			}
		}
		else {
			$response = wp_remote_post( self::$secure_server_url . $request, $remote_post_args );
			
			if ( is_wp_error( $response ) ) {
				$response = self::do_patched_post( $request, $remote_post_args );
				
				if ( ! is_wp_error( $response ) ) {
					$options['use_ca_patch'] = true;
				}
			}
		}
		
		if ( is_wp_error( $response ) ) {
			$response = wp_remote_post( self::$insecure_server_url . $request . '&insecure=1', $remote_post_args );
			
			$options['use_ssl'] = false;
			$options['use_ca_patch'] = false;
		}
		
		
		$GLOBALS['ithemes-sync-settings']->update_options( $options );
		
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( 200 != $response['response']['code'] ) {
			return new WP_Error( 'ithemes-sync-server-failed-request', $response['response']['message'] );
		}
		
		
		$body = json_decode( $response['body'], true );
		
		if ( ! is_array( $body ) ) {
			return new WP_Error( 'ithemes-sync-server-unknown-response', __( 'An unrecognized server response format was received from the iThemes Sync server.', 'it-l10n-ithemes-sync' ) );
		}
		
		if ( ! empty( $body['error'] ) ) {
			return new WP_Error( $body['error']['type'], $body['error']['message'] );
		}
		
		
		return $body;
	}
	
	private static function do_patched_post( $request, $remote_post_args ) {
		self::enable_ssl_ca_patch();
		$response = wp_remote_post( self::$secure_server_url . $request . '&ca_patch=1', $remote_post_args );
		self::disable_ssl_ca_patch();
		
		return $response;
	}
	
	private static function get_password_hash( $username, $password ) {
		require_once(ABSPATH . 'wp-includes/class-phpass.php');
		require_once(dirname(__FILE__) . '/class-ithemes-credentials.php');
		
		$password = iThemes_Credentials::get_password_hash( $username, $password );
		
		$salted_password = $password . $username . get_bloginfo( 'url' ) . Ithemes_Sync_Functions::get_wordpress_version();
		$salted_password = substr( $salted_password, 0, max( strlen( $password ), 512 ) );
		
		$hasher = new PasswordHash( self::$password_iterations, true );
		$auth_token = $hasher->HashPassword( $salted_password );
		
		return $auth_token;
	}
	
	public static function add_ca_patch_to_curl_opts( $handle ) {
		$url = curl_getinfo( $handle, CURLINFO_EFFECTIVE_URL );
		
		if ( ! preg_match( '/^' . preg_quote( self::$secure_server_url, '/' ) . '/', $url ) ) {
			return;
		}
		
		curl_setopt( $handle, CURLOPT_CAINFO, dirname( __FILE__ ) . '/ca/roots.crt' );
	}
	
	public static function enable_ssl_ca_patch() {
		add_action( 'http_api_curl', array( __CLASS__, 'add_ca_patch_to_curl_opts' ) );
	}
	
	public static function disable_ssl_ca_patch() {
		remove_action( 'http_api_curl', array( __CLASS__, 'add_ca_patch_to_curl_opts' ) );
	}
}
