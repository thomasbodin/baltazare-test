<?php

/*
Implementation of the get-wordpress-users verb.
Written by Chris Jean for iThemes.com
Version 1.1.0

Version History
	1.0.0 - 2013-11-19 - Chris Jean
		Initial version
	1.1.0 - 2014-01-20 - Chris Jean
		Added $status_element_name.
*/


class Ithemes_Sync_Verb_Get_Wordpress_Users extends Ithemes_Sync_Verb {
	public static $name = 'get-wordpress-users';
	public static $description = 'Provides a list of WordPress users and their IDs';
	public static $status_element_name = 'users';
	
	private $default_arguments = array();
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		$users = Ithemes_Sync_Functions::get_users( $arguments );
		
		return $users;
	}
}
