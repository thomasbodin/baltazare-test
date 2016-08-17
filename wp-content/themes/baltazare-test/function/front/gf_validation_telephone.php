<?php

/**
 * Validation du numéro de téléphone FR du formulaire Gravity Form
 */
add_filter( 'gform_field_validation', 'validate_phone', 10, 4 );
function validate_phone( $result, $value, $form, $field ) {
    $pattern = "/^0[1-8][0-9]{8}$/";
    if ( $field->type == 'phone' && ! preg_match( $pattern, $value ) ) {
        $result['is_valid'] = false;
    }

    return $result;
}