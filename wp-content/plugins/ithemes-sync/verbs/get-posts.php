<?php

/*
Implementation of the get-posts verb.
Written by Aaron D. Campbell for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2015-02-17 - Aaron D. Campbell
		Initial version
*/


class Ithemes_Sync_Verb_Get_Posts extends Ithemes_Sync_Verb {
	public static $name = 'get-posts';
	public static $description = 'Retrieve posts from WordPress.';
	public static $status_element_name = 'posts';
	public static $show_in_status_by_default = false;

	private $default_arguments = array(
		'numberposts' => 10,
	);

	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		$posts = get_posts( $arguments );

		// Add some additional data
		foreach ( $posts as &$post ) {
			$post->permalink = get_permalink( $post->ID );
			$post->author_display_name = get_the_author_meta( 'display_name', $post->post_author );
		}
		return $posts;
	}
}
