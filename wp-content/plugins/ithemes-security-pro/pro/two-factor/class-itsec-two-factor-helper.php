<?php

/**
 * Two-Factor Helper Class
 *
 * Code that's needed for both front end and admin.
 *
 * @package iThemes_Security
 */
class ITSEC_Two_Factor_Helper {

	/**
	 * The module's saved options
	 *
	 * @access private
	 * @var array
	 */
	private $_settings;

	/**
	 * The name of the module's saved options setting
	 *
	 * @access private
	 * @var string
	 */
	private $_setting_name = 'itsec_two_factor';

	/**
	 * Array of two-factor providers
	 *
	 * @access private
	 * @var array where key is class name and value is file
	 */
	private $_providers;

	/**
	 * Array of instances of two-factor providers
	 *
	 * @access private
	 * @var array where key is class name and value is the instance of that class
	 */
	private $_provider_instances;

	/**
	 * @var ITSEC_Two_Factor_Helper - Static property to hold our singleton instance
	 */
	static $instance = false;

	/**
	 * private construct to enforce singleton
	 */
	private function __construct() {
		$this->update_settings();

		/**
		 * Include the base provider class here, so that other plugins can also extend it.
		 */
		require_once('providers/class.two-factor-provider.php');

		/**
		 * Include the application passwords system.
		 */
		require_once('class.application-passwords.php');
		Application_Passwords::add_hooks();

		if ( is_admin() ) {
			// Always instantiate enabled providers in admin for use in settings, etc
			add_action( 'init', array( $this, 'get_enabled_provider_instances' ) );
		} else {
			add_action( 'init', array( $this, 'get_all_providers' ) );
		}

		// Sanitize options
		add_filter( "sanitize_option_{$this->_setting_name}", array( $this, 'sanitize_module_input' ) );

		// Reload options after they are saved
		add_action( "add_option_{$this->_setting_name}",         array( $this, 'update_settings' ), null, 0 );
		add_action( "update_option_{$this->_setting_name}",      array( $this, 'update_settings' ), null, 0 );
		add_action( "update_site_option_{$this->_setting_name}", array( $this, 'update_settings' ), null, 0 );

	}

	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function get_settings() {
		return $this->_settings;
	}

	public function update_settings() {
		$this->_settings = $this->_fill_settings( get_site_option( $this->_setting_name, array(), false ) );
		return $this->_settings;
	}

	private function _fill_settings( $settings ) {
		if ( empty( $settings ) || ! is_array( $settings['enabled-providers'] ) ) {
			if ( empty( $settings ) ) {
				$settings = array(
					'enabled' => false,
					'enabled-providers' => array(),
				);
			} else {
				$settings['enabled-providers'] = array();
			}
			update_site_option( $this->_setting_name, $settings );
		}
		return $settings;
	}

	/**
	 * Get a list of providers
	 *
	 * @return array where key is provider class name and value is the provider file
	 */
	public function get_all_providers( $refresh = false ) {
		if ( ! empty( $this->_providers ) && ! $refresh ) {
			return $this->_providers;
		}

		$this->_providers = array(
			'Two_Factor_Totp'         => 'providers/class.two-factor-totp.php',
			'Two_Factor_Email'        => 'providers/class.two-factor-email.php',
			'Two_Factor_Backup_Codes' => 'providers/class.two-factor-backup-codes.php',
		);

		/**
		 * Filter the supplied providers.
		 *
		 * This lets third-parties either remove providers (such as Email), or
		 * add their own providers (such as text message or Clef).
		 *
		 * @param array $providers A key-value array where the key is the class name, and
		 *                         the value is the path to the file containing the class.
		 */
		$this->_providers = apply_filters( 'two_factor_providers', $this->_providers );

		return $this->_providers;
	}

	/**
	 * Get a list of enabled providers
	 *
	 * @return array where key is provider class name and value is the provider file
	 */
	public function get_enabled_providers( $refresh = false ) {
		if ( ! empty( $this->_enabled_providers ) && ! $refresh ) {
			return $this->_enabled_providers;
		}

		if ( $refresh ) {
			$this->update_settings();
		}

		$this->_enabled_providers = array_intersect_key( $this->get_all_providers( $refresh ), array_fill_keys( $this->_settings['enabled-providers'], '' ) );

		/**
		 * Filter the supplied providers.
		 *
		 * This lets third-parties either remove providers (such as Email), or
		 * add their own providers (such as text message or Clef).
		 *
		 * @param array $providers A key-value array where the key is the class name, and
		 *                         the value is the path to the file containing the class.
		 */
		$this->_enabled_providers = apply_filters( 'enabled_two_factor_providers', $this->_enabled_providers );

		return $this->_enabled_providers;
	}

	public function get_all_provider_instances( $refresh = false ) {
		if ( ! empty( $this->_provider_instances ) && ! $refresh ) {
			return $this->_provider_instances;
		}

		$this->_provider_instances = $this->_instantiate_providers( $this->get_all_providers( $refresh ) );

		return $this->_provider_instances;
	}

	public function get_enabled_provider_instances( $refresh = false ) {
		if ( ! empty( $this->_enabled_provider_instances ) && ! $refresh ) {
			return $this->_enabled_provider_instances;
		}

		$this->_enabled_provider_instances = $this->_instantiate_providers( $this->get_enabled_providers( $refresh ) );

		return $this->_enabled_provider_instances;
	}

	private function _instantiate_providers( $providers ) {
		$provider_instances = array();

		foreach ( $providers as $class => $path ) {
			include_once( $path );

			/**
			 * Confirm that it's been successfully included before instantiating.
			 */
			if ( class_exists( $class ) ) {
				$provider_instances[ $class ] = call_user_func( array( $class, 'get_instance' ) );
			}
		}

		return $provider_instances;
	}

	public function set_core( $core ) {
		$this->core = $core;
	}

	/**
	 * Sanitize and validate input
	 *
	 * Sanitizes and validates module options saved on the settings page or via multisite.
	 *
	 * @since 1.2.0
	 *
	 * @param  Array $input array of input fields
	 *
	 * @return Array         Sanitized array
	 */
	public function sanitize_module_input( $input ) {
		// If nothing is passed in, then no providers are enabled.
		if ( ! is_array( $input ) || ! array( $input['enabled-providers'] ) ) {
			return array(
				'enabled' => false, // Required due to the way the modules are loaded
				'enabled-providers' => array()
			);
		}

		// Only allow valid providers
		$input['enabled-providers'] = array_intersect( array_keys( $this->get_all_providers() ), $input['enabled-providers'] );

		$input['enabled'] = ! empty( $input['enabled-providers'] );

		if ( is_multisite() ) {

			$this->core->show_network_admin_notice( false );

			$this->_settings = $input;

		}

		return $input;

	}

}
