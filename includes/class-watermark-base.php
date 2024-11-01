<?php
namespace GPLSCore\GPLS_PLUGIN_WMPDF;

use GPLSCore\GPLS_PLUGIN_WMPDF\Helpers;
use Symfony\Component\Process\Process;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;

/**
 * Watemarks Base Class.
 */
class Watermark_Base {

	use Helpers;

	/**
	 * Plugin Info array.
	 *
	 * @var array
	 */
	protected static $plugin_info = array();

	/**
	 * Preview Image Transient Key.
	 *
	 * @var string
	 */
	protected static $preview_transient_key;

	/**
	 * Preview PDF Transient Key.
	 *
	 * @var string
	 */
	protected static $preview_pdf_transient_key;

	/**
	 * Transient Expiry Duration.
	 *
	 * @var int
	 */
	protected static $transient_expiry = 60 * 60;

	/**
	 * Image Types mapping.
	 *
	 * @var array
	 */
	protected static $img_types = array(
		1  => 'gif',
		2  => 'jpeg',
		3  => 'png',
		15 => 'wbmp',
	);

	/**
	 * Init Function.
	 *
	 * @param array $plugin_info Plugin Info.
	 * @return void
	 */
	public static function init( $plugin_info ) {
		self::$plugin_info               = $plugin_info;
		self::$preview_transient_key     = self::$plugin_info['name'] . '-watermark-img-transient-key';
		self::$preview_pdf_transient_key = self::$plugin_info['name'] . '-watermark-pdf-transient-key';
		self::hooks();
	}

	/**
	 * Base Hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'delete_expired_transients', array( get_called_class(), 'remove_preview_image_file' ), 9 );
	}

	/**
	 * Remove temp preview File when [ deleting expired transients | Deactivate ].
	 *
	 * @param boolean $force_delete  Whether to force delete the preview file or check for expiration.
	 *
	 * @return void
	 */
	public static function remove_preview_image_file( $force_delete = false ) {
		$uploads = wp_get_upload_dir();

		// Preview PDF.
		$transient_option  = '_transient_' . self::$preview_pdf_transient_key;
		$transient_timeout = '_transient_timeout_' . self::$preview_pdf_transient_key;
		$timeout           = get_option( $transient_timeout );
		$preview_img_arr   = get_option( $transient_option );
		if ( $force_delete || ( false !== $timeout && $timeout < time() ) ) {
			if ( ! empty( $preview_img_arr ) && is_array( $preview_img_arr ) && ! empty( $preview_img_arr['relative_path'] ) ) {
				$preview_path = untrailingslashit( $uploads['basedir'] ) . $preview_img_arr['relative_path'];
				@unlink( $preview_path );
				delete_option( $transient_option );
				delete_option( $transient_timeout );
			}
		}
	}

	/**
	 * Save Image into FILe.
	 *
	 * @param string $img Image String.
	 * @param array  $image_details  Image details Array.
	 * @return true|\WP_Error
	 */
	public static function save_watermarked_pdf( $img, $img_details ) {
		$time    = current_time( 'mysql' );
		$uploads = wp_upload_dir( $time );

		// 1) Put the imge stream string into the file path.
		$watermarked = file_put_contents( $img_details['path'], $img );
		if ( false === $watermarked ) {
			return new \WP_Error(
				self::$plugin_info['name'] . '-save-watermarked-image-error',
				esc_html__( 'Failed to create watermarked image!', 'watermark-pdf' )
			);
		}

		// 2) Set correct file permissions.
		$stat  = stat( dirname( $img_details['path'] ) );
		$perms = $stat['mode'] & 0000666; // Same permissions as parent folder, strip off the executable bits.
		chmod( $img_details['path'], $perms );

		return true;
	}

	/**
	 * Repeat Watermark.
	 *
	 * @param object $img_resource       Image Resource.
	 * @param object $watermark_resource Watermark Resource.
	 * @param array  $watermark          Watermark Details Array.
	 * @return void
	 */
	protected function repeat_watermark( &$img_resource, &$watermark_resource, $watermark, $x, $y, $type = 'image' ) {
		// 1) Check if the watermark is repeated.
		if ( ! $watermark['isRepeat'] || empty( $watermark['repeatAxis'] ) ) {
			return;
		}
		$base_x        = $x;
		$base_y        = $y;
		$x_axis_offset = absint( $watermark['repeatXAxisOffset'] );
		$y_axis_offset = absint( $watermark['repeatYAxisOffset'] );
		if ( 'x' === $watermark['repeatAxis'] ) {
			if ( $x_axis_offset <= 0 ) {
				return;
			}
			$x += $x_axis_offset;
			while ( $x < $this->img['width'] ) {
				$this->draw_watermark_on_image( $img_resource, $watermark_resource, $watermark, $x, $y, $type );
				$x += $x_axis_offset;
			}
		} elseif ( 'y' === $watermark['repeatAxis'] ) {
			if ( $y_axis_offset <= 0 ) {
				return;
			}
			$y    += $y_axis_offset;
			$y_top = intval( $y - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
			while ( $y < $this->img['height'] || $y_top < $this->img['height'] ) {
				$this->draw_watermark_on_image( $img_resource, $watermark_resource, $watermark, $x, $y, $type );
				$y    += $y_axis_offset;
				$y_top = intval( $y - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
			}
		} elseif ( 'diagonal' === $watermark['repeatAxis'] ) {
			if ( $y_axis_offset <= 0 && $x_axis_offset <= 0 ) {
				return;
			}
			$y_top = intval( $y - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
			while ( ( $y < $this->img['height'] || $y_top < $this->img['height'] ) && ( $x < $this->img['width'] ) ) {
				$x += $x_axis_offset;
				$y += $y_axis_offset;
				$this->draw_watermark_on_image( $img_resource, $watermark_resource, $watermark, $x, $y, $type );
			}
		} elseif ( 'both' === $watermark['repeatAxis'] ) {
			if ( $x_axis_offset > 0 ) {
				$x += $x_axis_offset;
				while ( $x < $this->img['width'] ) {
					$this->draw_watermark_on_image( $img_resource, $watermark_resource, $watermark, $x, $y, $type );
					$x += $x_axis_offset;
				}
				$x = $base_x;
			}
			if ( $y_axis_offset > 0 ) {
				$y    += $y_axis_offset;
				$y_top = intval( $y - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
				while ( $y < $this->img['height'] || $y_top < $this->img['height'] ) {
					$this->draw_watermark_on_image( $img_resource, $watermark_resource, $watermark, $x, $y, $type );
					$y    += $y_axis_offset;
					$y_top = intval( $y - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
				}
			}
		} elseif ( 'full' === $watermark['repeatAxis'] ) {
			if ( $x_axis_offset <= 0 || $y_axis_offset <= 0 ) {
				return;
			}
			$x    += $x_axis_offset;
			$y_top = intval( $y - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
			while ( $y < $this->img['height'] || $y_top < $this->img['height'] ) {
				while ( $x < $this->img['width'] ) {
					$this->draw_watermark_on_image( $img_resource, $watermark_resource, $watermark, $x, $y, $type );
					$x += $x_axis_offset;
				}
				$x     = $base_x;
				$y    += $y_axis_offset;
				$y_top = intval( $y - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
			}
		}
	}

	/**
	 * Scale Text Watermarks based on the sub-size.
	 *
	 * @param array $watermark
	 * @return array
	 */
	protected function scale_text_watermarks( $watermark ) {
		if ( ! empty( $this->img['width_ratio'] ) || ! empty( $this->img['height_ratio'] ) ) {
			$new_fontsize = intval( $this->img['width_ratio'] * $watermark['styles']['font']['fontSize'] );
			$box          = imagettfbbox( $new_fontsize, 0, self::get_font_path( $watermark['styles']['font']['fontFamily'] ), $watermark['text'] );

			if ( is_array( $box ) ) {
				$x_coords                                = array( $box[0], $box[2], $box[4], $box[6] );
				$y_coords                                = array( $box[1], $box[3], $box[5], $box[7] );
				$box_width                               = max( $x_coords ) - min( $x_coords );
				$box_height                              = max( $y_coords ) - min( $y_coords );
				$base_x                                  = abs( min( $x_coords ) );
				$base_y                                  = abs( max( $y_coords ) );
				$watermark['styles']['font']['fontSize'] = $new_fontsize;
				$watermark['width']                      = $box_width;
				$watermark['height']                     = $box_height;
				$watermark['baselineOffset']             = $base_y;
				$watermark['exactWidth']                 = $box_width - ( 2 * $base_x );
			}
		}
		return $watermark;
	}

	/**
	 * Clear Watermarks Resources.
	 *
	 * @return void
	 */
	public function clear_watermarks() {
		foreach ( $this->watermarks as &$watermark ) {
			if ( 'image' === $watermark['type'] && ! empty( $watermark['resource'] ) ) {
				imagedestroy( $watermark['resource'] );
				unset( $watermark['resource'] );
			}
		}
	}

	/**
	 * Check if PDF is supported.
	 *
	 * @return boolean
	 */
	public static function is_pdf_supported() {
		return ( self::imagick_installed() && GhostscriptConverter::is_gs_installed( false ) );
	}

	/**
	 * Check if imagick is installed.
	 *
	 * @return boolean
	 */
	public static function imagick_installed() {
		return ( extension_loaded( 'imagick' ) || extension_loaded( 'Imagick' ) );
	}

}
