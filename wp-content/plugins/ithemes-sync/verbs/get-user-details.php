<?php

/*
Implementation of the get-user-details verb.
Written by Chris Jean for iThemes.com
Version 1.1.0

Version History
	1.0.0 - 2014-06-02 - Chris Jean
		Initial version
	1.1.0 - 2014-07-23 - Chris Jean
		Added first_name, last_name, nickname, description, user_level, primary_blog, and source_domain to the user data.
*/


class Ithemes_Sync_Verb_Get_User_Details extends Ithemes_Sync_Verb {
	public static $name = 'get-user-details';
	public static $description = 'Retrieve details about the site\'s users.';
	public static $status_element_name = 'users';
	public static $show_in_status_by_default = false;
	
	private $default_arguments = array(
		'args' => array(),
	);
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		$users = get_users( $arguments['args'] );
		
		foreach ( $users as $index => $user ) {
			$new_user = (array) $user;
			$new_user['data'] = (array) $new_user['data'];
			
			$new_user['data']['first_name']  = $user->first_name;
			$new_user['data']['last_name']   = $user->last_name;
			$new_user['data']['nickname']    = $user->nickname;
			$new_user['data']['description'] = $user->description;
			$new_user['data']['user_level']   = $user->user_level;
			
			if ( is_multisite() ) {
				$new_user['data']['primary_blog']  = $user->primary_blog;
				$new_user['data']['source_domain'] = $user->source_domain;
			}
			
			$users[$index] = $new_user;
		}
		
		return $users;
	}
}
