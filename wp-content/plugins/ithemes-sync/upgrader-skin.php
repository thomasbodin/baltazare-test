<?php

/*
Upgrader skin for preventing output when upgrades are occurring.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2013-11-05 - Chris Jean
		Initial version
*/


class Ithemes_Sync_Upgrader_Skin extends Bulk_Upgrader_Skin {
	function header() {}
	function footer() {}
	function bulk_header() {}
	function bulk_footer() {}
	function before( $title = '' ) {}
	function after( $title = '' ) {}
	function error( $errors ) {}
	function feedback( $string ) {}
}
