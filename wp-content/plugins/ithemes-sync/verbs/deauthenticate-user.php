<?php

/*
Implementation of the deauthenticate-user verb.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2013-11-19 - Chris Jean
		Initial version
*/


class Ithemes_Sync_Verb_Deauthenticate_User extends Ithemes_Sync_Verb {
	public static $name = 'deauthenticate-user';
	public static $description = 'Desync a user.';
	
	private $default_arguments = array();
	
	
	public function run( $arguments ) {
		if ( empty( $arguments['user_id'] ) ) {
			$response = new WP_Error( 'missing-user_id', 'The user_id argument is missing. The user could not be desynced.' );
		} else if ( empty( $arguments['username'] ) ) {
			$response = new WP_Error( 'missing-username', 'The username argument is missing. The user could not be desynced.' );
		} else {
			$response = $GLOBALS['ithemes-sync-settings']->remove_authentication( $arguments['user_id'], $arguments['username'] );
			
			if ( true === $response ) {
				$response = array( 'success' => 1 );
			}
		}
		
		return $response;
	}
}
