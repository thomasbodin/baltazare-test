<?php

class Ithemes_Sync_Social {

	private $_page_name = 'sync-social';

	/**
	 * Array containing all accounts linked with this site
	 *
	 * @var array - Index is account type (ie "twitter"), value is array of accounts
	 */
	private $_linked_accounts = array();

	/**
	 * Array containing accounts that are not used by default.
	 *
	 * @var array - Index is account type (ie "twitter"), value is array of accounts
	 */
	private $_non_default_accounts = array();

	/**
	 * Array containing the default content for each account type
	 *
	 * @var array - Index is account type (ie "twitter"), value is the default content string
	 */
	private $_default_content = array();

	public function __construct() {
		if ( $this->_have_linked_accounts() ) {
			add_action( 'init',                        array( $this, 'init' ) );
		}
	}

	public function init() {
		add_action( 'post_submitbox_misc_actions', array( $this, 'post_submitbox_misc_actions' ) );
		add_action( 'admin_enqueue_scripts',       array( $this, 'metabox_assets' ) );
		add_action( 'transition_post_status',      array( $this, 'transition_post_status' ), null, 3 );
	}

	public function metabox_assets( $hook ) {
		if ( $hook != 'post.php' && $hook != 'post-new.php' )
			return;

		wp_enqueue_script( 'ithemes-social-submitbox', plugins_url( 'js/social-metabox.js', __FILE__ ), array('jquery') );
		wp_enqueue_style(  'ithemes-social-submitbox', plugins_url( 'css/social-metabox.css', __FILE__ ) );
	}

	private function _get_linked_accounts( $nocache = false ) {
		if ( empty( $this->_linked_accounts ) || $nocache ) {
			$this->_linked_accounts['twitter'] = get_option( 'ithemes-sync-twitter-accounts', array() );
		}
		return $this->_linked_accounts;
	}

	private function _get_non_default_accounts( $nocache = false ) {
		if ( empty( $this->_non_default_accounts ) || $nocache ) {
			$this->_non_default_accounts['twitter'] = get_option( 'ithemes-sync-twitter-non-default-accounts', array() );
		}
		return $this->_non_default_accounts;
	}

	private function _get_default_content( $nocache = false ) {
		if ( empty( $this->_default_content ) || $nocache ) {
			$this->_default_content['twitter'] = get_option( 'ithemes-sync-twitter-default-content' );
			if ( false === $this->_default_content['twitter'] ) {
				$this->_default_content['twitter'] = '{post_title} - {post_url}';
			}
		}
		return $this->_default_content;
	}

	private function _have_linked_accounts() {
		foreach ( $this->_get_linked_accounts() as $type => $accounts ) {
			if ( ! empty( $accounts ) ) {
				return true;
			}
		}
		return false;
	}

	public function post_submitbox_misc_actions() {
		$post = get_post();
		?>
		<div id="ithemes-social" class="misc-pub-section">
			<div class="ithemes-social-header">
				<h3><?php _e('Social', 'it-l10n-ithemes-sync'); ?></h3>
			</div>
			<?php
			if ( 'publish' === $post->post_status ) {
				$this->_post_submitbox_content_published();
			} else {
				$this->_post_submitbox_content_unpublished();
			}
			?>
		</div>

		<?php
	}

	private function _post_submitbox_content_published() {
		?>
		<p class="it-social-notice"><?php _e('This post is already published.', 'it-l10n-ithemes-sync'); ?></p>
		<?php
	}

	private function _post_submitbox_content_unpublished() {
		$accounts = $this->_get_linked_accounts();
		$not_default = $this->_get_non_default_accounts();
		$default_content = $this->_get_default_content();
		?>
			<ul class="ithemes-social-accounts">
				<?php
				foreach ( $accounts['twitter'] as $twitter_id => $twitter_name ) {
					$checked = in_array( $twitter_id, $not_default['twitter'] )? '':' checked';
					?>
				<li class="test">
					<input type="checkbox" name="ithemes-social[twitter][<?php echo esc_attr( $twitter_id ) ?>][send]" id="ithemes-social-twitter-<?php echo esc_attr( $twitter_id ) ?>" value="true"<?php echo $checked; ?>>
					<label for="ithemes-social-twitter-<?php echo esc_attr( $twitter_id ) ?>">Twitter: <em>@<?php echo esc_html( $twitter_name ) ?></em></label>
					<a class="ithemes-social-edit-content"><?php _e('Edit', 'it-l10n-ithemes-sync'); ?></a>
					<div class="ithemes-social-account-content hidden">
						<textarea class="widefat" name="ithemes-social[twitter][<?php echo esc_attr( $twitter_id ) ?>][content]"><?php echo esc_textarea( $default_content['twitter'] ); ?></textarea>
						<div class="ithemes-social-template-tags">
							<?php _e('Template Tags', 'it-l10n-ithemes-sync'); ?>: <br />
							{post_title} {post_url} {post_author}
						</div>
						<div class="ithemes-social-char-count"></div>
					</div>
				</li>
					<?php
				}
				?>
			</ul>
		<?php
	}

	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( $old_status != 'publish' && $new_status == 'publish' && ! empty( $_POST['ithemes-social'] ) ) {
			/**
			 * @todo Allow custom tweet content
			 * @todo use send-to to say which accounts should be tweeted to
			 */
			$post_title = get_the_title( $post->ID );
			$data = array(
				'post' => $post,
				'twitter' => array(),
			);
			if ( ! empty( $_POST['ithemes-social']['twitter'] ) )
			foreach ( $_POST['ithemes-social']['twitter'] as $twitter_id => $twitter ) {
				if ( ! empty( $twitter['send'] ) && 'true' === $twitter['send'] && ! empty( $twitter['content'] ) ) {
					$data['twitter'][$twitter_id] = $this->_handle_template_tags( stripslashes( $twitter['content'] ), $post );
				}
			}
			$result = ithemes_sync_send_urgent_notice( 'ithemes-sync', 'social', 'Post published', "'$post_title' has been published", $data );
		}
	}

	private function _handle_template_tags( $content, $post ) {
		$tags = array(
			'{post_title}'  => $post->post_title,
			'{post_url}'    => get_permalink( $post->ID ),
			'{post_author}' => get_the_author_meta( 'display_name', $post->post_author ),
		);

		return str_replace( array_keys( $tags ), $tags, $content );
	}
}
new Ithemes_Sync_Social();
