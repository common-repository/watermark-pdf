<?php
namespace GPLSCore\GPLS_PLUGIN_WMPDF;

/**
 * Plugin Name:     WP Watermark PDF [[GrandPlugins]]
 * Description:     Add Text and Image Watermarks to PDF files.
 * Author:          GrandPlugins
 * Author URI:      https://grandplugins.com
 * Text Domain:     watermark-pdf
 * Std Name:        gpls-wmpdf-watermark-pdf
 * Version:         1.0.3
 *
 * @package         GPLS_WP_Watermark_PDF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GPLSCore\GPLS_PLUGIN_WMPDF\Core;
use GPLSCore\GPLS_PLUGIN_WMPDF\Watermark_Base;
use GPLSCore\GPLS_PLUGIN_WMPDF\PDF_Watermark;
use GPLSCore\GPLS_PLUGIN_WMPDF\Single_Apply_Watermarks;
use GPLSCore\GPLS_PLUGIN_WMPDF\Watermarks_Templates;

if ( ! class_exists( __NAMESPACE__ . '\GPLS_WMPDF_Class' ) ) :

	/**
	 * Watermark PDF Plugin Main Class.
	 */
	class GPLS_WMPDF_Class {

		/**
		 * Single Instance
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Plugin Info
		 *
		 * @var array
		 */
		private static $plugin_info;

		/**
		 * Debug Mode Status
		 *
		 * @var bool
		 */
		protected $debug;

		/**
		 * Core Object
		 *
		 * @return object
		 */
		private static $core;

		/**
		 * Singular init Function.
		 *
		 * @return Object
		 */
		public static function init() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Core Actions Hook.
		 *
		 * @return void
		 */
		public static function core_actions( $action_type ) {
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'core/bootstrap.php';
			self::$core = new Core( self::$plugin_info );
			if ( 'activated' === $action_type ) {
				self::$core->plugin_activated();
			} elseif ( 'deactivated' === $action_type ) {
				self::$core->plugin_deactivated();
			} elseif ( 'uninstall' === $action_type ) {
				self::$core->plugin_uninstalled();
			}
		}

		/**
		 * Check for Required Plugins before Activate.
		 *
		 * @return void
		 */
		private static function required_plugins_check_activate() {
			if ( empty( self::$plugin_info['required_plugins'] ) ) {
				return;
			}
			foreach ( self::$plugin_info['required_plugins'] as $plugin_basename => $plugin_details ) {
				if ( ! is_plugin_active( $plugin_basename ) ) {
					deactivate_plugins( self::$plugin_info['basename'] );
					wp_die( sprintf( esc_html__( '%1$s ( %2$s ) plugin is required in order to activate the plugin', '%3$s' ), $plugin_details['title'], $plugin_basename, self::$plugin_info['name'] ) );
				}
			}
		}

		/**
		 * Disable Duplicate Free/Pro.
		 *
		 * @return void
		 */
		private static function disable_duplicate() {
			if ( ! empty( self::$plugin_info['duplicate_base'] ) && is_plugin_active( self::$plugin_info['duplicate_base'] ) ) {
				deactivate_plugins( self::$plugin_info['duplicate_base'] );
			}
		}

		/**
		 * Check for Required Plugins before Load.
		 *
		 * @return void
		 */
		private static function required_plugins_check_load() {
			if ( empty( self::$plugin_info['required_plugins'] ) ) {
				return;
			}
			foreach ( self::$plugin_info['required_plugins'] as $plugin_basename => $plugin_details ) {
				if ( ! class_exists( $plugin_details['class_check'] ) ) {
					require_once \ABSPATH . 'wp-admin/includes/plugin.php';
					deactivate_plugins( self::$plugin_info['basename'] );
					return;
				}
			}
		}

		/**
		 * Plugin Activated Hook.
		 *
		 * @return void
		 */
		public static function plugin_activated() {
			self::setup_plugin_info();
			self::required_plugins_check_activate();
			self::disable_duplicate();
			self::core_actions( 'activated' );
			PDF_Watermark::on_activate( self::$core, self::$plugin_info );
			register_uninstall_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_WMPDF_Class', 'plugin_uninstalled' ) );
		}

		/**
		 * Plugin Deactivated Hook.
		 *
		 * @return void
		 */
		public static function plugin_deactivated() {
			self::setup_plugin_info();
			self::core_actions( 'deactivated' );
		}

		/**
		 * Plugin Installed hook.
		 *
		 * @return void
		 */
		public static function plugin_uninstalled() {
			self::setup_plugin_info();
			self::includes();
			self::core_actions( 'uninstall' );
			PDF_Watermark::on_uninstall( self::$core, self::$plugin_info );
		}
		/**
		 * Constructor
		 */
		private function __construct() {
			self::setup_plugin_info();
			$this->load_languages();
			self::includes();
			$this->load();
		}

		/**
		 * Includes Files
		 *
		 * @return void
		 */
		public static function includes() {
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'core/bootstrap.php';
		}

		/**
		 * Load languages Folder.
		 *
		 * @return void
		 */
		public function load_languages() {
			load_plugin_textdomain( self::$plugin_info['text_domain'], false, trailingslashit( dirname( self::$plugin_info['basename'] ) ) . 'languages/' );
		}

		/**
		 * Load Classes.
		 *
		 * @return void
		 */
		public function load() {
			self::required_plugins_check_load();
			self::$core = new Core( self::$plugin_info );

			new Settings( self::$core, self::$plugin_info );
			PDF::init( self::$plugin_info );
			Watermarks_Templates::init( self::$plugin_info, self::$core );
			Single_Apply_Watermarks::init( self::$plugin_info, self::$core );
			Watermark_Base::init( self::$plugin_info );
			PDF_Watermark::init( self::$plugin_info );
		}

		/**
		 * Set Plugin Info
		 *
		 * @return array
		 */
		public static function setup_plugin_info() {
			$plugin_data = get_file_data(
				__FILE__,
				array(
					'Version'     => 'Version',
					'Name'        => 'Plugin Name',
					'URI'         => 'Plugin URI',
					'SName'       => 'Std Name',
					'text_domain' => 'Text Domain',
				),
				false
			);

			self::$plugin_info = array(
				'id'                    => 1775,
				'basename'              => plugin_basename( __FILE__ ),
				'version'               => $plugin_data['Version'],
				'name'                  => $plugin_data['SName'],
				'text_domain'           => $plugin_data['text_domain'],
				'file'                  => __FILE__,
				'plugin_url'            => $plugin_data['URI'],
				'public_name'           => $plugin_data['Name'],
				'path'                  => trailingslashit( plugin_dir_path( __FILE__ ) ),
				'url'                   => trailingslashit( plugin_dir_url( __FILE__ ) ),
				'options_page'          => $plugin_data['SName'] . '-settings-tab',
				'single_apply_page'     => 'gpls-wmpdf',
				'localize_var'          => str_replace( '-', '_', $plugin_data['SName'] ) . '_localize_data',
				'type'                  => 'free',
				'classes_prefix'        => 'gpls-wmpdf',
				'classes_general'       => 'gpls-general',
				'main_watermark_prefix' => 'gpls-wmfw',
				'pro_link'              => 'https://grandplugins.com/product/wp-watermark-pdf/?utm_source=free',
				'review_link'           => 'https://wordpress.org/support/plugin/watermark-pdf/reviews/#new-post',
				'duplicate_base'        => 'gpls-wmpdf-watermark-pdf/gpls-wmpdf-watermark-pdf.php',
			);
		}

	}

	add_action( 'plugins_loaded', array( __NAMESPACE__ . '\GPLS_WMPDF_Class', 'init' ), 1000 );
	register_activation_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_WMPDF_Class', 'plugin_activated' ) );
	register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_WMPDF_Class', 'plugin_deactivated' ) );
endif;
