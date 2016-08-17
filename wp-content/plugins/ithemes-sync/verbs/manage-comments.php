<?php

/*
Implementation of the manage-comments verb.
Written by Chris Jean for iThemes.com
Version 1.0.0

Version History
	1.0.0 - 2014-06-02 - Chris Jean
		Initial version
*/


class Ithemes_Sync_Verb_Manage_Comments extends Ithemes_Sync_Verb {
	public static $name = 'manage-comments';
	public static $description = 'Approve, edit, and delete comments.';
	
	private $default_arguments = array();
	private $response = array();
	private $comments_cache = array();
	
	
	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );
		
		include_once(ABSPATH . WPINC . '/comment.php');
		
		
		$actions = array(
			'add'       => '',
			'edit'      => '',
			'trash'     => 'wp_trash_comment',
			'untrash'   => 'wp_untrash_comment',
			'spam'      => 'wp_spam_comment',
			'unspam'    => 'wp_unspam_comment',
			'approve'   => array( $this, 'approve_comment' ),
			'unapprove' => array( $this, 'unapprove_comment' ),
			'delete'    => array( $this, 'delete_comment' ),
		);
		
		foreach ( $arguments as $action => $data ) {
			if ( 'get-actions' == $action ) {
				$this->response[$action] = array_keys( $actions );
				continue;
			}
			
			if ( ! isset( $actions[$action] ) ) {
				$this->response[$action] = 'This action is not recognized.';
				continue;
			}
			if ( ! is_array( $data ) ) {
				$this->response[$action] = new WP_Error( 'invalid-argument', 'This action requires an array.' );
				continue;
			}
			
			if ( 'add' == $action ) {
				$this->response[$action] = $this->add_comment( $data );
			} else if ( 'edit' == $action ) {
				$this->response[$action] = $this->edit_comments( $data );
			} else {
				$this->response[$action] = $this->run_function_on_ids( $actions[$action], $data );
			}
		}
		
		return $this->response;
	}
	
	private function is_valid_comment_id( $id ) {
		if ( isset( $this->comments_cache[$id] ) ) {
			return $this->comments_cache[$id];
		}
		
		$comment = get_comment( $id );
		
		if ( empty( $comment ) || is_wp_error( $comment ) ) {
			$comment = false;
		} else {
			$comment = true;
		}
		
		$this->comments_cache[$id] = $comment;
		
		return $comment;
	}
	
	private function run_function_on_ids( $function, $ids ) {
		if ( is_array( $function ) ) {
			if ( 'delete_comment' == $function[1] ) {
				$check_function = 'wp_delete_comment';
			} else {
				$check_function = 'wp_set_comment_status';
			}
		} else {
			$check_function = $function;
		}
		
		if ( ! is_callable( $check_function ) ) {
			return new WP_Error( "missing-function-$check_function", "Due to an unknown issue, the $check_function function is not available." );
		}
		
		
		$response = array();
		
		foreach ( $ids as $id ) {
			if ( ! $this->is_valid_comment_id( $id ) ) {
				$response[$id] = new WP_Error( 'invalid-comment-id', 'The requested comment could not be found.' );
			} else {
				$response[$id] = call_user_func( $function, $id );
			}
		}
		
		return $response;
	}
	
	private function approve_comment( $id ) {
		return wp_set_comment_status( $id, 'approve' );
	}
	
	private function unapprove_comment( $id ) {
		return wp_set_comment_status( $id, 'hold' );
	}
	
	private function delete_comment( $id ) {
		return wp_delete_comment( $id, true );
	}
	
	private function edit_comments( $edits ) {
		if ( ! is_callable( 'wp_update_comment' ) ) {
			include_once( ABSPATH . WPINC . '/wp-includes/comment.php' );
		}
		if ( ! is_callable( 'wp_update_comment' ) ) {
			return new WP_Error( 'missing-function-wp_update_comment', 'Due to an unknown issue, the wp_update_comment function is not available.' );
		}
		
		
		$response = array();
		
		foreach ( $edits as $id => $data ) {
			if ( (int) $id != $id ) {
				$response[$id] = new WP_Error( 'invalid-index', 'Array keys for the edit argument must be integers, each representing a comment ID.' );
			} else if ( ! $this->is_valid_comment_id( $id ) ) {
				$response[$id] = new WP_Error( 'invalid-comment-id', 'The requested comment could not be found.' );
			} else {
				$data['comment_ID'] = $id;
				$result = wp_update_comment( $data );
				
				if ( is_int( $result ) ) {
					$result = (bool) $result;
				}
				
				$response[$id] = $result;
			}
		}
		
		return $response;
	}
	
	private function add_comment( $comment ) {
		if ( ! is_array( $comment ) ) {
			return new WP_Error( 'invalid-argument', 'This action requires an array of valid comment entries.' );
		}
		
		if ( ! isset( $comment['comment_post_ID'] ) ) {
			$response = array();
			$error_count = 0;
			
			foreach ( $comment as $id => $data ) {
				$response[$id] = $this->add_comment( $data );
				
				if ( is_wp_error( $response[$id] ) ) {
					$error_count++;
				}
			}
			
			if ( count( $comment ) == $error_count ) {
				return new WP_Error( 'invalid-argument', 'This action requires an array of valid comment entries.' );
			}
			
			return $response;
		}
		
		
		$required_indexes = array(
			'comment_author_IP',
			'comment_content',
			'comment_agent',
		);
		
		$comment_defaults = array(
			'comment_approved' => 1,
			'comment_karma'    => 0,
			'comment_parent'   => 0,
			'comment_type'     => '',
			'filtered'         => false,
			
			'sync_run_preprocess_comment_filter' => true, // Change to false to skip the preprocess_comment filter.
			'sync_send_comment_notifications'    => true, // Change to false to disable comment notification emails.
		);
		
		
		// Starting here, much of the following code mirrors similar code from wp-comments-post.php and wp-includes/comment.php from WP version 3.9.1.
		// Mirroring this code was the only way to reliably provide full comment functionality and flexibility while staying compatible with the WP API.
		
		if ( ! empty( $comment['user_id'] ) ) {
			$user = get_user_by( 'id', $comment['user_id'] );
			
			if ( ! is_object( $user ) || ! is_a( $user, 'WP_User' ) || ! $user->exists() ) {
				return new WP_Error( 'invalid-user-id', "A user with an ID of {$comment['user_id']} does not exist." );
			}
			
			if ( empty( $user->display_name ) ) {
				$user->display_name = $user->user_login;
			}
			
			$comment['comment_author']       = wp_slash( $user->display_name );
			$comment['comment_author_email'] = wp_slash( $user->user_email );
			$comment['comment_author_url']   = wp_slash( $user->user_url );
			
			kses_remove_filters();
			kses_init_filters();
		} else if ( isset( $comment['comment_author'] ) && isset( $comment['comment_author_email'] ) && isset( $comment['comment_author_url'] ) ) {
			$comment['user_id'] = 0;
		} else {
			return new WP_Error( 'missing-required-commenter-data', 'Either user_id or comment_author, comment_author_email, and comment_author_url must be supplied.' );
		}
		
		
		$comment = array_merge( $comment_defaults, $comment );
		
		
		$run_preprocess_comment_filter = $comment['sync_run_preprocess_comment_filter'];
		unset( $comment['sync_run_preprocess_comment_filter'] );
		
		$send_comment_notifications = $comment['sync_send_comment_notifications'];
		unset( $comment['sync_send_comment_notifications'] );
		
		
		$missing_indexes = array();
		
		foreach ( $required_indexes as $index ) {
			if ( empty( $comment[$index] ) ) {
				$missing_indexes[] = $index;
			}
		}
		
		if ( ! empty( $missing_indexes ) ) {
			return new WP_Error( 'missing-comment-data', 'The following required indexes were missing in the comment data: ' . implode( ', ', $missing_indexes ) );
		}
		
		
		if ( $run_preprocess_comment_filter ) {
			apply_filters( 'preprocess_comment', $comment );
		}
		
		$comment['comment_author_IP'] = preg_replace( '/[^0-9a-fA-F:., ]/', '', $comment['comment_author_IP'] );
		$comment['comment_agent']     = substr( $comment['comment_agent'], 0, 254 );
		
		$comment['comment_date']     = current_time( 'mysql' );
		$comment['comment_date_gmt'] = current_time( 'mysql', 1 );
		
		if ( ! $comment['filtered'] ) {
			$comment = wp_filter_comment( $comment );
		}
		
		$id = wp_insert_comment( $comment );
		
		if ( 0 == $id ) {
			if ( ! empty( $GLOBALS['wpdb']->last_error ) ) {
				$error = $GLOBALS['wpdb']->last_error;
			} else {
				$error = 'An unknown error prevented the comment from being added to the database.';
			}
			
			return new WP_Error( 'comment-insert-failure', $error );
		}
		
		
		do_action( 'comment_post', $id, $comment['comment_approved'] );
		
		if ( $send_comment_notifications && ( 'spam' !== $comment['comment_approved'] ) ) {
			if ( '0' == $comment['comment_approved'] ) {
				wp_notify_moderator( $id );
			}
			
			if ( get_option( 'comments_notify' ) && $comment['comment_approved'] ) {
				wp_notify_postauthor( $id );
			}
		}
		
		
		$comment['comment_ID'] = $id;
		
		return $comment;
	}
}
