<?php

/*
Implementation of the get-role-details verb.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2014-06-02 - Chris Jean
		Initial version
*/


class Ithemes_Sync_Verb_Get_Role_Details extends Ithemes_Sync_Verb {
	public static $name = 'get-role-details';
	public static $description = 'Retrieve details about the site\'s user roles.';
	public static $status_element_name = 'roles';
	public static $show_in_status_by_default = false;
	
	private $default_arguments = array(
		'just-names' => false,
	);
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		require_once(ABSPATH . 'wp-admin/includes/user.php');
		
		$roles = get_editable_roles();
		
		if ( $arguments['just-names'] ) {
			foreach ( $roles as $index => $data ) {
				$roles[$index] = $data['name'];
			}
		}
		
		return $roles;
	}
}
