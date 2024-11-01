<?php
namespace GPLSCore\GPLS_PLUGIN_WMPDF;

use GPLSCore\GPLS_PLUGIN_WMPDF\PDF_Watermark;
use GPLSCore\GPLS_PLUGIN_WMPDF\Apply_Watermarks_Queries;
use GPLSCore\GPLS_PLUGIN_WMPDF\Auto_Apply_Watermarks_Template;

/**
 * Watermarks Templates CPT Class.
 */
class Watermarks_Templates {
	/**
	 * Core Object
	 *
	 * @var object
	 */
	private static $core;

	/**
	 * Plugin Info
	 *
	 * @var object
	 */
	private static $plugin_info;

	/**
	 * Post Type Key
	 *
	 * @var string
	 */
	public static $post_type_key;

	/**
	 * Template Meta Data Key.
	 *
	 * @var string
	 */
	private static $watermarks_template_meta_key;

	/**
	 * Available watermarks Type.
	 *
	 * @var array
	 */
	private static $watermarks_types = array( 'text', 'image' );

	/**
	 * Text Watermark Fields
	 *
	 * @var array
	 */
	private static $text_watermark_fields = array( 'id', 'type', 'width', 'height', 'title', 'isRepeat', 'centerOffset', 'repeatAxis', 'repeatXAxisOffset', 'repeatYAxisOffset', 'positionSpot', 'positionType', 'absLeft', 'absTop', 'leftPercent', 'topPercent', 'baselineOffset', 'exactWidth', 'botLeft', 'botTop', 'opacity', 'degree', 'color', 'fontsize', 'fontfamily' );

	/**
	 * Image Watermark Fields.
	 *
	 * @var array
	 */
	private static $image_watermark_fields = array( 'id', 'type', 'width', 'height', 'imgID', 'isRepeat', 'centerOffset', 'repeatAxis', 'repeatXAxisOffset', 'repeatYAxisOffset', 'positionSpot', 'positionType', 'absLeft', 'absTop', 'leftPercent', 'topPercent', 'url', 'opacity', 'degree' );

	/**
	 * Distinct Date Options For All CPTs.  [ cpt_slug ] => array( array( author_obj => , post_type => ) )
	 *
	 * @var array
	 */
	protected static $cpts_author_options = array();

	/**
	 * Default Preview Images.
	 *
	 * @return array
	 */
	public static $default_preview_imgs;

	/**
	 * Available Auto Apply At Upload Contexts.
	 *
	 * @var array
	 */
	private static $auto_apply_contexts = array( 'media', 'posts' );

	/**
	 * ALlowed media types to apply the watermarks.
	 *
	 * @var array
	 */
	private static $allowed_media_types = array( 'pdf' );

	/**
	 * Preview PDF Filename
	 *
	 * @var string
	 */
	protected static $preview_pdf_filename;

	/**
	 * Default Watermarks Template MetaData Structure.
	 *
	 * @var array
	 */
	private static $default_metadata = array(
		'preview_img_id' => 0,
		'watermarks'     => array(),
		'auto_apply'     => array(
			'status'        => false,
			'context_type'  => array(),
			'context_posts' => array(),
			'apply_type'    => 'new',
			'create_backup' => false,
			'media_type'    => array( 'pdf' ),
		),
	);

	/**
	 * GIF Editor Initialization.
	 *
	 * @param array  $plugin_info Plugin Info Array.
	 * @param object $core Core Object.
	 * @return void
	 */
	public static function init( $plugin_info, $core ) {
		self::$plugin_info                  = $plugin_info;
		self::$core                         = $core;
		self::$post_type_key                = self::$plugin_info['main_watermark_prefix'] . '-pdf';
		self::$watermarks_template_meta_key = self::$plugin_info['classes_prefix'] . '-watermarks-template-meta-key';
		self::$preview_pdf_filename         = 'preview-pdf-tmp-' . self::$plugin_info['name'];
		self::setup();
		self::hooks();
	}

	/**
	 * Setup Variables.
	 *
	 * @return void
	 */
	public static function setup() {
		self::$default_preview_imgs = array(
			'preview_bg_white' => array(
				'url'    => self::$plugin_info['url'] . 'assets/dist/images/preview-default-pdf.pdf',
				'path'   => self::$plugin_info['path'] . 'assets/dist/images/preview-default-pdf.pdf',
				'width'  => 793,
				'height' => 1122,
				'type'   => 'pdf',
			),
		);
	}
	/**
	 * Actions and filters hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'admin_head', array( get_called_class(), 'load_fonts' ), 2 );
		add_action( 'wp_ajax_' . self::$plugin_info['name'] . '-preview-watermarks-template-action', array( get_called_class(), 'ajax_preview_watermarks_template' ) );
	}

	/**
	 * AJAX Preview Watermarks Template.
	 *
	 * @return void
	 */
	public static function ajax_preview_watermarks_template() {
		if ( ! empty( $_POST['nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['nonce'] ), self::$plugin_info['name'] . '-ajax-nonce' ) ) {
			if ( ! empty( $_POST['watermarks'] ) ) {
				$preview_image = ! empty( $_POST['preview_img'] ) ? map_deep( wp_unslash( $_POST['preview_img'] ), 'sanitize_text_field' ) : array();
				$watermarks    = map_deep( wp_unslash( $_POST['watermarks'] ), 'sanitize_text_field' );
				$preview_image = self::handle_preview_img( $preview_image, true );
				$preview_url   = self::preview_watermarks_template( $preview_image, $watermarks );
				if ( is_wp_error( $preview_url ) ) {
					wp_send_json_error(
						array(
							'status' => 'danger',
							'msg'    => $preview_url->get_error_message(),
						)
					);
				}
				wp_send_json_success(
					array(
						'status' => 'primary',
						'result' => $preview_url,
					)
				);
			}
		}
		wp_send_json_error(
			array(
				'status' => 'danger',
				'msg'    => esc_html__( 'The link has expired, please refresh the page!', 'gpls-wmpdf-watermark-pdf' ),
			)
		);
	}

	/**
	 * Handle Preview Image [ default or Saved ].
	 *
	 * @param string|int $img The Preview Image.
	 * @return array
	 */
	public static function handle_preview_img( $img, $is_preview = false ) {
		if ( is_array( $img ) && ! empty( $img['id'] ) ) {
			$img['path'] = get_attached_file( $img['id'] );
		} else {
			if ( ! $is_preview ) {
				$img = self::$default_preview_imgs['preview_bg_white'];
			}
		}
		return $img;
	}

	/**
	 * Perview Watermarks Template.
	 *
	 * @param array $preview_img Preview Image Array.
	 * @param array $watermarks Watermarks Array.
	 * @return string|\WP_Error [Preview String]
	 */
	public static function preview_watermarks_template( $preview_img, $watermarks ) {
		$uploads    = wp_upload_dir();
		$pdf_object = PDF_Watermark::watermark( $preview_img['path'], $watermarks, trailingslashit( $uploads['path'] ) . self::$preview_pdf_filename . '.pdf' );
		if ( is_wp_error( $pdf_object ) ) {
			return $pdf_object;
		}
		$preview_url = trailingslashit( $uploads['url'] ) . self::$preview_pdf_filename . '.pdf';
		$preview_url = add_query_arg(
			array(
				'refresh' => wp_generate_password( 5, false, false ),
			),
			$preview_url
		);
		return $preview_url . '#view=FitH&&navpanes=0&zoom=100';
	}

	/**
	 * Load Custom Fonts Files.
	 *
	 * @return void
	 */
	public static function load_fonts() {
		$screen = get_current_screen();
		if (
			( is_object( $screen ) && ! is_wp_error( $screen ) && ! empty( $screen->post_type ) && ( 'post' === $screen->base ) && ( $screen->post_type === self::$post_type_key ) )
			||
			( ! empty( $_GET['page'] ) && self::$plugin_info['single_apply_page'] === sanitize_text_field( wp_unslash( $_GET['page'] ) ) )
		) {
			$fonts = PDF_Watermark::get_available_fonts( true );
			ob_start();
			foreach ( $fonts as $font_family_name => $font ) :
				?>
				<link rel="preload" as="font" href="<?php echo esc_url( $font['url'] ); ?>" crossorigin="anonymous" >
				<?php
			endforeach;
		}
	}

	/**
	 * Handle Preview PDF [ default or Saved ].
	 *
	 * @param string|int $img The Preview Image.
	 * @return array
	 */
	public static function handle_preview_pdf( $img, $is_preview = false ) {
		if ( is_array( $img ) && ! empty( $img['id'] ) ) {
			$img['path'] = get_attached_file( $img['id'] );
		} else {
			if ( ! $is_preview ) {
				$img = self::$default_preview_imgs['preview_bg_white'];
			}
		}
		return $img;
	}

	/**
	 * List Current added Watermarks in the watermark template.
	 *
	 * @param object $post Curernt Post Object.
	 * @return void
	 */
	public static function current_watermarks_list( $post ) {
		$template_watermarks = self::get_template_watermarks( $post->ID );
		?>
		<div class="accordion watermarks-list-accordion" id="<?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-watermarks-list-accordion' ); ?>">
			<?php
			if ( ! empty( $template_watermarks['watermarks'] ) ) :
				$index = 0;
				foreach ( $template_watermarks['watermarks'] as $watermark_id => $watermark_data ) :
					?>
					<div class="accordion-item" data-id="<?php echo esc_attr( $watermark_id ); ?>">
						<h4 class="accordion-header watermark-specs-header" id="<?php echo esc_attr( $watermark_id . '_header' ); ?>" data-index="<?php echo esc_attr( $index ); ?>" data-id="<?php echo esc_attr( $watermark_id ); ?>">
						<div class="header-wrapper d-flex flex-row align-items-center">
							<button class="accordion-button" type="button" data-id="<?php echo esc_attr( $watermark_id ); ?>" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $watermark_id . '_specs' ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $watermark_id . '_specs' ); ?>">
								<?php echo esc_html( 'Watermark ' . ( $index + 1 ) . '  [' . $watermark_data['type'] . ']' ); ?>
							</button>
							<span class="dashicons dashicons-dismiss action action-remove mx-2 bg-white" style="color:#F00;" type="button" data-id="<?php echo esc_attr( $watermark_id ); ?>"></span>
						</div>
						<input type="hidden" name="watermarks[<?php echo esc_attr( $watermark_id ); ?>][type]" value="<?php echo esc_attr( $watermark_data['type'] ); ?>" />
						<input type="hidden" name="watermarks[<?php echo esc_attr( $watermark_id ); ?>][id]" value="<?php echo esc_attr( $watermark_id ); ?>" />
						<?php if ( ! empty( 'image' === $watermark_data['type'] ) ) : ?>
							<input type="hidden" name="watermarks[<?php echo esc_attr( $watermark_id ); ?>][url]" value="<?php echo esc_url_raw( $watermark_data['url'] ); ?>" />
							<input type="hidden" name="watermarks[<?php echo esc_attr( $watermark_id ); ?>][imgID]" value="<?php echo esc_attr( $watermark_data['imgID'] ); ?>" />
							<input type="hidden" name="watermarks[<?php echo esc_attr( $watermark_id ); ?>][width]" value="<?php echo esc_attr( $watermark_data['width'] ); ?>" />
							<input type="hidden" name="watermarks[<?php echo esc_attr( $watermark_id ); ?>][height]" value="<?php echo esc_attr( $watermark_data['height'] ); ?>" />
						<?php endif; ?>
						</h4>
						<div id="<?php echo esc_attr( $watermark_id . '_specs' ); ?>" class="accordion-collapse collapse" data-id="<?php echo esc_attr( $watermark_id ); ?>"  aria-labelledby="<?php echo esc_attr( $watermark_id . '_header' ); ?>" data-bs-parent="#<?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-watermarks-list-accordion' ); ?>" >
							<div class="accordion-body">
								<?php self::watermark_specs( $watermark_data ); ?>
							</div>
						</div>
					</div>
					<?php
					$index++;
				endforeach;
			endif;
			?>
		</div>
		<?php
		self::watermark_specs( array(), true );
	}

	/**
	 * Watermark Specs HTML.
	 *
	 * @param array   $watermark_data Watermark Specs Data Array.
	 * @param boolean $is_placeholder Is the The Specs HTML a placeholder or actual Watermark.
	 * @return void
	 */
	public static function watermark_specs( $watermark_data = array(), $is_placeholder = false, $context = 'create' ) {
		$plugin_info     = self::$plugin_info;
		$available_fonts = PDF_Watermark::get_available_fonts( true );
		$core            = self::$core;
		include self::$plugin_info['path'] . 'templates/watermark-specs-template-metabox.php';
	}

	/**
	 * Get Watermarks Template Watermarks Array.
	 *
	 * @param int $template_id Template ID.
	 * @return array
	 */
	public static function get_template_watermarks( $template_id, $return_part = '' ) {
		$watermarks_template_meta = get_post_meta( $template_id, self::$watermarks_template_meta_key, true );
		$watermarks_template_meta = ( empty( $watermarks_template_meta ) || false === $watermarks_template_meta ) ? self::$default_metadata : $watermarks_template_meta;
		return ( ! empty( $return_part ) && ! empty( $watermarks_template_meta[ $return_part ] ) ? $watermarks_template_meta[ $return_part ] : $watermarks_template_meta );
	}

	/**
	 * Adjust the Template Watermarks Data for Drawing the Watermarks.
	 *
	 * @param array $watermarks Watermarks Data Array.
	 * @return array
	 */
	public static function adjust_template_watermarks_data( $watermarks ) {
		foreach ( $watermarks as &$watermark ) {
			$watermark['styles']            = array();
			$watermark['styles']['opacity'] = $watermark['opacity'];
			$watermark['styles']['degree']  = $watermark['degree'];

			unset( $watermark['opacity'] );
			unset( $watermark['degree'] );

			if ( 'text' === $watermark['type'] ) {
				$watermark['text']                         = $watermark['title'];
				$watermark['styles']['font']               = array();
				$watermark['styles']['font']['color']      = $watermark['color'];
				$watermark['styles']['font']['fontFamily'] = $watermark['fontfamily'];
				$watermark['styles']['font']['fontSize']   = $watermark['fontsize'];

				unset( $watermark['title'] );
				unset( $watermark['fontfamily'] );
				unset( $watermark['fontsize'] );
				unset( $watermark['color'] );
			}
		}
		return $watermarks;
	}

	/**
	 * Prepare Current Saved Watermarks Objects for watermarks template post.
	 *
	 * @param int $watermarks_template_id Watermarks Template Post ID.
	 * @return array
	 */
	public static function prepare_current_watermarks_for_js( $watermarks_template_id = null ) {
		global $post;
		if ( is_null( $watermarks_template_id ) ) {
			$watermarks_template_id = $post->ID;
		}
		$js_response         = array();
		$template_watermarks = self::get_template_watermarks( $watermarks_template_id );
		if ( ! empty( $template_watermarks['preview_img_id'] ) ) {
			$GLOBALS[ self::$plugin_info['name'] . '-init-current-preview' ] = true;
			$js_response['preview'] = wp_prepare_attachment_for_js( $template_watermarks['preview_img_id'] );
			unset( $GLOBALS[ self::$plugin_info['name'] . '-init-current-preview' ] );
		}
		if ( empty( $template_watermarks['preview_img_id'] ) || is_null( $js_response['preview'] ) ) {
			$js_response['default_preview'] = self::$default_preview_imgs['preview_bg_white'];
		}

		if ( ! empty( $template_watermarks['watermarks'] ) ) {
			foreach ( $template_watermarks['watermarks'] as $watermark_id => $watermark_data ) {
				if ( 'text' === $watermark_data['type'] ) {
					$js_response['watermarks'][ $watermark_id ] = array(
						'id'                => $watermark_id,
						'type'              => 'text',
						'width'             => $watermark_data['width'],
						'height'            => $watermark_data['height'],
						'text'              => $watermark_data['title'],
						'isRepeat'          => $watermark_data['isRepeat'],
						'repeatAxis'        => $watermark_data['repeatAxis'],
						'repeatXAxisOffset' => $watermark_data['repeatXAxisOffset'],
						'repeatYAxisOffset' => $watermark_data['repeatYAxisOffset'],
						'positionType'      => $watermark_data['positionType'],
						'positionSpot'      => $watermark_data['positionSpot'],
						'centerOffset'      => ! empty( $watermark_data['centerOffset'] ) ? true : false,
						'absLeft'           => $watermark_data['absLeft'],
						'absTop'            => $watermark_data['absTop'],
						'leftPercent'       => $watermark_data['leftPercent'],
						'topPercent'        => $watermark_data['topPercent'],
						'baselineOffset'    => $watermark_data['baselineOffset'],
						'exactWidth'        => $watermark_data['exactWidth'],
						'botLeft'           => $watermark_data['botLeft'],
						'botTop'            => $watermark_data['botTop'],
						'styles'            => array(
							'font'    => array(
								'color'      => $watermark_data['color'],
								'fontSize'   => $watermark_data['fontsize'],
								'fontFamily' => $watermark_data['fontfamily'],
							),
							'opacity' => $watermark_data['opacity'],
							'degree'  => $watermark_data['degree'],
						),
					);
				} elseif ( 'image' === $watermark_data['type'] ) {
					$js_response['watermarks'][ $watermark_id ] = array(
						'id'                => $watermark_id,
						'type'              => 'image',
						'width'             => $watermark_data['width'],
						'height'            => $watermark_data['height'],
						'isRepeat'          => $watermark_data['isRepeat'],
						'repeatAxis'        => $watermark_data['repeatAxis'],
						'repeatXAxisOffset' => $watermark_data['repeatXAxisOffset'],
						'repeatYAxisOffset' => $watermark_data['repeatYAxisOffset'],
						'positionType'      => $watermark_data['positionType'],
						'positionSpot'      => $watermark_data['positionSpot'],
						'centerOffset'      => ! empty( $watermark_data['centerOffset'] ) ? true : false,
						'absLeft'           => $watermark_data['absLeft'],
						'absTop'            => $watermark_data['absTop'],
						'leftPercent'       => $watermark_data['leftPercent'],
						'topPercent'        => $watermark_data['topPercent'],
						'url'               => $watermark_data['url'],
						'imgID'             => $watermark_data['imgID'],
						'styles'            => array(
							'opacity' => $watermark_data['opacity'],
							'degree'  => $watermark_data['degree'],
						),
					);
				}
			}
		}
		return $js_response;
	}

	/**
	 * Watermarks Text Meta Type.
	 * @param array $watermark_data
	 * @return void
	 */
	public static function watermarks_text_meta_type( $watermark_data = array() ) {
		$selected_value = ( ! empty( $watermark_data['dynamicDataType'] ) ? $watermark_data['dynamicDataType'] : '' );
		?>
		<select class="watermark-dynamic-data-type" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][dynamicDataType]' : '' ); ?>" >
			<option <?php selected( 'user', $selected_value ); ?> value="user"><?php esc_html_e( 'User data', 'gpls-wmpdf-watermark-pdf' ); ?></option>
			<option <?php selected( 'customer', $selected_value ); ?> value="customer"><?php esc_html_e( 'Customer data', 'gpls-wmpdf-watermark-pdf' ); ?></option>
			<option <?php selected( 'order', $selected_value ); ?> value="order"><?php esc_html_e( 'Order data', 'gpls-wmpdf-watermark-pdf' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Watermarks Text meta select.
	 * @return void
	 */
	public static function watermarks_text_meta_select( $watermark_data = array() ) {
			$new_order            = new \WC_Order();
			$order_data           = $new_order->get_data();
			$order_data_formatted = array();
			foreach ( $order_data as $data_key => $data_value ) {
				if ( is_array( $data_value ) ) {
					foreach ( $data_value as $data_subkey => $data_subvalue ) {
						$order_data_formatted[] = $data_key . '_' . $data_subkey;
					}
				} else {
					$order_data_formatted[] = $data_key;
				}
			}

		$selected_value = ( ! empty( $watermark_data['dynamicDataField'] ) ? $watermark_data['dynamicDataField'] : '' );
		?>
		<select class="watermark-dynamic-data-field" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][dynamicDataField]' : '' ); ?>" data-watermarkid="<?php echo esc_attr( ! empty( $watermark_data ) ? $watermark_data['id'] : '' ); ?>" data-type="dynamic" class="edit edit-dynamic-data-field watermark-dynamic-data-field mt-1">
			<?php
			$new_customer = new \WP_User();
			$user_data    = array(
				'first_name',
				'user_firstname',
				'last_name',
				'user_lastname',
				'user_login',
				'user_pass',
				'user_nicename',
				'user_email',
				'user_url',
				'user_registered',
				'user_activation_key',
				'user_status',
				'user_level',
				'display_name',
				'locale',
				'nickname',
				'description',
				'user_description',
			);
			?>
			<option selected <?php selected( '', $selected_value ); ?> value="0"><?php esc_html_e( '--- Select data field ---', 'gpls-wmpdf-watermark-pdf' ); ?></option>
			<optgroup label="<?php esc_html_e( 'User data', 'gpls-wmpdf-watermark-pdf' ); ?>" >
				<option value="first_name_last_name"><?php esc_html_e( 'FirstName LastName', 'gpls-wmpdf-watermark-pdf' ); ?></option>
				<?php foreach ( $user_data as $user_data_key ) : ?>
					<option <?php selected( $user_data_key, $selected_value ); ?> value="<?php echo esc_attr( $user_data_key ); ?>"><?php echo esc_attr( $user_data_key ); ?></option>
				<?php endforeach; ?>
			</optgroup>
			<?php
				$new_customer            = new \WC_Customer();
				$customer_data           = $new_customer->get_data();
				$customer_data_formatted = array();
				foreach ( $customer_data as $data_key => $data_value ) {
					if ( is_array( $data_value ) ) {
						foreach ( $data_value as $data_subkey => $data_subvalue ) {
							$customer_data_formatted[] = $data_key . '_' . $data_subkey;
						}
					} else {
						$customer_data_formatted[] = $data_key;
					}
				}
				?>
				<optgroup label="<?php esc_html_e( 'Customer data', 'gpls-wmpdf-watermark-pdf' ); ?>" >
					<option value="first_name_last_name"><?php esc_html_e( 'FirstName LastName', 'gpls-wmpdf-watermark-pdf' ); ?></option>
					<?php foreach ( $customer_data_formatted as $customer_data_key ) : ?>
						<option <?php selected( $customer_data_key, $selected_value ); ?> value="<?php echo esc_attr( $customer_data_key ); ?>"><?php echo esc_attr( $customer_data_key ); ?></option>
					<?php endforeach; ?>
				</optgroup>
				<optgroup label="<?php esc_html_e( 'Order data', 'gpls-wmpdf-watermark-pdf' ); ?>" >
					<option value="billing_first_name_last_name"><?php esc_html_e( 'Billing FirstName LastName', 'gpls-wmpdf-watermark-pdf' ); ?></option>
					<option value="shipping_first_name_last_name"><?php esc_html_e( 'Shipping FirstName LastName', 'gpls-wmpdf-watermark-pdf' ); ?></option>
					<?php foreach ( $order_data_formatted as $order_data_key ) : ?>
						<option <?php selected( $order_data_key, $selected_value ); ?> value="<?php echo esc_attr( $order_data_key ); ?>"><?php echo esc_attr( $order_data_key ); ?></option>
					<?php endforeach; ?>
				</optgroup>
		</select>
		<?php
	}
}
