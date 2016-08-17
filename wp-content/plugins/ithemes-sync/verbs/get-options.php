<?php

/*
Implementation of the get-options verb.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2014-07-01 - Chris Jean
		Initial version
*/


class Ithemes_Sync_Verb_Get_Options extends Ithemes_Sync_Verb {
	public static $name = 'get-options';
	public static $description = 'Retrieve values from the WordPress options system.';
	public static $status_element_name = 'options';
	public static $show_in_status_by_default = false;
	
	private $default_arguments = array();
	
	private $response = array();
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		foreach ( $arguments as $var => $val ) {
			if ( 'options' == $var ) {
				$this->response[$var] = $this->get_values( $var, $val, 'get_option', 'wp_load_alloptions' );
			} else if ( 'site-options' == $var ) {
				$this->response[$var] = $this->get_values( $var, $val, 'get_site_option' );
			} else if ( 'transients' == $var ) {
				$this->response[$var] = $this->get_values( $var, $val, 'get_transient' );
			} else if ( 'site-transients' == $var ) {
				$this->response[$var] = $this->get_values( $var, $val, 'get_site_transient' );
			} else {
				$this->response[$var] = new WP_Error( 'invalid-argument', "The $var argument is not recognized by the get-options verb." );
			}
		}
		
		return $this->response;
	}
	
	private function get_values( $argument, $names, $function, $all_function = false ) {
		if ( true === $names ) {
			if ( false === $all_function ) {
				return new WP_Error( 'invalid-argument-value', "The $argument argument does not support listing all availble options. Please supply an array of strings." );
			} else if ( is_callable( $all_function ) ) {
				$options = call_user_func( $all_function );
				unset( $options['ithemes-sync-cache'] );
				
				return $options;
			} else {
				return new WP_Error( "missing-function-$all_function", "An unknown error is preventing the function for listing all $argument from being callable." );
			}
		} else if ( is_array( $names ) || is_string( $names ) ) {
			$options = array();
			
			foreach ( (array) $names as $name ) {
				$options[$name] = call_user_func( $function, $name );
			}
			
			return $options;
		} else {
			if ( false === $all_function ) {
				return new WP_Error( 'invalid-argument-value', "The $argument argument requires an array of strings." );
			} else {
				return new WP_Error( 'invalid-argument-value', "The $argument argument requires an array of strings or a boolean true." );
			}
		}
		
	}
}
