<?php

/*
Handle requests from Sync server.
Written by Chris Jean for iThemes.com
Version 1.3.5

Version History
	1.2.0 - 2014-01-20 - Chris Jean
		Changed send_response() from private to public to allow for sending responses from error handlers.
	1.2.1 - 2014-02-18 - Chris Jean
		Added a compatibility check to ensure that Gravity Forms's updates show up and can be applied.
		Added a function to fake that the request is taking place on an admin page. This is rudimentary and won't work for every situation.
	1.2.2 - 2014-02-19 - Chris Jean
		Changed method_exists to is_callable in order to avoid server-specific compatibility issues.
	1.3.0 - 2014-02-21 - Chris Jean
		Renamed hide_errors() to restore_error_settings() as that's a more accurate name.
		Added hide_errors() to hide all error output.
		hide_errors() is called when the response is done rendering to prevent stray output from messing up the server's parsing of the output.
	1.3.1 - 2014-03-06 - Chris Jean
		Rearranged permission-escalation code to after the request is authenticated.
		Sync requests will now be set to emulate an Administrator user to avoid checks by some security plugins.
		Added set_full_user_capabilities(), unset_full_user_capabilities(), and filter_user_has_cap().
	1.3.2 - 2014-08-22 - Chris Jean
		In order to avoid stale data, external object caches are now disabled on all authenticated requests.
	1.3.3 - 2014-08-25 - Chris Jean
		Disable two-factor authentication checks from the Duo Two-Factor Authentication plugin when an authenticated request is being handled.
	1.3.4 - 2014-10-13 - Chris Jean
		Added stronger verification of the $_POST['request'] data.
		Obfuscated the missing-var error responses.
	1.3.5 - 2014-11-21 - Chris Jean
		Added smarter use of stripslashes() to avoid issues in decoding utf8 characters.
		Moved validation check of $_POST['request'] to the constructor in order to better handle both forms of requests (legacy and admin-ajax.php).
*/


require_once( $GLOBALS['ithemes_sync_path'] . '/load-translations.php' );

class Ithemes_Sync_Request_Handler {
	private $logs = array();
	private $options = array();
	private $old_update_data = array();
	private $verb_time = false;
	
	
	public function __construct() {
		$this->show_errors();
		
		
		if ( empty( $_POST['request'] ) ) {
			return;
		}
		
		$request = $_POST['request'];
		
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || get_magic_quotes_gpc() ) {
			$request = stripslashes( $request );
		}
		
		$request = json_decode( $request, true );
		
		if ( ! is_array( $request ) ) {
			return;
		}
		
		
		$GLOBALS['ithemes_sync_request_handler'] = $this;
		
		
		add_action( 'ithemes-sync-add-log', array( $this, 'add_log' ), 10, 2 );
		add_action( 'shutdown', array( $this, 'handle_error' ) );
		
		add_action( 'ithemes_sync_verbs_registered', array( $this, 'handle_request' ) );
		
		require_once(dirname(__FILE__) . '/api.php');
		require_once(dirname(__FILE__) . '/functions.php');
		require_once(dirname(__FILE__) . '/settings.php');
		
		$this->options = $GLOBALS['ithemes-sync-settings']->get_options();
		
		$this->parse_request( $request );
		
		Ithemes_Sync_Functions::set_time_limit( 60 );
		
		$this->set_is_admin_to_true();
		$this->set_current_user_to_admin();
		$this->set_full_user_capabilities();
		$this->disable_ext_object_cache();
		$this->disable_2fa_verification();
	}
	
	private function show_errors() {
		$this->original_display_errors = ini_set( 'display_errors', 1 );
		$this->original_error_reporting = error_reporting( E_ALL );
	}
	
	private function restore_error_settings() {
		ini_set( 'display_errors', $this->original_display_errors );
		error_reporting( $this->original_error_reporting );
	}
	
	private function hide_errors() {
		ini_set( 'display_errors', 0 );
		error_reporting( null );
	}
	
	private function set_is_admin_to_true() {
		if ( defined( 'ITHEMES_SYNC_SKIP_SET_IS_ADMIN_TO_TRUE' ) && ITHEMES_SYNC_SKIP_SET_IS_ADMIN_TO_TRUE ) {
			return;
		}
		
		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}
	}
	
	private function disable_ext_object_cache() {
		// This disables object caching that many caching plugins offer which prevents the cache from supplying stale data to Sync.
		if ( is_callable( 'wp_using_ext_object_cache' ) ) {
			wp_using_ext_object_cache( false );
		}
	}
	
	private function disable_2fa_verification() {
		// Disable 2FA verification of the Duo Two-Factor Authentication plugin.
		add_filter( 'pre_site_option_duo_ikey', array( $this, 'return_empty_string' ) );
		add_filter( 'pre_option_duo_ikey', array( $this, 'return_empty_string' ) );
	}
	
	private function set_current_user_to_admin() {
		if ( ! class_exists( 'WP_Roles' ) ) {
			do_action( 'ithemes-sync-add-log', 'The WP_Roles class does not exist. Unable to set current user to admin.' );
			return false;
		}
		
		$wp_roles = new WP_Roles();
		
		if ( ! isset( $wp_roles->roles ) ) {
			do_action( 'ithemes-sync-add-log', 'Unable to find user roles. Unable to set current user to admin.', compact( 'wp_roles' ) );
			return false;
		}
		
		$roles = $wp_roles->roles;
		
		$max_caps = 0;
		$power_role = false;
		
		foreach ( $roles as $role => $role_data ) {
			if ( ! isset( $role_data['capabilities'] ) ) {
				continue;
			}
			
			$cap_count = count( $role_data['capabilities'] );
			$new_role = false;
			
			if ( $cap_count > $max_caps ) {
				$power_role = $role;
				$max_caps = $cap_count;
			} else if ( ( $cap_count == $max_caps ) && ( 'administrator' == $role ) ) {
				$power_role = $role;
				$max_caps = $cap_count;
			}
		}
		
		if ( false === $power_role ) {
			if ( isset( $roles['administrator'] ) ) {
				$power_role = 'administrator';
			} else {
				$role_names = array_keys( $roles );
				$power_role = $roles[0];
			}
		}
		
		
		if ( false === $power_role ) {
			do_action( 'ithemes-sync-add-log', 'Unable to find a power user role. Unable to set current user to admin.', compact( 'wp_roles' ) );
			return false;
		}
		
		
		if ( ! function_exists( 'get_users' ) ) {
			do_action( 'ithemes-sync-add-log', 'get_users() function does not exist. Unable to set current user to admin.' );
			return false;
		}
		
		$users = get_users( array( 'role' => $power_role ) );
		
		if ( ! is_array( $users ) ) {
			do_action( 'ithemes-sync-add-log', 'get_users() retured a non-array. Unable to set current user to admin.', $users );
			return false;
		}
		
		
		$user = current( $users );
		
		if ( isset( $user->ID ) ) {
			$GLOBALS['current_user'] = $user;
		} else {
			do_action( 'ithemes-sync-add-log', 'Unable to find a valid user object for the power user role. Unable to set current user to admin.', $user );
			return false;
		}
		
		return true;
	}
	
	private function set_full_user_capabilities() {
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 1000, 3 );
	}
	
	private function unset_full_user_capabilities() {
		remove_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 1000 );
	}
	
	public function filter_user_has_cap( $capabilities, $caps, $args ) {
		foreach ( $caps as $cap ) {
			$capabilities[$cap] = 1;
		}
		
		return $capabilities;
	}
	
	private function parse_request( $request ) {
		if ( empty( $this->options['authentications'] ) ) {
			$this->send_response( new WP_Error( 'site-not-authenticated', 'The site does not have any authenticated users.' ) );
		}
		
		
		$this->request = $request;
		
		
		$required_vars = array(
			'1' => 'action',
			'2' => 'arguments',
			'3' => 'user_id',
			'4' => 'hash',
			'5' => 'salt',
		);
		
		foreach ( $required_vars as $index => $var ) {
			if ( ! isset( $request[$var] ) ) {
				$this->send_response( new WP_Error( "missing-var-$index", 'Invalid request.' ) );
			}
		}
		
		
		if ( ! isset( $this->options['authentications'][$request['user_id']] ) ) {
			$this->send_response( new WP_Error( 'user-not-authenticated', 'The requested user is not authenticated.' ) );
		}
		
		
		$user_data = $this->options['authentications'][$request['user_id']];
		
		$hash = hash( 'sha256', $request['user_id'] . $request['action'] . $this->json_encode( $request['arguments'] ) . $user_data['key'] . $request['salt'] );
		
		if ( $hash !== $request['hash'] ) {
			$this->send_response( new WP_Error( 'hash-mismatch', 'The hash could not be validated as a correct hash.' ) );
		}
	}
	
	public function handle_request() {
		$this->add_third_party_compatibility();
		$this->disable_updater_transient_pre_filters();
		$this->add_old_plugin_updater_support();
		
		$start_time = microtime( true );
		$results = $GLOBALS['ithemes-sync-api']->run( $this->request['action'], $this->request['arguments'] );
		$this->verb_time = microtime( true ) - $start_time;
		
		$this->send_response( $results );
	}
	
	public function send_response( $data ) {
		if ( is_wp_error( $data ) ) {
			foreach ( $data->get_error_codes() as $code )
				$response['errors'][$code] = $data->get_error_message( $code );
		}
		else {
			$response = array(
				'response' => $data,
			);
		}
		
		if ( ! empty( $this->logs ) ) {
			$response['logs'] = $this->logs;
		}
		
		$response['verb_time'] = $this->verb_time;
		$json = $this->json_encode( $response );
		
		echo "\n\nv56CHRcOT+%K\$fk[*CrQ9B5<~9T=h?xx9C</`Sqv;M{Q0ms:FR0w\n\n$json";
		
		$this->hide_errors();
		
		remove_action( 'shutdown', array( $this, 'handle_error' ) );
		
		exit;
	}
	
	private function add_third_party_compatibility() {
		if ( is_callable( array( 'RGForms', 'check_update' ) ) ) {
			add_filter( 'transient_update_plugins', array( 'RGForms', 'check_update' ) );
			add_filter( 'site_transient_update_plugins', array( 'RGForms', 'check_update' ) );
		}
	}
	
	private function disable_updater_transient_pre_filters() {
		// Avoid conflicts with plugins that pre-filter the update transients.
		add_filter( 'pre_site_transient_update_plugins', array( $this, 'return_false' ), 9999 );
		add_filter( 'pre_site_transient_update_themes', array( $this, 'return_false' ), 9999 );
		add_filter( 'pre_site_transient_update_core', array( $this, 'return_false' ), 9999 );
	}
	
	public function return_false() {
		return false;
	}
	
	private function add_old_plugin_updater_support() {
		$plugins = Ithemes_Sync_Functions::get_plugin_details();
		
		$data['3.0'] = get_site_transient( 'update_plugins' );
		$data['2.8'] = get_transient( 'update_plugins' );
		$data['2.6'] = get_option( 'update_plugins' );
		
		foreach ( array( '2.8', '2.6' ) as $version ) {
			if ( is_object( $data[$version] ) && ! empty( $data[$version]->response ) ) {
				foreach ( $data[$version]->response as $plugin => $plugin_data ) {
					if ( ! empty( $data['3.0']->response[$plugin] ) || ! empty( $this->old_update_data['plugins'][$plugin] ) ) {
						continue;
					}
					
					if ( ! empty( $plugins[$plugin] ) && ! empty( $plugins[$plugin]['Version'] ) && version_compare( $plugin_data->new_version, $plugins[$plugin]['Version'], '<=' ) ) {
						continue;
					}
					
					$this->old_update_data['plugins'][$plugin] = $plugin_data;
				}
			}
		}
		
		if ( empty( $this->old_update_data['plugins'] ) ) {
			return;
		}
		
		
		add_filter( 'site_transient_update_plugins', array( $this, 'filter_update_plugins_add_old_update_data' ) );
	}
	
	public function filter_update_plugins_add_old_update_data( $update_plugins ) {
		if ( ! isset( $update_plugins->response ) || ! is_array( $update_plugins->response ) ) {
			return $update_plugins;
		}
		
		foreach ( $this->old_update_data['plugins'] as $plugin => $plugin_data ) {
			if ( ! empty( $update_plugins->response[$plugin] ) )
				continue;
			
			$plugin_data->from_old_update_data = true;
			$update_plugins->response[$plugin] = $plugin_data;
		}
		
		return $update_plugins;
	}
	
	public function remove_old_update_plugins_data( $plugin ) {
		if ( empty( $this->old_update_data['plugins'] ) || ! isset( $this->old_update_data['plugins'][$plugin] ) ) {
			return null;
		}
		
		$data['2.8'] = get_transient( 'update_plugins' );
		$data['2.6'] = get_option( 'update_plugins' );
		
		$found_match = array();
		
		foreach ( array( '2.8', '2.6' ) as $version ) {
			$found_match[$version] = false;
			
			if ( is_object( $data[$version] ) && ! empty( $data[$version]->response ) && isset( $data[$version]->response[$plugin] ) ) {
				unset( $data[$version]->response[$plugin] );
				$found_match[$version] = true;
			}
			
			if ( empty( $data[$version]->response ) && ( 1 == count( get_object_vars( $data[$version] ) ) ) ) {
				$data[$version] = false;
			}
		}
		
		if ( $found_match['2.8'] ) {
			if ( false === $data['2.8'] ) {
				delete_transient( 'update_plugins' );
			} else {
				update_transient( 'update_plugins', $data['2.8'] );
			}
		}
		
		if ( $found_match['2.6'] ) {
			if ( false === $data['2.6'] ) {
				delete_option( 'update_plugins' );
			} else {
				update_option( 'update_plugins', $data['2.6'] );
			}
		}
		
		
		return ( $found_match['2.8'] || $found_match['2.6'] );
	}
	
	public function add_log( $description, $data = 'nD{k*v8}Qn4x=_7/j&r83cGD?%GWk}wb6[xal[9;y`PfpLSY[7O>b' ) {
		if ( is_wp_error( $description ) ) {
			$description = array(
				'type'  => 'WP_Error',
			);
			
			$codes = $description->get_error_codes();
			$messages = $description->get_error_messages();
			
			if ( 1 == count( $codes ) ) {
				$description['code'] = current( $codes );
				$description['message'] = current( $messages );
			} else {
				$description['codes'] = $codes;
				$description['messages'] = $messages;
			}
		}
		
		$log['description'] = $description;
		
		if ( 'nD{k*v8}Qn4x=_7/j&r83cGD?%GWk}wb6[xal[9;y`PfpLSY[7O>b' != $data ) {
			$log['data'] = $data;
		}
		
		$this->logs[] = $log;
	}
	
	public function handle_error() {
		$this->send_response( new WP_Error( 'unhandled_request', 'This request was not handled by any registered verb. This was likely caused by a fatal error.' ) );
	}
	
	public function return_empty_string() {
		return '';
	}
	
	private function json_encode( $data ) {
		$json = json_encode( $data );
		
		if ( false !== $json ) {
			return $json;
		}
		
		require_once(dirname(__FILE__) . '/class-ithemes-sync-json.php');
		
		return Ithemes_Sync_JSON::encode( $data );
	}
}

new Ithemes_Sync_Request_Handler();
