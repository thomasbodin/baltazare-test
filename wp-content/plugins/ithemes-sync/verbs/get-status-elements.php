<?php

/*
Implementation of the get-status-elements verb.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2014-01-09 - Chris Jean
		Initial version
*/


class Ithemes_Sync_Verb_Get_Status_Elements extends Ithemes_Sync_Verb {
	public static $name = 'get-status-elements';
	public static $description = 'Retrieve a listing of the status elements.';
	public static $status_element_name = 'status-elements';
	public static $show_in_status_by_default = true;
	
	private $default_arguments = array();
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		return array_keys( Ithemes_Sync_Functions::get_status_elements( $arguments ) );
	}
}
