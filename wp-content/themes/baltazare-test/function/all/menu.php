<?php

    /**
     * Gestion des menus
     */
    function register_my_menu() {
        register_nav_menu('principal',__( 'Menu Principal' ));
    }
    add_action( 'init', 'register_my_menu' );