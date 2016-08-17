<?php

    /**
     * jQuery
     */
    add_action('wp_print_scripts','theme_slug_dequeue_footer_jquery');
    function theme_slug_dequeue_footer_jquery() {
        if( !is_admin()){
            wp_dequeue_script('jquery');
        }
    }