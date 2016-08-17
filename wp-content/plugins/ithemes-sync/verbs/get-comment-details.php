<?php

/*
Implementation of the get-comment-details verb.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2014-06-02 - Chris Jean
		Initial version
*/


class Ithemes_Sync_Verb_Get_Comment_Details extends Ithemes_Sync_Verb {
	public static $name = 'get-comment-details';
	public static $description = 'Retrieve details about the site\'s comments.';
	public static $status_element_name = 'comments';
	public static $show_in_status_by_default = false;
	
	private $response = array();
	
	private $default_arguments = array(
		'args'                   => array(
			'count'  => true,
			'status' => 'hold',
		),
		'include-parent-details' => true,
		'include-post-details'   => true,
		'include-user-details'   => true,
	);
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		if ( ! is_callable( 'get_comments' ) ) {
			include_once( ABSPATH . WPINC . '/wp-includes/comment.php' );
		}
		if ( ! is_callable( 'get_comments' ) ) {
			return new WP_Error( 'missing-function-get_comments', 'Due to an unknown issue, the wp_comments function is not available.' );
		}
		
		$comments = get_comments( $arguments['args'] );
		
		if ( is_array( $comments ) ) {
			$this->response['comments'] = array();
			
			foreach ( $comments as $index => $comment ) {
				$this->response['comments'][$comment->comment_ID] = (array) $comment;
			}
			
			if ( $arguments['include-parent-details'] ) {
				$this->add_parent_details();
			}
			
			if ( $arguments['include-post-details'] ) {
				$this->add_post_details();
			}
			
			if ( $arguments['include-user-details'] ) {
				$this->add_user_details();
			}
		} else {
			$this->response = $comments;
		}
		
		
		return $this->response;
	}
	
	private function add_parent_details() {
		$parent_ids = array();
		$parents = array();
		
		foreach ( $this->response['comments'] as $comment ) {
			if ( ! empty( $comment['comment_parent'] ) ) {
				$parent_ids[$comment['comment_parent']] = true;
			}
		}
		$parent_ids = array_keys( $parent_ids );
		
		foreach ( $parent_ids as $parent_id ) {
			$parents[$parent_id] = get_comment( $parent_id, ARRAY_A );
		}
		
		$this->response['parents'] = $parents;
	}
	
	private function add_post_details() {
		$post_ids = array();
		$posts = array();
		
		foreach ( $this->response['comments'] as $id => $comment ) {
			$post_ids[$comment['comment_post_ID']] = true;
		}
		$post_ids = array_keys( $post_ids );
		
		if ( ! empty( $post_ids ) ) {
			$posts = get_posts( array( 'post__in' => $post_ids ) );
		}
		
		foreach ( $posts as $post ) {
			$id = $post->ID;
			
			$post = (array) $post;
			unset( $post['ID'] );
			unset( $post['post_content'] );
			unset( $post['post_excerpt'] );
			unset( $post['post_password'] );
			unset( $post['post_modified'] );
			unset( $post['post_modified_gmt'] );
			unset( $post['to_ping'] );
			unset( $post['pinged'] );
			unset( $post['post_content_filtered'] );
			unset( $post['menu_order'] );
			unset( $post['filter'] );
			
			$this->response['posts'][$id] = $post;
		}
	}
	
	private function add_user_details() {
		$user_ids = array();
		
		$comments = $this->response['comments'];
		
		if ( isset( $this->response['parents'] ) ) {
			$comments = array_merge( $comments, $this->response['parents'] );
		}
		
		foreach ( $comments as $comment ) {
			if ( empty( $comment['user_id'] ) ) {
				continue;
			}
			
			$user_ids[$comment['user_id']] = true;
		}
		
		if ( isset( $this->response['posts'] ) ) {
			foreach ( $this->response['posts'] as $post ) {
				$user_ids[$post['post_author']] = true;
			}
		}
		
		$user_ids = array_keys( $user_ids );
		
		if ( empty( $user_ids ) ) {
			$this->response['users'] = array();
			return;
		}
		
		
		$users = get_users( array( 'include' => $user_ids ) );
		
		foreach ( $users as $user ) {
			$id = $user->ID;
			
			$user = (array) $user->data;
			unset( $user['ID'] );
			unset( $user['user_pass'] );
			unset( $user['user_login'] );
			unset( $user['user_nicename'] );
			unset( $user['user_activation_key'] );
			unset( $user['user_status'] );
			
			$this->response['users'][$id] = $user;
		}
	}
}
