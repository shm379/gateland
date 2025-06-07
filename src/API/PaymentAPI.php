<?php

namespace Nabik\Gateland\API;

use Nabik\Gateland\Pay;
use WP_REST_Request;

class PaymentAPI extends RestAPI {

	public function register_routes() {

		register_rest_route( 'gateland/payment', '(?P<id>\d+)/start', [
			'methods'             => [ 'GET', 'POST' ],
			'callback'            => [ $this, 'start' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'gateland/payment', '(?P<id>\d+)/callback', [
			'methods'             => [ 'GET', 'POST' ],
			'callback'            => [ $this, 'callback' ],
			'permission_callback' => '__return_true',
		] );

	}

	public function start( WP_REST_Request $request ) {

		$transaction_id = $request->get_param( 'id' );

		header( 'Content-Type: text/html' );

		Pay::pay( $transaction_id );

		exit();
	}

	public function callback( WP_REST_Request $request ) {

		$transaction_id = $request->get_param( 'id' );

		header( 'Content-Type: text/html' );

		Pay::callback( $transaction_id, $request->get_param( 'sign' ) );

		exit();
	}

}