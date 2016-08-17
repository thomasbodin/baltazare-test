<?php

/*
Simple API for managing verbs for Sync.
Written by Chris Jean for iThemes.com
Version 1.4.1

Version History
	1.0.0 - 2013-10-01 - Chris Jean
		Initial version
	1.1.0 - 2013-11-18 - Chris Jean
		Updated default verbs.
	1.2.0 - 2014-01-20 - Chris Jean
		Added: get-status-elements, manage-plugins, and manage-themes.
		Added: is_default_status_element(), get_default_status_elements().
	1.2.1 - 2014-02-07 - Chris Jean
		When including a verb's file, errors are no longer hidden. This allows for easier detection of verbs with problems.
		Better handling of invalid verbs.
	1.3.0 - 2014-03-25 - Chris Jean
		Added: get-notices.
	1.4.0 - 2014-07-01 - Chris Jean
		Added: get-comment-details, get-options, get-role-details, get-user-details, manage-commments, manage-roles, manage-users.
		register() now returns true when a Verb has been successfully registered.
	1.4.1 - 2014-11-10 - Chris Jean
		Changed init hook priority to 11 in order to avoid issues with some plugin updates not appearing.
*/


class Ithemes_Sync_API {
	private $verbs = array();
	
	private $default_verbs = array(
		'deauthenticate-user'          => 'Ithemes_Sync_Verb_Deauthenticate_User',
		'do-update'                    => 'Ithemes_Sync_Verb_Do_Update',
		'get-admin-bar-item-whitelist' => 'Ithemes_Sync_Verb_Get_Admin_Bar_Item_Whitelist',
		'get-admin-bar-items'          => 'Ithemes_Sync_Verb_Get_Admin_Bar_Items',
		'get-admin-menu'               => 'Ithemes_Sync_Verb_Get_Admin_Menu',
		'get-comment-details'          => 'Ithemes_Sync_Verb_Get_Comment_Details',
		'get-dashboard-widgets'        => 'Ithemes_Sync_Verb_Get_Dashboard_Widgets',
		'get-options'                  => 'Ithemes_Sync_Verb_Get_Options',
		'get-php-details'              => 'Ithemes_Sync_Verb_Get_PHP_Details',
		'get-plugin-details'           => 'Ithemes_Sync_Verb_Get_Plugin_Details',
		'get-posts'                    => 'Ithemes_Sync_Verb_Get_Posts',
		'get-notices'                  => 'Ithemes_Sync_Verb_Get_Notices',
		'get-role-details'             => 'Ithemes_Sync_Verb_Get_Role_Details',
		'get-server-details'           => 'Ithemes_Sync_Verb_Get_Server_Details',
		'get-status'                   => 'Ithemes_Sync_Verb_Get_Status',
		'get-status-elements'          => 'Ithemes_Sync_Verb_Get_Status_Elements',
		'get-supported-verbs'          => 'Ithemes_Sync_Verb_Get_Supported_Verbs',
		'get-sync-settings'            => 'Ithemes_Sync_Verb_Get_Sync_Settings',
		'get-theme-details'            => 'Ithemes_Sync_Verb_Get_Theme_Details',
		'get-update-details'           => 'Ithemes_Sync_Verb_Get_Update_Details',
		'get-updates'                  => 'Ithemes_Sync_Verb_Get_Updates',
		'get-user-details'             => 'Ithemes_Sync_Verb_Get_User_Details',
		'get-wordpress-details'        => 'Ithemes_Sync_Verb_Get_Wordpress_Details',
		'get-wordpress-users'          => 'Ithemes_Sync_Verb_Get_Wordpress_Users',
		'manage-comments'              => 'Ithemes_Sync_Verb_Manage_Comments',
		'manage-ithemes-licenses'      => 'Ithemes_Sync_Verb_Manage_Ithemes_Licenses',
		'manage-options'               => 'Ithemes_Sync_Verb_Manage_Options',
		'manage-plugins'               => 'Ithemes_Sync_Verb_Manage_Plugins',
		'manage-roles'                 => 'Ithemes_Sync_Verb_Manage_Roles',
		'manage-themes'                => 'Ithemes_Sync_Verb_Manage_Themes',
		'manage-users'                 => 'Ithemes_Sync_Verb_Manage_Users',
		'set-admin-bar-item-whitelist' => 'Ithemes_Sync_Verb_Set_Admin_Bar_Item_Whitelist',
		'update-show-sync'             => 'Ithemes_Sync_Verb_Update_Show_Sync',
	);
	
	
	public function __construct() {
		$GLOBALS['ithemes-sync-api'] = $this;
		
		require_once(dirname(__FILE__) . '/functions.php');
		
		// Gravity Forms Verbs
		if ( class_exists( 'GFForms' ) ) {
			$this->default_verbs['get-gf-forms']        = 'Ithemes_Sync_Verb_Get_GF_Forms';
			$this->default_verbs['get-gf-form-entries'] = 'Ithemes_Sync_Verb_Get_GF_Form_Entries';
		}
		
		add_action( 'init', array( $this, 'init' ), 11 );
	}
	
	public function init() {
		$path = dirname( __FILE__ );
		
		require_once( "$path/verbs/verb.php" );
		
		foreach ( $this->default_verbs as $name => $class ) {
			$this->register( $name, $class, "$path/verbs/$name.php" );
		}
		
		do_action( 'ithemes_sync_register_verbs', $this );
		
		do_action( 'ithemes_sync_verbs_registered' );
	}
	
	public function register( $name, $class, $file = '' ) {
		if ( isset( $this->verbs[$name] ) ) {
			do_action( 'ithemes-sync-add-log', 'Tried to add a verb name that already exists.', compact( 'name', 'class', 'file' ) );
			return false;
		}
		
		$this->verbs[$name] = compact( 'name', 'class', 'file' );
		
		return true;
	}
	
	public function get_names() {
		return array_keys( $this->verbs );
	}
	
	public function get_description( $name ) {
		$class = $this->get_class( $name );
		
		if ( false === $class )
			return '';
		
		
		$vars = get_class_vars( $class );
		
		if ( isset( $vars['description'] ) )
			return $vars['description'];
		
		return '';
	}
	
	public function get_descriptions() {
		$names = $this->get_names();
		$descriptions = array();
		
		foreach ( $names as $name )
			$descriptions[$name] = $this->get_description( $name );
		
		return $descriptions;
	}
	
	public function get_status_element( $name ) {
		$class = $this->get_class( $name );
		
		if ( false === $class ) {
			return false;
		}
		
		
		$vars = get_class_vars( $class );
		
		if ( ! empty( $vars['status_element_name'] ) ) {
			return $vars['status_element_name'];
		}
		
		return false;
	}
	
	public function get_status_elements() {
		$names = $this->get_names();
		$status_elements = array();
		
		foreach ( $names as $name ) {
			$status_element = $this->get_status_element( $name );
			
			if ( false !== $status_element ) {
				$status_elements[$status_element] = $name;
			}
		}
		
		return $status_elements;
	}
	
	public function is_default_status_element( $name ) {
		$class = $this->get_class( $name );
		
		if ( false === $class ) {
			return false;
		}
		
		
		$vars = get_class_vars( $class );
		
		if ( ! empty( $vars['show_in_status_by_default'] ) ) {
			return $vars['show_in_status_by_default'];
		}
		
		return false;
	}
	
	public function get_default_status_elements() {
		$names = $this->get_names();
		$default_status_elements = array();
		
		foreach ( $names as $name ) {
			if ( $this->is_default_status_element( $name ) ) {
				$default_status_elements[] = $this->get_status_element( $name );
			}
		}
		
		return $default_status_elements;
	}
	
	public function run( $name, $arguments = array() ) {
		$object = $this->get_object( $name );
		
		if ( false == $object ) {
			return new WP_Error( 'invalid-verb-object', "Unable to find a valid object for the requested verb: $name" );
		}
		
		return $object->run( $arguments );
	}
	
	private function get_class( $name ) {
		if ( ! isset( $this->verbs[$name] ) ) {
			do_action( 'ithemes-sync-add-log', 'Unable to find requested verb.', array( 'name' => $name ) );
			return false;
		}
		
		if ( ! class_exists( $this->verbs[$name]['class'] ) ) {
			if ( empty( $this->verbs[$name]['file'] ) ) {
				do_action( 'ithemes-sync-add-log', 'Unable to find requested verb\'s class.', $this->verbs[$name] );
				return false;
			}
			else if ( ! is_file( $this->verbs[$name]['file'] ) ) {
				do_action( 'ithemes-sync-add-log', 'Unable to find requested verb\'s file.', $this->verbs[$name] );
				return false;
			}
			else {
				include_once( $this->verbs[$name]['file'] );
				
				if ( ! class_exists( $this->verbs[$name]['class'] ) ) {
					do_action( 'ithemes-sync-add-log', 'Unable to find requested verb\'s class even after loading its file.', $this->verbs[$name] );
					return false;
				}
				
				if ( ! is_subclass_of( $this->verbs[$name]['class'], 'Ithemes_Sync_Verb' ) ) {
					do_action( 'ithemes-sync-add-log', 'Verb added without being a subclass of Ithemes_Sync_Verb', $this->verbs[$name] );
					return false;
				}
			}
		}
		
		return $this->verbs[$name]['class'];
	}
	
	private function get_object( $name ) {
		if ( ! isset( $this->verbs[$name] ) ) {
			do_action( 'ithemes-sync-add-log', 'Unable to find requested verb.', array( 'name' => $name ) );
			return false;
		}
		
		if ( ! isset( $this->verbs[$name]['object'] ) ) {
			$class = $this->get_class( $name );
			
			if ( false === $class ) {
				return false;
			}
			
			$object = new $class();
			
			$this->verbs[$name]['object'] = $object;
		}
		
		return $this->verbs[$name]['object'];
	}
}

new Ithemes_Sync_API();
