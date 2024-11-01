<?php defined( 'ABSPATH' ) || exit();

use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;

?>
<div class="container">
	<!-- Remove Password Requirements -->
	<div class="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-settings-wrapper' ); ?> my-5">
		<h5 class="border p-3 bg-white shadow-sm"><?php esc_html_e( 'Required libs' ); ?></h5>
		<div class="requirements-list bg-light p-3">
            <style>
.<?php echo esc_attr( $plugin_info['classes_prefix'] . '-settings-wrapper' ); ?> .led-green {
    margin: 0 auto;
    width: 24px;
    height: 24px;
    background-color: #abff00;
    border-radius: 50%;
    box-shadow: rgba(0,0,0,0.2) 0 -1px 7px 1px,inset #304701 0 -1px 9px,#89ff00 0 2px 12px;
    display: inline-block;
    vertical-align: middle
}

.<?php echo esc_attr( $plugin_info['classes_prefix'] . '-settings-wrapper' ); ?> .led-red {
    margin: 0 auto;
    width: 24px;
    height: 24px;
    background-color: #F00;
    border-radius: 50%;
    box-shadow: rgba(0,0,0,0.2) 0 -1px 7px 1px,inset #441313 0 -1px 9px,rgba(255,0,0,0.5) 0 2px 12px;
    display: inline-block;
    vertical-align: middle
}
            </style>
			<ul class="px-0">
				<!-- GhostScript -->
				<?php $gs_installed = GhostscriptConverter::is_gs_installed(); ?>
				<li class="req-row px-3 border shadow-sm bg-white py-3">
					<div class="row align-items-center">
						<div class="col-12">
							<div class="row">
								<div class="col-md-3 border bg-light px-3 py-2">
									<div class="req-name">
										<a target="_blank" href="https://www.ghostscript.com/"><?php esc_html_e( 'GhostScript' ); ?></a>
									</div>
								</div>
								<div class="col-md-9 border bg-light px-3 py-2">
									<div class="req-status text-end">
										<span class="install-status-icon <?php echo esc_attr( ( $gs_installed && ! is_wp_error( $gs_installed ) ) ? 'led-green' : 'led-red' ); ?> mx-2 align-middle"></span>
										<span class="align-middle"><?php printf( esc_html( '%s', 'watermark-pdf' ), ( $gs_installed && ! is_wp_error( $gs_installed ) ) ? 'V ' . $gs_installed : 'Not installed' ); ?></span>
									</div>
								</div>
								<?php if ( ! $gs_installed || is_wp_error( $gs_installed ) ) : ?>
								<div class="col-md-12 mt-4 ps-4">
									<h6><?php esc_html_e( 'Version check result: ', 'watermark-pdf' ); ?></h6>
									<code style="padding:4px;">
										<?php echo esc_html( is_wp_error( $gs_installed ) ? $gs_installed->get_error_message() : $gs_installed ); ?>
									</code>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<div class="row my-4">
						<div class="col-md-12 mt-4 ps-4">
							<div class="usage-box bg-white">
								<h6><?php esc_html_e( 'Used for', 'watermark-pdf' ); ?></h6>
								<ul class="list-group">
									<li class="list-group-item my-0"><?php esc_html_e( 'Watermark PDF files with version more than 1.4', 'watermark-pdf' ); ?></li>
								</ul>
							</div>
						</div>
					</div>
					<?php if ( ! $gs_installed || is_wp_error( $gs_installed ) ) : ?>
					<!-- How to Install -->
					<div class="row-my-4">
						<div class="col-md-12 mt-4 ps-4">
							<h6><?php esc_html_e( 'Install command', 'watermark-pdf' ); ?></h6>
							<ul>
								<li class="my-0">
									<div class="row my-0">
										<div class="col-md-3 border p-3"><?php echo esc_html( 'apt' ); ?></div>
										<div class="col-md-9 border p-3"><code><?php echo esc_html( 'sudo apt-get install -y ghostscript' ); ?></code></div>
									</div>
								</li>
								<li class="my-0">
									<div class="row my-0">
										<div class="col-md-3 border p-3"><?php echo esc_html( 'yum' ); ?></div>
										<div class="col-md-9 border p-3"><code><?php echo esc_html( 'sudo yum -y install ghostscript' ); ?></code></div>
									</div>
								</li>
								<li class="my-0">
									<div class="row my-0">
										<div class="col-md-3 border p-3"><?php echo esc_html( 'dnf' ); ?></div>
										<div class="col-md-9 border p-3"><code><?php echo esc_html( 'sudo dnf -y install ghostscript' ); ?></code></div>
									</div>
								</li>
							</ul>
						</div>
						<div class="col-12">
							<?php if ( is_wp_error( $gs_installed ) && ( 'The Process class relies on proc_open, which is not available on your PHP installation.' === $gs_installed->get_error_message() ) ) : ?>
							<h6 class="p-3 my-4 bg-light"><?php esc_html_e( 'proc_open() function seems not available, please contact your hosting support if it can be enabled or consider upgrading to a higher hosting plan', 'watermark-pdf' ); ?></h6>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
				</li>
			</ul>
		</div>
	</div>
</div>
