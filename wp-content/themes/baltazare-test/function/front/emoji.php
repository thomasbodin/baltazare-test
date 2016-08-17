<?php

    /**
     * Supprimer le style des emoticones du wp_head
     * sur le frontend
     */
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    add_action('get_header', 'remove_admin_login_header');
    function remove_admin_login_header() {
        remove_action('wp_head', '_admin_bar_bump_cb');
    }