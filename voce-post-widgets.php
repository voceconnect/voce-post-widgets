<?php
/*
  Plugin Name: Voce Post Widgets
  Plugin URI: https://github.com/voceconnect/voce-post-widgets
  Description: A better interface for managing your widgets.
  Author: johnciacia, markparolisi, voceplatforms
  Version: 0.5
  Author URI: http://vocecommunications.com
  License: GPLv2 or later
  License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( !class_exists( 'Voce_Post_Widgets' ) ) {

	/**
	 * 
	 * @class Voce_Post_Widgets 
	 */
	class Voce_Post_Widgets {

		const WIDGET_ID_PREFIX = "_";

		/**
		 * Setup the plugin
		 * 
		 * @method setup
		 * @global String $pagenow
		 * @return Void 
		 */
		public static function setup() {
			global $pagenow;
			require_once( ABSPATH . '/wp-admin/includes/widgets.php' );
			add_action( 'init', array( __CLASS__, 'init' ) );
			add_action( 'wp_ajax_get-active-widgets', array( __CLASS__, 'ajax_get_active_widgets' ) );
			add_action( 'wp_ajax_register-sidebar', array( __CLASS__, 'ajax_register_sidebar' ) );

			if ( 'post.php' != $pagenow ) {
				return;
			}
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		}

		/**
		 * This filter is called by the dynamic_sidebar function. Switch the
		 * sidebar that is being called with the Post Widgets sidebar that is 
		 * mapped to.
		 * 
		 * @method sidebars_widget
		 * @global Object $post
		 * @param Array $sidebars_widgets
		 * @return Array new sidebar widgets
		 */
		public static function sidebars_widgets( $sidebars_widgets ) {
			global $post;
			if ( $post == NULL ) {
				return $sidebars_widgets;
			}
			$sidebars = get_option( 'page_widgets', array( ) );
			if ( $sidebars == "" ) {
				return $sidebars_widgets;
			}
			$widgets = $sidebars_widgets;

			foreach ($sidebars as $sidebar => $attrs) {
				if ( strpos( $sidebar, self::WIDGET_ID_PREFIX . $post->post_name ) === 0 ) {
					$widgets[$attrs['original_sidebar']] = $sidebars_widgets[$sidebar];
				}
			}

			return $widgets;
		}

		/**
		 * 
		 * @method init
		 * @return Void 
		 */
		public static function init() {
			// This filter must be called after the global post variable has been set.
			if ( !is_admin() )
				add_filter( 'sidebars_widgets', array( __CLASS__, 'sidebars_widgets' ) );

			$sidebars = get_option( 'page_widgets', array( '' ) );
			if ( $sidebars == "" )
				return;

			$args = array(
				'description' => 'Widgets in this area will be shown in the sidebar on the %1 page.',
				'before_widget' => '<li id="%1$s" class="widget %2$s">',
				'after_widget' => '</li>',
				'before_title' => '<h2 class="widgettitle">',
				'after_title' => '</h2>'
			);

			$args = apply_filters( 'post_widgets_default_sidebar_args', $args );
			foreach ($sidebars as $sidebar => $attrs) {
				if ( !is_array( $attrs ) || empty( $attrs ) ) {
					return;
				}
				$args = apply_filters( 'post_widgets_default_sidebar_args-' . $attrs['original_sidebar'], $args );
				$args = apply_filters( 'post_widgets_default_sidebar_args-' . $attrs['post_name'], $args );
				$args = apply_filters( 'post_widgets_default_sidebar_args-' . $attrs['original_sidebar'] . '-' . $attrs['post_name'], $args );


				register_sidebar( array(
					'name' => $attrs['post_name'] . ' [' . $attrs['original_sidebar'] . ']',
					'id' => $sidebar,
					'description' => __( str_replace( '%1', $attrs['post_name'], $args['description'] ) ),
					'before_title' => $args['before_title'],
					'after_title' => $args['after_title'],
					'before_widget' => $args['before_widget'],
					'after_widget' => $args['after_widget']
				) );
			}
		}

		/**
		 * @method plugins_url
		 * @param type $relative_path
		 * @param type $plugin_path
		 * @return string 
		 */
		public static function plugins_url( $relative_path, $plugin_path ) {
			$template_dir = get_template_directory();

			foreach (array( 'template_dir', 'plugin_path' ) as $var) {
				$$var = str_replace( '\\', '/', $$var ); // sanitize for Win32 installs
				$$var = preg_replace( '|/+|', '/', $$var );
			}
			if ( 0 === strpos( $plugin_path, $template_dir ) ) {
				$url = get_template_directory_uri();
				$folder = str_replace( $template_dir, '', dirname( $plugin_path ) );
				if ( '.' != $folder ) {
					$url .= '/' . ltrim( $folder, '/' );
				}
				if ( !empty( $relative_path ) && is_string( $relative_path ) && strpos( $relative_path, '..' ) === false ) {
					$url .= '/' . ltrim( $relative_path, '/' );
				}
				return $url;
			} else {
				return plugins_url( $relative_path, $plugin_path );
			}
		}

		/**
		 * Load plugin Javascript
		 * 
		 * @method admin_enqueue_scripts
		 * @global Object $post
		 * @return Void 
		 */
		public static function admin_enqueue_scripts() {
			global $post;

			wp_enqueue_style( 'widgets-admin-defaultb', get_admin_url() . '/css/widgets.css' );
			wp_enqueue_style( 'widgets-admin', self::plugins_url( 'css/voce-post-widgets.css', __FILE__ ) );
			wp_enqueue_script( 'widgets-admin', self::plugins_url( 'js/voce-post-widgets.js', __FILE__ ), array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), false, true );

			// Get all of the sidebars and widgets. Used by the widgets-order AJAX call
			$sidebars_widgets = get_option( 'sidebars_widgets', array( ) );
			if ( $sidebars_widgets == "" )
				return;

			foreach ($sidebars_widgets as $key => &$sidebar) {

				// WordPress adds an 'array_version' key for internal use.
				if ( 'array_version' === $key || count( $sidebar ) == 0 )
					continue;
				array_walk( $sidebar, create_function( '&$v', '$v = "widget-_".$v;' ) );
			}

			$args = array(
				'post_name' => $post->post_name,
				'sidebars_widgets' => json_encode( $sidebars_widgets )
			);
			wp_localize_script( 'widgets-admin', 'widgetsAdmin', $args );
		}

		/**
		 * Register a new metabox for every post type in customizable array
		 * 
		 * @method add_meta_boxes
		 * @return Void 
		 */
		public static function add_meta_boxes() {
			$post_types = apply_filters( 'voce_post_widgets_post_types', array( 'page' ) );

			foreach ($post_types as $post_type) {
				add_meta_box( 'sidebar_admin', 'Sidebar Admin', array( __CLASS__, 'sidebar_admin_metabox' ), $post_type, 'advanced', 'high' );
			}
		}

		/**
		 * Generate HTML for meta box
		 * 
		 * @method sidebar_admin_metabox
		 * @global Object $post 
		 * @return Void
		 */
		public static function sidebar_admin_metabox() {
			global $post;
			?>

			<div id="widget-list" class="column-1">
				<strong><?php _e( 'Available Widgets' ); ?></strong>
				<p class="description"><?php _e( 'Drag widgets from here into the center to activate them.' ); ?></p>
				<?php wp_list_widgets(); ?>
			</div>

			<div class="column-2">
				<strong><?php _e( 'Active Widgets' ); ?></strong>
				<div class="sidebar widget-droppable widget-list" id="<?php echo $post->post_name; ?>_0">
					<?php self::get_active_widgets( self::get_sidebar_id( $post->post_name, 0 ) ); ?>
				</div>
			</div>

			<div class="column-3">
				<strong><?php _e( 'Available Sidebars' ); ?></strong>
				<p class="description"><?php _e( 'Select a sidebar to edit.' ); ?></p>
				<?php self::get_sidebars(); ?>
			</div>

			<?php wp_nonce_field( 'save-sidebar-widgets', '_wpnonce_widgets', false ); ?>

			<div class="clear"></div>

			<?php
		}

		/**
		 * Retrieve sidebars and output HTML for metabox
		 * 
		 * @global Object $post
		 * @global Array $wp_registered_sidebars 
		 * @return Void
		 */
		public static function get_sidebars() {
			global $post, $wp_registered_sidebars;

			$i = 0;
			foreach ($wp_registered_sidebars as $sidebar) {
				// Ignore sidebars registered by this plugin.
				if ( strpos( $sidebar['id'], self::WIDGET_ID_PREFIX ) === 0 )
					continue;
				?>
				<div class="widget" id="<?php echo self::get_sidebar_id( $post->post_name, $i ); ?>" data-sidebar="<?php echo $sidebar['id']; ?>">
					<div class="widget-top">
						<div class="widget-title-action">
							<a class="widget-action hide-if-no-js" href="#available-widgets"></a>
						</div>
						<div class="widget-title"><h4><?php echo $sidebar['name']; ?></h4></div>
					</div>

					<div class="widget-inside">
						<div class="widget-control-actions">
							<div class="alignleft">
								<a class="widget-control-remove" href="#remove">Delete</a> |
								<a class="widget-control-close" href="#close">Close</a>
							</div>

							<div class="alignright">
								<img src="<?php echo admin_url(); ?>images/wpspin_light.gif" class="ajax-feedback" title="" alt="">
								<input type="submit" name="savewidget" class="button-primary widget-control-save" value="Save">
							</div>
							<br class="clear">
						</div>
					</div>
				</div>
				<?php
				$i++;
			}
		}

		/**
		 * AJAX handler to retreive active widets
		 * @method ajax_get_active_widgets
		 * @return Void
		 */
		public static function ajax_get_active_widgets() {
			self::get_active_widgets( $_POST['sidebar'] );
			die();
		}

		public static function get_active_widgets( $sidebar ) {
			global $sidebars_widgets;
			$temp = $sidebars_widgets;
			$sidebars_widgets = array( $sidebar => array( ) );
			wp_list_widget_controls( $sidebar );
			$sidebars_widgets = $temp;
		}

		/**
		 * AJAX handler to update sidebar in wp_options
		 * 
		 * @method ajax_register_sidebar
		 * @return Void
		 */
		public static function ajax_register_sidebar() {
			$sidebars = get_option( 'page_widgets', array( ) );

			$sidebars[$_POST['sidebar']] = array(
				'original_sidebar' => $_POST['original_sidebar'],
				'post_name' => $_POST['post_name']
			);

			update_option( 'page_widgets', $sidebars );
			die();
		}

		/**
		 * Return the concatenated string of the sidebar ID
		 * 
		 * @method get_sidebar_id
		 * @param String $post_name
		 * @param String $i
		 * @return String ID of custom sidebar 
		 */
		private static function get_sidebar_id( $post_name, $i ) {
			return self::WIDGET_ID_PREFIX . $post_name . '-' . $i;
		}

	}

	Voce_Post_Widgets::setup();
}