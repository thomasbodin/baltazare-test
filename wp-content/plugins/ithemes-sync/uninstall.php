<?php

/*
Handles uninstalling the plugin so that options can be cleaned up.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2013-11-06 - Chris Jean
		Initial version
*/


if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();


delete_site_option( 'ithemes-sync-cache' );
delete_site_option( 'ithemes-sync-authenticated' );
delete_site_option( 'ithemes_sync_hide_authenticate_notice' );

delete_site_transient( 'ithemes-sync-activated' );
