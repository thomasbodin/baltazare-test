<?php

    /**
     * Ne pas afficher la barre d'administration
     * sur le frontend
     */
    add_filter('show_admin_bar', '__return_false');