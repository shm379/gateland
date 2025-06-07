<?php

use Nabik\Gateland\Models\Gateway;

defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'gateland-custom', GATELAND_URL . 'assets/css/custom.css', [], GATELAND_VERSION );
wp_enqueue_style( 'bootstrap', GATELAND_URL . 'assets/css/bootstrap.min.css', [], GATELAND_VERSION );

wp_enqueue_script( 'gateland-custom', GATELAND_URL . 'assets/js/custom.js', [ 'jquery' ], GATELAND_VERSION, true );

/** @var Gateway $gateway */
try {
	$gatewayBuilder = $gateway->build();
} catch ( Exception $e ) {
	wp_die( esc_html( $e->getMessage() ) );
}

?>

<div class="wrap">
	<h2>تنظیمات درگاه <?php echo esc_html( $gatewayBuilder->name() ) ?></h2>

	<form method="post">
		<?php

		wp_nonce_field( 'gateland_update_gateway', 'update_gateway_nonce' );

		foreach ( $gatewayBuilder->options() as $option ) {

			$value       = $gatewayBuilder->options[ $option['key'] ] ?? $option['default'] ?? '';
			$type        = $option['type'] ?? 'text';
			$description = $option['description'] ?? '';

			if ( $type == 'text' ): ?>
				<div class="row mt-3">
					<div class="col-md-6 col-12 form-group">
						<label class="mb-2" for="<?php echo esc_attr( $option['key'] ); ?>">
							<?php echo esc_html( $option['label'] ); ?>
						</label>
						<input type="text" style="direction: ltr;" class="form-control"
							   name="options[<?php echo esc_attr( $option['key'] ); ?>]"
							   placeholder="<?php echo esc_attr( $option['placeholder'] ?? '' ); ?>"
							   value="<?php echo esc_attr( $value ); ?>"
						>
						<p class="mt-1 text-secondary"><?php echo esc_html( $description ); ?></p>
					</div>
				</div>
			<?php elseif ( $type == 'hidden' ): ?>
				<div class="row mt-3">
					<input type="hidden"
						   name="options[<?php echo esc_attr( $option['key'] ); ?>]"
						   value="<?php echo esc_attr( $value ); ?>">
				</div>
			<?php elseif ( $type == 'textarea' ): ?>
				<div class="row mt-3">
					<div class="col-md-6 col-12 form-group">
						<label class="mb-2" for="<?php echo esc_attr( $option['key'] ); ?>">
							<?php echo esc_html( $option['label'] ); ?>
						</label>
						<textarea
								style="direction: ltr;"
								name="options[<?php echo esc_attr( $option['key'] ); ?>]"
								id="<?php echo esc_attr( $option['key'] ); ?>"
								class="form-control"><?php echo esc_textarea( trim( $value ) ); ?></textarea>
						<p class="mt-1 text-secondary"><?php echo esc_html( $description ); ?></p>
					</div>
				</div>
			<?php elseif ( $type == 'select' ): ?>
				<div class="row mt-3">
					<div class="col-md-6 col-12 form-group">
						<label class="mb-2" for="<?php echo esc_attr( $option['key'] ); ?>">
							<?php echo esc_html( $option['label'] ); ?>
						</label>
						<select class="form-select"
								name="options[<?php echo esc_attr( $option['key'] ); ?>]"
								id="<?php echo esc_attr( $option['key'] ); ?>">
							<?php

							foreach ( $option['options'] ?? [] as $key => $label ) {
								printf(
									'<option value="%s" %s>%s</option>',
									esc_attr( $key ),
									selected( $key, $value, false ),
									esc_html( $label )
								);
							}

							?>
						</select>
						<p class="mt-1 text-secondary"><?php echo esc_html( $description ); ?></p>
					</div>
				</div>
			<?php elseif ( $type == 'checkbox' ): ?>
				<div class="row mt-3">
					<div class="col-md-6 col-12 form-group">
						<label class="form-check-label" for="<?php echo esc_attr( $option['key'] ); ?>">
							<input class="form-check-input" type="checkbox"
								   id="<?php echo esc_attr( $option['key'] ); ?>"
								   name="options[<?php echo esc_attr( $option['key'] ); ?>]"
								   value="1" <?php checked( $value, 1 ); ?>>
							<span><?php echo esc_html( $option['label'] ); ?></span>
						</label>
						<p class="mt-1 text-secondary"><?php echo esc_html( $description ); ?></p>
					</div>
				</div>
			<?php elseif ( $type == 'section' ): ?>
				<hr class="border-dark-3 my-2">
				<div class="row mt-3">
					<div class="col-md-6 col-12 form-group">
						<label class="mb-2" for="<?php echo esc_attr( $option['key'] ); ?>">
							<?php echo esc_html( $option['label'] ); ?>
						</label>
						<p class="mt-1 text-secondary"><?php echo esc_html( $description ); ?></p>
					</div>
				</div>
			<?php endif; ?>
		<?php } ?>

		<input class="btn btn-success mt-3" type="submit" value="ذخیره">

		<?php if ( isset( $_SESSION['success'] ) && $_SESSION['success'] === true ): ?>
			<div class="row mt-5">
				<div class="col-md-6 col-12 alert alert-success" role="alert">
					اطلاعات با موفقیت ذخیره شد.
				</div>
			</div>
			<?php
			unset( $_SESSION['success'] );
		endif; ?>
	</form>
</div>