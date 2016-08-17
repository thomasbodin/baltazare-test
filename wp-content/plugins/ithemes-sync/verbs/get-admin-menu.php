<?php

/**
 * Implementation of the get-admin-menu verb.
 *
 * @author Aaron D. Campbell <aaron@ithemes.com>
 * @version 1.0.0
 *
 * Version History
 *   1.0.0 - 2014-09-02 - Aaron D. Campbell
 *       Initial version
 */


class Ithemes_Sync_Verb_Get_Admin_Menu extends Ithemes_Sync_Verb {
	public static $name = 'get-admin-menu';
	public static $description = 'Retrieve admin menu items.';

	public function run( $arguments ) {
		return get_option( 'ithemes-sync-admin_menu' );
	}
}
