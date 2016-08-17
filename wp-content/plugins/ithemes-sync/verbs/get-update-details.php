<?php

/*
Implementation of the get-update-details verb.
Written by Chris Jean for iThemes.com
Version 1.1.0

Version History
	1.0.0 - 2013-11-14 - Chris Jean
		Initial version
	1.1.0 - 2014-01-20 - Chris Jean
		Added $status_element_name and $show_in_status_by_default.
*/


class Ithemes_Sync_Verb_Get_Update_Details extends Ithemes_Sync_Verb {
	public static $name = 'get-update-details';
	public static $description = 'Find details about all available updates on the site. This includes details about updates to WordPress, plugins, themes, and translations.';
	public static $status_element_name = 'updates';
	public static $show_in_status_by_default = true;
	
	private $default_arguments = array();
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		return Ithemes_Sync_Functions::get_update_details( $arguments );
	}
}
