<?php

    /**
     * Title page login
     */
    function custom_title_login($message) {
        return get_bloginfo('description');
    }
    add_filter('login_headertitle', 'custom_title_login');