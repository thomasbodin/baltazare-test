<?php

/**
 * Implementation of the get-admin-bar-item-whitelist verb.
 *
 * @author Aaron D. Campbell <aaron@ithemes.com>
 * @version 1.0.0
 *
 * Version History
 *   1.0.0 - 2014-10-01 - Aaron D. Campbell
 *       Initial version
 */


class Ithemes_Sync_Verb_Get_Admin_Bar_Item_Whitelist extends Ithemes_Sync_Verb {
	public static $name = 'get-admin-bar-item-whitelist';
	public static $description = 'Get the admin bar items a user can see.';

	public function run( $arguments ) {
		if ( empty( $arguments['id'] ) ) {
			return WP_Error( 'user-id-required', 'User ID Required' );
		}

		$user = get_user_by( 'id', absint( $arguments['id'] ) );
		if ( ! is_object( $user ) || ! is_a( $user, 'WP_User' ) ) {
			return WP_Error( 'invalid-user-id', "Unable to find the requested user with an ID of {$arguments['id']}." );
		}

		$meta_key = 'ithemes-sync-admin-bar-item-whitelist-' . get_current_blog_id();
		return get_user_meta( $user->ID, $meta_key, true );
	}
}
