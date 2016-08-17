<?php

/*
Implementation of the manage-users verb.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.1 - 2014-08-01 - Aaron D. Campbell
		Add get-meta, set-meta, and delete-meta actions
	1.0.0 - 2014-06-02 - Chris Jean
		Initial version
*/


class Ithemes_Sync_Verb_Manage_Users extends Ithemes_Sync_Verb {
	public static $name = 'manage-users';
	public static $description = 'Create, edit, and delete users.';
	
	private $default_arguments = array();
	private $response = array();
	private $user_cache = array();
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		require_once(ABSPATH . 'wp-admin/includes/user.php');
		
		
		$actions = array(
			'add'         => 'wp_insert_user',
			'edit'        => 'wp_update_user',
			'set-role'    => array( 'user', 'set_role' ),
			'add-role'    => array( 'user', 'add_role' ),
			'remove-role' => array( 'user', 'remove_role' ),
			'add-cap'     => array( 'user', 'add_cap' ),
			'remove-cap'  => array( 'user', 'remove_cap' ),
			'get-meta'    => 'get_user_meta',
			'set-meta'    => 'update_user_meta',
			'remove-meta' => 'delete_user_meta',
			'delete'      => 'wp_delete_user',
		);
		
		foreach ( $arguments as $action => $data ) {
			if ( 'get-actions' == $action ) {
				$this->response[$action] = array_keys( $actions );
				continue;
			}
			
			if ( ! isset( $actions[$action] ) ) {
				$this->response[$action] = 'This action is not recognized.';
				continue;
			}
			if ( ! is_array( $data ) ) {
				$this->response[$action] = new WP_Error( 'invalid-argument', 'This action requires an array.' );
				continue;
			}
			
			$this->response[$action] = $this->run_function( $actions[$action], $data );
		}
		
		return $this->response;
	}
	
	private function run_function( $function, $data ) {
		if ( ! is_array( $function ) && ! is_callable( $function ) ) {
			return new WP_Error( "missing-function-$function", "Due to an unknown issue, the $function function is not available." );
		}
		
		
		$response = array();
		$update_user_level = false;
		
		if ( is_array( $function ) && preg_match( '/_cap$/', $function[1] ) ) {
			$update_user_level = true;
		}
		
		foreach ( $data as $id => $params ) {
			if ( ! is_array( $function ) ) {
				$response[$id] = call_user_func_array( $function, $params );
				continue;
			}
			
			
			$function = $function[1];
			
			if ( isset( $this->user_cache[$id] ) ) {
				$user = $this->user_cache[$id];
			} else {
				$user = get_user_by( 'id', $id );
				$this->user_cache[$id] = $user;
			}
			
			if ( ! is_object( $user ) || ! is_a( $user, 'WP_User' ) ) {
				$response[$id] = new WP_Error( 'invalid-user-id', "Unable to find the requested user with an ID of $id." );
				continue;
			}
			
			if ( ! is_callable( array( $user, $function ) ) ) {
				$response[$id] = new WP_Error( "missing-function-user-$function", "Due to an unknown issue, the \$user->$function function is not available." );
				continue;
			}
			
			if ( ! is_array( $params[0] ) ) {
				$response[$id] = call_user_func_array( array( $user, $function ), $params );
			} else {
				foreach ( $params as $index => $param ) {
					$response[$id][$index] = call_user_func_array( array( $user, $function ), $param );
				}
			}
			
			if ( $update_user_level ) {
				$user->update_user_level_from_caps();
			}
		}
		
		return $response;
	}
}
