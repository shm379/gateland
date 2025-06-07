<?php

namespace Nabik\Gateland;

use Carbon\Carbon;

class Cron {

	public function __construct() {
		add_action( 'woocommerce_cancel_unpaid_orders', [ $this, 'cancel_unpaid_orders' ] );
	}

	public function cancel_unpaid_orders() {
		$held_duration = get_option( 'woocommerce_hold_stock_minutes' );

		// Re-schedule the event before cancelling orders
		// this way in case of a DB timeout or (plugin) crash the event is always scheduled for retry.
		wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_orders' );
		$cancel_unpaid_interval = apply_filters( 'woocommerce_cancel_unpaid_orders_interval_minutes', absint( $held_duration ) );
		wp_schedule_single_event( time() + ( absint( $cancel_unpaid_interval ) * 60 ), 'woocommerce_cancel_unpaid_orders' );

		if ( $held_duration < 1 || 'yes' !== get_option( 'woocommerce_manage_stock' ) ) {
			return;
		}

		$unpaid_orders = $this->get_unpaid_orders( Carbon::now()->subMinutes( $held_duration ) );

		if ( $unpaid_orders ) {
			foreach ( $unpaid_orders as $unpaid_order ) {
				$order = wc_get_order( $unpaid_order );

				if ( apply_filters( 'woocommerce_cancel_unpaid_order', 'checkout' === $order->get_created_via(), $order ) ) {
					$order->update_status( 'cancelled', 'گیت‌لند - ' . __( 'Unpaid order cancelled - time limit reached.', 'gateland' ) );
				}
			}
		}
	}

	public static function verify() {

	}

	public function get_unpaid_orders( Carbon $datetime ): array {

		return \Nabik_Net_Database::DB()
		                          ->select( 'id' )
		                          ->table( 'wc_orders' )
		                          ->whereIn( 'type', wc_get_order_types() )
		                          ->whereIn( 'status', [ 'wc-pending', 'wc-failed' ] )
		                          ->where( 'date_updated_gmt', '<', $datetime )
		                          ->get()
		                          ->pluck( 'id' )
		                          ->toArray();

	}

}
