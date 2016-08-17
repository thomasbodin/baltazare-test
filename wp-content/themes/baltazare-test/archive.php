<?php
/**
 * The template for displaying archive pages
 */

get_header(); ?>

	<?php if ( have_posts() ) : ?>

		<?php the_archive_title(); ?>
		<?php the_archive_description(); ?>


		<?php while ( have_posts() ) : the_post(); ?>

			<?php the_title(); ?>
			<?php the_content(); ?>

		<?php endwhile; ?>

	<?php else : ?>

		<p>Aucune archive</p>

	<?php endif; ?>

<?php get_footer(); ?>
