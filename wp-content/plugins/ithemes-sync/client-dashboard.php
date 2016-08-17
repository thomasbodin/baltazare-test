<?php

class Ithemes_Sync_Client_Dashboard {

	/**
	 * @var array List of item IDs to ignore from our list and never send to Sync
	 */
	private $_admin_bar_ignored_items = array(
		'menu-toggle', //This is for admin responsiveness
	);

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		// If this user is supposed to see the client dashboard
		if ( get_user_meta( get_current_user_id(), 'ithemes-sync-client-dashboard', true ) ) {
			if ( ! get_user_meta( get_current_user_id(), 'ithemes-sync-client-dashboard-no-css', true ) ) {
				// Enqueue our admin scripts and styles
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_scripts' ) );
			}

			if ( get_user_meta( get_current_user_id(), 'ithemes-sync-suppress-admin-notices', true ) ) {
				add_action( 'network_admin_notices', array( $this, 'admin_notices_start' ), 1 );
				add_action( 'user_admin_notices',    array( $this, 'admin_notices_start' ), 1 );
				add_action( 'admin_notices',         array( $this, 'admin_notices_start' ), 1 );
				add_action( 'all_admin_notices',     array( $this, 'admin_notices_start' ), 1 );
				add_action( 'network_admin_notices', array( $this, 'admin_notices_end' ),   999 );
				add_action( 'user_admin_notices',    array( $this, 'admin_notices_end' ),   999 );
				add_action( 'admin_notices',         array( $this, 'admin_notices_end' ),   999 );
				add_action( 'all_admin_notices',     array( $this, 'admin_notices_end' ),   999 );
			}

			// Filter menu items
			add_action( 'admin_menu', array( $this, 'filter_admin_menu' ), 999999 ); //We want to be last!

			// Filter admin bar items
			add_action( 'wp_before_admin_bar_render', array( $this, 'filter_admin_bar_menu' ), 1002 );

			// Filter dashboard widgets - screen_layout_columns fires late but before dashboard widgets are run or referenced in screen options
			add_action( 'screen_layout_columns', array( $this, 'filter_dashboard_widgets' ) );

			// Filter welcome panel
			add_filter( 'show_welcome_panel', array( $this, 'show_welcome_panel' ) );
		}

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 999999 ); //We want to be last!
		add_action( 'wp_before_admin_bar_render', array( $this, 'admin_bar_menu' ), 999 );

		add_action( 'switch_theme', array( $this, 'clear_cache' ) );
		add_action( 'activate_plugin', array( $this, 'clear_cache' ) );
		add_action( 'deactivate_plugin', array( $this, 'clear_cache' ) );
		add_action( 'update_option_active_plugins', array( $this, 'clear_cache' ) );
		add_action( 'add_option_active_plugins', array( $this, 'clear_cache' ) );

		add_action( 'update_site_option_active_sitewide_plugins', array( $this, 'clear_cache_network' ) );
		add_action( 'add_site_option_active_sitewide_plugins', array( $this, 'clear_cache_network' ) );

		/**
		 * Handle Dashboard Widgets
		 */
		add_action( 'admin_footer-index.php', array( $this, 'dashboard_admin_footer' ) );
	}

	public function enqueue_dashboard_scripts() {
		// Enqueue an additional CSS file on the admin dashboard
		wp_enqueue_style( 'ithemes-sync-client-dashboard', plugins_url( 'css/client-dashboard.css', __FILE__ ), array( 'wp-admin' ), '20140801' );
	}

	/**
	 * @param int User Id
	 *
	 * @return array List of menu_slugs to allow in menu. index is top level item, value is array of submenu items (or true for 'all')
	 */
	public function get_allowed_admin_menu_items( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$menu_whitelist = get_user_meta( $user_id, 'ithemes-sync-admin_menu-whitelist', true );

		// If the user's admin_menu whitelist doesn't exist, set it.
		if ( ! is_array( $menu_whitelist ) ) {
			$menu_whitelist = array(
				'index.php' => array(),
				'edit.php' => true,
				'edit.php?post_type=page' => true,
				'edit-comments.php' => array(),
			);
			update_user_meta( $user_id, 'ithemes-sync-admin_menu-whitelist', $menu_whitelist );
		}

		return apply_filters( 'ithemes-sync-menu-items-whitelist', $menu_whitelist );
	}

	public function filter_admin_menu() {
		/**
		 * @var array An array of menu elements. Each element is an array containing:
		 *     [0] - Label
		 *     [1] = capability
		 *     [2] = menu_slug
		 *     [3] = page_title
		 *     [4] = classes
		 *     [5] = hookname
		 *     [6] = icon_url
		 */
		global $menu;

		/**
		 * @var array An array of arrays of submenu elements, indexed by the menu_slug of the main menu item. Each submenu element is an array containing:
		 *     [0] - Label
		 *     [1] = capability
		 *     [2] = menu_slug
		 */
		global $submenu;

		/**
		 * @var array List of menu_slugs to allow in menu. index is top level item, value is array of submenu items (or true for 'all')
		 */
		$menu_whitelist = $this->get_allowed_admin_menu_items();

		foreach ( $menu as $pos => $menu_item ) {
			if ( 'wp-menu-separator' == $menu_item[4] ) {
				continue;
			}
			if ( ! in_array( $menu_item[2], array_keys( $menu_whitelist ) ) ) {
				if ( isset( $submenu[ $menu_item[2] ] ) ) {
					unset( $submenu[ $menu_item[2] ] );
				}
				unset( $menu[ $pos ] );
			} elseif ( true !== $menu_whitelist[ $menu_item[2] ] && isset( $submenu[ $menu_item[2] ] ) ) {
				foreach ( $submenu[ $menu_item[2] ] as $subpos => $submenu_item ) {
					if ( ! in_array( $submenu_item[2], $menu_whitelist[ $menu_item[2] ] ) ) {
						unset( $submenu[ $menu_item[2] ][ $subpos ] );
					}
				}
			}
		}
	}

	public function admin_menu() {
		$admin_menu = get_option( 'ithemes-sync-admin_menu' );
		if ( false === $admin_menu ) {
			/**
			 * @var array An array of menu elements. Each element is an array containing:
			 *     [0] - Label
			 *     [1] = capability
			 *     [2] = menu_slug
			 *     [3] = page_title
			 *     [4] = classes
			 *     [5] = hookname
			 *     [6] = icon_url
			 */
			global $menu;

			/**
			 * @var array An array of arrays of submenu elements, indexed by the menu_slug of the main menu item. Each submenu element is an array containing:
			 *     [0] - Label
			 *     [1] = capability
			 *     [2] = menu_slug
			 */
			global $submenu;

			$admin_menu = array();
			foreach ( $menu as $menu_item ) {
				$admin_menu[$menu_item[2]] = array();
				$admin_menu[$menu_item[2]]['label'] = $menu_item[0];

				if ( ! empty( $submenu[$menu_item[2]] ) ) {
					$admin_menu[$menu_item[2]]['children'] = array();
					foreach ( $submenu[$menu_item[2]] as $submenu_item ) {
						$admin_menu[$menu_item[2]]['children'][$submenu_item[2]] = array( 'label' => $submenu_item[0] );
					}
				}
			}

			update_option( 'ithemes-sync-admin_menu', $admin_menu );
		}
	}

	/**
	 * @param int User Id
	 *
	 * @return array List of dashboard widget ids to allow
	 */
	public function get_allowed_admin_bar_items( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$whitelist = get_user_meta( $user_id, 'ithemes-sync-admin-bar-item-whitelist-' . get_current_blog_id(), true );

		// If the user's admin_menu whitelist doesn't exist, set it.
		if ( ! is_array( $whitelist ) ) {
			$whitelist = array(
				'wp-logo',
					'about',
					'wp-logo-external',
						'wporg',
						'documentation',
						'support-forums',
						'feedback',
				'site-name',
				'comments',
				'new-content',
					'new-post',
					'new-page',
				'top-secondary',
					'my-account',
						'user-actions',
							'user-info',
							'edit-profile',
							'logout',
			);
			update_user_meta( $user_id, 'ithemes-sync-admin-bar-item-whitelist-' . get_current_blog_id(), $whitelist );
		}

		$whitelist = array_merge( $whitelist, $this->_admin_bar_ignored_items );

		return apply_filters( 'ithemes-sync-admin-bar-item-whitelist-' . get_current_blog_id(), $whitelist );
	}

	public function filter_admin_bar_menu() {
		global $wp_admin_bar;
		$whitelist = $this->get_allowed_admin_bar_items();

		foreach ( $wp_admin_bar->get_nodes() as $node ) {
			if ( ! in_array( $node->id, $whitelist ) ) {
				$wp_admin_bar->remove_node( $node->id );
			}
		}
	}

	public function admin_bar_menu() {
		global $wp_admin_bar;
		$meta_key = 'ithemes-sync-admin-bar-items-' . get_current_blog_id();

		if ( is_array( get_user_meta( get_current_user_id(), $meta_key, true ) ) ) {
			return true;
		}

		$admin_bar = array();
		$admin_bar_nodes = $wp_admin_bar->get_nodes();

		$last_count = null;
		$iterations = 0;
		while ( ! empty( $admin_bar_nodes ) && ++$iterations <= 100 ) {
			foreach ( $admin_bar_nodes as $key => $current_node ) {
				if ( in_array( $current_node->id, $this->_admin_bar_ignored_items ) ) {
					// Don't send ignored items
					unset( $admin_bar_nodes[ $key ] );
				} elseif ( false == $current_node->parent ) {
					// Process parents
					$admin_bar[ $current_node->id ] = $this->_create_admin_bar_node( $current_node );
					unset( $admin_bar_nodes[ $key ] );
				} elseif ( $this->_place_child( $admin_bar, $this->_create_admin_bar_node( $current_node ) ) ) {
					// If we placed a child node successfully, remove it
					unset( $admin_bar_nodes[ $key ] );
				}
			}

			/**
			 * If we haven't parsed any elements out since the last time through
			 * the loop, break out to avoid an infinite loop. This only happens
			 * when there are mal-formed admin bar nodes (such as nodes with a
			 * non-existent parent)
			 */
			if ( $last_count == count( $admin_bar_nodes ) ) {
				break;
			}
			$last_count = count( $admin_bar_nodes );
		}

		update_user_meta( get_current_user_id(), $meta_key, $admin_bar );
	}

	private function _place_child( &$all_nodes, $child_node ) {
		foreach ( $all_nodes as $id => &$node ) {
			if ( $id == $child_node->parent ) {
				$node->children[ $child_node->id ] = $child_node;
				return true;
			} elseif ( ! empty( $node->children ) && $this->_place_child( $node->children, $child_node ) ) {
				return true;
			}
		}
		return false;
	}

	private function _create_admin_bar_node( $node_info ) {
		$node = new stdClass();
		$node->id = $node_info->id;
		$node->title = $node_info->title;
		$node->parent = $node_info->parent;
		$node->type = $node_info->group? 'group':'item';
		$node->children = array();
		return $node;
	}

	/**
	 * Clear the cache of our admin_menu and dashboard metaboxes by deleting the option
	 */
	public function clear_cache() {
		delete_option( 'ithemes-sync-admin_menu' );
		delete_option( 'ithemes-sync-dashboard-metaboxes' );

		$users = get_users( array( 'blog_id' => get_current_blog_id(), 'fields' => array( 'ID' ) ) );
		$meta_key = 'ithemes-sync-admin-bar-items-' . get_current_blog_id();
		foreach ( $users as $user ) {
			delete_user_meta( $user->ID, $meta_key );
		}
	}

	/**
	 * Clear the cache of our admin_menu and dashboard metaboxes from the whole
	 * network (site...ugh) by deleting the option from each site (blog...ugh again)
	 */
	public function clear_cache_network() {
		// Get the current site and only clear options from blogs in this site
		$site = get_current_site(); // Site = Network? Ugh.
		if ( $site && isset( $site->id ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "SELECT `blog_id` FROM $wpdb->blogs WHERE `site_id`=%d", absint( $site->id ) );
			$users = get_users( array( 'blog_id' => 0, 'fields' => array( 'ID' ) ) );
			foreach( $wpdb->get_col( $query ) as $blog_id ) {
				delete_blog_option( $blog_id, 'ithemes-sync-admin_menu' );
				delete_blog_option( $blog_id, 'ithemes-sync-dashboard-metaboxes' );

				$meta_key = 'ithemes-sync-admin-bar-items-' . $blog_id;
				foreach ( $users as $user ) {
					delete_user_meta( $user->ID, $meta_key );
				}
			}
		}
	}

	public function dashboard_admin_footer() {
		$meta_box_list = get_option( 'ithemes-sync-dashboard-metaboxes' );
		if ( false === $meta_box_list ) {
			global $wp_meta_boxes;
			$screen = get_current_screen();
			$meta_box_list = array();
			foreach ( $wp_meta_boxes[$screen->id] as $box_position ) {
				foreach ( $box_position as $box_set ) {
					foreach ( $box_set as $box ) {
						$meta_box_list[ $box['id'] ] = $box['title'];
					}
				}
			}
			$meta_box_list['show_welcome_panel'] = _x( 'Welcome', 'Welcome panel' );
			update_option( 'ithemes-sync-dashboard-metaboxes', $meta_box_list );
		}
	}

	/**
	 * @param int User Id
	 *
	 * @return array List of dashboard widget ids to allow
	 */
	public function get_allowed_dashboard_widgets( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$whitelist = get_user_meta( $user_id, 'ithemes-sync-dashboard-widget-whitelist', true );

		// If the user's admin_menu whitelist doesn't exist, set it.
		if ( ! is_array( $whitelist ) ) {
			$whitelist = array(
				'dashboard_right_now',
				'dashboard_quick_press',
				'show_welcome_panel',
			);
			update_user_meta( $user_id, 'ithemes-sync-dashboard-widget-whitelist', $whitelist );
		}

		return apply_filters( 'ithemes-sync-dashboard-widget-whitelist', $whitelist );
	}

	public function filter_dashboard_widgets() {
		$screen = get_current_screen();
		if ( $screen && 'dashboard' == $screen->id ) {
			global $wp_meta_boxes;
			$whitelist = $this->get_allowed_dashboard_widgets();

			foreach ( $wp_meta_boxes[$screen->id] as $box_position => $box_position_boxes ) {
				foreach ( $box_position_boxes as $priority => $box_set ) {
					foreach ( $box_set as $index => $box ) {
						if ( ! in_array( $box['id'], $whitelist ) ) {
							unset( $wp_meta_boxes[$screen->id][$box_position][$priority][$index] );
						}
					}
				}
			}
		}
	}

	public function show_welcome_panel( $show ) {
		if ( ! in_array( 'show_welcome_panel', $this->get_allowed_dashboard_widgets() ) ) {
			$show = 0;
		}
		return $show;
	}

	public function admin_notices_start() {
		ob_start();
	}

	public function admin_notices_end() {
		ob_end_clean();
	}
}
new Ithemes_Sync_Client_Dashboard();
