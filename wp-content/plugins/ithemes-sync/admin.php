<?php

/*
Set up admin interface.
Written by Chris Jean for iThemes.com
Version 1.2.0

Version History
	1.0.0 - 2013-10-02 - Chris Jean
		Initial version
	1.1.0 - 2013-11-19 - Chris Jean
		Added the ability for the show_sync option to control who sees the Sync interface and plugin.
	1.2.0 - 2014-02-14 - Chris Jean
		Added support for ?ithemes-sync-force-display=1 in the admin page to force a hidden Sync plugin to display for that specific user.
*/


require_once( $GLOBALS['ithemes_sync_path'] . '/load-translations.php' );

class Ithemes_Sync_Admin {
	private $page_name = 'ithemes-sync';
	
	private $page_ref;
	
	
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_ithemes_sync_hide_notice', array( $this, 'hide_authenticate_notice' ) );
	}
	
	public function modify_plugins_page() {
		add_filter( 'all_plugins', array( $this, 'remove_sync_plugin' ) );
	}
	
	public function remove_sync_plugin( $plugins ) {
		unset( $plugins[basename( dirname( __FILE__ ) ) . '/init.php'] );
		
		return $plugins;
	}
	
	public function init() {
		require_once(dirname(__FILE__) . '/settings.php');
		
		
		$show_sync = $GLOBALS['ithemes-sync-settings']->get_option( 'show_sync' );
		
		if ( is_array( $show_sync ) ) {
			$show_sync = in_array( get_current_user_id(), $show_sync );
		}
		
		if ( ! $show_sync && current_user_can( 'manage_options' ) ) {
			$user_id = get_current_user_id();
			
			if ( isset( $_GET['ithemes-sync-force-display'] ) ) {
				if ( ! empty( $_GET['ithemes-sync-force-display'] ) ) {
					$show_sync = true;
					set_site_transient( "ithemes-sync-force-display-$user_id", true, 600 );
					
					add_action( 'all_admin_notices', array( $this, 'show_force_display_notice' ), 0 );
				} else {
					delete_site_transient( "ithemes-sync-force-display-$user_id" );
					
					add_action( 'all_admin_notices', array( $this, 'show_force_display_disable_notice' ), 0 );
				}
			} else if ( false !== get_site_transient( "ithemes-sync-force-display-$user_id" ) ) {
				$show_sync = true;
				
				add_action( 'all_admin_notices', array( $this, 'show_force_display_notice' ), 0 );
			}
		}
		
		
		if ( $show_sync ) {
			if ( ! is_multisite() || is_super_admin() ) {
				add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
			}
			
			add_action( 'network_admin_menu', array( $this, 'add_network_admin_pages' ) );
			
			
			if ( current_user_can( 'manage_options' ) ) {
				if ( ! get_site_option( 'ithemes-sync-authenticated' ) && ( empty( $_GET['page'] ) || ( $this->page_name != $_GET['page'] ) ) && ! get_site_option( 'ithemes_sync_hide_authenticate_notice' ) ) {
					require_once(dirname(__FILE__) . '/functions.php');
					
					$path_url = Ithemes_Sync_Functions::get_url( dirname( __FILE__ ) );
					wp_enqueue_style( 'ithemes-updater-admin-notice-style', "$path_url/css/admin-notice.css" );
					wp_enqueue_script( 'ithemes-updater-admin-notice-script', "$path_url/js/admin-notice.js", array( 'jquery' ) );
					
					add_action( 'all_admin_notices', array( $this, 'show_authenticate_notice' ), 0 );
					
					delete_site_transient( 'ithemes-sync-activated' );
				}
				else if ( ! empty( $_GET['activate'] ) && get_site_transient( 'ithemes-sync-activated' ) ) {
					require_once(dirname(__FILE__) . '/functions.php');
					
					$path_url = Ithemes_Sync_Functions::get_url( dirname( __FILE__ ) );
					wp_enqueue_style( 'ithemes-updater-admin-notice-style', "$path_url/css/admin-notice.css" );
					wp_enqueue_script( 'ithemes-updater-admin-notice-script', "$path_url/js/admin-notice.js", array( 'jquery' ) );
					
					add_action( 'all_admin_notices', array( $this, 'show_activate_notice' ), 0 );
					
					delete_site_transient( 'ithemes-sync-activated' );
				}
			}
		} else {
			add_action( 'load-plugins.php', array( $this, 'modify_plugins_page' ) );
		}
	}
	
	public function show_activate_notice() {
		if ( is_multisite() && is_network_admin() )
			$url = network_admin_url( 'settings.php' ) . "?page={$this->page_name}";
		else
			$url = admin_url( 'options-general.php' ) . "?page={$this->page_name}";
		
?>
	<div class="updated" id="ithemes-sync-notice">
		<?php printf( __( 'iThemes Sync is active. <a class="ithemes-sync-notice-button" href="%s">Manage Sync</a> <a class="ithemes-sync-notice-dismiss" href="#">×</a>', 'it-l10n-ithemes-sync' ), $url ); ?>
	</div>
<?php
		
	}
	
	public function show_authenticate_notice() {
		if ( is_multisite() && is_network_admin() )
			$url = network_admin_url( 'settings.php' ) . "?page={$this->page_name}";
		else
			$url = admin_url( 'options-general.php' ) . "?page={$this->page_name}";
		
?>
	<div class="updated" id="ithemes-sync-notice">
		<?php printf( __( 'iThemes Sync is almost ready. <a class="ithemes-sync-notice-button" href="%s">Set Up Sync</a> <a class="ithemes-sync-notice-hide" href="#">×</a>', 'it-l10n-ithemes-sync' ), $url ); ?>
	</div>
<?php
		
	}
	
	public function show_force_display_notice() {
		$user_id = get_current_user_id();
		$time = get_site_option( "_site_transient_timeout_ithemes-sync-force-display-$user_id" );
		$time_diff = human_time_diff( time(), $time );
		
		$url = admin_url( 'index.php?ithemes-sync-force-display=0' );
		
?>
	<div class="updated">
		<p><?php printf( __( 'iThemes Sync will show for your user for the next %1$s. Click <a href="%2$s">here</a> to hide iThemes Sync again.', 'it-l10n-ithemes-sync' ), $time_diff, $url ); ?></p>
	</div>
<?php
		
	}
	
	public function show_force_display_disable_notice() {
		
?>
	<div class="updated">
		<p><?php _e( 'iThemes Sync is now hidden from your user again.', 'it-l10n-ithemes-sync' ); ?></p>
	</div>
<?php
		
	}
	
	public function hide_authenticate_notice() {
		update_site_option( 'ithemes_sync_hide_authenticate_notice', true );
	}
	
	public function add_admin_pages() {
		$this->page_ref = add_options_page( __( 'iThemes Sync', 'it-l10n-ithemes-sync' ), __( 'iThemes Sync', 'it-l10n-ithemes-sync' ), 'manage_options', $this->page_name, array( $this, 'settings_index' ) );
		
		add_action( "load-{$this->page_ref}", array( $this, 'load_settings_page' ) );
	}
	
	public function add_network_admin_pages() {
		$this->page_ref = add_submenu_page( 'settings.php', __( 'iThemes Sync', 'it-l10n-ithemes-sync' ), __( 'iThemes Sync', 'it-l10n-ithemes-sync' ), 'manage_options', $this->page_name, array( $this, 'settings_index' ) );
		
		add_action( "load-{$this->page_ref}", array( $this, 'load_settings_page' ) );
	}
	
	public function load_settings_page() {
		require_once(dirname(__FILE__) . '/settings.php');
		
		require(dirname(__FILE__) . '/settings-page.php');
	}
	
	public function settings_index() {
		do_action( 'ithemes_sync_settings_page_index' );
	}
	
	private function set_package_details() {
		if ( false !== $this->package_details )
			return;
		
		require_once( $GLOBALS['ithemes_updater_path'] . '/packages.php' );
		$this->package_details = Ithemes_Updater_Packages::get_local_details();
	}
	
	private function set_registration_link() {
		if ( false !== $this->registration_link )
			return;
		
		$url = admin_url( 'options-general.php' ) . "?page={$this->page_name}";
		$this->registration_link = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $url, __( 'Manage iThemes product licenses to receive automatic upgrade support', 'it-l10n-ithemes-sync' ), __( 'License', 'it-l10n-ithemes-sync' ) );
	}
	
	public function filter_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		$this->set_package_details();
		$this->set_registration_link();
		
		if ( isset( $this->package_details[$plugin_file] ) )
			$actions[] = $this->registration_link;
		
		return $actions;
	}
	
	public function filter_theme_action_links( $actions, $theme ) {
		$this->set_package_details();
		$this->set_registration_link();
		
		if ( is_object( $theme ) )
			$path = basename( $theme->get_stylesheet_directory() ) . '/style.css';
		else if ( is_array( $theme ) && isset( $theme['Stylesheet Dir'] ) )
			$path = $theme['Stylesheet Dir'] . '/style.css';
		else
			$path = '';
		
		if ( isset( $this->package_details[$path] ) )
			$actions[] = $this->registration_link;
		
		return $actions;
	}
}

new Ithemes_Sync_Admin();
