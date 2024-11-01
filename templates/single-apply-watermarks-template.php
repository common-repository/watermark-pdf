<?php
use GPLSCore\GPLS_PLUGIN_WMPDF\Watermark_Base;
use GPLSCore\GPLS_PLUGIN_WMPDF\Watermarks_Templates;

defined( 'ABSPATH' ) || exit(); ?>
<div class="wrap">
	<div class="watermark-template-creator-wrapper">
		<div id="poststuff">
			<div id="post-body" class="metabox-holder">
			<?php if ( ! Watermark_Base::is_pdf_supported() ) : ?>
				<div id="message" class="notice notice-error is-dismissble" >
					<p><?php esc_html_e( 'PDF support requires Imagick and Ghostscript modules installed', 'pls-wmfw-watermark-image-for-wordpress' ); ?></p>
				</div>
			<?php endif; ?>
				<!-- === Create Watemark Template === -->
				<div class="mb-5 border p-3 mb-5">
					<h5 class="mb-4"><?php esc_html_e( 'Single PDF Watermarks Editor', 'watermark-pdf' ); ?></h5>
					<p class="mt-3"><?php esc_html_e( 'Select a PDF file and apply watermarks on it', 'watermark-pdf' ); ?></p>
				</div>

				<div class="border shadow-sm p-3 mb-3">
					<h6><?php esc_html_e( 'Watermark PDF files in bulk, Apply watermarks automatically on PDF files, PDF backups, Dynamic watermarks for WooCommerce PDFs and MasterStudy LMS PDFs are features of ', 'watermark-pdf' ); $core->pro_btn(); esc_html_e( 'Version' ); ?></h6>
				</div>

				<div class="row">

					<div class="col-md-10">
						<div class="create-watermark-template-container pt-5 position-relative">
							<?php self::loader_html( null, true, 'loader' ); ?>
							<!-- Preview Image select - Display -->
							<div class="image-select">
								<div class="row">
									<div class="col-md-6 select-image-btn-section">

										<h3 class="my-3"><?php esc_html_e( 'Select pdf', 'watermark-pdf' ); ?></h3>
										<button data-context="select-preview-image" id="insert-media-button" class="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-open-gallery-btn' ); ?> button">
											<span class="wp-media-butons-icon"></span>
											<?php esc_html_e( 'Media Gallery', 'watermark-pdf' ); ?>
										</button>
									</div>
									<div class="col-md-6 select-watermark-btn-section d-none">
										<h3 class="my-3"><?php esc_html_e( 'Add Watermark', 'watermark-pdf' ); ?></h3>
										<button data-context="select-watermark" id="insert-media-button" class="float-left mr-2 <?php echo esc_attr( $plugin_info['classes_prefix'] . '-open-gallery-btn' ); ?> button d-inline-block">
											<span class="wp-media-butons-icon"></span>
											<?php esc_html_e( 'Image Watermark', 'watermark-pdf' ); ?>
										</button>
										<button data-context="select-watermark" class="float-left mr-2 <?php echo esc_attr( $plugin_info['classes_prefix'] . '-add-text-watermark' ); ?> button d-inline-block">
											<span class="wp-media-butons-icon"></span>
											<?php esc_html_e( 'Text Watermark', 'watermark-pdf' ); ?>
										</button>
									</div>
								</div>
								<!-- === Preview Image section === -->
								<div class="preview-selected-wrapper mx-auto my-5">
									<div class="img-item preview-selected-item text-center overflow-auto">
										<div id="selected-preview-container" class="selected-preview-container position-relative d-inline-block" style="overflow: auto !important; width: auto; margin: 0px;">
										<iframe
											class="d-none selected-preview selected-preview-pdf"
											src=""
											type="application/pdf"
											frameborder="0"
											scrolling="auto"
										></iframe>
											<input type="hidden" name="selected-preview-img-id" class="selected-preview-img-id" value="<?php echo absint( esc_attr( ! empty( $template_watermarks['preview_img_id'] ) ? $template_watermarks['preview_img_id'] : 0 ) ); ?>" >
											<!-- === Watermark Image Placeholder === -->
											<div class="watermark-image-placeholder-none ui-draggable ui-draggable-handle d-none">
												<div class="wrapper position-relative">
													<div class="img-placeholder watermark-placeholder-wrapper watermark-img-wrapper">
														<img src="#" alt="preview">
													</div>
													<div class="actions">
														<span class="dashicons dashicons-dismiss action action-remove"></span>
														<span class="watermark-placeholder-rotate-handle dashicons dashicons-image-rotate action action-rotate"></span>
														<span class="dashicons dashicons-admin-settings action action-edit"></span>
													</div>
												</div>
											</div>
											<!-- === Watermark Text Placeholder === -->
											<div class="watermark-text-placeholder-none ui-draggable ui-resizable ui-draggable-handle d-none">
												<div class="wrapper position-relative">
													<div class="watermark-text-wrapper watermark-placeholder-wrapper">
														<div spellcheck="false" contenteditable="true" class="overflow-hidden watermark-text-textarea text-start w-100 h-100"></div>
													</div>
													<div class="actions">
														<span class="dashicons dashicons-dismiss action action-remove"></span>
														<span class="watermark-placeholder-rotate-handle dashicons dashicons-image-rotate action action-rotate"></span>
														<span class="dashicons dashicons-admin-settings action action-edit"></span>
													</div>
												</div>
											</div>
											<div class="repeated-clones-wrapper position-absolute">

											</div>
										</div>
									</div>
								</div>
								<!-- Preview Watermarks Start section -->
								<div class="img-preview d-flex align-items-center mb-4 d-none" >
									<button class="button preview-watermark-preview-btn me-2" ><?php esc_html_e( 'Preview Watermarks', 'watermark-pdf' ); ?></button>
									<button class="mt-1 py-0 px-2 tooltip-btn btn btn-secondary rounded-circle" type="button" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body" title="<?php esc_html_e( 'This will show how the watermarks will be applied on pdf, It is applied on a separate pdf which is created temporarily for the preview', 'watermark-pdf' ); ?>" >?</button>
									<span class="spinner"></span>
								</div>
								<!-- Preview Result Section -->
								<div class="preview-result d-none">
									<div class="wrapper text-center" style="overflow: auto !important;">
										<iframe
											class="d-none preview-pdf"
											src=""
											type="application/pdf"
											frameborder="0"
											scrolling="auto"
											height="1000px"
											width="1000px"
										></iframe>
									</div>
								</div>

								<!-- Save Section -->
								<div class="save-section collapse my-5 subtitle card">
									<!-- Apply Type -->
									<div class="apply-type">
										<h5 class="mb-3">
											<?php esc_html_e( 'How to apply the watermarks', 'watermark-pdf' ); ?>
										</h5>

										<!-- Create New -->
										<div class="my-4">
											<input type="radio" value="1" id="apply-watermarks-type-add-new" name="apply-watermarks-type" class="form-check-input apply-watermarks-type">
											<label class="mb-1" for="apply-watermarks-type-add-new"><?php esc_html_e( 'Create new', 'watermark-pdf' ); ?></label>
											<small class="d-block mt-2 subtitle"><?php esc_html_e( 'Create a separate watermarked pdf', 'watermark-pdf' ); ?></small>
										</div>
										<!-- Overwrite -->
										<div class="my-4">
											<input type="radio" value="2" id="apply-watermarks-type-overwrite" name="apply-watermarks-type" class="form-check-input apply-watermarks-type">
											<label class="mb-1" for="apply-watermarks-type-overwrite"><?php esc_html_e( 'Overwrite', 'watermark-pdf' ); ?></label>
											<small class="d-block mt-2 subtitle"><?php esc_html_e( 'Overwrite the original pdf', 'watermark-pdf' ); ?></small>
										</div>
									</div>
									<!-- Submit -->
									<div class="step-4 mb-3 apply-watermarks-final-step collapse">
										<button class="button submit apply-watermarks-submit-btn"><?php esc_html_e( 'Apply Watermarks', 'watermark-pdf' ); ?></button>
										<span class="spinner"></span>
									</div>
								</div>

										<!-- Result Image Holder -->
							<div class="p-2 m-2 d-none img-icon-box-container"></div>
							</div>
						</div>
					</div>

					<!-- Watermarks List -->
					<div class="col-md-2" style="position:sticky; top:30px; height:100%;">
						<div id="side-sortables" class="meta-box-sortables ui-sortable" style="overflow-y: scroll;max-height: 800px;overflow-x:hidden">
							<div class="postbox" id="<?php echo esc_attr( $plugin_info['name'] . '-added-watermarks-list' ); ?>">
								<div class="postbox-header text-left px-3 py-1">
									<h5><?php esc_html_e( 'Current Watermarks', 'watermark-pdf' ); ?></h5>
								</div>
								<div class="inside">
									<div class="accordion watermarks-list-accordion" id="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermarks-list-accordion' ); ?>">
									</div>
									<?php Watermarks_Templates::watermark_specs( array(), true ); ?>
								</div>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
</div>

<div class="review-wrapper mt-5">
	<?php $core->review_notice(); ?>
	<?php $core->default_footer_section(); ?>
</div>


<div style="margin-top:50px;" role="alert" aria-live="assertive" aria-atomic="true" class="fixed-top mx-auto text-white toast <?php echo esc_attr( $plugin_info['classes_prefix'] . '-msgs-toast' ); ?>" >
	<div class="toast-header">
		<button type="button" class="btn close-toast bg-transparent me-2 m-auto border-0" data-bs-dismiss="toast" aria-label="close">
			<span class="bg-transparent dashicons dashicons-dismiss border-0"></span>
		</button>
	</div>
	<div class="toast-body">
	</div>
</div>
