<?php

use Nabik\Gateland\Gateways\BaseGateway;
use Nabik\Gateland\Gateways\Features\FreeFeature;
use Nabik\GatelandPro\GatelandPro;

defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'bootstrap', GATELAND_URL . 'assets/css/bootstrap.min.css', [], GATELAND_VERSION );
wp_enqueue_style( 'gateland-custom', GATELAND_URL . 'assets/css/custom.css', [], GATELAND_VERSION );

wp_enqueue_script( 'gateland-custom', GATELAND_URL . 'assets/js/custom.js', [ 'jquery' ], GATELAND_VERSION, true );
?>

<div class="wrap">
	<h2>لیست درگاه‌ها</h2>

	<div id="poststuff">

		<div id="post-body-content">
			<div class="meta-box-sortablesui-sortable">
				<?php
				$table->prepare_items();
				$table->views();
				?>
				<form method="post">
					<?php
					$table->display();
					?>
				</form>
			</div>
		</div>

		<br class="clear">

		<h2>افزودن درگاه جدید</h2>

		<div class="gateway-container">

			<?php

			/** @var BaseGateway[] $gateways */
			foreach ( $gateways as $gateway ) {

				$label  = 'افزودن درگاه';
				$target = '_self';
				$type   = 'primary';
				$url    = admin_url( 'admin.php?page=gateland-gateways&add-gateway=' . base64_encode( get_class( $gateway ) ) );
				$url    = wp_nonce_url( $url, 'gateland-add-gateway' );

				$gateway_pro = str_replace( '\Gateland\\', '\GatelandPro\\', get_class( $gateway ) );

				if ( ! is_a( $gateway, FreeFeature::class, true ) && ! class_exists( $gateway_pro ) ) {

					if ( class_exists( GatelandPro::class ) && GatelandPro::is_active() ) {
						$label = 'بروزرسانی گیت‌لند';
					} else {
						$label = 'ارتقا گیت‌لند';
					}

					$target = '_blank';
					$type   = 'secondary';
					$url    = 'https://l.nabik.net/gateland-pro/?utm_campaign=gateland-upgrade&utm_medium=free-license&utm_source=website&utm_content=' . urlencode( get_class( $gateway ) );

				}

				?>
				<div class="gateway-item">

					<span class="gateway-image">
						<img src="<?php echo esc_url( $gateway->icon() ); ?>"
							 alt="<?php echo esc_attr( $gateway->name() ); ?>">
					</span>

					<br>

					<span class="gateway-name">
						<?php echo esc_html( $gateway->name() ); ?>
					</span>

					<span class="gateway-add">
						<a href="<?php echo esc_url( $url ); ?>" target="<?php echo esc_attr( $target ); ?>">
						<input type="button" name="button" id="button"
							   class="button button-<?php echo esc_attr( $type ); ?>"
							   value="<?php echo esc_attr( $label ); ?>">
						</a>
					</span>

				</div>
				<?php
			}

			?>

		</div>

	</div>
</div>