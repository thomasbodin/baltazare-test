<?php

/**
 * Implementation of the get-dashboard-widgets verb.
 *
 * @author Aaron D. Campbell <aaron@ithemes.com>
 * @version 1.0.0
 *
 * Version History
 *   1.0.0 - 2014-09-12 - Aaron D. Campbell
 *       Initial version
 */


class Ithemes_Sync_Verb_Get_Dashboard_Widgets extends Ithemes_Sync_Verb {
	public static $name = 'get-dashboard-widgets';
	public static $description = 'Retrieve list of dashboard widgets.';

	public function run( $arguments ) {
		return get_option( 'ithemes-sync-dashboard-metaboxes' );
	}
}
