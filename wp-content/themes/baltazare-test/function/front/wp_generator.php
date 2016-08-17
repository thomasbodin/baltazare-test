<?php

    /**
     * Ne pas afficher la version de WordPress utilisé
     * sur le frontend
     */
    remove_action('wp_head', 'wp_generator');