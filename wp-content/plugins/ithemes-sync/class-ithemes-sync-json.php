<?php

final class Ithemes_Sync_JSON {
	public static function encode( $data ) {
		$json = json_encode( $data );
		
		if ( false !== $json ) {
			return $json;
		}
		
		$data = self::get_fixed_data( $data );
		$json = json_encode( $data );
		
		if ( false !== $json ) {
			return $json;
		}
		
		return json_encode( 'Encoding error. Unable to JSON encode the data due to an unknown error.' );
	}
	
	private static function get_fixed_data( $data ) {
		if ( false !== json_encode( $data ) ) {
			return $data;
		}
		
		if ( is_string( $data ) ) {
			require_once(dirname(__FILE__) . '/class-ithemes-sync-utf8-encoder.php');
			$data = Ithemes_Sync_UTF8_Encoder::toUTF8( $data );
			
			if ( false === json_encode( $data ) ) {
				return 'INVALID STRING DATA';
			}
			
			return $data;
		}
		
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( false === json_encode( $key ) ) {
					unset( $data[$key] );
					$key = self::get_fixed_data( $key );
					$data[$key] = $value;
				}
				
				if ( false === json_encode( $value ) ) {
					$value = self::get_fixed_data( $value );
					$data[$key] = $value;
				}
			}
			
			if ( false === json_encode( $data ) ) {
				return 'INVALID ARRAY DATA';
			}
			
			return $data;
		}
		
		if ( is_object( $data ) ) {
			return 'INVALID CLASS ' . get_class( $data ) . ' DATA';
		}
		
		return 'INVALID DATA (' . gettype( $data ) . ')';
	}
}
