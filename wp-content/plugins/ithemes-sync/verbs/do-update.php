<?php

/*
Implementation of the do-update verb.
Written by Chris Jean for iThemes.com
Version 1.2.2

Version History
	1.0.0 - 2013-10-01 - Chris Jean
		Initial version
	1.1.0 - 2014-02-18 - Chris Jean
		Updated the responses to better indicate whether updates applied successfully or not.
	1.2.0 - 2014-03-28 - Chris Jean
		Core updates now provide more information back to the server.
		Multisite updates now properly apply the network upgrade.
	1.2.1 - 2014-08-22 - Chris Jean
		In order to avoid issues with stale data, update details are now forcibly refreshed before attempting to run updates.
	1.2.2 - 2015-07-17 - Chris Jean
		An error is no longer sent when a plugin or theme updates to a newer version than the one reported.
*/


class Ithemes_Sync_Verb_Do_Update extends Ithemes_Sync_Verb {
	public static $name = 'do-update';
	public static $description = 'Update WordPress, plugins, and themes.';
	
	private $default_arguments = array();
	private $original_update_details;
	
	
	public function run( $arguments ) {
		require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
		require_once(dirname(dirname(__FILE__)) . '/upgrader-skin.php');
		
		$this->skin = new Ithemes_Sync_Upgrader_Skin();
		
		
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		
		$this->original_update_details = Ithemes_Sync_Functions::get_update_details( array( 'verbose' => true, 'force_refresh' => true ) );
		
		
		$response = array();
		
		if ( ! empty( $arguments['plugin'] ) ) {
			$response['plugin'] = $this->do_plugin_upgrade( $arguments['plugin'] );
		}
		if ( ! empty( $arguments['theme'] ) ) {
			$response['theme'] = $this->do_theme_upgrade( $arguments['theme'] );
		}
		if ( ! empty( $arguments['core'] ) ) {
			$response['core'] = $this->do_core_upgrade( $arguments['core'] );
		}
		
		
		return $response;
	}
	
	public function do_core_upgrade( $params ) {
		$required_fields = array(
			'upgrade_id',
			'locale',
			'version',
		);
		
		$errors = array();
		
		foreach ( $required_fields as $field ) {
			if ( ! isset( $params[$field] ) ) {
				$errors[] = "The '$field' field is missing.";
			}
		}
		
		
		if ( empty( $errors ) ) {
			require_once( $GLOBALS['ithemes_updater_path'] . '/functions.php' );
			
			$updates = Ithemes_Sync_Functions::get_update_details( array( 'verbose' => true, 'force_refresh' => array( 'core' ) ) );
			
			if ( empty( $updates['core'] ) ) {
				$errors[] = 'No core updates are currently available.';
			} else if ( empty( $updates['core'][$params['upgrade_id']] ) ) {
				$errors[] = 'Unable to find an availble upgrade matching the requested upgrade_id.';
			} else if ( $params['locale'] != $updates['core'][$params['upgrade_id']]->locale ) {
				$errors[] = 'The requested upgrade does not match the requested locale.';
			} else if ( isset( $updates['core'][$params['upgrade_id']]->version ) && ( $params['version'] != $updates['core'][$params['upgrade_id']]->version ) ) {
				$errors[] = 'The requested upgrade does not match the requested version.';
			} else if ( isset( $updates['core'][$params['upgrade_id']]->current ) && ( $params['version'] != $updates['core'][$params['upgrade_id']]->current ) ) {
				$errors[] = 'The requested upgrade does not match the requested version.';
			}
		}
		
		if ( ! empty( $errors ) ) {
			return array( 'errors' => $errors );
		}
		
		
		Ithemes_Sync_Functions::set_time_limit( 300 );
		
		$original_version = Ithemes_Sync_Functions::get_wordpress_version();
		
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/update.php');
		require_once(ABSPATH . 'wp-admin/includes/misc.php');
		
		$upgrader = new Core_Upgrader( $this->skin );
		$result = $upgrader->upgrade( $updates['core'][$params['upgrade_id']] );
		
		Ithemes_Sync_Functions::refresh_core_updates();
		
		if ( is_wp_error( $result ) ) {
			return array(
				'errors' => array(
					$result->get_error_code() => $result->get_error_message(),
				),
			);
		}
		
		
		$current_version = Ithemes_Sync_Functions::get_wordpress_version();
		$current_updates = Ithemes_Sync_Functions::get_update_details( array( 'force_refresh' => array( 'core' ) ) );
		$current_update_version = false;
		
		foreach ( $current_updates['core'] as $index => $update ) {
			if ( version_compare( $update->version, $current_update_version, '>' ) ) {
				$current_update_version = $update->version;
			}
		}
		
		
		$response = array(
			'wordpress_response'      => $result,
			'original_version'        => $original_version,
			'current_version'         => $current_version,
			'current_update_version'  => $current_update_version,
			'original_update_version' => $updates['core'][$params['upgrade_id']]->version,
		);
		
		
		if ( is_multisite() ) {
			// Based on the upgrade action of wp-admin/network/upgrade.php
			
			$wp_db_version = Ithemes_Sync_Functions::get_wordpress_db_version();
			update_site_option( 'wpmu_upgrade_site', $wp_db_version );
			
			global $wpdb;
			$blogs = $wpdb->get_results( "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' AND spam = '0' AND deleted = '0' AND archived = '0'", ARRAY_A );
			
			$response['network_upgrade'] = true;
			
			
			foreach ( (array) $blogs as $details ) {
				switch_to_blog( $details['blog_id'] );
				$siteurl = site_url();
				$upgrade_url = admin_url( 'upgrade.php?step=upgrade_db' );
				restore_current_blog();
				
				$result = wp_remote_get( $upgrade_url, array( 'timeout' => 120, 'httpversion' => '1.1' ) );
				
				if ( is_wp_error( $result ) ) {
					$response['network_upgrade'] = false;
					$response['errors'][] = 'Unable to successfully upgrade the network. You may need to visit the network admin dashboard to manually upgrade the network.';
					break;
				}
				
				do_action( 'after_mu_upgrade', $result );
				do_action( 'wpmu_upgrade_site', $details['blog_id'] );
			}
		}
		
		
		if ( $current_version == $params['version'] ) {
			$response['success'] = true;
		}
		
		
		return $response;
	}
	
	public function do_plugin_upgrade( $plugins ) {
		return $this->do_bulk_upgrade( $plugins, 'plugin' );
	}
	
	public function do_theme_upgrade( $themes ) {
		return $this->do_bulk_upgrade( $themes, 'theme' );
	}
	
	private function do_bulk_upgrade( $packages, $type ) {
		if ( ! in_array( $type, array( 'plugin', 'theme' ) ) ) {
			return new WP_Error( 'unrecognized-bulk-upgrade-type', "An unrecognized type ($type) was passed to do_bulk_upgrade()." );
		}
		
		
		Ithemes_Sync_Functions::set_time_limit( 300 );
		
		
		$original_versions = array();
		
		foreach ( $packages as $package ) {
			if ( 'plugin' === $type ) {
				$file_data = Ithemes_Sync_Functions::get_file_data( WP_PLUGIN_DIR . "/$package" );
			} else {
				$file_data = Ithemes_Sync_Functions::get_file_data( WP_CONTENT_DIR . "/themes/$package/style.css" );
			}
			
			$original_versions[$package] = $file_data['version'];
		}
		
		
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		require_once(ABSPATH . 'wp-admin/includes/misc.php');
		
		
		if ( 'plugin' === $type ) {
			$upgrader = new Plugin_Upgrader( $this->skin );
			$result = $upgrader->bulk_upgrade( $packages );
			Ithemes_Sync_Functions::refresh_plugin_updates();
		} else {
			$upgrader = new Theme_Upgrader( $this->skin );
			$result = $upgrader->bulk_upgrade( $packages );
			Ithemes_Sync_Functions::refresh_theme_updates();
		}
		
		
		if ( is_wp_error( $result ) ) {
			return array(
				'errors' => array(
					$result->get_error_code() => $result->get_error_message(),
				),
			);
		} else if ( false === $result ) {
			if ( 'plugin' === $type ) {
				$result = $upgrader->fs_connect( array( WP_CONTENT_DIR, WP_PLUGIN_DIR ) );
			} else {
				$result = $upgrader->fs_connect( array( WP_CONTENT_DIR ) );
			}
			
			
			if ( is_wp_error( $result ) ) {
				return array(
					'errors' => array(
						$result->get_error_code() => $result->get_error_message(),
					),
				);
			} else {
				return array(
					'errors' => array(
						'non-connected-filesystem' => 'Unable to update due to a non-connected filesystem.',
					),
				);
			}
		}
		
		
		$update_details = Ithemes_Sync_Functions::get_update_details( array( 'verbose' => true ) );
		$response = array();
		
		$update_index = "{$type}s";
		
		foreach ( $result as $package => $info ) {
			if ( false === $info ) {
				$response[$package]['errors']['non-connected-filesystem'] = 'Unable to update due to a non-connected filesystem.';
			} else if ( is_wp_error( $info ) ) {
				$response[$package]['errors'][$info->get_error_code()] = $info->get_error_message();
			} else {
				$response[$package]['wordpress_response'] = $info;
				
				if ( 'plugin' === $type ) {
					$file_data = Ithemes_Sync_Functions::get_file_data( WP_PLUGIN_DIR . "/$package" );
				} else {
					$file_data = Ithemes_Sync_Functions::get_file_data( WP_CONTENT_DIR . "/themes/$package/style.css" );
				}
				
				$response[$package]['current_version'] = $file_data['version'];
				
				if ( isset( $original_versions[$package] ) ) {
					$response[$package]['original_version'] = $original_versions[$package];
				}
				if ( isset( $update_details[$update_index][$package] ) ) {
					if ( ( 'plugin' === $type ) && isset( $update_details[$update_index][$package]->new_version ) ) {
						$response[$package]['current_update_version'] = $update_details[$update_index][$package]->new_version;
					} else if ( ( 'theme' === $type ) && isset( $update_details[$update_index][$package]['new_version'] ) ) {
						$response[$package]['current_update_version'] = $update_details[$update_index][$package]['new_version'];
					}
				}
				if ( isset( $this->original_update_details[$update_index][$package] ) ) {
					if ( ( 'plugin' === $type ) && isset( $this->original_update_details[$update_index][$package]->new_version ) ) {
						$response[$package]['original_update_version'] = $this->original_update_details[$update_index][$package]->new_version;
					} else if ( ( 'theme' === $type ) && isset( $this->original_update_details[$update_index][$package]['new_version'] ) ) {
						$response[$package]['original_update_version'] = $this->original_update_details[$update_index][$package]['new_version'];
					}
				}
				
				
				if ( 'plugin' === $type ) {
					$removed_old_update_data = $GLOBALS['ithemes_sync_request_handler']->remove_old_update_plugins_data( $package );
					
					if ( ! is_null( $removed_old_update_data ) ) {
						$response[$package]['removed_old_update_data'] = $removed_old_update_data;
					}
				}
				
				
				if ( ! isset( $response[$package]['original_update_version'] ) ) {
					$response[$package]['errors']['no-update'] = 'No update was available.';
				} else if ( version_compare( $response[$package]['current_version'], $response[$package]['original_update_version'], '>=' ) ) {
					$response[$package]['success'] = 1;
					
					if ( isset( $response[$package]['current_update_version'] ) ) {
						if ( version_compare( $response[$package]['current_version'], $response[$package]['current_update_version'], '>=' ) ) {
							$response[$package]['errors']['old-update-remains-available'] = 'The original update is still listed despite the update working properly.';
						} else {
							$response[$package]['errors']['new-update-available'] = 'An update is available.';
						}
					}
				} else {
					$response[$package]['errors']['unknown-error'] = 'An unknown error prevented the update from completing successfully.';
				}
			}
		}
		
		return $response;
	}
}
