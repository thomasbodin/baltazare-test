<?php

/*
Implementation of the update-show-sync verb.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2013-11-19 - Chris Jean
		Initial version
*/


class Ithemes_Sync_Verb_Update_Show_Sync extends Ithemes_Sync_Verb {
	public static $name = 'update-show-sync';
	public static $description = 'Controls whether the Sync plugin and the iThemes Sync page shows up in the WordPress Dashboard.';
	
	private $default_arguments = array();
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		
		if ( ! isset( $arguments['show_sync'] ) ) {
			return new WP_Error( 'missing-show_sync-argument', 'The show_sync argument is missing. The new show_sync value should be sent in the show_sync argument.' );
		}
		
		
		$options = $GLOBALS['ithemes-sync-settings']->get_options();
		$options['show_sync'] = $arguments['show_sync'];
		
		$GLOBALS['ithemes-sync-settings']->update_options( $options );
		
		
		return array( 'success' => 1 );
	}
}
