<?php

    /**
     * Rename url /wp-admin to /admin
     */
    add_filter('site_url', 'wpadmin_filter', 10, 3);
    function wpadmin_filter( $url, $path, $orig_scheme ) {
        $old = array( "/(wp-admin)/");
        $admin_dir = WP_ADMIN_DIR;
        $new = array($admin_dir);
        return preg_replace( $old, $new, $url, 1);
    }