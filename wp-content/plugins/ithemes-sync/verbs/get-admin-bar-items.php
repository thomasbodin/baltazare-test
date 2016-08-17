<?php

/**
 * Implementation of the get-admin-bar-items verb.
 *
 * @author Aaron D. Campbell <aaron@ithemes.com>
 * @version 1.0.0
 *
 * Version History
 *   1.0.0 - 2014-10-01 - Aaron D. Campbell
 *       Initial version
 */


class Ithemes_Sync_Verb_Get_Admin_Bar_Items extends Ithemes_Sync_Verb {
	public static $name = 'get-admin-bar-items';
	public static $description = 'Retrieve list of admin bar items for a user.';

	public function run( $arguments ) {
		if ( empty( $arguments['id'] ) ) {
			return array( 'error' => 'User ID Required' );
		}

		$user = get_user_by( 'id', absint( $arguments['id'] ) );
		if ( ! is_object( $user ) || ! is_a( $user, 'WP_User' ) ) {
			return WP_Error( 'invalid-user-id', "Unable to find the requested user with an ID of {$arguments['id']}." );
		}

		$meta_key = 'ithemes-sync-admin-bar-items-' . get_current_blog_id();
		return get_user_meta( $user->ID, $meta_key, true );
	}
}
