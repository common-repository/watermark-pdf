<?php
namespace GPLSCore\GPLS_PLUGIN_WMPDF;

use GPLSCore\GPLS_PLUGIN_WMPDF\FPDI_Wrapper;
use setasign\Fpdi\Fpdi;
/**
 * PDF Watermark.
 */
class PDF_Watermark {

	use Helpers;

	/**
	 * Singular Instance.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Plugin Info Array.
	 *
	 * @var array
	 */
	private static $plugin_info;

	/**
	 * Auto Apply Watermarks Templates Key.
	 *
	 * @var string
	 */
	private static $auto_apply_options_key;

	/**
	 * Position Spots Mapping.
	 *
	 * @var array
	 */
	public static $spots_mapping = array(
		'tl' => array(
			'left' => 0,
			'top'  => 0,
		),
		'tm' => array(
			'left' => 1,
			'top'  => 0,
		),
		'tr' => array(
			'left' => 2,
			'top'  => 0,
		),
		'ml' => array(
			'left' => 0,
			'top'  => 1,
		),
		'mm' => array(
			'left' => 1,
			'top'  => 1,
		),
		'mr' => array(
			'left' => 2,
			'top'  => 1,
		),
		'bl' => array(
			'left' => 0,
			'top'  => 2,
		),
		'bm' => array(
			'left' => 1,
			'top'  => 2,
		),
		'br' => array(
			'left' => 2,
			'top'  => 2,
		),
	);

	/**
	 * Fonts Folder Name.
	 *
	 * @var string
	 */
	private static $fonts_folder;

	/**
	 * Fonts Folder Path.
	 *
	 * @var string
	 */
	private static $fonts_folder_path;

	/**
	 * Fonts Folder URL.
	 *
	 * @var string
	 */
	private static $fonts_folder_url;

	/**
	 * Constructor.
	 *
	 * @param array $plugin_info
	 */
	private function __construct( $plugin_info ) {
		self::$plugin_info = $plugin_info;
		self::setup_fonts();
		$this->hooks();
	}

	/**
	 * Setup Fonts.
	 *
	 */
	private static function setup_fonts() {
		$uploads                 = wp_upload_dir();
		self::$fonts_folder      = self::$plugin_info['name'] . '-assets';
		self::$fonts_folder_path = trailingslashit( $uploads['basedir'] ) . trailingslashit( self::$fonts_folder );
		self::$fonts_folder_url  = trailingslashit( $uploads['baseurl'] ) . trailingslashit( self::$fonts_folder );
	}

	/**
	 * On Activation.
	 *
	 * @param Core  $core
	 * @param array $plugin_info
	 * @return void
	 */
	public static function on_activate( $core, $plugin_info ) {
		$uploads           = wp_upload_dir();
		$fonts_folder      = $plugin_info['name'] . '-assets';
		$fonts_folder_path = trailingslashit( $uploads['basedir'] ) . $fonts_folder;
		if ( ! is_dir( $fonts_folder_path ) ) {
			@mkdir( $fonts_folder_path );
		}
	}

	/**
	 * On Uninstall
	 *
	 * @param Core   $core
	 * @param array $plugin_info
	 * @return void
	 */
	public static function on_uninstall( $core, $plugin_info ) {
		if ( is_plugin_active( $plugin_info['duplicate_base'] ) ) {
			return;
		}
		$uploads            = wp_upload_dir();
		$fonts_folder       = $plugin_info['name'] . '-assets';
		$fonts_folder_path  = trailingslashit( $uploads['basedir'] ) . $fonts_folder;
		@rmdir( $fonts_folder_path );
	}

	/**
	 * Initialize func.
	 *
	 * @param array $plugin_info Plugin Info Array.
	 * @return object
	 */
	public static function init( $plugin_info ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $plugin_info );
		}
		return self::$instance;
	}

	/**
	 * Get Fonts Folder PATH.
	 *
	 * @return string
	 */
	public static function get_fonts_folder_path() {
		return self::$fonts_folder_path;
	}

	/**
	 * Get Fonts Folder URL.
	 *
	 * @return string
	 */
	public static function get_fonts_folder_url() {
		return self::$fonts_folder_url;
	}

	/**
	 * Auto Apply Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		// add_filter( 'wp_prepare_attachment_for_js', array( get_called_class(), 'add_watermark_chosen_size_to_js_object' ), 100, 3 );
		add_filter( 'wp_get_attachment_url', array( get_called_class(), 'force_cache_bust_after_watermarking' ), 100, 2 );
		add_filter( 'wp_get_attachment_image_src', array( get_called_class(), 'force_cache_bust_in_media_listing' ), 100, 4 );
		add_filter( 'image_make_intermediate_size', array( $this, 'adjust_watermarked_pdf_preview_img' ), 1000, 1 );
		add_filter( 'image_resize_dimensions', array( $this, 'pypass_pdf_sizes' ), 1000, 6 );
		add_action( 'wp_ajax_get_pdf_dimensions', array( $this, 'ajax_get_pdf_dimensions' ), 1000, 2 );
	}

	/**
	 * AJAX Get PDF Dimensions.
	 * @return void
	 */
	public function ajax_get_pdf_dimensions() {
		if ( ! empty( $_POST['nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['nonce'] ), self::$plugin_info['name'] . '-ajax-nonce' ) ) {
			$pdf_url = ! empty( $_POST['pdfURL'] ) ? wp_unslash( $_POST['pdfURL'] ) : '';
			if ( empty( $pdf_url ) ) {
				wp_send_json_error(
					array(
						'status' => 'danger',
						'msg'    => esc_html__( 'Invalid PDF Path', 'watermark-pdf' ),
					)
				);
			}

			$results = self::get_pdf_dimensions( self::convert_url_to_path( $pdf_url ) );
			if ( is_wp_error( $results ) ) {
				wp_send_json_error(
					array(
						'status' => 'danger',
						'msg'    => $results->get_error_message(),
					)
				);
			}

			wp_send_json_success(
				array(
					'status'     => 'success',
					'dimensions' => $results,
				)
			);
		}

		wp_send_json_error(
			array(
				'status' => 'danger',
				'msg'    => esc_html__( 'The link has expired, please refresh the page!', 'watermark-pdf' ),
			)
		);
	}

	/**
	 * Force Cache Busting for images after adding watermarks to purge the browser cached version.
	 *
	 * @param string $image Image Array ( 0 => URL, 1 => width, 2 => height, 3 => is a resized image boolean ).
	 * @param int    $attachment_id Attachment Post ID.
	 * @param int    $size Target Size.
	 * @param int    $icon where fallback to icon.
	 * @return array
	 */
	public static function force_cache_bust_in_media_listing( $image, $attachment_id, $size, $icon ) {
		if ( is_array( $image ) && ! empty( $_SERVER['HTTP_REFERER'] ) && ( admin_url( 'upload.php' ) === esc_url( strtok( wp_unslash( $_SERVER['HTTP_REFERER'] ), '?' ) ) ) ) {
			$image[0] = add_query_arg(
				array(
					'refresh'     => wp_generate_password( 5, false, false ),
					'dontreplace' => '',
				),
				$image[0]
			);
		}
		return $image;
	}

	/**
	 * Force Cache Busting for images after adding watermarks to purge the browser cached version.
	 *
	 * @param string $url
	 * @param int    $post_id
	 * @return string $url
	 */
	public static function force_cache_bust_after_watermarking( $url, $post_id ) {
		if ( ! empty( $url ) && ! empty( $_REQUEST[ self::$plugin_info['classes_prefix'] . '-force-pdf-refresh' ] ) || ( ( ! empty( $_SERVER['HTTP_REFERER'] ) ) && admin_url( 'upload.php' ) === esc_url( strtok( wp_unslash( $_SERVER['HTTP_REFERER'] ), '?' ) ) ) ) {
			$url = add_query_arg(
				array(
					'refresh'     => wp_generate_password( 5, false, false ),
					'dontreplace' => '',
				),
				$url
			);
		}
		return $url;
	}

	/**
	 * Pypass PDF Sizes when watermarking them.
	 *
	 * @param array|null $output
	 * @param int        $orig_w
	 * @param int        $orig_h
	 * @param int        $dest_w
	 * @param int        $dest_h
	 * @param boolean    $crop
	 * @return array|null
	 */
	public function pypass_pdf_sizes( $output, $orig_w, $orig_h, $dest_w, $dest_h, $crop ) {
		if ( ! empty( $GLOBALS[ self::$plugin_info['name'] . '-single-pdf-watermark-apply' ] ) ) {
			if ( $crop ) {
				$aspect_ratio = $orig_w / $orig_h;
				$size_ratio   = max( $dest_w / $orig_w, $dest_h / $orig_h );

				$crop_w = round( $dest_w / $size_ratio );
				$crop_h = round( $dest_h / $size_ratio );

				if ( ! is_array( $crop ) || count( $crop ) !== 2 ) {
					$crop = array( 'center', 'center' );
				}

				list( $x, $y ) = $crop;

				if ( 'left' === $x ) {
					$s_x = 0;
				} elseif ( 'right' === $x ) {
					$s_x = $orig_w - $crop_w;
				} else {
					$s_x = floor( ( $orig_w - $crop_w ) / 2 );
				}

				if ( 'top' === $y ) {
					$s_y = 0;
				} elseif ( 'bottom' === $y ) {
					$s_y = $orig_h - $crop_h;
				} else {
					$s_y = floor( ( $orig_h - $crop_h ) / 2 );
				}
			} else {
				$s_x = 0;
				$s_y = 0;
			}

			return array( 0, 0, (int) $s_x, (int) $s_y, (int) $dest_w, (int) $dest_h, (int) $dest_w, (int) $dest_h );
		}

		return $output;
	}

	/**
	 * Fix WP Core PDF predefined Image Sizes.
	 *
	 * @param array $fallback_sizes
	 * @param array $metadata
	 * @return array
	 */
	public function fix_wp_core_pdf_img_sizes( $fallback_sizes, $metadata ) {

		return $metadata;
	}

	/**
	 * Watermarked PDFs leads to black background preview file.
	 * Overwrite the created preview file the right way.
	 *
	 * @param string $preview_file_path
	 * @return string
	 */
	public function adjust_watermarked_pdf_preview_img( $preview_file_path ) {
		if ( ! empty( $GLOBALS[ self::$plugin_info['name'] . '-is-auto-apply-watermarks-template-on-pdf' ] ) ) {

			$pdf_details = $GLOBALS[ self::$plugin_info['name'] . '-is-auto-apply-watermarks-template-on-pdf' ];
			if ( ! is_array( $pdf_details ) || empty( $pdf_details['path'] ) ) {
				return $preview_file_path;
			}

			self::generate_pdf_thumbnail_v1( $pdf_details['path'], $preview_file_path );

			// Reset the flag.
			unset( $GLOBALS[ self::$plugin_info['name'] . '-is-auto-apply-watermarks-template-on-pdf' ] );
		}

		return $preview_file_path;
	}

	/**
	 * Generate Thumbnail Image for PDF from first page.
	 *
	 * @param string $pdf_path PDF PATH.
	 * @param string $dest_path PDF PATH.
	 * @return void
	 */
	public static function generate_pdf_thumbnail_v1( $pdf_path, $dest_path ) {
		$img = new \Imagick( $pdf_path );
		$img->setIteratorIndex( 0 );
		$img->setImageAlphaChannel( \Imagick::ALPHACHANNEL_REMOVE );
		$img->mergeImageLayers( \Imagick::LAYERMETHOD_FLATTEN );
		$img->setImageFormat( 'jpg' );
		$img->writeImage( $dest_path );
		$img->clear();
	}

	/**
	 * Apply Watermark Wrapper to PDF.
	 *
	 * @param array  $watermarks
	 * @param string $pdf_path
	 * @param array  $watermark
	 * @param string $watermark_type
	 * @return FPDI_Wrapper|\WP_Error
	 */
	public static function watermark( $pdf_path, $watermarks, $dest_path = null, $force_convert = false ) {
		try {
			$pdf_wrapper = self::init_pdf_wrapper( $pdf_path, $watermarks, $force_convert );
			if ( is_wp_error( $pdf_wrapper ) ) {
				return $pdf_wrapper;
			}
	
			$pdf_wrapper->apply_watermarks();
			if ( $dest_path ) {
				$pdf_wrapper->save_pdf( $dest_path );
			}
	
			return $pdf_wrapper;
		} catch ( \Exception $e ) {
			$error_message = $e->getMessage();
			if ( str_starts_with( $error_message, 'This PDF document probably uses a compression technique which is not supported by the free parser' ) ) {
				$error_message = sprintf(
					wp_kses_post( 'PDF file uses an advanced compression technique and it seems Ghostscript is not installed. Please check %s page for more details.', 'watermark-pdf' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::$plugin_info['options_page'] ) ) . '" >' . esc_html__( 'Status', 'watermark-pdf' ) . '</a>'
				);
			}
			return new \WP_Error( 'pdf-apply-watermarks', $error_message );
		}
	}

	/**
	 * Init PDF Wrapper.
	 *
	 * @param string $pdf_path
	 * @param boolean $force_convert
	 * @return FPDI_Wrapper|\WP_Error
	 */
	private static function init_pdf_wrapper( $pdf_path, $watermarks = array(), $force_convert = false ) {
		try {
			$pdf_watermark = new FPDI_Wrapper( self::$plugin_info, $pdf_path, $watermarks, $force_convert );
			return $pdf_watermark;
		} catch ( \Exception $e ) {
			if ( ! $force_convert ) {
				return self::init_pdf_wrapper( $pdf_path, $watermarks, true );
			}

			$error_message = $e->getMessage();
			if ( str_starts_with( $error_message, 'This PDF document probably uses a compression technique which is not supported by the free parser' ) ) {
				$error_message = sprintf(
					esc_html__( 'PDF file uses an advanced compression technique and it seems Ghostscript is not installed. Please check %s page for more details.', 'watermark-pdf' ),
					'<a target="_blank" href="' . esc_url( admin_url( 'admin.php?page=' . self::$plugin_info['options_page'] ) ) . '" >' . esc_html__( 'Status', 'watermark-pdf' ) . '</a>'
				);
			}

			return new \WP_Error(
				'init-pdf-wrapper',
				$error_message
			);
		}
	}

	/**
	 * Get PDF Dimensions.
	 *
	 * @param string $pdf_path
	 * @return array|\WP_Error
	 */
	public static function get_pdf_dimensions( $pdf_path, $converted = false ) {
		try {
			$pdf1 = new Fpdi( 'P', 'pt' );
			$pdf1->setSourceFile( $pdf_path );
			$pdf_page = $pdf1->importPage( 1 );
			$size     = $pdf1->getTemplateSize( $pdf_page );
			$w        = $size['width'];
			$h        = $size['height'];
			return array(
				'width' => round( $w * ( 1 / 0.75 ) ),
				'height' => round( $h * ( 1 / 0.75 ) ),
			);
		} catch ( \Exception $e ) {
			if ( ! $converted ) {
				$pdfi_wrapper = self::init_pdf_wrapper( $pdf_path, array(), true );
				unset( $pdf1 );
				$result = self::get_pdf_dimensions( $pdf_path, true );
				if ( ! is_wp_error( $result ) ) {
					$pdfi_wrapper->revert_version();
				}
				return $result;
			}
			$error_message = $e->getMessage();
			if ( str_starts_with( $error_message, 'This PDF document probably uses a compression technique which is not supported by the free parser' ) ) {
				$error_message = sprintf(
					esc_html__( 'PDF file uses an advanced compression technique and it seems Ghostscript is not installed. Please check %s page for more details.', 'watermark-pdf' ),
					'<a target="_blank" href="' . esc_url( admin_url( 'admin.php?page=' . self::$plugin_info['options_page'] ) ) . '" >' . esc_html__( 'Status', 'watermark-pdf' ) . '</a>'
				);
			}
			$error_message = str_replace( 'FPDF error: ', '', $error_message );
			return new \WP_Error(
				self::$plugin_info['classes_prefix'] . '-watermark-pdf-error',
				$error_message
			);
		}
	}

	/**
	 * Get available Fonts for Text watermark.
	 *
	 * @param boolean $prepare Return paths only or paths with names.
	 * @return array
	 */
	public static function get_available_fonts( $prepare = false ) {
		$fonts_path  = self::$plugin_info['path'] . 'assets/dist/fonts/';
		$fonts_url   = self::$plugin_info['url'] . 'assets/dist/fonts/';
		$fonts_files = array();

		require_once \ABSPATH . 'wp-admin/includes/file.php';

		$fonts = list_files( $fonts_path, 1 );
		if ( ! $prepare ) {
			return $fonts;
		}

		foreach ( $fonts as $font_file ) {
			$font_name                        = wp_basename( $font_file );
			$font_ext                         = pathinfo( $font_file, PATHINFO_EXTENSION );
			$font_name_without_ext            = wp_basename( $font_file, '.' . $font_ext );
			$font_family_name                 = sanitize_title_with_dashes( $font_name_without_ext );
			$font_title                       = str_replace( array( '-', '_' ), ' ', $font_name_without_ext );
			$fonts_files[ $font_family_name ] = array(
				'title'       => $font_title,
				'path'        => $font_file,
				'font_family' => $font_family_name,
				'url'         => $fonts_url . $font_name,
				'name'        => $font_name,
			);
		}

		return $fonts_files;
	}

	/**
	 * Get Font PATH.
	 *
	 * @param string $font_family_name the name of the font file.
	 * @return string
	 */
	public static function get_font_path( $font_family_name ) {
		$fonts = self::get_available_fonts( true );
		if ( ! empty( $fonts[ $font_family_name ] ) ) {
			return $fonts[ $font_family_name ]['path'];
		} else {
			return $fonts['georgia']['path'];
		}
	}


	/**
	 * List Fonts CSS.
	 *
	 * @return string
	 */
	public static function list_fonts_css( $plugin_info ) {
		$css   = '';
		$fonts = self::get_available_fonts( true );
		ob_start();
		foreach ( $fonts as $font_family_name => $font ) :
			?>@font-face {
			font-family: "<?php echo esc_attr( $font_family_name ); ?>";
			src: url( '<?php echo esc_url_raw( $font['url'] ); ?>' ) format('truetype');
		}
			<?php
		endforeach;
		$css .= ob_get_clean();
		return $css;
	}


	/**
	 * Add subsizes to watermarks Modal media and force refresh param on images URLs.
	 *
	 * @param array  $response
	 * @param object $attachment
	 * @param array  $meta
	 * @return array
	 */
	public static function add_watermark_chosen_size_to_js_object( $response, $attachment, $meta ) {
		// Check if its auto apply watermarks, force refresh link.
		if ( ( ! empty( $GLOBALS[ self::$plugin_info['name'] . '-is-auto-apply-watermarks-template' ] ) && ! empty( $response['type'] ) && ( 'image' === $response['type'] ) ) || ( ! empty( $_SERVER['HTTP_REFERER'] ) && admin_url( 'upload.php' ) === esc_url( strtok( wp_unslash( $_SERVER['HTTP_REFERER'] ), '?' ) ) ) ) {
			// cache bust refresh.
			$cache_bust_refresh = wp_generate_password( 5, false, false );
			if ( ! empty( $response['size']['url'] ) ) {
				$response['size']['url'] = add_query_arg(
					array(
						'refresh'     => $cache_bust_refresh,
						'dontreplace' => '',
					),
					$response['size']['url']
				);
			}
			if ( ! empty( $response['sizes'] ) ) {
				foreach ( $response['sizes'] as $size_name => $size_arr ) {
					if ( ! empty( $response['sizes'][ $size_name ]['url'] ) ) {
						$response['sizes'][ $size_name ]['url'] = add_query_arg(
							array(
								'refresh'     => $cache_bust_refresh,
								'dontreplace' => '',
							),
							$response['sizes'][ $size_name ]['url']
						);
					}
				}
			}
		}

		// Selecting preview for watermarking action.
		if (
				(
					! empty( $_REQUEST['query'] ) && ! empty( $_REQUEST['query'][ self::$plugin_info['name'] . '-context-modal' ] ) && 'select-preview-image' === sanitize_text_field( wp_unslash( $_REQUEST['query'][ self::$plugin_info['name'] . '-context-modal' ] ) )
				)
					||
				(
					! empty( $GLOBALS[ self::$plugin_info['name'] . '-init-current-preview' ] )
				)
			)
		{
			if ( 'application' === $response['type'] && 'pdf' === $response['subtype'] ) {
				$pdf_dimensions = self::get_pdf_dimensions( self::convert_url_to_path( $response['url'] ) );
				if ( is_array( $pdf_dimensions ) ) {
					$response['width']  = round( $pdf_dimensions['width'] );
					$response['height'] = round( $pdf_dimensions['height'] );
				}
			}
		}

		return $response;
	}


}
