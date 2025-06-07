<?php

namespace Nabik\Gateland;

use Exception;
use Nabik\Gateland\Exceptions\ValidationErrorException;
use WP_REST_Request;

class Rest {

	public function __construct() {
		add_filter( 'nabik/gateland/transaction_clients', [ $this, 'add_gateland_client' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function add_gateland_client( array $clients ): array {

		$clients['api'] = 'وب سرویس';

		return $clients;
	}

	public function register_routes() {

		register_rest_route( 'gateland/v1', 'request', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'request' ],
			'permission_callback' => [ $this, 'permission' ],
		] );

		register_rest_route( 'gateland/v1', 'pay/(?P<id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'pay' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'gateland/v1', 'callback/(?P<id>\d+)', [
			'methods'             => [ 'GET', 'POST' ],
			'callback'            => [ $this, 'callback' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'gateland/v1', 'verify/(?P<id>\d+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'verify' ],
			'permission_callback' => [ $this, 'permission' ],
		] );

	}

	public function request( WP_REST_Request $request ) {

		try {

			$data = $request->get_body_params();

			$data['client'] = 'api';

			return Pay::request( $data );

		} catch ( ValidationErrorException $e ) {
			self::response( false, null, $e->getErrors()->toArray() );
		} catch ( Exception $e ) {
			self::response( false, $e->getMessage() );
		}

	}

	public function pay( WP_REST_Request $request ) {

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

	public function verify( WP_REST_Request $request ) {

		$transaction_id = $request->get_param( 'id' );

		return Pay::verify( $transaction_id, 'api' );
	}

	public static function permission( WP_REST_Request $request ): bool {
		return $request->get_param( 'merchant' ) == md5( AUTH_KEY );
	}

	public static function response( bool $success, string $message = null, array $data = [] ) {

		echo wp_json_encode( [
			'success' => $success,
			'message' => $message,
			'data'    => $data,
		] );

		die();
	}
}
