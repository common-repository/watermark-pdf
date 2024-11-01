<?php
defined( 'ABSPATH' ) || exit();
?>
<!-- Main Settings Template -->
<div class="wrap position-relative">
	<div class="container-fluid watermark-creator-wrapper w-100 p-5">
		<div class="row">
			<div class="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-page-template-tabs-nav gpls-general-page-template-tabs-nav' ); ?> mt-0 bg-light p-3 my-3 border-bottom shadow-sm">
				<ul class="list-group list-group-horizontal">
					<li class="list-group-item btn d-flex p-0 <?php echo esc_attr( empty( $_GET['tab'] ) || 'status' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ? 'active' : '' ); ?>">
						<a
							class="list-group-item-link text-decoration-none py-2 px-3 fw-bold"
							href="<?php echo esc_url_raw(
								add_query_arg(
									array(
										'tab' => 'status',
									),
									admin_url( 'admin.php?page=' . $plugin_info['options_page'] ),
								)
							); ?>"
						>
						<?php echo esc_html( 'Status', 'gpls-wmpdf-watermark-pdf' ); ?>
						</a>
					</li>
					<li class="list-group-item btn d-flex p-0 <?php echo esc_attr( ! empty( $_GET['tab'] ) && 'fonts' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ? 'active' : '' ); ?>">
						<a
							class="list-group-item-link text-decoration-none py-2 px-3 fw-bold"
							href="<?php echo esc_url_raw(
								add_query_arg(
									array(
										'tab' => 'fonts',
									),
									admin_url( 'admin.php?page=' . $plugin_info['options_page'] . '&tab=fonts' ),
								)
							); ?>"
						>
						<?php echo esc_html( 'Fonts', 'gpls-wmpdf-watermark-pdf' ); ?>
						</a>
					</li>
				</ul>
			</div>
			<?php if ( ! empty( $_GET['tab'] ) && 'fonts' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) : ?>
			<div id="custom-fonts-file-wrapper" class="col-12 custom-fonts card">
				<h4 class="mb-3"><?php esc_html_e( 'Custom Font files', 'gpls-wmpdf-watermark-pdf' ); ?></h4>
				<span class="mb-5"><?php esc_html_e( 'Upload custom font files for Text Watermarks is a feature of ', 'gpls-wmpdf-watermark-pdf' ); ?><?php $core->pro_btn(); ?></span>

			</div>
			<?php else : ?>
				<?php require_once $plugin_info['path'] . 'templates/status-template.php'; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="main-loader loader w-100 h-100 position-absolute top-0 left-0  d-none">
		<div class="text-white wrapper text-center position-absolute d-block w-100 ">
			<img class="loader-icon position-fixed" src="<?php echo esc_url_raw( admin_url( 'images/spinner-2x.gif' ) ); ?>"  />
		</div>
		<div class="overlay position-absolute d-block w-100 h-100"></div>
	</div>

</div>
