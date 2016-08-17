<?php

/**
 * Implementation of the get-gf-form-entries verb.
 *
 * @author Aaron D. Campbell <aaron@ithemes.com>
 * @version 1.0.0
 *
 * Version History
 *   1.0.0 - 2014-07-07 - Aaron D. Campbell
 *       Initial version
 */


class Ithemes_Sync_Verb_Get_GF_Form_Entries extends Ithemes_Sync_Verb {
	public static $name = 'get-gf-form-entries';
	public static $description = 'Retrieve Gravity Forms form entries.';

	public function run( $arguments ) {
		if ( class_exists( 'RGFormsModel' ) && is_callable( array( 'RGFormsModel', 'get_form_meta' ) )
			&& class_exists( 'GFAPI' ) && is_callable( array( 'GFAPI', 'get_entries' ) ) ) {

			if ( empty( $arguments['id'] ) ) {
				return array( 'error' => 'Form ID Required' );
			}
			$return = array();
			$form_meta = GFFormsModel::get_form_meta( absint( $arguments['id'] ) );
			$return['field_labels'] = array();
			foreach ( $form_meta['fields'] as $field ) {
				$return['field_labels'][ "{$field['id']}" ] = $this->_process_label( $field );
			}

			$return['current_page'] = empty( $arguments['page'] ) ? 1 : absint( $arguments['page'] );
			if ( $return['current_page'] < 1 ) {
				$return['current_page'] = 1;
			}

			$paging = array(
				'page_size' => 20,
			);
			$paging['offset'] = ( $return['current_page'] - 1 ) * $paging['page_size'];

			$return['total_count'] = 0;
			$return['entries'] = GFAPI::get_entries( absint( $arguments['id'] ), null, null, $paging, $return['total_count'] );
			$return['page_size'] = $paging['page_size'];
			$return['total_pages'] = ceil( $return['total_count'] / $paging['page_size'] );
			return $return;
		}
		return false;
	}

	private function _process_label( $field ) {
		$return = array( 'label' => $field['label'] );

		if ( ! empty( $field['inputs'] ) ) {
			$return['children'] = array();
			foreach ( $field['inputs'] as $input ) {
				$return['children'][ "{$input['id']}" ] = $this->_process_label( $input );
			}
		}

		return $return;
	}
}
