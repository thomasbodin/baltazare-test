<?php
    /**
     *
     * functions.php
     * Fichier de modification du comportement du WordPress
     *
     * Appel des fonctions selon l'utilisation : ajax, admin, front ou all
     * Inclure les fonctions à utiliser sur le projet dans le fichier correspondant function/
     * Inclure la fonction dans un fichier à son nom function/front/ ou function/admin/ ou function/ajax/
     *
     * Les fonctions récurentes sur les projets sont déjà dans les répertoires associés,
     * il faut décommenter leur appel dans le fichier associé dans function/
     *
     */
    $templatepath = get_template_directory();

    if ( defined('DOING_AJAX') && DOING_AJAX && is_admin() ) {

        include( $templatepath.'/function/ajax.php' );

    } elseif ( is_admin() ) {

        include( $templatepath.'/function/admin.php' );

    } elseif ( !defined( 'XMLRPC_REQUEST' ) && !defined( 'DOING_CRON' ) ) {

        include( $templatepath.'/function/front.php' );

    }

    include( $templatepath.'/function/all.php' );



    /**
     *
     * Liste des fonctions pour une utilisation front, back et ajax
     *
     */

    //remove_role('subscriber');
    //remove_role('contributor');
    //remove_role('editor');
    //remove_role('author');


    /**
     * Custom theme
     */

    // CUSTOM POST TYPE
    /*function cpt_init() {
        // Valeurs entrés
        $nom = "site";
        $menu_name = "Sites";
        $genre = "M";

        $result = init($nom,$menu_name,$genre);
        CreateCPT($result);
    }
    add_action('init', 'cpt_init');*/

    // Ajout du rôle client
    function createRoleClient() {
        global $wp_roles;
        if (!isset( $wp_roles )) {
            $wp_roles = new WP_Roles();
        }
        $adm = $wp_roles->get_role('administrator');
        $wp_roles->add_role('client', 'Client', $adm->capabilities);
    }
    add_action('init', 'createRoleClient');


    // La fonction ne sera pas utilisée avant le 'init'.
    add_action( 'init', 'my_custom_init' );


    function my_custom_init() {
            $labels = array(
            'name' => _x( 'Films', 'post type general name' ),
            'singular_name' => _x( 'Film', 'post type singular name' ),
            'add_new' => _x( 'Ajouter nouveau', 'Film' ),
            'add_new_item' => __( 'Ajouter nouveau Film' ),
            'edit_item' => __( 'Éditer Film' ),
            'new_item' => __( 'Nouveau Film' ),
            'view_item' => __( 'Voir Film' ),
            'search_items' => __( 'Chercher Films' ),
            'set_featured_image'    => 'Set featured image',
            'remove_featured_image' => 'Remove featured image',
            'use_featured_image'    => 'Use as featured image',
            'not_found' =>  __( 'Les Films ne se sont pas trouvés' ),
            'not_found_in_trash' => __( 'Les Films ne se sont pas trouvés dans la papeterie' ),
            'parent_item_colon' => ''
        );
     
        // Creé dans array pour $args
        $args = array( 'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array( 'title', 'editor', 'thumbnail','page-attributes' ),
        );
     
        register_post_type( 'Film', $args ); 
    }

   