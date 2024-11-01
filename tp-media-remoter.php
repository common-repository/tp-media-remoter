<?php
/**
 * Plugin Name: TP Media Remoter
 * Plugin URI: https://wordpress.org/plugins/tp-media-remoter/
 * Description: Insert featured image and media to Editor using WordPress Media Library with a external library. The best way to save your hosting storage.  
 * Version: 1.0.1
 * Author: ThemesPond
 * Author URI: https://themespond.com/
 * License: GNU General Public License v3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Requires at least: 4.3
 * Tested up to: 4.9
 * Text Domain: tp-media-remoter
 * Domain Path: /languages/
 *
 * @package TPMR
 */
if ( !class_exists( 'TPMR' ) ) {

	final class TPMR {

		function __construct() {

			$this->defined();
			$this->hook();
			$this->includes();

			do_action( 'tpmr_loaded' );
		}

		/**
		 * The single instance of the class.
		 *
		 * @var TPMR
		 * @since 1.0
		 */
		protected static $_instance = null;

		/**
		 * Main Tp Media Remoter Instance.
		 *
		 * Ensures only one instance of TPMR is loaded or can be loaded.
		 *
		 * @since 1.0
		 * @static
		 * @see WC()
		 * @return WooCommerce - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Call functions to WordPress hooks
		 * @since 1.0
		 * @return void
		 */
		public function hook() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 15 );
			add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			add_action( 'admin_menu', array( $this, 'register_page' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
		}

		/**
		 * Show notices in admin
		 * @since 1.0
		 */
		public function admin_notices( $hook ) {

			$user = new TPMR_User();

			if ( $user->get_token() == '' && $user->get_email() == '' ) {
				echo '<div class="notice error"><p>';
				printf( __( '<strong>TP Media Remoter:</strong> <a href="%s">Please register or provide a product key to start using.</a>', 'media-remoter' ), admin_url( 'options-general.php?page=tpmr-settings' ) );
				echo '</p></div>';
			} else if ( !$user->is_validate() ) {
				$is_plugin_page = isset( $_GET['page'] ) && $_GET['page'] == 'tpmr-settings';

				if ( !$is_plugin_page ) {
					echo '<div class="notice error"><p>';
					printf( __( '<strong>TP Media Remoter:</strong> Email and product key are invalid. <a href="%s">Fix now</a>', 'media-remoter' ), admin_url( 'options-general.php?page=tpmr-settings' ) );
					echo '</p></div>';
				}
			}
		}

		/**
		 * Init classes and functions were written for third party
		 * @since 1.0
		 * @return void
		 */
		public function init() {
			
		}

		/**
		 * Include toolkit functions
		 * @since 1.0
		 */
		public function includes() {
			require TPMR_DIR . 'includes/helper-functions.php';
			require TPMR_DIR . 'includes/class-tpmr-user.php';
			require TPMR_DIR . 'includes/class-tpmr-media.php';
			require TPMR_DIR . 'includes/class-tpmr-featured-image.php';
			require TPMR_DIR . 'includes/class-tpmr-hooks.php';
			require TPMR_DIR . 'includes/class-tpmr-ajax.php';
		}

		/**
		 * Register plugin page
		 */
		public function register_page() {
			add_submenu_page( 'options-general.php', esc_html__( 'TP Media Remoter', 'tp-media-remoter' ), esc_html__( 'TP Media Remoter', 'tp-media-remoter' ), 'manage_options', 'tpmr-settings', array( $this, 'settings_page' ) );
		}

		/**
		 * Setting page
		 */
		public function settings_page() {
			tpmr_template( 'install' );
		}

		/**
		 * Activation function fires when the plugin is activated.
		 * 
		 * @since 1.0
		 * @return void
		 */
		public function activation() {
			// is this plugin active?
			if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				// unset activation notice
				unset( $_GET['activate'] );
				// display notice
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			}
		}

		/**
		 * Defined 
		 */
		public function defined() {
			define( 'TPMR_URL', plugin_dir_url( __FILE__ ) );
			define( 'TPMR_DIR', plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Register TP UI page font url
		 * @since 1.0.0
		 * @return string Font url
		 */
		public function font_url() {

			$fonts_url = '';
			$font_families = array();

			$font1 = _x( 'on', 'Poppins font', 'tpui' );

			if ( 'off' !== $font1 ) {
				$font_families[] = 'Poppins:300,400,600,700';
			}

			$font2 = _x( 'on', 'Baloo font', 'tpui' );

			if ( 'off' !== $font2 ) {
				$font_families[] = 'Baloo';
			}

			if ( !empty( $font_families ) ) {
				$query_args = array(
					'family' => urlencode( implode( '|', $font_families ) ),
					'subset' => urlencode( 'latin,latin-ext' ),
				);

				$fonts_url = add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );

				$fonts_url = apply_filters( 'tpmr_fonts_url', $fonts_url );
			}

			return esc_url_raw( $fonts_url );
		}

		/**
		 * Enqueue admin script
		 * @since 1.0
		 * @param string $hook
		 * @return void
		 */
		public function admin_scripts( $hook ) {

			wp_enqueue_script( 'tpmr-media', TPMR_URL . 'assets/js/media.js', array( 'media-editor', 'media-views' ), true );

			wp_localize_script( 'tpmr-media', 'tpmr_var', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'tpmr-get_attachments' ),
				'cancelExport' => __( 'Cancel Export', 'tp-media-remoter', 'tp-media-remoter' ),
				'uploadSelectedTo' => __( 'Export Selected To', 'tp-media-remoter' ),
				'uploadConfirmMsg' => __( "You are about to upload all of the selected media to this selected ImgDrive account.\n'Cancel' to stop, 'OK' to upload.", 'tp-media-remoter' ),
				'invalid_email' => __( 'Please, enter a valid email.', 'tp-media-remoter' ),
				'invalid_token' => __( 'Please, enter a valid product key.', 'tp-media-remoter' )
			) );

			wp_enqueue_script( 'tpmr-uploader', TPMR_URL . 'assets/js/uploader.js', array( 'media-editor', 'media-views', 'tpmr-media', 'wp-plupload' ), true );
			wp_enqueue_script( 'tpmr-settings', TPMR_URL . 'assets/js/settings.js', array(), true );

			wp_enqueue_style( 'tpui', TPMR_URL . 'assets/css/tpui.css' );
			wp_enqueue_style( 'ionicons', TPMR_URL . 'assets/css/ionicons.min.css', array() );
			wp_enqueue_style( 'tpui-fonts', $this->font_url(), array(), null );
			wp_enqueue_style( 'tpmr-admin', TPMR_URL . 'assets/css/admin.css' );
		}

		/**
		 * Load Local files.
		 * @since 1.0.0
		 * @return void
		 */
		public function load_plugin_textdomain() {

			// Set filter for plugin's languages directory
			$dir = TPMR_DIR . 'languages/';
			$dir = apply_filters( 'tpmr_languages_directory', $dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'tp-media-remoter' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'tp-media-remoter', $locale );

			// Setup paths to current locale file
			$mofile_local = $dir . $mofile;

			$mofile_global = WP_LANG_DIR . '/tp-media-remoter/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/epl folder
				load_textdomain( 'tp-media-remoter', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/tpmr/languages/ folder
				load_textdomain( 'tp-media-remoter', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'tp-media-remoter', false, $dir );
			}
		}

		/**
		 * Add links to Plugins page
		 * @since 1.0.0
		 * @return array
		 */
		function add_action_links( $links ) {

			$plugin_links = array(
				'page' => '<a href="' . esc_url( apply_filters( 'tpmr_page_url', admin_url( 'options-general.php?page=tpmr-settings' ) ) ) . '" aria-label="' . esc_attr__( 'Settings', 'tp-media-remoter' ) . '">' . esc_html__( 'Settings', 'tp-media-remoter' ) . '</a>',
			);

			return array_merge( $links, $plugin_links );
		}

	}

	/**
	 * Main instance of TPMR.
	 *
	 * Returns the main instance of TPMR to prevent the need to use globals.
	 *
	 * @since  1.0
	 * @return TPMR
	 */
	function TPMR() {
		return TPMR::instance();
	}

	/**
	 * Global for backwards compatibility.
	 */
	$GLOBALS['tpmr'] = TPMR();
}
