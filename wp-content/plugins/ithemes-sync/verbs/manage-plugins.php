<?php

/*
Implementation of the manage-plugins verb.
Written by Chris Jean for iThemes.com
Version 1.2.0

Version History
	1.0.0 - 2013-10-01 - Chris Jean
		Initial version
	1.1.0 - 2014-01-29 - Chris Jean
		Added the install action.
	1.1.1 - 2014-01-29 - Chris Jean
		Updated the uninstall action to deactivate the plugins before attempting to uninstall.
	1.2.0 - 2014-05-20 - Chris Jean
		Added install-and-activate.
		Added network-activate as a duplicate of network_activate.
		Updated install output to include result, slug, and success details.
*/


class Ithemes_Sync_Verb_Manage_Plugins extends Ithemes_Sync_Verb {
	public static $name = 'manage-plugins';
	public static $description = 'Activate, deactivate, and uninstall plugins.';
	
	private $default_arguments = array();
	private $handled_activation = false;
	private $response = array();
	private $current_action = '';
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		
		
		$actions = array(
			'deactivate'           => 'deactivate_plugins',
			'install'              => 'install_plugins',
			'uninstall'            => 'uninstall_plugins',
			'install-and-activate' => 'install_and_activate_plugin',
			'activate'             => 'activate_plugin',
			'network-activate'     => 'network_activate_plugin',
			'network_activate'     => 'network_activate_plugin',
		);
		
		if ( isset( $actions['get-actions'] ) ) {
			$this->response['get-actions'] = array_keys( $actions );
		}
		
		foreach ( $arguments as $action => $data ) {
			$this->current_action = $action;
			
			if ( isset( $actions[$action] ) ) {
				$this->response[$action] = call_user_func( array( $this, $actions[$action] ), $data );
			} else {
				$this->response[$action] = 'This action is not recognized';
			}
		}
		
		
		return $this->response;
	}
	
	private function set_fatal_error_handler() {
		if ( function_exists( 'error_get_last' ) ) {
			register_shutdown_function( array( $this, 'handle_fatal_error' ) );
		}
	}
	
	private function handle_fatal_error() {
		$error = error_get_last();
		
		if ( is_array( $error ) ) {
			$this->response['error'] = array(
				'error_trigger_action' => $this->current_action,
				'error_details'        => $error,
			);
			
			$GLOBALS['ithemes_sync_request_handler']->send_response( $this->response );
		}
	}
	
	private function install_and_activate_plugin( $plugin ) {
		if ( ! is_string( $plugin ) ) {
			return new WP_Error( 'invalid-argument', 'The install-and-activate argument takes a string representing an individual plugin.' );
		}
		
		$result['install'] = $this->install_plugins( array( $plugin ) );
		
		if ( isset( $result['install'][$plugin] ) ) {
			$result['install'] = $result['install'][$plugin];
		}
		
		$this->response['install-and-activate'] = $result;
		
		if ( ! empty( $result['install']['slug'] ) ) {
			$result['activate'] = $this->activate_plugin( $result['install']['slug'] );
		} else {
			$result['activate'] = new WP_Error( 'skip-activate', 'Unable to activate due to failed install.' );
		}
		
		if ( ! is_wp_error( $result['activate'] ) ) {
			$result['success'] = true;
		}
		
		return $result;
	}
	
	private function activate_plugin( $plugin ) {
		if ( $this->handled_activation ) {
			return new WP_Error( 'already-handled-activation', 'Another activation has already been handled in this request. Only one activation per request is supported.' );
		}
		
		if ( ! is_string( $plugin ) ) {
			return new WP_Error( 'invalid-argument', 'The activate argument only accepts a string representing a single plugin.' );
		}
		
		$this->handled_activation = true;
		
		return activate_plugin( $plugin );
	}
	
	private function network_activate_plugin( $plugin ) {
		if ( $this->handled_activation ) {
			return new WP_Error( 'already-handled-activation', 'Another activation has already been handled in this request. Only one activation per request is supported.' );
		}
		
		if ( ! is_string( $plugin ) ) {
			return new WP_Error( 'invalid-argument', 'The network_activate argument only accepts a string representing a single plugin.' );
		}
		
		$this->handled_activation = true;
		
		return activate_plugin( $plugin, '', true );
	}
	
	private function deactivate_plugins( $plugins ) {
		return deactivate_plugins( (array) $plugins );
	}
	
	private function install_plugins( $plugins ) {
		require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
		require_once(dirname(dirname(__FILE__)) . '/upgrader-skin.php');
		
		$upgrader = new Plugin_Upgrader( new Ithemes_Sync_Upgrader_Skin() );
		
		$results = array();
		
		
		foreach ( (array) $plugins as $plugin ) {
			Ithemes_Sync_Functions::set_time_limit( 300 );
			
			if ( preg_match( '{^(http|https|ftp)://}i', $plugin ) ) {
				$result = $upgrader->install( $plugin );
			} else {
				$api = plugins_api( 'plugin_information', array( 'slug' => $plugin, 'fields' => array( 'sections' => false ) ) );
				
				if ( is_wp_error( $api ) ) {
					$result = $api;
				} else {
					$result = $upgrader->install( $api->download_link );
				}
			}
			
			if ( is_wp_error( $result ) ) {
				$results[$plugin]['error'] = array(
					'error_code'    => $result->get_error_code(),
					'error_details' => $result->get_error_message(),
				);
			} else {
				$results[$plugin] = array(
					'result' => $result,
					'slug'   => $upgrader->plugin_info(),
				);
				
				if ( true === $result ) {
					$results[$plugin]['success'] = true;
				}
			}
		}
		
		Ithemes_Sync_Functions::refresh_plugin_updates();
		
		
		return $results;
	}
	
	private function uninstall_plugins( $plugins ) {
		require_once(ABSPATH . '/wp-admin/includes/file.php');
		
		// First ensure that the plugins are deactivated.
		$this->deactivate_plugins( $plugins );
		
		return delete_plugins( (array) $plugins );
	}
}
