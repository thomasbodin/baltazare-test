<?php

/*
Loads up the translations for the plugin.
Written by Chris Jean for iThemes.com
Version 1.1.0

Version History
	1.0.0 - 2013-11-06 - Chris Jean
		Initial version
	1.1.0 - 2015-08-18 - Chris Jean
		Enhancement: Added support for translations being located outside the plugin.
		Bug Fix: Changed plugin textdomain path from absolute to relative.
*/


$ithemes_sync_plugin_dir = basename( dirname( __FILE__ ) );
$ithemes_sync_locale = apply_filters( 'plugin_locale', get_locale(), 'it-l10n-ithemes-sync' );

load_textdomain( 'it-l10n-ithemes-sync', WP_LANG_DIR . "/plugins/ithemes-sync/it-l10n-ithemes-sync-$ithemes_sync_locale.mo" );
load_plugin_textdomain( 'it-l10n-ithemes-sync', false, "$ithemes_sync_plugin_dir/lang/" );
