<?php

/*
Implementation of the get-status verb.
Written by Chris Jean for iThemes.com
Version 1.4.1

Version History
	1.0.0 - 2013-10-01 - Chris Jean
		Initial version
	1.1.0 - 2013-11-18 - Chris Jean
		Updated code to use external functions to pull the data from.
		Added "php", "server", and "settings" sections.
	1.2.0 - 2013-11-22 - Chris Jean
		Added "supported-verbs" section.
	1.3.0 - 2013-12-19 - Chris Jean
		Updated to support variable element output.
	1.4.0 - 2014-01-20 - Chris Jean
		Enhancement: The status elements are now variable, allowing for requests for specific data.
	1.4.1 - 2014-06-23 - Chris Jean
		Added the ability to provide arguments specific to individual status elements.
*/


class Ithemes_Sync_Verb_Get_Status extends Ithemes_Sync_Verb {
	public static $name = 'get-status';
	public static $description = 'Retrieve basic details about the site.';
	
	private $default_arguments = array(
		'status_elements' => array(),
	);
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		if ( ! is_callable( array( $GLOBALS['ithemes-sync-api'], 'run' ) ) ) {
			return new WP_Error( 'missing-method-api-run', 'The Ithemes_Sync_API::run function is not callable. Unable to generate status details.' );
		}
		
		
		$status_elements = Ithemes_Sync_Functions::get_status_elements( $arguments );
		
		if ( ! empty( $arguments['status_elements'] ) ) {
			if ( is_array( $arguments['status_elements'] ) ) {
				$show_status_elements = $arguments['status_elements'];
			} else {
				trigger_error( 'A non-array status_elements argument was supplied. The argument will be ignored.' );
			}
			
			unset( $arguments['status_elements'] );
		}
		
		if ( ! isset( $show_status_elements ) ) {
			$show_status_elements = Ithemes_Sync_Functions::get_default_status_elements();
		}
		
		foreach ( $show_status_elements as $element ) {
			if ( isset( $status_elements[$element] ) ) {
				$var = $status_elements[$element];
				
				$element_arguments = $arguments;
				
				if ( isset( $arguments[$var] ) ) {
					$element_arguments = $arguments[$var];
				}
				
				$data = $GLOBALS['ithemes-sync-api']->run( $status_elements[$element], $element_arguments );
			} else {
				$data = "This element is not recognized";
			}
			
			$status[$element] = $data;
		}
		
		
		return $status;
	}
}
