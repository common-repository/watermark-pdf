<?php

namespace GPLSCore\GPLS_PLUGIN_WMPDF;

use GPLSCore\GPLS_PLUGIN_WMPDF\PDF_Watermark;

/**
 * Helper Traits.
 */
trait Helpers {

	/**
	 * Loader HTML Code.
	 *
	 * @return mixed
	 */
	public static function loader_html( $prefix = null, $pre_hide = true, $full_class = false, $_return = false, $small_spinner = false ) {
		if ( $_return ) {
			ob_start();
		}
		?>
		<div style="width:100%;height:100%;position:absolute;left:0;top:0;z-index:1000;<?php echo esc_attr( $pre_hide ? 'display :none;' : '' ); ?>" class="<?php echo esc_attr( ! empty( $full_class ) ? $full_class : ( ! empty( $prefix ) ? $prefix . '-loader' : '' ) ); ?>">
			<div style="position:sticky;top:50%;text-align:center;display:flex;justify-content:center;">
				<img src="<?php echo esc_url_raw( admin_url( 'images/' . ( $small_spinner ? 'spinner.gif' : 'spinner-2x.gif' ) ) ); ?>"  />
			</div>
			<div style="position:absolute;display:block;opacity:0.5;width:100%;height:100%;background-color:#EEE;top:0;left:0;bottom:0;right:0;" class="overlay position-absolute d-block w-100 h-100 bg-light opacity-50"></div>
		</div>
		<?php
		if ( $_return ) {
			return ob_get_clean();
		}
	}

	/**
	 * Get Top and Left Point after rotating watermark around center.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @param int $rotationAngle
	 * @return array
	 */
	public function top_left_after_rotation_around_center( $watermark, $rotation_angle ) {
		$center_x             = $watermark['absLeft'];
		$center_y             = $watermark['absTop'];
		$width                = $watermark['width'];
		$height               = $watermark['height'];
		$rad                  = sqrt( $width * $width + $height * $height ) / 2;
		$rect_deg             = atan2( $height, $width );
		$rot_deg              = $rotation_angle * M_PI / 180;
		$top_left_x           = $rad * cos( $rot_deg + ( $rect_deg ) );
		$top_left_y           = $rad * sin( $rot_deg + ( $rect_deg ) );
		$watermark['absLeft'] = $center_x - $top_left_x;
		$watermark['absTop']  = $center_y - $top_left_y;

		return $watermark;
	}

	/**
	 * Calculate Watermark Position based on the position Spot and offset.
	 *
	 * @param array $watmark
	 * @param array $img
	 * @return array|false
	 */
	public function calculate_watermark_position( $watermark, $img ) {
		$box_mapping   = PDF_Watermark::$spots_mapping[ $watermark['positionSpot'] ];
		$square_width  = round( $img['width'] / 3 );
		$square_height = round( $img['height'] / 3 );

		if ( 'pixel' === $watermark['positionType'] ) {
			$pos = array(
				'left' => round( $box_mapping['left'] * $square_width ) + intval( ! empty( $img['width_ratio'] ) ? round( $img['width_ratio'] * $watermark['absLeft'] ) : $watermark['absLeft'] ),
				'top'  => round( $box_mapping['top'] * $square_height ) + intval( ! empty( $img['height_ratio'] ) ? round( $img['height_ratio'] * $watermark['absTop'] ) : $watermark['absTop'] ),
			);
		} elseif ( 'percent' === $watermark['positionType'] ) {
			$pos = array(
				'left' => round( $box_mapping['left'] * $square_width ) + intval( intval( $square_width ) * floatval( $watermark['leftPercent'] ) / 100 ),
				'top'  => round( $box_mapping['top'] * $square_height ) + intval( intval( $square_height ) * floatval( $watermark['topPercent'] ) / 100 ),
			);
		}

		if ( $pos ) {

			$pos['baseLeft'] = $pos['left'];
			$pos['baseTop']  = $pos['top'];

			if ( ( 'image' === $watermark['type'] ) && ! empty( $watermark['centerOffset'] ) && ( 'true' === $watermark['centerOffset'] || 'yes' === $watermark['centerOffset'] ) ) {
				$pos['left'] = $pos['left'] - round( $watermark['orig_width'] / 2 );
				$pos['top']  = $pos['top'] - round( $watermark['orig_height'] / 2 );
			}

			return $pos;
		}
		return false;
	}

	/**
	 * Get the Text position based on the text rotation.
	 *
	 * @param array $watermark
	 * @param float $degree
	 * @return array
	 */
	public function text_watermark_position_from_rotation( $watermark, $degree ) {
		$y_spacing         = round( $watermark['baselineOffset'] );
		$x_spacing         = round( ( $watermark['width'] - $watermark['exactWidth'] ) / 2 );
		$degree_in_radians = $degree * M_PI / 180;
		if ( 0 === $degree ) {
			$h_spacing = abs( $x_spacing );
			$v_spacing = abs( $y_spacing );
		} elseif ( 90 === $degree ) {
			$h_spacing = abs( $y_spacing );
			$v_spacing = abs( $x_spacing );
		} elseif ( 270 === $degree ) {
			$h_spacing = - abs( $y_spacing );
			$v_spacing = abs( $x_spacing );
		} elseif ( $degree > 0.0 && $degree < 90.0 ) {
			$h_spacing = round( abs( ( $y_spacing - ( $x_spacing / tan( $degree_in_radians ) ) ) * sin( $degree_in_radians ) ) );
			$diag2     = pow( $x_spacing, 2 ) + pow( $y_spacing, 2 );
			$v_spacing = round( sqrt( absint( $diag2 - pow( $h_spacing, 2 ) ) ) );
		} elseif ( $degree > 270 ) {
			$abs_degree        = 360 - $degree;
			$degree_in_radians = $abs_degree * M_PI / 180;
			$diag2             = pow( $x_spacing, 2 ) + pow( $y_spacing, 2 );
			$h_spacing         = - round( abs( absint( $y_spacing - ( $x_spacing / tan( $degree_in_radians ) ) ) * sin( $degree_in_radians ) ) );
			$v_spacing         = sqrt( absint( $diag2 - pow( $h_spacing, 2 ) ) );
		} else {
			$h_spacing = $x_spacing;
			$v_spacing = $y_spacing;
		}

		return array( $h_spacing, $v_spacing );
	}

		/**
	 * Setup Image Watermark Position based on rotation.
	 *
	 * @param array $watermark Watermark Details.
	 * @return array Watermark details array.
	 */
	public function image_watermark_position_from_rotation( $watermark, $degree ) {
		$degree_in_radians = $degree * M_PI / 180;
		if ( 90.0 === $degree ) {
			$watermark['absLeft'] -= $watermark['height'];
		} elseif ( 180.0 === $degree ) {
			$watermark['absLeft'] -= $watermark['width'];
			$watermark['absTop']  -= $watermark['height'];
		} elseif ( 270.0 === $degree ) {
			$watermark['absTop'] -= $watermark['width'];
		} elseif ( $degree > 0.0 && $degree < 90.0 ) {
			$watermark['absLeft'] -= round( $watermark['height'] * sin( $degree_in_radians ) );
		} elseif ( $degree > 180.0 && $degree < 270.0 ) {
			$degree               -= 180;
			$degree_in_radians     = $degree * M_PI / 180;
			$watermark['absLeft'] -= round( $watermark['width'] * cos( $degree_in_radians ) );
			$watermark['absTop']  -= round( ( $watermark['width'] * sin( $degree_in_radians ) ) + ( $watermark['height'] * cos( $degree_in_radians ) ) );
		} elseif ( $degree > 90.0 && $degree < 180.0 ) {
			$watermark['absTop']  += round( $watermark['height'] * cos( $degree_in_radians ) );
			$watermark['absLeft'] += round( - ( $watermark['height'] * sin( $degree_in_radians ) ) + ( $watermark['width'] * cos( $degree_in_radians ) ) );
		} elseif ( $degree > 270.0 && $degree < 360.0 ) {
			$watermark['absTop'] += round( $watermark['width'] * sin( $degree_in_radians ) );
		}
		if ( ! empty( $watermark['centerOffset'] ) && ( 'true' === $watermark['centerOffset'] || 'yes' === $watermark['centerOffset'] ) ) {
			$watermark['absLeft'] += round( $watermark['width'] / 2 );
			$watermark['absTop']  += round( $watermark['height'] / 2 );
		}
		return $watermark;
	}

	/**
	 * Convert Image URL to PATH.
	 *
	 * @param string $img_url
	 * @return string
	 */
	public static function convert_url_to_path( $img_url ) {
		$uploads  = wp_get_upload_dir();
		$img_path = str_replace( $uploads['baseurl'], $uploads['basedir'], $img_url );
		return $img_path;
	}

}
