<?php

namespace GPLSCore\GPLS_PLUGIN_WMPDF;

use GPLSCore\GPLS_PLUGIN_WMPDF\PDF_Watermark;

class Single_Apply_Watermarks {

	/**
	 * Plugin Info Array.
	 *
	 * @var array
	 */
	private static $plugin_info;

	/**
	 * Core Object.
	 *
	 * @var object
	 */
	private static $core;

	/**
	 * Init Function.
	 *
	 * @return void
	 */
	public static function init( $plugin_info, $core ) {
		self::$plugin_info = $plugin_info;
		self::$core        = $core;
		self::hooks();
	}

	/**
	 * Actions and Filters Hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'admin_enqueue_scripts', array( get_called_class(), 'page_assets' ) );
		add_action( 'wp_ajax_' . self::$plugin_info['name'] . '-single-apply-watermarks-template', array( get_called_class(), 'ajax_single_apply_watermarks' ) );
	}

	/**
	 * Page Assets.
	 *
	 * @return void
	 */
	public static function page_assets() {

		// Single Apply Page.
		if ( ! empty( $_GET['page'] ) && self::$plugin_info['classes_prefix'] === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			wp_enqueue_style( self::$plugin_info['name'] . '-settings-menu-bootstrap-style', self::$core->core_assets_lib( 'bootstrap', 'css' ), array(), self::$plugin_info['version'], 'all' );
			wp_enqueue_style( self::$plugin_info['name'] . '-watermark-template-css', self::$plugin_info['url'] . 'assets/dist/css/admin/admin-styles.min.css', array(), self::$plugin_info['version'], 'all' );
			wp_add_inline_style(
				self::$plugin_info['name'] . '-watermark-template-css',
				PDF_Watermark::list_fonts_css( self::$plugin_info )
			);
			wp_enqueue_style( self::$plugin_info['name'] . '-select2-css', self::$core->core_assets_lib( 'select2', 'css' ), array(), self::$plugin_info['version'], 'all' );

			if ( ! wp_script_is( 'jquery' ) ) {
				wp_enqueue_script( 'jquery' );
			}

			wp_enqueue_media();

			if ( ! wp_script_is( 'jquery-ui-core' ) ) {
				wp_enqueue_script( 'jquery-ui-core' );
			}
			if ( ! wp_script_is( 'jquery-touch-punch' ) ) {
				wp_enqueue_script( 'jquery-touch-punch' );
			}
			if ( ! wp_script_is( 'jquery-ui-draggable' ) ) {
				wp_enqueue_script( 'jquery-ui-draggable' );
			}
			if ( ! wp_script_is( 'jquery-ui-droppable' ) ) {
				wp_enqueue_script( 'jquery-ui-droppable' );
			}
			if ( ! wp_script_is( 'jquery-ui-accordion' ) ) {
				wp_enqueue_script( 'jquery-ui-accordion' );
			}
			if ( ! wp_script_is( 'jquery-ui-sortable' ) ) {
				wp_enqueue_script( 'jquery-ui-sortable' );
			}
			wp_enqueue_script( self::$plugin_info['name'] . '-bootstrap-js', self::$core->core_assets_lib( 'bootstrap.bundle', 'js' ), array(), self::$plugin_info['version'], true );
			wp_enqueue_script( self::$plugin_info['name'] . '-jquery-ui-rotatable-js', self::$plugin_info['url'] . 'assets/libs/jquery.ui.rotatable.min.js', array( 'jquery-ui-core', 'jquery-ui-draggable' ), self::$plugin_info['version'], true );
			wp_enqueue_script( self::$plugin_info['name'] . '-select2-js', self::$core->core_assets_lib( 'select2.full', 'js' ), array( 'jquery' ), self::$plugin_info['version'], true );
			wp_enqueue_script( self::$plugin_info['name'] . '-watermark-template-js', self::$plugin_info['url'] . 'assets/dist/js/admin/single-apply-watermarks.min.js', array( 'jquery', self::$plugin_info['name'] . '-jquery-ui-rotatable-js', self::$plugin_info['name'] . '-select2-js' ), self::$plugin_info['version'], true );
			wp_localize_script(
				self::$plugin_info['name'] . '-watermark-template-js',
				str_replace( '-', '_', self::$plugin_info['name'] . '_localize_vars' ),
				array(
					'ajaxUrl'                         => admin_url( 'admin-ajax.php' ),
					'spinner'                         => admin_url( 'images/spinner.gif' ),
					'nonce'                           => wp_create_nonce( self::$plugin_info['name'] . '-ajax-nonce' ),
					'wp_nonce'                        => wp_create_nonce( 'wp-nonce' ),
					'previewWatermarkstemplateAction' => self::$plugin_info['name'] . '-preview-watermarks-template-action',
					'saveWatermarkstemplateAction'    => self::$plugin_info['name'] . '-save-watermarks-template-action',
					'singleApplyWatermarksAction'     => self::$plugin_info['name'] . '-single-apply-watermarks-template',
					'labels'                          => array(
						'watermark'            => esc_html__( 'Watermark', 'watermark-pdf' ),
						'select_images'        => esc_html__( 'Select PDFs', 'watermark-pdf' ),
						'select_image'         => esc_html__( 'Select PDF', 'watermark-pdf' ),
						'select_watermark'     => esc_html__( 'Select Watermark', 'watermark-pdf' ),
						'choose_watermark'     => esc_html__( 'Choose Watermark', 'watermark-pdf' ),
						'big_watermark_notice' => esc_html__( 'The selected watermark is bigger than the image', 'watermark-pdf' ),
						'search_term'          => esc_html__( 'Search Term', 'watermark-pdf' ),
						'search_terms'         => esc_html__( 'Search Terms', 'watermark-pdf' ),
						'remove_watermark'     => esc_html__( 'You are about to remove a watermark, confirm?', 'watermark-pdf' ),
					),
					'classes_prefix'                  => self::$plugin_info['classes_prefix'],
					'img_mime_types'                  => array( 'image/png', 'image/jpg', 'image/jpeg', 'image/bmp', 'image/tiff', 'image/x-icon', 'image/heic' ),
					'pdf_mime_types'                  => array( 'application/pdf' ),
				)
			);
		}
	}

		/**
		 * Ajax Apply Watermarks.
		 *
		 * @return void
		 */
	public static function ajax_single_apply_watermarks() {
		if ( ! empty( $_POST['nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['nonce'] ), self::$plugin_info['name'] . '-ajax-nonce' ) ) {
			$errors        = array();
			$preview_image = ! empty( $_POST['preview_img'] ) ? map_deep( wp_unslash( $_POST['preview_img'] ), 'sanitize_text_field' ) : array();
			$watermarks    = map_deep( wp_unslash( $_POST['watermarks'] ), 'sanitize_text_field' );
			$options       = ! empty( $_POST['options'] ) ? map_deep( wp_unslash( $_POST['options'] ), 'sanitize_text_field' ) : array();

			$img_post = get_post( $preview_image['id'] );
			if ( is_wp_error( $img_post ) || ! $img_post ) {
				wp_send_json_error(
					array(
						'status' => 'danger',
						'msg'    => esc_html__( 'Selected media doesn\'t exist!', 'watermark-pdf' ),
					)
				);
			}

			if ( empty( $options ) ) {
				wp_send_json_error(
					array(
						'status' => 'danger',
						'msg'    => esc_html__( 'Apply rules are empty!', 'watermark-pdf' ),
					)
				);
			}
			if ( ! $preview_image ) {
				wp_send_json_error(
					array(
						'status' => 'danger',
						'msg'    => esc_html__( 'No selected image!', 'watermark-pdf' ),
					)
				);
			}
			$options = array_merge(
				array(
					'applyTemplateType' => 1,
					'createBackup'      => false,
					'imageSizes'        => array(),
				),
				$options
			);
			// Fix any boolean values.
			foreach ( $options as $option_key => $option_value ) {
				if ( 'false' === $option_value ) {
					$options[ $option_key ] = false;
				} elseif ( 'true' === $option_value ) {
					$options[ $option_key ] = true;
				}
			}
			$preview_image = Watermarks_Templates::handle_preview_pdf( $preview_image );
			$result        = self::single_pdf_apply_watermarks( $preview_image, $watermarks, $options );

			if ( is_array( $result ) ) {
				$errors[] = $result;
			}

			if ( ! empty( $errors ) ) {
				wp_send_json_error(
					array(
						'status' => 'danger',
						'errors' => $errors,
					)
				);
			} else {
				// Get Image Box.
				wp_send_json_success(
					array(
						'status'  => 'primary',
						'msg'     => esc_html__( 'Watermarks have been applied successfully!', 'watermark-pdf' ),
						'display' => self::display_img_icon_box( $result ),
					)
				);
			}
		}
		wp_send_json_error(
			array(
				'status' => 'danger',
				'msg'    => esc_html__( 'The link has expired, please refresh the page!', 'watermark-pdf' ),
			)
		);
	}


	/**
	 * Single Apply Watermarks on a pdf.
	 *
	 * @param array $pdf
	 * @param array $watermarks
	 * @param array $apply_options
	 * @return array|int
	 */
	public static function single_pdf_apply_watermarks( $pdf, $watermarks, $apply_options ) {
		$errors                  = array();
		$uploads                 = wp_get_upload_dir();
		$attachment_id           = $pdf['id'];
		$image_metadata          = wp_get_attachment_metadata( $pdf['id'] );
		$pdf_details             = PDF::get_pdf_file_details_direct( $pdf['path'] );
		$original_img_details    = PDF::get_image_file_details( $attachment_id, 'original' );

		// Create New.
		if ( 1 == $apply_options['applyTemplateType'] ) {
			// 1) Generate Unique Filename with the same details.
			$original_path           = $pdf_details['path'];
			$filename                = wp_unique_filename( $pdf_details['full_path_without_name'], $pdf_details['filename'] );
			$pdf_details['filename'] = $filename;
			$pdf_details['url']      = trailingslashit( $uploads['baseurl'] ) . trailingslashit( $pdf_details['relative_path'] ) . $filename;
			$pdf_details['path']     = trailingslashit( $pdf_details['full_path_without_name'] ) . $filename;

			// 2) Create a copy of the pdf to the new Filename.
			$copied = copy( $original_path, $pdf_details['path'] );

			if ( ! $copied ) {
				$errors[] = esc_html__( 'Failed to create watermarked pdf file!', 'watermark-pdf' );
				return $errors;
			}

			// 3) Create an Image media post.
			$attachment_obj = get_post( $attachment_id );
			$attachment     = array(
				'post_mime_type' => $pdf_details['mime_type'],
				'guid'           => $pdf_details['url'],
				'post_parent'    => $attachment_obj->post_parent,
				'post_title'     => wp_basename( $filename, '.' . $pdf_details['ext'] ),
				'post_content'   => '',
				'post_excerpt'   => '',
			);
			$attachment_id  = wp_insert_attachment( $attachment, $pdf_details['path'] );

			if ( is_wp_error( $attachment_id ) ) {
				$errors[] = esc_html__( 'Failed to create pdf watermarked attachment!', 'watermark-pdf' );
				return $errors;
			}

			$pdf_watermark = PDF_Watermark::watermark( $pdf_details['path'], $watermarks, $pdf_details['path'] );

			if ( is_wp_error( $pdf_watermark ) ) {
				$errors[] = $pdf_watermark->get_error_message();
				wp_delete_attachment( $attachment_id, true );
				@unlink( $pdf_details['path'] );
				return $errors;
			}

			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $pdf_details['path'] ) );

			$image_metadata       = wp_get_attachment_metadata( $attachment_id );
			$pdf_details          = PDF::get_pdf_file_details_direct( $pdf_details['path'] );
			$original_img_details = PDF::get_image_file_details( $attachment_id, 'original' );
		} else {
			// Overwrite.
			// Draw the Watermarks on the pdf.
			$pdf_watermark = PDF_Watermark::watermark( $pdf['path'], $watermarks, $pdf['path'] );

			if ( is_wp_error( $pdf_watermark ) ) {
				$errors[] = $pdf_watermark->get_error_message();
				return $errors;
			}
		}

		// 6) Generate Sub-sizes of the image after watermarks.
		$GLOBALS[ self::$plugin_info['name'] . '-single-pdf-watermark-apply' ] = $pdf;

		// Create the preview image from pdf first page[ full size image ].
		$dirname = dirname( $original_img_details['path'] ) . '/';
		$ext     = '.' . pathinfo( $original_img_details['path'], PATHINFO_EXTENSION );

		if ( ! empty( $image_metadata['sizes'] ) && ! empty( $image_metadata['sizes']['full'] ) ) {
			// Full_PATH/year/month.
			$preview_file_path = $dirname . $image_metadata['sizes']['full']['file'];
		} else {
			$preview_file_path = $dirname . wp_unique_filename( $dirname, wp_basename( $original_img_details['path'], $ext ) . '-pdf.jpg' );
		}

		PDF_Watermark::generate_pdf_thumbnail_v1( $original_img_details['path'], $preview_file_path );

		unset( $GLOBALS[ self::$plugin_info['name'] . '-single-pdf-watermark-apply' ] );

		if ( ! empty( $errors ) ) {
			return $errors;
		} else {
			return $attachment_id;
		}
	}

	/**
	 * Display Image Box after save.
	 *
	 * @return void
	 */
	public static function display_img_icon_box( $media_id ) {
		$media_post = get_post( $media_id );
		$title      = get_the_title( $media_post );
		$thumb      = wp_get_attachment_image( $media_id, array( 150, 150 ), true, array( 'alt' => '' ) );
		$edit_link  = get_edit_post_link( $media_id );
		$file       = get_attached_file( $media_id );
		$edit_link  = add_query_arg(
			self::$plugin_info['classes_prefix'] . '-force-pdf-refresh',
			'true',
			$edit_link
		);
		?>
		<div class="img-media-icon-box card mb-3 w-auto mx-auto border container px-0 py-0">
			<div class="card-body">
				<h5 class="card-title"><a target="_blank" href="<?php echo esc_url_raw( $edit_link ); ?>"><strong><?php echo esc_html( $title ); ?></strong></a></h5>
				<p class="card-text mt-4"><?php echo esc_html( wp_basename( $file ) ); ?></p>
				<a class="mt-3" target="_blank" href="<?php echo esc_url_raw( $edit_link ); ?>"><?php echo $thumb; ?></a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

}
