<?php

/*
Parent class for all Sync verbs.
Written by Chris Jean for iThemes.com
Version 1.1.0

Version History
	1.0.0 - 2013-10-01 - Chris Jean
		Initial version
	1.1.0 - 2014-01-20 - Chris Jean
		Added $status_element_name and $show_in_status_by_default.
*/


class Ithemes_Sync_Verb {
	public static $name = 'example';
	public static $description = 'This verb is not meant to be used; rather, it serves as the building block for all other verbs.';
	public static $status_element_name = '';
	public static $show_in_status_by_default = false;
	
	private $default_arguments = array();
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		return array();
	}
}
