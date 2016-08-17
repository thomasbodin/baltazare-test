<?php

/**
 * Two-Factor Execution
 *
 * Handles all two-factor execution once the feature has been
 * enabled by the user.
 *
 * @since   1.2.0
 *
 * @package iThemes_Security
 */
class ITSEC_Two_Factor {

	/**
	 * The module's saved options
	 *
	 * @since  1.2.0
	 * @access private
	 * @var array
	 */
	private $_settings;

	/**
	 * Helper class
	 *
	 * @access private
	 * @var ITSEC_Two_Factor_Helper
	 */
	private $_helper;

	/**
	 * The user meta provider key.
	 *
	 * @access private
	 * @var string
	 */
	private $_provider_user_meta_key = '_two_factor_provider';

	/**
	 * The user meta enabled providers key.
	 *
	 * @access private
	 * @var string
	 */
	private $_enabled_providers_user_meta_key = '_two_factor_enabled_providers';

	/**
	 * The user meta nonce key.
	 *
	 * @var string
	 */
	private $_user_meta_nonce_key = '_two_factor_nonce';

	public function __construct() {
		require_once('class-itsec-two-factor-helper.php');
		require_once('class-itsec-two-factor-core-compat.php');
		$this->_helper  = ITSEC_Two_Factor_Helper::get_instance();
	}

	/**
	 * Setup the module's functionality.
	 *
	 * Loads the two-factor module's unprivileged functionality.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	function run( $core ) {

		$this->_settings = $this->_helper->get_settings();

		if ( is_multisite() ) {
			$this->_helper->set_core( $core );
		}

		add_action( 'wp_login',                 array( $this, 'wp_login' ), 10, 2 );
		add_action( 'login_form_validate_2fa',  array( $this, 'login_form_validate_2fa' ) );
		add_action( 'login_form_backup_2fa',    array( $this, 'backup_2fa' ) );
		add_action( 'show_user_profile',        array( $this, 'user_two_factor_options' ) );
		add_action( 'edit_user_profile',        array( $this, 'user_two_factor_options' ) );
		add_action( 'personal_options_update',  array( $this, 'user_two_factor_options_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'user_two_factor_options_update' ) );

		add_filter( 'itsec_logger_modules', array( $this, 'itsec_logger_modules' ) );
		add_filter( 'itsec_sync_modules', array( $this, 'itsec_sync_modules' ) ); //register sync modules

	}

	/**
	 * Register two-factor for logger.
	 *
	 * Registers the two-factor module with the core logger functionality.
	 *
	 * @since 1.2.0
	 *
	 * @param  array $logger_modules array of logger modules
	 *
	 * @return array array of logger modules
	 */
	public function itsec_logger_modules( $logger_modules ) {

		$logger_modules['two_factor'] = array(
			'type'     => 'two_factor',
			'function' => __( 'Two-Factor Login Failure', 'it-l10n-ithemes-security-pro' ),
		);

		return $logger_modules;

	}

	/**
	 * Register two-factor for Sync
	 *
	 * Reigsters iThemes Sync verbs for the two-factor module.
	 *
	 * @since 1.12.0
	 *
	 * @param  array $sync_modules array of sync modules
	 *
	 * @return array array of sync modules
	 */
	public function itsec_sync_modules( $sync_modules ) {

		$sync_modules['two_factor'] = array(
			'verbs'      => array(
				'itsec-get-two-factor-users'     => 'Ithemes_Sync_Verb_ITSEC_Get_Two_Factor_Users',
				'itsec-override-two-factor-user' => 'Ithemes_Sync_Verb_ITSEC_Override_Two_Factor_User',
			),
			'everything' => 'itsec-get-two-factor-users',
			'path'       => dirname( __FILE__ ),
		);

		return $sync_modules;

	}

	/**
	 * Add user profile fields.
	 *
	 * This executes during the `show_user_profile` & `edit_user_profile` actions.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public function user_two_factor_options( $user ) {
		$enabled_providers = get_user_meta( $user->ID, $this->_enabled_providers_user_meta_key, true );
		if ( empty( $enabled_providers ) ) {
			// Because get_user_meta() has no way of providing a default value.
			$enabled_providers = array();
		}
		$primary_provider = get_user_meta( $user->ID, $this->_provider_user_meta_key, true );
		wp_nonce_field( 'user_two_factor_options', '_nonce_user_two_factor_options', false );
		?>
		<h3><?php esc_html_e( 'Two-Factor Authentication Options', 'it-l10n-ithemes-security-pro' ); ?></h3>
		<p><?php esc_html_e( 'Enabling two-factor authentication greatly increases the security of your user account on this site. With two-factor authentication enabled, after you login with your username and password, you will be asked for an authentication code. This code can come from an app that runs on your mobile device, an email that is sent to you after you login with your username and password, or from a pre-generated list of codes. The settings below allow you to configure which of these authentication code providers are enabled for your user.', 'it-l10n-ithemes-security-pro' ); ?></p>
		<p><?php esc_html_e( "You may enable as many of the listed authentication code providers as you would like, but must choose only one to be your primary provider. The code for your primary provider is asked for by default at login with options to provide a code from one of your other enabled providers. This allows you to use the enabled providers that are not your primary as backups in case you are unable to get a code from your primary provider.", 'it-l10n-ithemes-security-pro' ); ?></p>
		<p><?php esc_html_e( 'A recommended setup is to enable either the Time-Based One-Time Password provider or the Email provider and select it to be the primary. Then enable the Backup Verification Codes provider and generate the verification codes. Store the verification codes in a safe place that is separate from your primary provider, such as printing or writing down the codes. This way even if your phone or email access is lost, you still have the codes to gain access to the site.', 'it-l10n-ithemes-security-pro' ); ?></p>
		<table class="two-factor-methods-table widefat wp-list-table striped">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-primary column-method"><?php esc_html_e( 'Provider', 'it-l10n-ithemes-security-pro' ); ?></th>
					<th scope="col" class="manage-column column-enable"><?php esc_html_e( 'Enabled', 'it-l10n-ithemes-security-pro' ); ?></th>
					<th scope="col" class="manage-column column-make-primary"><?php esc_html_e( 'Primary', 'it-l10n-ithemes-security-pro' ); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
			<?php foreach ( $this->_helper->get_enabled_provider_instances() as $class => $object ) : ?>
				<tr>
					<td class="column-method column-primary" style="width:60%;vertical-align:top;">
						<strong><?php $object->print_label(); ?></strong>
						<?php do_action( 'two-factor-user-options-' . $class, $user ); ?>
						<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
					</td>
					<td class="column-enable" style="width:20%;vertical-align:top;">
						<input type="checkbox" name="<?php echo esc_attr( $this->_enabled_providers_user_meta_key ); ?>[]" id="<?php echo esc_attr( $this->_enabled_providers_user_meta_key . '-' . $class ); ?>" value="<?php echo esc_attr( $class ); ?>" <?php checked( in_array( $class, $enabled_providers ) ); ?> />
						<label for="<?php echo esc_attr( $this->_enabled_providers_user_meta_key . '-' . $class ); ?>">
							<?php esc_html_e( 'Enable', 'it-l10n-ithemes-security-pro' )  ?>
						</label>
					</td>
					<td class="column-make-primary" style="width:20%;vertical-align:top;">
						<input type="radio" name="<?php echo esc_attr( $this->_provider_user_meta_key ); ?>" value="<?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $this->_provider_user_meta_key . '-' . $class ); ?>" <?php checked( $class, $primary_provider ); ?> />
						<label for="<?php echo esc_attr( $this->_provider_user_meta_key . '-' . $class ); ?>">
							<?php esc_html_e( 'Make Primary', 'it-l10n-ithemes-security-pro' )  ?>
						</label>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th scope="col" class="manage-column column-primary column-method"><?php esc_html_e( 'Method', 'it-l10n-ithemes-security-pro' ); ?></th>
					<th scope="col" class="manage-column column-enable"><?php esc_html_e( 'Enabled', 'it-l10n-ithemes-security-pro' ); ?></th>
					<th scope="col" class="manage-column column-make-primary"><?php esc_html_e( 'Primary', 'it-l10n-ithemes-security-pro' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<?php
		/**
		 * Fires after the Two Factor methods table.
		 *
		 * To be used by Two Factor methods to add settings UI.
		 */
		do_action( 'show_user_security_settings', $user );
	}

	/**
	 * Update the user meta value.
	 *
	 * This executes during the `personal_options_update` & `edit_user_profile_update` actions.
	 *
	 * @param int $user_id User ID.
	 */
	public function user_two_factor_options_update( $user_id ) {
		if ( isset( $_POST['_nonce_user_two_factor_options'] ) ) {
			check_admin_referer( 'user_two_factor_options', '_nonce_user_two_factor_options' );
			$providers         = $this->_helper->get_enabled_provider_instances();
			// If there are no providers enabled for the site, then let's not worry about this.
			if ( empty( $providers ) ) {
				return;
			}

			if ( isset( $_POST[ $this->_enabled_providers_user_meta_key ] ) ) {
				$enabled_providers = $_POST[ $this->_enabled_providers_user_meta_key ];
				if ( ! is_array( $enabled_providers ) ) {
					// Make sure enabled providers is an array
					$enabled_providers = array();
				} else {
					// Only site-enabled providers can be enabled for a user
					$enabled_providers = array_intersect( $enabled_providers, array_keys( $providers ) );
				}
			} else {
				// Nothing was selected, set to empty
				$enabled_providers = array();
			}
			update_user_meta( $user_id, $this->_enabled_providers_user_meta_key, $enabled_providers );

			// Whitelist the new values to only the available classes and empty.
			$new_provider = $_POST[ $this->_provider_user_meta_key ];
			if ( empty( $new_provider ) || array_key_exists( $new_provider, $providers ) ) {
				update_user_meta( $user_id, $this->_provider_user_meta_key, $new_provider );
			}
		}
	}

	/**
	 * Get all Two-Factor Auth providers that are enabled for the specified|current user.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return array
	 */
	public function get_enabled_providers_for_user( $user = null ) {
		if ( empty( $user ) || ! is_a( $user, 'WP_User' ) ) {
			$user = wp_get_current_user();
		}

		$providers         = $this->_helper->get_enabled_provider_instances();
		$enabled_providers = get_user_meta( $user->ID, $this->_enabled_providers_user_meta_key, true );
		if ( empty( $enabled_providers ) ) {
			$enabled_providers = array();
		}
		$enabled_providers = array_intersect( $enabled_providers, array_keys( $providers ) );

		return $enabled_providers;
	}

	/**
	 * Get all Two-Factor Auth providers that are both enabled and configured for the specified|current user.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return array
	 */
	public function get_available_providers_for_user( $user = null ) {
		if ( empty( $user ) || ! is_a( $user, 'WP_User' ) ) {
			$user = wp_get_current_user();
		}

		$providers            = $this->_helper->get_enabled_provider_instances();
		$enabled_providers    = $this->get_enabled_providers_for_user( $user );
		$configured_providers = array();

		foreach ( $providers as $classname => $provider ) {
			if ( in_array( $classname, $enabled_providers ) && $provider->is_available_for_user( $user ) ) {
				$configured_providers[ $classname ] = $provider;
			}
		}

		return $configured_providers;
	}

	/**
	 * Gets the Two-Factor Auth provider for the specified|current user.
	 *
	 * @param int $user_id Optional. User ID. Default is 'null'.
	 * @return object|null
	 */
	public function get_primary_provider_for_user( $user_id = null ) {
		if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$providers           = $this->_helper->get_enabled_provider_instances();
		$available_providers = $this->get_available_providers_for_user( get_userdata( $user_id ) );

		// If there's only one available provider, force that to be the primary.
		if ( empty( $available_providers ) ) {
			return null;
		} elseif ( 1 === count( $available_providers ) ) {
			$provider = key( $available_providers );
		} else {
			$provider = get_user_meta( $user_id, $this->_provider_user_meta_key, true );

			// If the provider specified isn't enabled, just grab the first one that is.
			if ( ! isset( $available_providers[ $provider ] ) ) {
				$provider = key( $available_providers );
			}
		}

		/**
		 * Filter the two-factor authentication provider used for this user.
		 *
		 * @param string $provider The provider currently being used.
		 * @param int    $user_id  The user ID.
		 */
		$provider = apply_filters( 'two_factor_primary_provider_for_user', $provider, $user_id );

		if ( isset( $providers[ $provider ] ) ) {
			return $providers[ $provider ];
		}

		return null;
	}

	/**
	 * Quick boolean check for whether a given user is using two-step.
	 *
	 * @param int $user_id Optional. User ID. Default is 'null'.
	 */
	public function is_user_using_two_factor( $user_id = null ) {
		$provider = $this->get_primary_provider_for_user( $user_id );
		return ! empty( $provider );
	}

	/**
	 * Handle the browser-based login.
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public function wp_login( $user_login, $user ) {
		if ( ! $this->is_user_using_two_factor( $user->ID ) ) {
			return;
		}

		wp_clear_auth_cookie();

		$this->show_two_factor_login( $user );
		exit;
	}

	/**
	 * Display the login form.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public function show_two_factor_login( $user ) {
		if ( ! function_exists( 'login_header' ) ) {
			require_once( ABSPATH . WPINC . '/functions.wp-login.php' );
		}

		if ( ! $user ) {
			$user = wp_get_current_user();
		}

		$login_nonce = $this->create_login_nonce( $user->ID );
		if ( ! $login_nonce ) {
			wp_die( esc_html__( 'Could not save login nonce.', 'it-l10n-ithemes-security-pro' ) );
		}

		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : $_SERVER['REQUEST_URI'];

		$this->login_html( $user, $login_nonce['key'], $redirect_to );
	}

	/**
	 * @todo doc
	 */
	public function backup_2fa() {
		if ( ! isset( $_GET['wp-auth-id'], $_GET['wp-auth-nonce'], $_GET['provider'] ) ) {
			return;
		}

		$user = get_userdata( $_GET['wp-auth-id'] );
		if ( ! $user ) {
			return;
		}

		$nonce = $_GET['wp-auth-nonce'];
		if ( true !== $this->verify_login_nonce( $user->ID, $nonce ) ) {
			wp_safe_redirect( get_bloginfo( 'url' ) );
			exit;
		}

		$providers = $this->get_available_providers_for_user( $user );
		if ( isset( $providers[ $_GET['provider'] ] ) ) {
			$provider = $providers[ $_GET['provider'] ];
		} else {
			wp_die( esc_html__( 'Cheatin&#8217; uh?', 'it-l10n-ithemes-security-pro' ), 403 );
		}

		$this->login_html( $user, $_GET['wp-auth-nonce'], $_GET['redirect_to'], '', $provider );

		exit;
	}

	/**
	 * Generates the html form for the second step of the authentication process.
	 *
	 * @param WP_User       $user WP_User object of the logged-in user.
	 * @param string        $login_nonce A string nonce stored in usermeta.
	 * @param string        $redirect_to The URL to which the user would like to be redirected.
	 * @param string        $error_msg Optional. Login error message.
	 * @param string|object $provider An override to the provider.
	 */
	public function login_html( $user, $login_nonce, $redirect_to, $error_msg = '', $provider = null ) {
		if ( empty( $provider ) ) {
			$provider = $this->get_primary_provider_for_user( $user->ID );
		} elseif ( is_string( $provider ) && method_exists( $provider, 'get_instance' ) ) {
			$provider = call_user_func( array( $provider, 'get_instance' ) );
		}

		$provider_class = get_class( $provider );

		$available_providers = $this->get_available_providers_for_user( $user );
		$backup_providers = array_diff_key( $available_providers, array( $provider_class => null ) );
		$interim_login = isset($_REQUEST['interim-login']);

		$rememberme = 0;
		if ( isset( $_REQUEST['rememberme'] ) && $_REQUEST['rememberme'] ) {
			$rememberme = 1;
		}

		login_header();

		if ( ! empty( $error_msg ) ) {
			echo '<div id="login_error"><strong>' . esc_html( $error_msg ) . '</strong><br /></div>';
		}
		?>

		<form name="validate_2fa_form" id="loginform" action="<?php echo esc_url( site_url( 'wp-login.php?action=validate_2fa', 'login_post' ) ); ?>" method="post" autocomplete="off">
				<input type="hidden" name="provider" id="provider" value="<?php echo esc_attr( $provider_class ); ?>" />
				<input type="hidden" name="wp-auth-id" id="wp-auth-id" value="<?php echo esc_attr( $user->ID ); ?>" />
				<input type="hidden" name="wp-auth-nonce" id="wp-auth-nonce" value="<?php echo esc_attr( $login_nonce ); ?>" />
				<?php	if ( $interim_login ) { ?>
					<input type="hidden" name="interim-login" value="1" />
				<?php	} else { ?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />
				<?php 	} ?>
				<input type="hidden" name="rememberme" id="rememberme" value="<?php echo esc_attr( $rememberme ); ?>" />

				<?php $provider->authentication_page( $user ); ?>

				<?php if ( $backup_providers ) : ?>
				<div class="itsec-backup-methods" style="clear:both;margin-top:4em;padding-top:2em;border-top:1px solid #ddd;">
					<p><?php esc_html_e( 'Or, use a backup method:', 'it-l10n-ithemes-security-pro' ); ?></p>
					<ul style="margin-left:1em;">
						<?php foreach ( $backup_providers as $backup_classname => $backup_provider ) : ?>
							<li><a href="<?php echo esc_url( add_query_arg( urlencode_deep( array(
											'action'        => 'backup_2fa',
											'provider'      => $backup_classname,
											'wp-auth-id'    => $user->ID,
											'wp-auth-nonce' => $login_nonce,
											'redirect_to'   => $redirect_to,
											'rememberme'    => $rememberme,
										) ) ) ); ?>"><?php $backup_provider->print_label(); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>
		</form>

		<p id="backtoblog">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php esc_attr_e( 'Are you lost?', 'it-l10n-ithemes-security-pro' ); ?>"><?php echo esc_html( sprintf( __( '&larr; Back to %s', 'it-l10n-ithemes-security-pro' ), get_bloginfo( 'title', 'display' ) ) ); ?></a>
		</p>

		</body>
		</html>
		<?php
	}

	/**
	 * Create the login nonce.
	 *
	 * @param int $user_id User ID.
	 */
	public function create_login_nonce( $user_id ) {
		$login_nonce               = array();
		$login_nonce['key']        = wp_hash( $user_id . mt_rand() . microtime(), 'nonce' );
		$login_nonce['expiration'] = time() + HOUR_IN_SECONDS;

		if ( ! update_user_meta( $user_id, $this->_user_meta_nonce_key, $login_nonce ) ) {
			return false;
		}

		return $login_nonce;
	}

	/**
	 * Delete the login nonce.
	 *
	 * @param int $user_id User ID.
	 */
	public function delete_login_nonce( $user_id ) {
		return delete_user_meta( $user_id, $this->_user_meta_nonce_key );
	}

	/**
	 * Verify the login nonce.
	 *
	 * @param int    $user_id User ID.
	 * @param string $nonce Login nonce.
	 */
	public function verify_login_nonce( $user_id, $nonce ) {
		$login_nonce = get_user_meta( $user_id, $this->_user_meta_nonce_key, true );
		if ( ! $login_nonce ) {
			return false;
		}

		if ( $nonce !== $login_nonce['key'] || time() > $login_nonce['expiration'] ) {
			$this->delete_login_nonce( $user_id );
			return false;
		}

		return true;
	}

	/**
	 * Login form validation.
	 */
	public function login_form_validate_2fa() {
		if ( ! isset( $_POST['wp-auth-id'], $_POST['wp-auth-nonce'] ) ) {
			return;
		}

		$user = get_userdata( $_POST['wp-auth-id'] );
		if ( ! $user ) {
			return;
		}

		$nonce = $_POST['wp-auth-nonce'];
		if ( true !== $this->verify_login_nonce( $user->ID, $nonce ) ) {
			wp_safe_redirect( get_bloginfo( 'url' ) );
			exit;
		}

		global $interim_login;

		$interim_login = isset($_REQUEST['interim-login']);

		/**
		 * iThemes Sync override
		 */
		$sync_override    = intval( get_user_option( 'itsec_two_factor_override', $user->ID ) ) === 1 ? true : false;
		$override_expires = intval( get_user_option( 'itsec_two_factor_override_expires', $user->ID ) );

		if ( ! $sync_override || current_time( 'timestamp' ) > $override_expires ) {
			if ( isset( $_POST['provider'] ) ) {
				$providers = $this->get_available_providers_for_user( $user );
				if ( isset( $providers[ $_POST['provider'] ] ) ) {
					$provider = $providers[ $_POST['provider'] ];
				} else {
					wp_die( esc_html__( 'Cheatin&#8217; uh?', 'it-l10n-ithemes-security-pro' ), 403 );
				}
			} else {
				$provider = $this->get_primary_provider_for_user( $user->ID );
			}

			if ( true !== $provider->validate_authentication( $user ) ) {
				do_action( 'wp_login_failed', $user->user_login );

				$login_nonce = $this->create_login_nonce( $user->ID );
				if ( ! $login_nonce ) {
					return;
				}

				if ( empty( $_REQUEST['redirect_to'] ) ) {
					$_REQUEST['redirect_to'] = '';
				}
				$this->login_html( $user, $login_nonce['key'], $_REQUEST['redirect_to'], esc_html__( 'ERROR: Invalid verification code.', 'it-l10n-ithemes-security-pro' ) );
				exit;
			}
		}

		$this->delete_login_nonce( $user->ID );

		$rememberme = false;
		if ( isset( $_REQUEST['rememberme'] ) && $_REQUEST['rememberme'] ) {
			$rememberme = true;
		}

		wp_set_auth_cookie( $user->ID, $rememberme );

		if ( $interim_login ) {
			$customize_login = isset( $_REQUEST['customize-login'] );
			if ( $customize_login ) {
				wp_enqueue_script( 'customize-base' );
			}
			$message = '<p class="message">' . __('You have logged in successfully.') . '</p>';
			$interim_login = 'success';
			login_header( '', $message ); ?>
			</div>
			<?php
			/** This action is documented in wp-login.php */
			do_action( 'login_footer' ); ?>
			<?php if ( $customize_login ) : ?>
				<script type="text/javascript">setTimeout( function(){ new wp.customize.Messenger({ url: '<?php echo wp_customize_url(); ?>', channel: 'login' }).send('login') }, 1000 );</script>
			<?php endif; ?>
			</body></html>
<?php		exit;
		}

		$redirect_to = apply_filters( 'login_redirect', $_REQUEST['redirect_to'], $_REQUEST['redirect_to'], $user );
		wp_safe_redirect( $redirect_to );

		exit;
	}
}
