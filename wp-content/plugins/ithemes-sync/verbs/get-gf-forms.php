<?php

/**
 * Implementation of the get-gf-forms verb.
 *
 * @author Aaron D. Campbell <aaron@ithemes.com>
 * @version 1.0.0
 *
 * Version History
 *   1.0.0 - 2014-07-07 - Aaron D. Campbell
 *       Initial version
 */


class Ithemes_Sync_Verb_Get_GF_Forms extends Ithemes_Sync_Verb {
	public static $name = 'get-gf-forms';
	public static $description = 'Retrieve Gravity Forms forms.';

	public function run( $arguments ) {
		if ( class_exists( 'RGFormsModel' ) && is_callable( array( 'RGFormsModel', 'get_forms' ) ) ) {
			return RGFormsModel::get_forms();
		}
		return false;
	}
}
