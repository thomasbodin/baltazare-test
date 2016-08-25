<?php get_header(); ?>

<?php if (have_posts()) : while (have_posts()) : the_post();?>

	<div class="col-md-6 col-md-offset-3">
	<h1><?php the_title(); ?></h1>
    <?php the_post_thumbnail('medium'); ?>
    <?php the_content();     ?>

	</div>


<?php endwhile; endif; ?>

<?php get_footer(); ?>