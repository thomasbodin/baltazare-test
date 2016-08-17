<?php

/*
Implementation of the get-supported-verbs verb.
Written by Chris Jean for iThemes.com
Version 1.2.0

Version History
	1.0.0 - 2013-10-01 - Chris Jean
		Initial version
	1.1.0 - 2013-11-22 - Chris Jean
		Simplified the run() function by having it simply call the Ithemes_Sync_Functions::get_supported_verbs() function.
	1.2.0 - 2014-01-20 - Chris Jean
		Added $status_element_name and $show_in_status_by_default.
*/


class Ithemes_Sync_Verb_Get_Supported_Verbs extends Ithemes_Sync_Verb {
	public static $name = 'get-supported-verbs';
	public static $description = 'Retrieve a listing of the supported verbs.';
	public static $status_element_name = 'supported-verbs';
	public static $show_in_status_by_default = true;
	
	private $default_arguments = array();
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		return Ithemes_Sync_Functions::get_supported_verbs( $arguments );
	}
}
