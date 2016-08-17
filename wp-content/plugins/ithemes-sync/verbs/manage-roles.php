<?php

/*
Implementation of the manage-roles verb.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2014-06-02 - Chris Jean
		Initial version
*/


class Ithemes_Sync_Verb_Manage_Roles extends Ithemes_Sync_Verb {
	public static $name = 'manage-roles';
	public static $description = 'Create, edit, and delete roles.';
	
	private $default_arguments = array();
	private $response = array();
	private $role_cache = array();
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		
		$actions = array(
			'add'         => 'add_role',
			'add-cap'     => array( $this, 'add_cap' ),
			'remove-cap'  => array( $this, 'remove_cap' ),
			'delete'      => 'remove_role',
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
		
		foreach ( $data as $id => $params ) {
			if ( ! is_array( $function ) ) {
				$response[$id] = call_user_func_array( $function, $params );
				continue;
			}
			
			
			$function = $function[1];
			
			if ( isset( $this->role_cache[$id] ) ) {
				$role = $this->role_cache[$id];
			} else {
				$role = get_role( $id );
				$this->role_cache[$id] = $role;
			}
			
			if ( ! is_object( $role ) || ! is_a( $role, 'WP_Role' ) ) {
				$response[$id] = new WP_Error( 'invalid-role-name', "Unable to find the requested role with the name of $id." );
				continue;
			}
			
			if ( ! is_callable( array( $role, $function ) ) ) {
				$response[$id] = new WP_Error( "missing-function-role-$function", "Due to an unknown issue, the \$role->$function function is not available." );
				continue;
			}
			
			if ( ! is_array( $params[0] ) ) {
				$response[$id] = call_user_func_array( array( $role, $function ), $params );
			} else {
				foreach ( $params as $index => $param ) {
					$response[$id][$index] = call_user_func_array( array( $role, $function ), $param );
				}
			}
		}
		
		return $response;
	}
}
