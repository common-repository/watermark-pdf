<?php
namespace GPLSCore\GPLS_PLUGIN_WMPDF;

/**
 * PDF Class.
 */
class PDF {

	/**
	 * Plugin Info Array.
	 *
	 * @var array
	 */
	private static $plugin_info;

	/**
	 * Supported Image Types.
	 *
	 * @var array
	 */
	private static $supported_types = array(
		2  => 'jpeg',
		3  => 'png',
		15 => 'wbmp',
	);

	/**
	 * Initialize Image Class.
	 *
	 * @param array $plugin_info Plugin Info Array.
	 * @return void
	 */
	public static function init( $plugin_info ) {
		self::$plugin_info = $plugin_info;
	}

	/**
	 * Get Image Path - Filename - size - ext details.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array|\WP_Error
	 */
	public static function get_image_file_details( $attachment_id, $img_size_name = 'original' ) {
		$img_details       = array();
		$uploads           = wp_get_upload_dir();
		$attachment_obj    = get_post( $attachment_id );
		$img_meta          = wp_get_attachment_metadata( $attachment_id );
		$img_relative_path = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$img_file_name     = wp_basename( $img_relative_path );

		// Check if the image is scaled, get the original image.
		if ( ! empty( $img_meta['original_image'] ) && ( $img_file_name !== $img_meta['original_image'] ) ) {
			$original_img_filename      = $img_meta['original_image'];
			$original_img_relative_path = str_replace( $img_file_name, $img_meta['original_image'], $img_relative_path );
			$original_full_path         = trailingslashit( $uploads['basedir'] ) . $original_img_relative_path;
			// check if the original exists.
			if ( @file_exists( $original_full_path ) ) {
				$img_details['scaled_path'] = get_attached_file( $attachment_id );
				$img_relative_path          = $original_img_relative_path;
				$img_file_name              = $original_img_filename;
			}
		}

		$img_relative_subdirectory_path = str_replace( $img_file_name, '', $img_relative_path );
		if ( 'original' === $img_size_name ) {
			$img_full_path = trailingslashit( $uploads['basedir'] ) . $img_relative_path;
			$img_full_url  = trailingslashit( $uploads['baseurl'] ) . $img_relative_path;
			$filetype      = wp_check_filetype( $img_file_name );

		} elseif ( 'original' !== $img_size_name && ! empty( $img_meta['sizes'] ) && ! empty( $img_meta['sizes'][ $img_size_name ] ) ) {
			$size_file_name    = $img_meta['sizes'][ $img_size_name ]['file'];
			$img_relative_path = str_replace( $img_file_name, $size_file_name, $img_relative_path );
			$img_file_name     = wp_basename( $img_relative_path );
			$img_full_path     = trailingslashit( $uploads['basedir'] ) . $img_relative_path;
			$img_full_url      = trailingslashit( $uploads['baseurl'] ) . $img_relative_path;
			$filetype          = wp_check_filetype( $img_file_name );
		} else {
			return new \WP_Error(
				self::$plugin_info['name'] . '-attachment-subsize-not-found',
				sprintf( esc_html__( 'Image file sub-size: %s not found!', 'watermark-pdf' ), $img_size_name )
			);
		}

		if ( ! file_exists( $img_full_path ) ) {
			return new \WP_Error(
				self::$plugin_info['name'] . '-attachment-file-not-found',
				sprintf( esc_html__( 'image file %s not found!', 'watermark-pdf' ), $img_file_name )
			);
		}

		if ( ( 0 === strpos( $attachment_obj->post_mime_type, 'image/' ) ) ) {
			if ( 'original' === $img_size_name ) {
				$img_size              = getimagesize( $img_full_path );
				$img_details['width']  = $img_size[0];
				$img_details['height'] = $img_size[1];
			} else {
				$img_details['width']  = ( 'original' === $img_size_name ) ? $img_meta['width'] : $img_meta['sizes'][ $img_size_name ]['width'];
				$img_details['height'] = ( 'original' === $img_size_name ) ? $img_meta['height'] : $img_meta['sizes'][ $img_size_name ]['height'];
			}

			if ( 'original' !== $img_size_name ) {
				$img_details['width_ratio']  = number_format( floatval( $img_details['width'] / $img_meta['width'] ), 2 );
				$img_details['height_ratio'] = number_format( floatval( $img_details['height'] / $img_meta['height'] ), 2 );
				$img_details['width_ratio']  = ( $img_details['width_ratio'] < 0.10 ) ? 0.10 : $img_details['width_ratio'];
				$img_details['height_ratio'] = ( $img_details['height_ratio'] < 0.10 ) ? 0.10 : $img_details['height_ratio'];
			}
		}

		$img_details['attachment_id']          = $attachment_id;
		$img_details['size_name']              = $img_size_name;
		$img_details['path']                   = $img_full_path;
		$img_details['url']                    = $img_full_url;
		$img_details['filename']               = $img_file_name;
		$img_details['relative_path']          = $img_relative_subdirectory_path;
		$img_details['full_path_without_name'] = trailingslashit( dirname( $img_full_path ) );
		$img_details['ext']                    = $filetype['ext'];
		$img_details['mime_type']              = $filetype['type'];
		return $img_details;
	}

	/**
	 * Get Image File Details Direct from the file itself.
	 *
	 * @param string $image_full_path Image PATH.
	 * @return array
	 */
	public static function get_image_file_details_direct( $image_full_path ) {
		$img_details           = array();
		$uploads               = wp_get_upload_dir();
		$img_size              = getimagesize( $image_full_path );
		$filename              = wp_basename( $image_full_path );
		$img_path_without_name = dirname( $image_full_path );
		$relative_path         = ltrim( str_replace( $uploads['basedir'], '', $img_path_without_name ), '/' );
		$filetype              = wp_check_filetype( $filename );

		$img_details['width']                  = $img_size[0];
		$img_details['height']                 = $img_size[1];
		$img_details['path']                   = $image_full_path;
		$img_details['filename']               = $filename;
		$img_details['relative_path']          = $relative_path;
		$img_details['full_path_without_name'] = $img_path_without_name;
		$img_details['ext']                    = $filetype['ext'];
		$img_details['mime_type']              = $filetype['type'];

		return $img_details;
	}

	/**
	 * Get PDF File Details Direct from the file itself.
	 *
	 * @param string $pdf_full_path Image PATH.
	 * @return array
	 */
	public static function get_pdf_file_details_direct( $pdf_full_path ) {
		$pdf_details           = array();
		$uploads               = wp_get_upload_dir();
		$filename              = wp_basename( $pdf_full_path );
		$pdf_path_without_name = dirname( $pdf_full_path );
		$relative_path         = ltrim( str_replace( $uploads['basedir'], '', $pdf_path_without_name ), '/' );
		$filetype              = wp_check_filetype( $filename );

		$pdf_details['path']                   = $pdf_full_path;
		$pdf_details['filename']               = $filename;
		$pdf_details['relative_path']          = $relative_path;
		$pdf_details['full_path_without_name'] = $pdf_path_without_name;
		$pdf_details['ext']                    = $filetype['ext'];
		$pdf_details['mime_type']              = $filetype['type'];

		return $pdf_details;
	}

}
