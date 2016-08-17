<?php

/*
Load the Sync plugin components.
Written by Chris Jean for iThemes.com
Version 1.1.1

Version History
	1.0.0 - 2014-03-26 - Chris Jean
		Created from version 1.3.5 of init.php.
	1.0.1 - 2014-10-13 - Chris Jean
		Added more robust checking of the $_GET['ithemes-sync-request'] data.
	1.1.0 - 2014-11-07 - Chris Jean
		Added a admin-ajax.php handler for sending Sync requests.
	1.1.1 - 2014-11-21 - Chris Jean
		Removed call to stripslashes_deep() in AJAX handler.
*/


$GLOBALS['ithemes_sync_path'] = dirname( __FILE__ );


function ithemes_sync_handle_ajax_request() {
	require_once( $GLOBALS['ithemes_sync_path'] . '/request-handler.php' );
}
add_action( 'wp_ajax_nopriv_ithemes_sync_request', 'ithemes_sync_handle_ajax_request' );

if ( ! empty( $_GET['ithemes-sync-request'] ) ) {
	require_once( $GLOBALS['ithemes_sync_path'] . '/request-handler.php' );
}


if ( is_admin() ) {
	require( $GLOBALS['ithemes_sync_path'] . '/admin.php' );
}

require_once(dirname(__FILE__) . '/client-dashboard.php');
require_once(dirname(__FILE__) . '/social.php');


/**
 * Add a notice to be sent to the server when it makes a status or notice request.
 *
 * This function only sends notices to the server during the ithemes_sync_add_notices action. If a notice must be sent
 * to the server outside of this time, use the ithemes_sync_urgent_notice() function.
 *
 * @since 1.4.0
 *
 * @param string $source Uniquely identifies the project that is sending the notice.
 * @param string $id Identifies the type of notice. This is to allow the server to differentiate different kinds of
 *   notices without having to parse the message.
 * @param string $subject A brief subject description of the notice that is fit for presentation to Sync users.
 * @param string $message A message that is fit for presentation to Sync users.
 * @param array $data Optional. Data that is relevant to the notice. For notices that may be best presented in a
 *   graphical manner, this field could be used to send data used to construct the graphic.
 * @return bool Currently, it always returns true.
 */
function ithemes_sync_add_notice( $source, $id, $subject, $message, $data = array() ) {
	require_once( $GLOBALS['ithemes_sync_path'] . '/notice-handler.php' );

	return $GLOBALS['ithemes_sync_notice_handler']->add_notice( $source, $id, $subject, $message, $data );
}

/**
 * Send an urgent notice to the Sync server.
 *
 * This function only sends notices to the server during the ithemes_sync_add_notices action. If a notice must be sent
 * to the server outside of this time, use the ithemes_sync_urgent_notice() function.
 *
 * @since 1.4.0
 *
 * @param string $source Uniquely identifies the project that is sending the notice.
 * @param string $id Identifies the type of notice. This is to allow the server to differentiate different kinds of
 *   notices without having to parse the message.
 * @param string $subject A brief subject description of the notice that is fit for presentation to Sync users.
 * @param string $message A message that is fit for presentation to Sync users.
 * @param array $data Optional. Data that is relevant to the notice. For notices that may be best presented in a
 *   graphical manner, this field could be used to send data used to construct the graphic.
 * @return bool Currently, it always returns true.
 */
function ithemes_sync_send_urgent_notice( $source, $id, $subject, $message, $data = array() ) {
	require_once( $GLOBALS['ithemes_sync_path'] . '/notice-handler.php' );

	return $GLOBALS['ithemes_sync_notice_handler']->send_urgent_notice( $source, $id, $subject, $message, $data );
}


function ithemes_sync_handle_activation_hook() {
	set_site_transient( 'ithemes-sync-activated', true, 120 );
	delete_site_option( 'ithemes_sync_hide_authenticate_notice' );
}
register_activation_hook( __FILE__, 'ithemes_sync_handle_activation_hook' );

function ithemes_sync_handle_deactivation_hook() {
	delete_site_option( 'ithemes_sync_hide_authenticate_notice' );
	delete_site_transient( 'ithemes-sync-activated' );
}
register_deactivation_hook( __FILE__, 'ithemes_sync_handle_deactivation_hook' );


function ithemes_sync_updater_register( $updater ) {
	$updater->register( 'ithemes-sync', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_sync_updater_register' );

require(dirname(__FILE__) . '/lib/updater/load.php');
