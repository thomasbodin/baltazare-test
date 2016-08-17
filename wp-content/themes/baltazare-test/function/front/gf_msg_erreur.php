<?php

/**
 * Message d'erreur customisable du formulaire Gravity Form
 */
add_filter( 'gform_validation_message_2', 'change_message', 10, 2 );
function change_message( $message, $form ) {
    return '<div class="validation_error">Le formulaire n\'a pas été complété correctement.</div>';
}