<?php
namespace GPLSCore\GPLS_PLUGIN_WMPDF;

use Exception;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverterCommand;
use Xthiago\PDFVersionConverter\Guesser\RegexGuesser;
use Symfony\Component\Filesystem\Filesystem;
use GPLSCore\GPLS_PLUGIN_WMPDF\Helpers;
use setasign\Fpdi\Fpdi;
use function setasign\Fpdf\MakeFont;

/**
 * PDF Watermark.
 */
class FPDI_Wrapper extends Fpdi {

	use Helpers;

	/**
	 * Plugin Info.
	 *
	 * @var array
	 */
	private $plugin_info;

	/**
	 * PDF Pages Count.
	 *
	 * @var int
	 */
	private $pages_count;

	/**
	 * PDF Path.
	 *
	 * @var string
	 */
	private $pdf_path;

	/**
	 * PDF Path.
	 *
	 * @var string
	 */
	private $watermark_type;

	/**
	 * Watemark angle.
	 *
	 * @var integer
	 */
	private $angle = 0;

	/**
	 * Width in pixels.
	 *
	 * @var int
	 */
	private $w_in_pixels;

	/**
	 * Height in pixels.
	 *
	 * @var int
	 */
	private $h_in_pixels;

	/**
	 * PDF Path.
	 *
	 * @var array
	 */
	private $watermarks = array();

	/**
	 * Alpha Ext States.
	 *
	 * @var array
	 */
	protected $extgstates = array();

	/**
	 * Current Applying watermark.
	 *
	 * @var array
	 */
	private $current_watermark = array();

	/**
	 * PDF file original Version.
	 *
	 * @var string
	 */
	private $original_version = null;

	/**
	 * Is Version converted.
	 *
	 * @var boolean
	 */
	private $is_converted = false;

	/**
	 * Constructor.
	 *
	 * @param string $pdf_path
	 * @param string $watermark_type
	 * @param array  $watermark_details
	 */
	public function __construct( $plugin_info, $pdf_path, $watermarks = array(), $force_convert = false ) {
		$this->plugin_info = $plugin_info;
		$this->pdf_path    = $pdf_path;
		$this->watermarks  = $watermarks;
		$this->fontpath    = PDF_Watermark::get_fonts_folder_path();

		parent::__construct( 'p', 'pt', 'A4' );

		$this->prepare_pdf( $force_convert );
		$this->setup();
	}

	/**
	 * Set PDF Watermarks.
	 *
	 * @param array $watermarks
	 * @return void
	 */
	public function set_watermarks( $watermarks ) {
		$this->watermarks = $watermarks;
	}

	/**
	 * Setup the PDF.
	 *
	 * @return void
	 */
	private function setup() {
		$this->pages_count = $this->setSourceFile( $this->pdf_path );
		$this->setup_dimensions_in_pixels();
	}

	/**
	 * Adjust the pdf verison - encryption etc.
	 *
	 * @return void
	 */
	private function prepare_pdf( $force_convert = false ) {
		$this->handle_pdf_version( $force_convert );
	}

	/**
	 * Downgrade the PDF version if more than 1.4.
	 *
	 * @return void
	 */
	private function handle_pdf_version( $force_convert ) {
		$guesser                = new RegexGuesser();
		$this->original_version = $guesser->guess( $this->pdf_path );

		if ( is_null( $this->original_version ) ) {
			throw new Exception( esc_html__( 'Failed to detect the PDF version.', 'watermark-pdf' ) );
		}

		if ( $force_convert ) {
			$this->is_converted = true;
			$this->convert_pdf_version( '1.4' );
		} else {
			// If more than 1.4.
			if ( version_compare( $this->original_version, '1.4' ) > 0 ) {
				$this->is_converted = true;
				$this->convert_pdf_version( '1.4' );
			}
		}
	}

	/**
	 * Clean Up Streams.
	 *
	 * @return void
	 */
	public function clean() {
		$this->cleanUp();
	}

	/**
	 * Convert PDF Version.
	 *
	 * @param string $target_version
	 * @return void
	 */
	private function convert_pdf_version( $target_version, $dest = null ) {
		try {
			$command       = new GhostscriptConverterCommand();
			$converter     = new GhostscriptConverter( $command, new Filesystem() );
			$pdf_path      = $dest ?? $this->pdf_path;
			$temp_pdf_path = trailingslashit( dirname( $pdf_path ) ) . wp_unique_filename( dirname( $pdf_path ), wp_basename( $pdf_path ) );
	
			$converter->convert( $pdf_path, $temp_pdf_path, $target_version );
	
			@copy( $temp_pdf_path, $pdf_path );
	
			@unlink( $temp_pdf_path );
		} catch ( \Exception $e ) {
		}
	}

	/**
	 * Revert the original version of pdf.
	 *
	 * @return void
	 */
	public function revert_version( $dest = null ) {
		if ( $this->is_converted ) {
			if ( $dest ) {
				$this->convert_pdf_version( $this->original_version, $dest );
			}
			$this->convert_pdf_version( $this->original_version, $this->pdf_path );
			$this->is_converted = false;
		}
	}

	/**
	 * Convert the dimensions from points to pixels.
	 */
	private function setup_dimensions_in_pixels() {
		// Get PDF First Page.
		$pdf_page_template = $this->importPage( 1 );
		$size              = $this->getTemplateSize( $pdf_page_template );
		$this->w_in_pixels = round( $size['width'] * ( 1 / 0.75 ) );
		$this->h_in_pixels = round( $size['height'] * ( 1 / 0.75 ) );
	}

	/**
	 * GET PDF Dimensions.
	 *
	 * @return array
	 */
	public function get_pdf_dimensions() {
		return array(
			'width'  => $this->w_in_pixels,
			'height' => $this->h_in_pixels,
		);
	}

	/**
	 * PDF Page Header.
	 *
	 * @return void
	 */
	public function Header() {
		if ( 'text' === $this->watermark_type ) {
			$this->setup_text_watemark();
		} elseif ( 'image' === $this->watermark_type ) {
			$this->setup_image_watemark();
		}
	}

	/**
	 * Filter color string to rgb values.
	 *
	 * @param string $color
	 * @return array
	 */
	private function filter_text_color( $color ) {
		$color = str_replace( '#', '', $color );
		$rgb   = array_map( 'hexdec', str_split( $color, 2 ) );
		return $rgb;
	}

	/**
	 * Check if the font family .php and .z files exist.
	 *
	 * @param string $font_family
	 * @return boolean
	 */
	private function is_font_files_ready( $font_family ) {
		return ( file_exists( trailingslashit( $this->fontpath ) . $font_family . '.php' ) && file_exists( trailingslashit( $this->fontpath ) . $font_family . '.z' ) );
	}

	/**
	 * Setup Text watermark before Adding page for PDF.
	 *
	 * @return void
	 */
	private function setup_text_watemark() {
		if ( ! $this->is_font_files_ready( $this->current_watermark['styles']['font']['fontFamily'] ) ) {
			MakeFont( PDF_Watermark::get_font_path( $this->current_watermark['styles']['font']['fontFamily'] ), 'cp1252', $this->fontpath, true, true );
		}
		$this->AddFont( $this->current_watermark['styles']['font']['fontFamily'], '' );
		$font_size_in_points = $this->current_watermark['styles']['font']['fontSize'] * 0.75;
		$this->SetFont( $this->current_watermark['styles']['font']['fontFamily'], '', $font_size_in_points );

		$rgb = $this->filter_text_color( $this->current_watermark['styles']['font']['color'] );
		$this->SetTextColor( $rgb[0], $rgb[1], $rgb[2] );
	}

	/**
	 * Setup image watermark before Adding page for PDF.
	 *
	 * @return void
	 */
	private function setup_image_watemark() {

	}

	/**
	 * Apply Watermarks on PDF.
	 *
	 * @return void
	 */
	public function apply_watermarks() {
		// Loop over the PDF pages and apply the text watermark.
		for ( $index = 1; $index <= $this->pages_count; $index++ ) {

			// Get PDF Page template.
			$pdf_page_template = $this->importPage( $index );

			// Get template Dimensions.
			$pdf_page_template_dimension = $this->getTemplateSize( $pdf_page_template );

			$orientation = ( $pdf_page_template_dimension['height'] > $pdf_page_template_dimension['width'] ) ? 'P' : 'L';
			if ( 'P' === $orientation ) {
				$this->AddPage( $orientation, array( $pdf_page_template_dimension['width'], $pdf_page_template_dimension['height'] ) );
			} else {
				$this->AddPage( $orientation, array( $pdf_page_template_dimension['height'], $pdf_page_template_dimension['width'] ) );
			}

			// Use imported Page as a template.
			$this->useTemplate( $pdf_page_template, 0, 0, null, null, false );

			foreach ( $this->watermarks as $watermark ) {
				$this->watermark_type    = $watermark['type'];
				$this->current_watermark = $watermark;
				if ( 'text' === $watermark['type'] ) {
					$this->add_text_watermark( $watermark );
				} elseif ( 'image' === $watermark['type'] ) {
					$this->add_image_watermark( $watermark );
				}
			}
		}
	}

	/**
	 * Draw Watermark on PDF.
	 *
	 * @param array $watermark
	 * @return void
	 */
	private function draw_watermark_on_pdf( $watermark ) {

		if ( 'image' === $watermark['type'] ) {

			$this->set_alpha( floatval( $watermark['styles']['opacity'] ) );

			if ( ! empty( $watermark['centerOffset'] ) && ( 'true' === $watermark['centerOffset'] || 'yes' === $watermark['centerOffset'] ) ) {
				$watermark['rotateLeft'] = $watermark['absLeft'] + $watermark['width'] / 2;
				$watermark['rotateTop']  = $watermark['absTop'] + $watermark['height'] / 2;
			} else {
				$watermark['rotateLeft'] = $watermark['absLeft'];
				$watermark['rotateTop']  = $watermark['absTop'];
			}

			$this->rotate_text_around_center( -$watermark['styles']['degree'], $watermark['rotateLeft'], $watermark['rotateTop'] );

			$this->Image( $watermark['path'], ceil( $watermark['absLeft'] ), ceil( $watermark['absTop'] ), $watermark['width'], $watermark['height'] );

		} elseif ( 'text' === $watermark['type'] ) {

			$this->set_alpha( floatval( $watermark['styles']['opacity'] ) );

			$this->setup_text_watemark();

			$this->rotate_text_around_center( -$watermark['styles']['degree'], $watermark['absLeft'], $watermark['absTop'] );

			$this->Text( ceil( $watermark['absLeft'] ), ceil( $watermark['absTop'] ), $watermark['text'] );

		}

		$this->reset_after_watermark( $watermark );
	}

	/**
	 * Apply Text Watermark on PDF.
	 *
	 * @return void
	 */
	public function add_text_watermark( $watermark ) {
		// Set the watermark Details and styles.
		$watermark['text']             = mb_convert_encoding( $watermark['text'], 'ISO-8859-1', 'UTF-8' );
		$watermark                     = $this->calculate_watermark_dimension_on_pdf( $watermark, 'text' );
		$degree                        = (int) ( $watermark['styles']['degree'] );
		$degree                        = ( $degree < 0 ? ( 360 + $degree ) : $degree );
		$degree_in_radians             = round( $degree * M_PI / 180, 2 );
		$watermark['styles']['degree'] = $degree;

		$pdf_template_dim = array(
			'width'  => $this->w_in_pixels,
			'height' => $this->h_in_pixels,
		);

		$position              = $this->calculate_watermark_position( $watermark, $pdf_template_dim );
		$watermark['absLeft']  = $position['left'];
		$watermark['absTop']   = $position['top'];
		$watermark['baseLeft'] = $position['baseLeft'];
		$watermark['baseTop']  = $position['baseTop'];

		if ( ! empty( $watermark['centerOffset'] ) && ( 'true' === $watermark['centerOffset'] || 'yes' === $watermark['centerOffset'] ) ) {
			$watermark = $this->top_left_after_rotation_around_center( $watermark, $degree );
		}

		$watermark['botLeft']          = round( $watermark['absLeft'] ) - round( ( $watermark['height'] ) * sin( $degree_in_radians ) );
		$watermark['botTop']           = round( $watermark['absTop'] ) + round( ( $watermark['height'] ) * cos( $degree_in_radians ) );
		list( $h_spacing, $v_spacing ) = $this->text_watermark_position_from_rotation( $watermark, $degree );
		$font_baseline_left            = intval( $watermark['botLeft'] ) + $h_spacing + 2;
		$font_baseline_top             = intval( $watermark['botTop'] ) - $v_spacing;

		$watermark['absLeft'] = round( $font_baseline_left * 0.75 );
		$watermark['absTop']  = round( $font_baseline_top * 0.75 );

		$this->draw_watermark_on_pdf( $watermark );

		$this->repeat_watermark( $watermark );
	}

	/**
	 * Apply Image Watemrark on PDF.
	 *
	 * @return void
	 */
	public function add_image_watermark( $watermark ) {
		// Set the watermark Details and styles.
		$watermark         = $this->calculate_watermark_dimension_on_pdf( $watermark );
		$watermark_path    = self::convert_url_to_path( $watermark['url'] );
		$watermark['path'] = $watermark_path;
		$degree            = (float) round( $watermark['styles']['degree'] );
		$degree            = ( $degree < 0 ? ( 360 + $degree ) : $degree );
		$pdf_template_dim  = array(
			'width'  => $this->w_in_pixels,
			'height' => $this->h_in_pixels,
		);

		$position = $this->calculate_watermark_position( $watermark, $pdf_template_dim );

		$watermark['absLeft']  = round( $position['left'] * 0.75 );
		$watermark['absTop']   = round( $position['top'] * 0.75 );
		$watermark['baseLeft'] = round( $position['baseLeft'] * 0.75 );
		$watermark['baseTop']  = round( $position['baseTop'] * 0.75 );

		$this->draw_watermark_on_pdf( $watermark );

		$this->repeat_watermark( $watermark );
	}

	/**
	 * Repeat Watermark.
	 *
	 * @param array  $watermark
	 * @param string $type
	 * @return void
	 */
	private function repeat_watermark( $watermark ) {
		// 1) Check if the watermark is repeated.
		if ( ! $watermark['isRepeat'] || empty( $watermark['repeatAxis'] ) ) {
			return;
		}
		// PDF dimensions in pixels.
		$pdf_width  = $this->w;
		$pdf_height = $this->h;

		$base_x        = $watermark['absLeft'];
		$base_y        = $watermark['absTop'];
		$x_axis_offset = absint( $watermark['repeatXAxisOffset'] ) * 0.75;
		$y_axis_offset = absint( $watermark['repeatYAxisOffset'] ) * 0.75;
		if ( 'x' === $watermark['repeatAxis'] ) {
			if ( $x_axis_offset <= 0 ) {
				return;
			}
			$watermark['absLeft'] += $x_axis_offset;
			while ( $watermark['absLeft'] < $pdf_width ) {
				$this->draw_watermark_on_pdf( $watermark );
				$watermark['absLeft'] += $x_axis_offset;
			}
		} elseif ( 'y' === $watermark['repeatAxis'] ) {
			if ( $y_axis_offset <= 0 ) {
				return;
			}
			$watermark['absTop'] += $y_axis_offset;
			$y_top                = intval( $watermark['absTop'] - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
			while ( $watermark['absTop'] < $pdf_height || $y_top < $pdf_height ) {
				$this->draw_watermark_on_pdf( $watermark );
				$watermark['absTop'] += $y_axis_offset;
				$y_top                = intval( $watermark['absTop'] - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
			}
		} elseif ( 'diagonal' === $watermark['repeatAxis'] ) {
			if ( $y_axis_offset <= 0 && $x_axis_offset <= 0 ) {
				return;
			}
			$y_top = intval( $watermark['absTop'] - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
			while ( ( $watermark['absTop'] < $pdf_height || $y_top < $pdf_height ) && ( $watermark['absLeft'] < $pdf_width ) ) {
				$watermark['absLeft'] += $x_axis_offset;
				$watermark['absTop']  += $y_axis_offset;
				$this->draw_watermark_on_pdf( $watermark );
			}
		} elseif ( 'both' === $watermark['repeatAxis'] ) {
			if ( $x_axis_offset > 0 ) {
				$watermark['absLeft'] += $x_axis_offset;
				while ( $watermark['absLeft'] < $pdf_width ) {
					$this->draw_watermark_on_pdf( $watermark );
					$watermark['absLeft'] += $x_axis_offset;
				}
				$watermark['absLeft'] = $base_x;
			}
			if ( $y_axis_offset > 0 ) {
				$watermark['absTop'] += $y_axis_offset;
				$y_top                = intval( $watermark['absTop'] - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
				while ( $watermark['absTop'] < $pdf_height || $y_top < $pdf_height ) {
					$this->draw_watermark_on_pdf( $watermark );
					$watermark['absTop'] += $y_axis_offset;
					$y_top                = intval( $watermark['absTop'] - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
				}
			}
		} elseif ( 'full' === $watermark['repeatAxis'] ) {
			if ( $x_axis_offset <= 0 || $y_axis_offset <= 0 ) {
				return;
			}
			$watermark['absLeft'] += $x_axis_offset;
			$y_top                 = intval( $watermark['absTop'] - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
			while ( $watermark['absTop'] < $pdf_height || $y_top < $pdf_height ) {
				while ( $watermark['absLeft'] < $pdf_width ) {
					$this->draw_watermark_on_pdf( $watermark );
					$watermark['absLeft'] += $x_axis_offset;
				}
				$watermark['absLeft'] = $base_x;
				$watermark['absTop'] += $y_axis_offset;
				$y_top                = intval( $watermark['absTop'] - ( intval( $watermark['height'] ) * cos( floatval( $watermark['styles']['degree'] ) ) ) );
			}
		}
	}

	/**
	 * Get the real watermark dimension that will be painted on the pdf page.
	 *
	 * @param array $watermark
	 * @return array
	 */
	private function calculate_watermark_dimension_on_pdf( $watermark, $type = 'image' ) {

		$watermark['orig_width']  = $watermark['width'];
		$watermark['orig_height'] = $watermark['height'];
		if ( 'image' === $type ) {
			$watermark['width']  = - $watermark['width'] * 72 / -96 / $this->k;
			$watermark['height'] = - $watermark['height'] * 72 / -96 / $this->k;
		}
		return $watermark;
	}

	/**
	 * Reset any styles - rotation - etc after watermark.
	 *
	 * @return void
	 */
	private function reset_after_watermark( $watermark ) {
		$this->set_alpha( 1 );
		$this->rotate_text_around_center( 0, $watermark['absLeft'], $watermark['absTop'] );
	}

	/**
	 * Rotate Text.
	 *
	 * @param integer $angle Rotate Angle.
	 * @param integer $x    Rotate Center X.
	 * @param integer $y    Rotate Center Y.
	 * @return void
	 */
	private function rotate_text_around_center( $angle, $x = -1, $y = -1 ) {
		if ( -1 === $x ) {
			$x = $this->x;
		}
		if ( -1 === $y ) {
			$y = $this->y;
		}
		if ( 0 !== $this->angle ) {
			$this->_out( 'Q' );
		}

		$this->angle = $angle;

		if ( 0 !== $angle ) {
			$angle *= M_PI / 180;
			$c      = cos( $angle );
			$s      = sin( $angle );
			$cx     = $x * $this->k;
			$cy     = ( $this->h - $y ) * $this->k;
			$this->_out( sprintf( 'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy ) );
		}
	}

	/**
	 * End PDF Page.
	 *
	 * @return void
	 */
	public function _endpage() {
		if ( 0 !== $this->angle ) {
			$this->angle = 0;
			$this->_out( 'Q' );
		}
		parent::_endpage();
	}

	/**
	 * Save PDF Result to Desination File.
	 *
	 * @param string $dest
	 * @return void
	 */
	public function save_pdf( $dest ) {
		$this->Output( 'F', $dest );
		$this->revert_version( $dest );
	}

	// =============== Alpha Support Section ===================== //.

	// alpha: real value from 0 (transparent) to 1 (opaque)
	// bm:    blend mode, one of the following:
	// Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
	// HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity
	protected function set_alpha( $alpha, $bm = 'Normal' ) {
		// set alpha for stroking (CA) and non-stroking (ca) operations
		$gs = $this->AddExtGState(
			array(
				'ca' => $alpha,
				'CA' => $alpha,
				'BM' => '/' . $bm,
			)
		);
		$this->SetExtGState( $gs );
	}

	protected function AddExtGState( $parms ) {
		$n                               = count( $this->extgstates ) + 1;
		$this->extgstates[ $n ]['parms'] = $parms;
		return $n;
	}

	protected function SetExtGState( $gs ) {
		$this->_out( sprintf( '/GS%d gs', $gs ) );
	}

	protected function _enddoc() {
		if ( ! empty( $this->extgstates ) && $this->PDFVersion < '1.4' ) {
			$this->PDFVersion = '1.4';
		}
		parent::_enddoc();
	}

	protected function _putextgstates() {
		for ( $i = 1; $i <= count( $this->extgstates ); $i++ ) {
			$this->_newobj();
			$this->extgstates[ $i ]['n'] = $this->n;
			$this->_put( '<</Type /ExtGState' );
			$parms = $this->extgstates[ $i ]['parms'];
			$this->_put( sprintf( '/ca %.3F', $parms['ca'] ) );
			$this->_put( sprintf( '/CA %.3F', $parms['CA'] ) );
			$this->_put( '/BM ' . $parms['BM'] );
			$this->_put( '>>' );
			$this->_put( 'endobj' );
		}
	}

	protected function _putresourcedict() {
		parent::_putresourcedict();
		$this->_put( '/ExtGState <<' );
		foreach ( $this->extgstates as $k => $extgstate ) {
			$this->_put( '/GS' . $k . ' ' . $extgstate['n'] . ' 0 R' );
		}
		$this->_put( '>>' );
	}

	protected function _putresources() {
		$this->_putextgstates();
		parent::_putresources();
	}

}
