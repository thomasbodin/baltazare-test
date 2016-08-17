<!DOCTYPE html>
    <!--[if IE 7]><html class="ie ie7" <?php language_attributes(); ?>> <![endif]-->
    <!--[if IE 8]><html class="ie ie8" <?php language_attributes(); ?>><![endif]-->
    <!--[if !(IE 7) | !(IE 8)  ]><!--><html <?php language_attributes(); ?>><!--<![endif]-->
    <head>
        <title><?php the_title(); ?></title>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

        <!-- Favicon -->
        <link rel="apple-touch-icon" sizes="57x57" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/apple-touch-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/apple-touch-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/apple-touch-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/apple-touch-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="114x114" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/apple-touch-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="120x120" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/apple-touch-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="144x144" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/apple-touch-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/apple-touch-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/apple-touch-icon-180x180.png">
        <link rel="icon" type="image/png" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/favicon-32x32.png" sizes="32x32">
        <link rel="icon" type="image/png" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/android-chrome-192x192.png" sizes="192x192">
        <link rel="icon" type="image/png" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/favicon-96x96.png" sizes="96x96">
        <link rel="icon" type="image/png" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/favicon-16x16.png" sizes="16x16">
        <link rel="manifest" href="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/manifest.json">
        <meta name="msapplication-TileColor" content="#000000">
        <meta name="msapplication-TileImage" content="<?php bloginfo( 'template_directory' ); ?>/assets/favicon/mstile-144x144.png">
        <meta name="theme-color" content="#ffffff">

        <!-- Styles -->
        <?php $version = filemtime( get_theme_root().'/'.get_template() . '/style/build/main.css' ); ?>
        <link rel="stylesheet" href="<?php bloginfo( 'template_directory' ); ?>/style/build/main.css?v=<?= $version; ?>">

        <!--[if lt IE 9]>
        <script src="<?php bloginfo( 'template_directory' ); ?>/script/build/html5shiv.min.js"></script>
        <script src="<?php bloginfo( 'template_directory' ); ?>/script/build/respond.min.js"></script>
        <![endif]-->

        <!-- Scripts -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
        <script>window.jQuery || document.write('<script src="<?php bloginfo( 'template_directory' ); ?>/script/build/jquery.min.js"><\/script>')</script>
        <?php wp_head(); ?>
    </head>

    <body <?php body_class(); ?>>

        <header class="">
            <!-- your code HTML header here -->
        </header>

        <?php
        wp_nav_menu(array(
            'theme_location'    => '',
            'container'         => 'nav',
            'menu_id'           => '',
            'menu_class'        => '',
            'container_id'      => '',
            'container_class'   => ''
        ));
        ?>

        <section class="">

