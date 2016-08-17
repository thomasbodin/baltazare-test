<?php

/*
Offers interface to add and retrieve notices.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2014-03-28 - Chris Jean
		Initial version
*/


require_once( $GLOBALS['ithemes_sync_path'] . '/load-translations.php' );


class Ithemes_Sync_Notice_Handler {
	private $urgent_notice_cache_option_name = 'ithemes_sync_urgent_notice_cache';
	private $notice_timestamps_option_name = 'ithemes_sync_notice_timestamps';
	
	private $notices = array();
	
	
	public function __construct() {
		$GLOBALS['ithemes_sync_notice_handler'] = $this;
	}
	
	public function add_notice( $source, $id, $subject, $message, $data = array() ) {
		$this->notices[] = compact( 'source', 'id', 'subject', 'message', 'data' );
		
		return true;
	}
	
	public function get_notices( $arguments ) {
		do_action( 'ithemes_sync_add_notices', $arguments );
		
		$notice_timestamps = get_site_option( $this->notice_timestamps_option_name, array() );
		$new_notice_timestamps = array();
		
		foreach ( $this->notices as $index => $notice ) {
			if ( isset( $notice_timestamps[$notice['source']] ) && isset( $notice_timestamps[$notice['source']][$notice['id']] ) ) {
				$timestamp = $notice_timestamps[$notice['source']][$notice['id']];
			} else {
				$timestamp = time();
			}
			
			$new_notice_timestamps[$notice['source']][$notice['id']] = $timestamp;
			
			$this->notices[$index]['timestamp'] = $timestamp;
		}
		
		update_site_option( $this->notice_timestamps_option_name, $new_notice_timestamps );
		
		return $this->notices;
	}
	
	public function send_urgent_notice( $source, $id, $subject, $message, $data = array() ) {
		require_once(dirname(__FILE__) . '/server.php');
		require_once(dirname(__FILE__) . '/settings.php');
		
		$timestamp = time();
		
		$notice = compact( 'source', 'id', 'subject', 'message', 'data', 'timestamp' );
		
		$notices = $this->get_urgent_notices();
		$notices[] = $notice;
		
		$options = $GLOBALS['ithemes-sync-settings']->get_options();
		
		foreach ( $options['authentications'] as $user_id => $user ) {
			$result = Ithemes_Sync_Server::send_urgent_notices( $user_id, $user['username'], $user['key'], $notices );
			
			if ( ! is_wp_error( $result ) && is_array( $result ) && isset( $result['success'] ) && $result['success'] ) {
				$this->clear_urgent_notices();
				return true;
			}
		}
		
		$this->set_urgent_notices( $notices );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return new WP_Error( 'unknown-response', __( 'The Sync server returned an unknown response.', 'it-l10n-ithemes-sync' ) );
	}
	
	public function get_urgent_notices() {
		return get_site_option( $this->urgent_notice_cache_option_name, array() );
	}
	
	public function set_urgent_notices( $notices ) {
		update_site_option( $this->urgent_notice_cache_option_name, $notices );
	}
	
	public function clear_urgent_notices() {
		delete_site_option( $this->urgent_notice_cache_option_name );
	}
}

new Ithemes_Sync_Notice_Handler();
