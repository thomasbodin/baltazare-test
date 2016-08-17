<?php

    /**
     * Login CSS custom
     */
    function custom_login_css(){
        echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('template_directory') . '/style/build/lecentre.css" />';
    }
    add_action('login_head', 'custom_login_css');