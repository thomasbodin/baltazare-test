<?php

/**
 * Implementation of the manage-options verb.
 * Written by Aaron D. Campbell for iThemes.com
 *
 * @version 1.0.0
 *
 * Version History
 * 	1.0.0 - 2015-02-04 - Aaron D. Campbell
 * 		Initial version
 */

class Ithemes_Sync_Verb_Manage_Options extends Ithemes_Sync_Verb {
	public static $name = 'manage-options';
	public static $description = 'Manage values from the WordPress options system.';

	private $default_arguments = array();
	private $response = array();

	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );

		$actions = array(
			'update-options'         => 'update_option',
			'add-options'            => 'add_option',
			'delete-options'         => 'delete_option',
			'update-site-options'    => 'update_site_option',
			'add-site-options'       => 'add_site_option',
			'delete-site-options'    => 'delete_site_option',
			'set-transients'         => 'set_transient',
			'delete-transients'      => 'delete_transient',
			'set-site-transients'    => 'set_site_transient',
			'delete-site-transients' => 'delete_site_transient',
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

			$this->response[$action] = $this->handle_request( $actions[$action], $data );
		}

		return $this->response;
	}

	private function handle_request( $function, $data ) {
		if ( ! is_callable( $function ) ) {
			return new WP_Error( "missing-function-$function", "Due to an unknown issue, the $function function is not available." );
		}

		$response = array();

		foreach ( $data as $id => $params ) {
			if ( ! is_array( $function ) ) {
				$response[$id] = call_user_func_array( $function, $params );
				continue;
			}
		}

		return $response;
	}
}
