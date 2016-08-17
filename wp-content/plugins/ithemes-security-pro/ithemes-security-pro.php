<?php

/*
 * Plugin Name: iThemes Security Pro
 * Plugin URI: https://ithemes.com/security
 * Description: Protect your WordPress site by hiding vital areas of your site, protecting access to important files, preventing brute-force login attempts, detecting attack attempts and more.
 * Author: iThemes
 * Author URI: https://ithemes.com
 * Version: 2.0.2
 * Text Domain: it-l10n-ithemes-security-pro
 * Domain Path: /lang
 * Network: True
 * License: GPLv2
 * iThemes Package: ithemes-security-pro
 */


$itsec_dir = dirname( __FILE__ );

$locale = apply_filters( 'plugin_locale', get_locale(), 'it-l10n-ithemes-security-pro' );
load_textdomain( 'it-l10n-ithemes-security-pro', WP_LANG_DIR . "/plugins/ithemes-security-pro/it-l10n-ithemes-security-pro-$locale.mo" );
load_plugin_textdomain( 'it-l10n-ithemes-security-pro', false, basename( $itsec_dir ) . '/lang/' );

if ( is_admin() ) {
	require("$itsec_dir/lib/icon-fonts/load.php");
	require("$itsec_dir/lib/one-version/index.php");
}

require("$itsec_dir/core/class-itsec-core.php");
new ITSEC_Core( __FILE__, __( 'iThemes Security Pro', 'it-l10n-ithemes-security-pro' ) );


function ithemes_repository_name_updater_register( $updater ) {
	$updater->register( 'ithemes-security-pro', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_repository_name_updater_register' );

require("$itsec_dir/lib/updater/load.php");
