<?php

namespace Nabik\Gateland\Plugins\Woocommerce;

use Nabik\Gateland\Gateland;
use Nabik\Gateland\Gateland_WC_Gateway;
use Nabik\Gateland\Models\Gateway;

class Load {

	protected static ?Load $_instance = null;

	public static function instance(): ?Load {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		include 'Gateway.php';

		new OrderMetabox();

		add_action( 'woocommerce_before_thankyou', 'wc_print_notices' );
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_gateways' ] );
	}

	public function add_gateways( array $gateways ): array {

		$gateways[] = Gateland_WC_Gateway::class;

		foreach ( \Nabik\Gateland\Services\GatewayService::activated() as $gateway_id => $gateway ) {
			$gateways[] = new Gateland_WC_Gateway( $gateway_id );
		}

		return $gateways;
	}
}