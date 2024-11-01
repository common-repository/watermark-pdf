<?php
namespace GPLSCore\GPLS_PLUGIN_WMPDF;

use GPLSCore\GPLS_PLUGIN_WMPDF\PDF_Watermark;
use GPLSCore\GPLS_PLUGIN_WMPDF\Watermarks_Templates;

/**
 * Redirects To Checkout Class.
 */
class Settings {

	use Helpers;

	/**
	 * Core Object
	 *
	 * @var object
	 */
	public $core;

	/**
	 * Plugin Info
	 *
	 * @var object
	 */
	public static $plugin_info;

	/**
	 * Settings Name.
	 *
	 * @var string
	 */
	public static $settings_name;

	/**
	 * Settings Tab Key
	 *
	 * @var string
	 */
	protected $settings_tab_key;

	/**
	 * Settings Tab name
	 *
	 * @var array
	 */
	protected $settings_tab;


	/**
	 * Current Settings Active Tab.
	 *
	 * @var string
	 */
	protected $current_active_tab;

	/**
	 * Settings Array.
	 *
	 * @var array
	 */
	public static $settings;

	/**
	 * Settings Tab Fields
	 *
	 * @var Array
	 */
	protected $fields = array();


	/**
	 * Constructor.
	 *
	 * @param object $core Core Object.
	 * @param object $plugin_info Plugin Info Object.
	 */
	public function __construct( $core, $plugin_info ) {
		$this->core             = $core;
		self::$plugin_info      = $plugin_info;
		$this->settings_tab_key = self::$plugin_info['options_page'];
		self::$settings_name    = self::$plugin_info['name'] . '-main-settings-name';
		$this->hooks();
	}

	/**
	 * Filters and Actions Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'settings_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'settings_assets' ) );
		add_action( 'plugin_action_links_' . self::$plugin_info['basename'], array( $this, 'settings_link' ), 5, 1 );
	}

	/**
	 * Settings Assets.
	 *
	 * @return void
	 */
	public function settings_assets() {
		if ( ! empty( $_GET['page'] ) && self::$plugin_info['options_page'] === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			wp_enqueue_style( self::$plugin_info['name'] . '-settings-menu-bootstrap-style', $this->core->core_assets_lib( 'bootstrap', 'css' ), array(), self::$plugin_info['version'], 'all' );
			wp_enqueue_style( self::$plugin_info['name'] . '-settings-css', self::$plugin_info['url'] . 'assets/dist/css/admin/admin-styles.min.css', array(), self::$plugin_info['version'], 'all' );

			if ( ! wp_script_is( 'jquery' ) ) {
				wp_enqueue_script( 'jquery' );
			}
			wp_enqueue_media();

			wp_enqueue_style( 'wp-jquery-ui-dialog' );
			wp_enqueue_script( 'jquery-ui-dialog' );

			wp_enqueue_script( self::$plugin_info['name'] . '-bootstrap-js', $this->core->core_assets_lib( 'bootstrap.bundle', 'js' ), array(), self::$plugin_info['version'], true );
			wp_enqueue_script( self::$plugin_info['name'] . '-dmuploader-js', self::$plugin_info['url'] . 'assets/libs/jquery.dm-uploader.min.js', array( 'jquery' ), self::$plugin_info['version'], true );
			wp_enqueue_script( self::$plugin_info['name'] . '-settings-js', self::$plugin_info['url'] . 'assets/dist/js/admin/settings.min.js', array( 'jquery' ), self::$plugin_info['version'], true );
			wp_localize_script(
				self::$plugin_info['name'] . '-settings-js',
				str_replace( '-', '_', self::$plugin_info['name'] . '_localize_vars' ),
				array(
					'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
					'spinner'              => admin_url( 'images/spinner.gif' ),
					'nonce'                => wp_create_nonce( self::$plugin_info['name'] . '-ajax-nonce' ),
					'uploadFontFileAction' => self::$plugin_info['name'] . '-upload-custom-font-file-action',
					'classes_prefix'       => self::$plugin_info['classes_prefix'],
					'labels'               => array(
						'only_ttf'      => esc_html__( 'Only True Type fonts are allowed', 'watermark-pdf' ),
						'select_images' => esc_html__( 'Select images', 'watermark-pdf' ),
					),
				)
			);
		}
	}


	/**
	 * Settings Link.
	 *
	 * @param array $links Plugin Row Links.
	 * @return array
	 */
	public function settings_link( $links ) {
		$links[] = '<a href="' . esc_url_raw( admin_url( 'admin.php?page=' . self::$plugin_info['options_page'] ) ) . '">' . esc_html__( 'Settings' ) . '</a>';
		$links[] = '<a style="font-weight:bolder;" href="' . esc_url_raw( self::$plugin_info['pro_link'] ) . '">' . esc_html__( 'Pro' ) . '</a>';
		return $links;
	}

	/**
	 * Settings Menu Page Func.
	 *
	 * @return void
	 */
	public function settings_menu_page() {
		add_menu_page(
			esc_html__( 'Watermark PDF', 'watermark-pdf' ),
			esc_html__( 'Watermark PDF', 'watermark-pdf' ),
			'upload_files',
			self::$plugin_info['classes_prefix'],
		);
		// Single Apply Watermarks on image.
		add_submenu_page(
			self::$plugin_info['classes_prefix'],
			esc_html__( 'Single Editor', 'watermark-pdf' ),
			esc_html__( 'Single PDF Watermarker', 'watermark-pdf' ),
			'upload_files',
			self::$plugin_info['classes_prefix'],
			array( $this, 'single_apply_page' )
		);
		// Settings Page.
		add_submenu_page(
			self::$plugin_info['classes_prefix'],
			esc_html__( 'Settings', 'watermark-pdf' ),
			esc_html__( 'Settings', 'watermark-pdf' ),
			'upload_files',
			self::$plugin_info['options_page'],
			array( $this, 'main_settings_page' )
		);

	}

	/**
	 * Is settings page.
	 *
	 * @return boolean
	 */
	public function is_settings_page( $tab = '' ) {
		if ( ! empty( $_GET['page'] ) && self::$plugin_info['options_page'] === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			if ( ! empty( $tab ) ) {
				if ( ! empty( $_GET['tab'] ) && ( sanitize_text_field( wp_unslash( $_GET['tab'] ) ) === $tab ) ) {
					return true;
				} else {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Main Settings page.
	 *
	 * @return void
	 */
	public function main_settings_page() {
		$plugin_info  = self::$plugin_info;
		$core         = $this->core;
		$settings_obj = $this;

		require_once self::$plugin_info['path'] . 'templates/settings-page-template.php';
	}

	/**
	 * Single Apply page.
	 *
	 * @return void
	 */
	public function single_apply_page() {
		$plugin_info = self::$plugin_info;
		$core        = $this->core;

		require_once self::$plugin_info['path'] . 'templates/single-apply-watermarks-template.php';
	}
}
