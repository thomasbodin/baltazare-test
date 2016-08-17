<?php

    /**
     * Move Yoast to bottom
     */

    function yoasttobottom() {
        return 'low';
    }
    add_filter( 'wpseo_metabox_prio', 'yoasttobottom');
