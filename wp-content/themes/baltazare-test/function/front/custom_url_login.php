<?php

    /**
     * siteurl du login
     */
    function custom_url_login() {
        return get_bloginfo( 'url' );
    }
    add_filter('login_headerurl', 'custom_url_login');