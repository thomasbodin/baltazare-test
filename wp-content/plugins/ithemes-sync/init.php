<?php

/*
Plugin Name: iThemes Sync
Plugin URI: http://ithemes.com/
Description: Manage updates to your WordPress sites easily in one place.
Author: iThemes
Version: 1.8.3
Author URI: http://ithemes.com/
Domain Path: /lang/
iThemes Package: ithemes-sync
*/


if ( ! empty( $GLOBALS['ithemes_sync_path'] ) ) {
	$active_plugin_path = preg_replace( '|^' . preg_quote( ABSPATH, '|' ) . '|', '', $GLOBALS['ithemes_sync_path'] );
	$this_plugin_path = preg_replace( '|^' . preg_quote( ABSPATH, '|' ) . '|', '', dirname( __FILE__ ) );

	if ( $active_plugin_path != $this_plugin_path ) {
		$function = 'echo \'<div class="error"><p>';
		$function .= sprintf( __( 'Only one iThemes Sync plugin can be active at a time. The plugin at <code>%1$s</code> is running while the plugin at <code>%2$s</code> was skipped in order to prevent errors. Please deactivate the plugin that you do not wish to use.', 'it-l10n-ithemes-sync' ), $active_plugin_path, $this_plugin_path );
		$function .= '</p></div>\';';

		$function_ref = create_function( '', $function );

		add_action( 'all_admin_notices', $function_ref, 0 );
	}

	return;
}


require(dirname(__FILE__) . '/load.php');
