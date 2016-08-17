<?php
/**
 * The template for displaying Author archive pages
 */

get_header(); ?>

    <?php if ( have_posts() ) : ?>
        <h1>
            <?php the_author_meta('first_name'); ?>
        </h1>

    <?php else : ?>

       <p>Aucun auteur trouvé</p>

    <?php endif; ?>

<?php
get_footer();
?>
