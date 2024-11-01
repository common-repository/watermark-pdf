<?php
defined( 'ABSPATH' ) || exit(); 

use GPLSCore\GPLS_PLUGIN_WMPDF\Watermarks_Templates;

?>

<div id="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermark-specs-item-' . $watermark_data['id'] : '' ); ?>" class="watermark-specs<?php echo esc_attr( $is_placeholder ? '-placeholder d-none' : '' ); ?>">
	<div class="position-relative" >
		<div class="row position-relative">
			<div class="col-md-12">
				<div class="watermark-dimension-wrapper ms-1 row mb-2 <?php echo esc_attr( ! empty( $watermark_data['type'] ) && 'text' === $watermark_data['type'] ? 'd-none' : '' ); ?>">
					<!-- Width -->
					<div class="col">
						<label class="d-block form-label"><strong><?php esc_html_e( 'Width', 'watermark-pdf' ); ?></strong></label>
						<div class="edit-width-wrapper">
							<div class="edit-position-top-input-wrapper">
								<input type="text" class="edit edit-width form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][width]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data ) ? $watermark_data['width'] : '' ); ?>" data-type="width">
								<?php echo esc_attr( 'px' ); ?>
							</div>
						</div>
					</div>
					<!-- Height -->
					<div class="col">
						<label class="d-block form-label"><strong><?php esc_html_e( 'Height', 'watermark-pdf' ); ?></strong></label>
						<div class="edit-width-wrapper">
							<div class="edit-position-top-input-wrapper">
								<input type="text" class="edit edit-height form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][height]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data ) ? $watermark_data['height'] : '' ); ?>" data-type="height">
								<?php echo esc_attr( 'px' ); ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Text -->
				<div class="form-group edit-row edit-text text-left mb-3">
					<label class="form-label" for="gpls-wgr-watermark-text-title"><strong><?php esc_html_e( 'Text', 'watermark-pdf' ); ?></strong></label>
					<input type="text" class="edit form-control edit-title" name="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? 'watermarks[' . $watermark_data['id'] . '][title]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data['title'] ) && ( 'text' === $watermark_data['type'] ) ? $watermark_data['title'] : '' ); ?>" data-type="title">
				</div>

				<!-- Font Color -->
				<div class="form-group edit-row edit-text text-left mb-3">
					<label class="form-label" for="gpls-wgr-watermark-text-color"><strong><?php esc_html_e( 'Color', 'watermark-pdf' ); ?></strong></label>
					<input type="color" class="edit form-control edit-color" name="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? 'watermarks[' . $watermark_data['id'] . '][color]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data['color'] ) && ( 'text' === $watermark_data['type'] ) ? $watermark_data['color'] : '#000000' ); ?>" data-type="color">
				</div>

				<!-- Font Size -->
				<div class="form-group edit-row edit-text text-left mb-3">
					<label class="form-label" for="gpls-wgr-watermark-text-size"><strong><?php esc_html_e( 'Font Size', 'watermark-pdf' ); ?></strong></label>
					<input type="number" class="edit form-control edit-font-size" name="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? 'watermarks[' . $watermark_data['id'] . '][fontsize]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data['fontsize'] ) && ( 'text' === $watermark_data['type'] ) ? $watermark_data['fontsize'] : '' ); ?>" data-type="fontSize">
				</div>

				<!-- Font Family -->
				<div class="form-group edit-row edit-text text-left mb-3">
					<label class="form-label" for="gpls-wgr-watermark-text-font"><strong><?php esc_html_e( 'Font Family', 'watermark-pdf' ); ?></strong></label>
					<select class="edit form-control edit-font-family" name="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? 'watermarks[' . $watermark_data['id'] . '][fontfamily]' : '' ); ?>" data-type="fontFamily">
						<?php
						foreach ( $available_fonts as $font_family_name => $font_arr ) :
							?>
							<option <?php selected( $font_family_name, esc_attr( ! empty( $watermark_data['fontfamily'] ) ? $watermark_data['fontfamily'] : 'Verdana.ttf' ) ); ?> value="<?php echo esc_attr( $font_family_name ); ?>"><?php echo esc_html( $font_arr['title'] ); ?></option>
							<?php
						endforeach;
						?>
					</select>
				</div>
				<!-- Opacity -->
				<div class="form-group edit-row edit-general text-left mb-3">
					<label class="d-block form-label" for="gpls-wgr-watermark-opacity"><strong><?php esc_html_e( 'Opacity', 'watermark-pdf' ); ?></strong></label>
					<input type="number" min="0.1" step="0.1" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][opacity]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data['opacity'] ) ? $watermark_data['opacity'] : '1' ); ?>" max="1" class="edit edit-opacity" data-type="opacity">
				</div>

				<!-- Position -->
				<div class="form-group edit-row edit-general text-left mb-3">
					<h6 class="d-block mb-2">
						<span class="me-1"><?php esc_html_e( 'Position', 'watermark-pdf' ); ?></span>
						<button style="padding: 1px 8px;" type="button" class="btn btn-secondary rounded-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php esc_html_e( 'Select which side on the PDF which the watermark will be placed at.', 'watermark-pdf' ); ?>" >?</button>
					</h6>
					<div class="row position-box-wrapper mb-3">
						<div class="position-box col-4 d-flex justify-content-center align-items-center"><input type="radio" value="tl" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionSpot]' : '' ); ?>" class="edit edit-position-spot <?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-spot-tl' ); ?>" data-type="position-spot"></div>
						<div class="position-box col-4 d-flex justify-content-center align-items-center"><input type="radio" value="tm" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionSpot]' : '' ); ?>" class="edit edit-position-spot <?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-spot-tm' ); ?>" data-type="position-spot"></div>
						<div class="position-box col-4 d-flex justify-content-center align-items-center"><input type="radio" value="tr" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionSpot]' : '' ); ?>" class="edit edit-position-spot <?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-spot-tr' ); ?>" data-type="position-spot"></div>
						<div class="position-box col-4 d-flex justify-content-center align-items-center"><input type="radio" value="ml" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionSpot]' : '' ); ?>" class="edit edit-position-spot <?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-spot-ml' ); ?>" data-type="position-spot"></div>
						<div class="position-box col-4 d-flex justify-content-center align-items-center"><input type="radio" value="mm" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionSpot]' : '' ); ?>" class="edit edit-position-spot <?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-spot-mm' ); ?>" data-type="position-spot"></div>
						<div class="position-box col-4 d-flex justify-content-center align-items-center"><input type="radio" value="mr" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionSpot]' : '' ); ?>" class="edit edit-position-spot <?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-spot-mr' ); ?>" data-type="position-spot"></div>
						<div class="position-box col-4 d-flex justify-content-center align-items-center"><input type="radio" value="bl" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionSpot]' : '' ); ?>" class="edit edit-position-spot <?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-spot-bl' ); ?>" data-type="position-spot"></div>
						<div class="position-box col-4 d-flex justify-content-center align-items-center"><input type="radio" value="bm" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionSpot]' : '' ); ?>" class="edit edit-position-spot <?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-spot-bm' ); ?>" data-type="position-spot"></div>
						<div class="position-box col-4 d-flex justify-content-center align-items-center"><input type="radio" value="br" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionSpot]' : '' ); ?>" class="edit edit-position-spot <?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-spot-br' ); ?>" data-type="position-spot"></div>
					</div>
					<div class="ms-1 form-check mt-2 mb-4 d-flex align-items-center">
						<input value="yes" data-watermarkid="<?php echo esc_attr( ! empty( $watermark_data ) ? $watermark_data['id'] : '' ); ?>" <?php echo esc_attr( ! empty( $watermark_data ) && ! empty( $watermark_data['centerOffset'] ) && ( $watermark_data['centerOffset'] ) ? 'checked' : '' ); ?> type="checkbox" id="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-repeat-status' ); ?>"  name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][centerOffset]' : '' ); ?>" class="edit edit-position-center-offset form-check-input watermark-position-center-offset mt-1" data-type="position-center-offset">
						<label for="<?php echo esc_attr( $plugin_info['name'] . '-watermark-position-repeat-status' ); ?>" class="me-1 form-check-label mt-1"><?php esc_html_e( 'Relative to center', 'watermark-pdf' ); ?></label>
						<button style="padding: 1px 9px;" type="button" class="btn btn-secondary rounded-circle mt-1" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php esc_html_e( 'Set offset and rotation relative to the watermark\'s center instead of the watermark\'s left-top corner', 'watermark-pdf' ); ?>" >?</button>
					</div>
					<!-- Offset -->
					<div class="position-type my-2">
						<h6 class="mb-2">
							<span class="me-1"><?php esc_html_e( 'Offset', 'watermark-pdf' ); ?></span>
							<button style="padding: 1px 8px;" type="button" class="btn btn-secondary rounded-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php esc_html_e( 'The offset is calculated from the side\'s left-top corner which is selected from the position above', 'watermark-pdf' ); ?>" >?</button>
						</h6>
						<div class="ms-1 form-check my-2 d-flex align-items-center">
							<input <?php echo esc_attr( ! empty( $watermark_data ) && ( 'pixel' === $watermark_data['positionType'] ) ? 'checked' : '' ); ?> <?php echo esc_attr( empty( $watermark_data ) ? 'checked' : '' ); ?> type="radio" value="pixel" id="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-type-pixel' ); ?>"  name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionType]' : '' ); ?>" class="edit edit-position-type form-check-input watermark-position-type watermark-position-type-pixel" data-type="position-type">
							<label for="<?php echo esc_attr( $plugin_info['name'] . '-watermark-position-type-pixels' ); ?>" class="form-check-label" style="margin-bottom:3px;" ><?php esc_html_e( 'Pixels', 'watermark-pdf' ); ?></label>
						</div>
						<div class="ms-1 form-check my-2 d-flex align-items-center">
							<input <?php echo esc_attr( ! empty( $watermark_data ) && ( 'percent' === $watermark_data['positionType'] ) ? 'checked' : '' ); ?> type="radio" value="percent" id="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-type-percent' ); ?>"  name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][positionType]' : '' ); ?>" class="edit edit-position-type form-check-input watermark-position-type watermark-position-type-percent" data-type="position-type">
							<label for="<?php echo esc_attr( $plugin_info['name'] . '-watermark-position-type-percent' ); ?>" class="form-check-label" style="margin-bottom:5px;" ><?php esc_html_e( 'percentage', 'watermark-pdf' ); ?></label>
						</div>

						<div class="ms-1 row mb-2">
							<div class="col">
								<label class="d-block form-label"><strong><?php esc_html_e( 'Left', 'watermark-pdf' ); ?></strong></label>
								<div class="position-type-input pixel-position collapse <?php echo esc_attr( ! empty( $watermark_data ) && ( 'pixel' === $watermark_data['positionType'] ) ? 'show' : '' ); ?> <?php echo esc_attr( empty( $watermark_data['positionType'] ) ? 'show' : '' ); ?>">
									<div class="edit-position-left-input-wrapper">
										<input type="number" class="edit edit-position-left form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][absLeft]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data['absLeft'] ) ? $watermark_data['absLeft'] : '' ); ?>" data-type="position-left">
										<?php echo esc_attr( 'px' ); ?>
									</div>
								</div>
								<div class="position-type-input percent-position collapse <?php echo esc_attr( ! empty( $watermark_data ) && ( 'percent' === $watermark_data['positionType'] ) ? 'show' : '' ); ?>">
									<input type="number" step="0.1" class="edit edit-position-left-percent form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][leftPercent]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data['leftPercent'] ) ? $watermark_data['leftPercent'] : 0 ); ?>" data-type="position-left-percent" >
									<?php echo esc_attr( '%' ); ?>
								</div>
							</div>
							<div class="col">
								<label class="d-block form-label"><strong><?php esc_html_e( 'Top', 'watermark-pdf' ); ?></strong></label>
								<div class="position-type-input pixel-position collapse <?php echo esc_attr( ! empty( $watermark_data ) && ( 'pixel' === $watermark_data['positionType'] ) ? 'show' : '' ); ?> <?php echo esc_attr( empty( $watermark_data['positionType'] ) ? 'show' : '' ); ?>">
									<div class="edit-position-top-input-wrapper">
										<input type="number" class=" edit edit-position-top form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][absTop]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data['absTop'] ) ? $watermark_data['absTop'] : '' ); ?>" data-type="position-top">
										<?php echo esc_attr( 'px' ); ?>
									</div>
								</div>
								<div class="position-type-input percent-position collapse <?php echo esc_attr( ! empty( $watermark_data ) && ( 'percent' === $watermark_data['positionType'] ) ? 'show' : '' ); ?>">
									<input type="number" step="0.1" class="edit edit-position-top-percent form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][topPercent]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data['topPercent'] ) ? $watermark_data['topPercent'] : 0 ); ?>" data-type="position-top-percent" >
									<?php echo esc_attr( '%' ); ?>
								</div>
							</div>
						</div>
					</div>
					<!-- Corner -->
					<div class="pro-featuers form-group edit-row" style="opacity:0.5;padding:10px 5px;border:1px solid #EEE;">
						<div class="position-offset-corner mt-3">
							<div class="col mt-2">
								<label class="mb-2" for="<?php echo esc_attr( $plugin_info['name'] . '-position-offset-corner' ); ?>"><span><?php $core->new_keyword( 'New', false ); ?></span> <?php esc_html_e( 'Offset corner', 'gpls-wmfw-watermark-image-for-wordpress' ); ?><button style="padding: 1px 9px;" type="button" class="btn btn-secondary rounded-circle mt-1" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php esc_html_e( 'Select which corner of the selected position box above to set offset from', 'gpls-wmfw-watermark-image-for-wordpress' ); ?>" >?</button><?php $core->pro_btn(); ?></label>
								<div class="edit offset-corner-select-wrapper">
									<select data-watermarkid="<?php echo esc_attr( ! empty( $watermark_data ) ? $watermark_data['id'] : '' ); ?>" class="position-offset-corner form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][offsetCorner]' : '' ); ?>" id="<?php echo esc_attr( $plugin_info['name'] . '-offset-corner' ); ?>" data-type="position-offset-corner">
										<option value="">&mdash; <?php echo esc_attr( esc_html__( 'Select', 'gpls-wmfw-watermark-image-for-wordpress' ) ); ?> &mdash;</option>
										<option <?php echo esc_attr( empty( $watermark_data ) || empty( $watermark_data['offsetCorner'] ) || ( ! empty( $watermark_data['offsetCorner'] ) && 'tl' === $watermark_data['offsetCorner'] ) ? 'selected' : '' ); ?> value="tl"><?php echo esc_html_e( 'Top Left', 'gpls-wmfw-watermark-image-for-wordpress' ); ?></option>
										<option <?php echo esc_attr( ! empty( $watermark_data ) && ! empty( $watermark_data['offsetCorner'] ) && ( 'tr' === $watermark_data['offsetCorner'] ) ? 'selected' : '' ); ?> value="tr"><?php esc_html_e( 'Top Right', 'gpls-wmfw-watermark-image-for-wordpress' ); ?></option>
										<option <?php echo esc_attr( ! empty( $watermark_data ) && ! empty( $watermark_data['offsetCorner'] ) && ( 'bl' === $watermark_data['offsetCorner'] ) ? 'selected' : '' ); ?> value="bl"><?php esc_html_e( 'Bottom Left', 'gpls-wmfw-watermark-image-for-wordpress' ); ?></option>
										<option <?php echo esc_attr( ! empty( $watermark_data ) && ! empty( $watermark_data['offsetCorner'] ) && ( 'br' === $watermark_data['offsetCorner'] ) ? 'selected' : '' ); ?> value="br"><?php esc_html_e( 'Bottom Right', 'gpls-wmfw-watermark-image-for-wordpress' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<!-- Position Status -->
					<div class="position-repeat-status mb-4 mt-4">
						<div class="ms-1 form-check my-2">
							<input data-watermarkid="<?php echo esc_attr( ! empty( $watermark_data ) ? $watermark_data['id'] : '' ); ?>" <?php echo esc_attr( ! empty( $watermark_data ) && ( $watermark_data['isRepeat'] ) ? 'checked' : '' ); ?> type="checkbox" id="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-position-repeat-status' ); ?>"  name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][isRepeat]' : '' ); ?>" class="edit-position-repeat-status form-check-input watermark-position-repeat-status mt-1" data-type="position-repeat-status">
							<label for="<?php echo esc_attr( $plugin_info['name'] . '-watermark-position-repeat-status' ); ?>" class="form-check-label" style="margin-top:3px;" ><?php esc_html_e( 'Repeat', 'watermark-pdf' ); ?></label>
						</div>
					</div>
					<!-- Position Repeat Axis -->
					<div class="ms-1 row mb-2 repeat-axis-wrapper p-3 border collapse <?php echo esc_attr( ! empty( $watermark_data ) && ! empty( $watermark_data['isRepeat'] ) ? 'show' : '' ); ?>">
						<div class="col mt-2">
							<label class="mb-2" for="<?php echo esc_attr( $plugin_info['name'] . '-position-repeat-axis' ); ?>"><?php esc_html_e( 'Repeat Axis', 'watermark-pdf' ); ?></label>
							<div class="repeat-axis-select-wrapper">
								<select data-watermarkid="<?php echo esc_attr( ! empty( $watermark_data ) ? $watermark_data['id'] : '' ); ?>" class="repeat-axis-select" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][repeatAxis]' : '' ); ?>" id="<?php echo esc_attr( $plugin_info['name'] . '-position-repeat-axis' ); ?>">
									<option value="">&mdash; <?php esc_html_e( 'Select', 'watermark-pdf' ); ?> &mdash;</option>
									<option <?php echo esc_attr( ! empty( $watermark_data ) && ( 'x' === $watermark_data['repeatAxis'] ) ? 'selected' : '' ); ?> value="x"><?php echo esc_html( 'X axis', 'watermark-pdf' ); ?></option>
									<option <?php echo esc_attr( ! empty( $watermark_data ) && ( 'y' === $watermark_data['repeatAxis'] ) ? 'selected' : '' ); ?> value="y"><?php echo esc_html( 'Y axis', 'watermark-pdf' ); ?></option>
									<option <?php echo esc_attr( ! empty( $watermark_data ) && ( 'diagonal' === $watermark_data['repeatAxis'] ) ? 'selected' : '' ); ?> value="diagonal"><?php echo esc_html( 'Diagonal Axis', 'watermark-pdf' ); ?></option>
									<option <?php echo esc_attr( ! empty( $watermark_data ) && ( 'both' === $watermark_data['repeatAxis'] ) ? 'selected' : '' ); ?> value="both"><?php echo esc_html( 'X and Y Axis', 'watermark-pdf' ); ?></option>
									<option <?php echo esc_attr( ! empty( $watermark_data ) && ( 'full' === $watermark_data['repeatAxis'] ) ? 'selected' : '' ); ?> value="full"><?php echo esc_html( 'Full Repeat', 'watermark-pdf' ); ?></option>
								</select>
							</div>
						</div>
						<!-- Repeat Offset -->
						<div class="col mt-5">
							<label class="mb-2"><?php esc_html_e( 'Repeat Axis Offset', 'watermark-pdf' ); ?></label>
							<div class="row">
								<div class="col-md-6">
									<div class="repeat-x-axis-offset-input-wrapper">
										<label for="<?php echo esc_attr( $plugin_info['name'] . '-repeat-x-axis-offset' ); ?>" ><?php esc_html_e( 'X Axis', 'watermark-pdf' ); ?></label>
										<input data-axistype="x" min="1" data-watermarkid="<?php echo esc_attr( ! empty( $watermark_data ) ? $watermark_data['id'] : '' ); ?>" class="w-100 repeat-axis-offset-input repeat-x-axis-offset-input" type="number" value="<?php echo esc_attr( ! empty( $watermark_data ) && ! empty( $watermark_data['repeatXAxisOffset'] ) ? $watermark_data['repeatXAxisOffset'] : 200 ); ?>" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][repeatXAxisOffset]' : '' ); ?>" id="<?php echo esc_attr( $plugin_info['name'] . '-repeat-x-axis-offset' ); ?>" />
										<?php echo esc_attr( 'px' ); ?>
									</div>
								</div>
								<div class="col-md-6">
									<div class="repeat-y-axis-offset-input-wrapper">
										<label for="<?php echo esc_attr( $plugin_info['name'] . '-repeat-y-axis-offset' ); ?>" ><?php esc_html_e( 'Y Axis', 'watermark-pdf' ); ?></label>
										<input data-axistype="y" min="1" data-watermarkid="<?php echo esc_attr( ! empty( $watermark_data ) ? $watermark_data['id'] : '' ); ?>" class="w-100 repeat-axis-offset-input repeat-y-axis-offset-input" type="number" value="<?php echo esc_attr( ! empty( $watermark_data ) && ! empty( $watermark_data['repeatYAxisOffset'] ) ? $watermark_data['repeatYAxisOffset'] : 200 ); ?>" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][repeatYAxisOffset]' : '' ); ?>" id="<?php echo esc_attr( $plugin_info['name'] . '-repeat-y-axis-offset' ); ?>" />
										<?php echo esc_attr( 'px' ); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- Rotation -->
				<div class="form-group edit-row edit-general text-left mb-3">
					<label class="d-block form-label" for="gpls-wgr-watermark-rotation"><strong><?php esc_html_e( 'Rotation', 'watermark-pdf' ); ?></strong></label>
					<input type="number" min="0" step="1" name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][degree]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data['degree'] ) ? $watermark_data['degree'] : '0' ); ?>" max="360" class="edit edit-degree" data-type="degree">
				</div>

				<?php if ( 'create' === $context ) : ?>
				<!-- Dynamic -->
				<div class="pro-featuers form-group edit-row" style="opacity:0.5;padding:10px 5px;border:1px solid #EEE;">
					<div class="form-group dynamic-row edit-text text-left mb-3">
						<input value="yes" data-watermarkid="<?php echo esc_attr( ! empty( $watermark_data ) ? $watermark_data['id'] : '' ); ?>" <?php echo esc_attr( ! empty( $watermark_data ) && ! empty( $watermark_data['dynamicStatus'] ) && ( $watermark_data['dynamicStatus'] ) ? 'checked' : '' ); ?> type="checkbox" id="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-dynamic-status' ); ?>"  name="<?php echo esc_attr( ! empty( $watermark_data ) ? 'watermarks[' . $watermark_data['id'] . '][dynamicStatus]' : '' ); ?>" class="edit edit-dynamic-status form-check-input watermark-dynamic-status mt-1" data-type="dynamic">
						<label for="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-watermark-dynamic-status' ); ?>" class="me-1 form-check-label mt-1"><?php esc_html_e( 'Dynamic', 'gpls-wmpdf-watermark-pdf' ); ?> <span><?php $core->new_keyword( 'New', false ); ?></span> </label>
						<button style="padding: 1px 9px;" type="button" class="btn btn-secondary rounded-circle mt-1" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php esc_html_e( 'This watermark will take the value of the selected data field for each order / customer. The current text will be just a placeholder.', 'gpls-wmpdf-watermark-pdf' ); ?>" >?</button>
						<?php $core->pro_btn(); ?>
					</div>
					
					<div class="form-group dynamic-row edit-text text-left mb-3">
						<h6 class="me-1 form-check-label mt-1"><?php esc_html_e( 'Data field type', 'gpls-wmpdf-watermark-pdf' ); ?></h6>
						<?php Watermarks_Templates::watermarks_text_meta_type( $watermark_data ); ?>
					</div>

					<div class="form-group dynamic-row edit-row edit-text text-left mb-3">
						<h6 class="me-1 form-check-label mt-1"><?php esc_html_e( 'Data Field', 'gpls-wmpdf-watermark-pdf' ); ?></h6>
						<?php Watermarks_Templates::watermarks_text_meta_select( $watermark_data ); ?>
					</div>
				</div>
				<?php endif; ?>

				<!-- Text Bot Top | Left ExactWidth baseLineOffset -->
				<div class="form-group edit-row edit-text text-left mb-3 d-none">
					<!-- boteft -->
					<input type="hidden" class="edit edit-position-botleft form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? 'watermarks[' . $watermark_data['id'] . '][botLeft]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? $watermark_data['botLeft'] : '' ); ?>" >
					<!-- bottop -->
					<input type="hidden" class="edit edit-position-bottop form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? 'watermarks[' . $watermark_data['id'] . '][botTop]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? $watermark_data['botTop'] : '' ); ?>" >
					<!-- exactwidth -->
					<input type="hidden" class="edit edit-position-exactwidth form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? 'watermarks[' . $watermark_data['id'] . '][exactWidth]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? $watermark_data['exactWidth'] : '' ); ?>" >
					<!-- baslineoffset -->
					<input type="hidden" class="edit edit-position-baselineoffset form-control" name="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? 'watermarks[' . $watermark_data['id'] . '][baselineOffset]' : '' ); ?>" value="<?php echo esc_attr( ! empty( $watermark_data ) && ( 'text' === $watermark_data['type'] ) ? $watermark_data['baselineOffset'] : '' ); ?>" >

				</div>
				<?php
				if ( ! empty( $watermark_data ) ) :
					?>
					<!-- ID -->
					<input type="hidden" name="<?php echo esc_attr( 'watermarks[' . $watermark_data['id'] . '][id]' ); ?>" value="<?php echo esc_attr( $watermark_data['id'] ); ?>" >
					<!-- Type -->
					<input type="hidden" name="<?php echo esc_attr( 'watermarks[' . $watermark_data['id'] . '][type]' ); ?>" value="<?php echo esc_attr( $watermark_data['type'] ); ?>" >
					<?php
					if ( ! empty( $watermark_data['type'] ) && ( 'image' === $watermark_data['type'] ) ) :
						?>
					<!-- URL -->
					<input type="hidden" name="<?php echo esc_attr( 'watermarks[' . $watermark_data['id'] . '][url]' ); ?>" value="<?php echo esc_attr( $watermark_data['url'] ); ?>" >
					<!-- imgID -->
					<input type="hidden" name="<?php echo esc_attr( 'watermarks[' . $watermark_data['id'] . '][imgID]' ); ?>" value="<?php echo esc_attr( $watermark_data['imgID'] ); ?>" >
					<!-- width -->
					<input type="hidden" name="<?php echo esc_attr( 'watermarks[' . $watermark_data['id'] . '][width]' ); ?>" value="<?php echo esc_attr( $watermark_data['width'] ); ?>" >
					<!-- height -->
					<input type="hidden" name="<?php echo esc_attr( 'watermarks[' . $watermark_data['id'] . '][height]' ); ?>" value="<?php echo esc_attr( $watermark_data['height'] ); ?>" >
						<?php
					endif;
				endif;
				?>
			</div>
		</div>
	</div>
</div>
