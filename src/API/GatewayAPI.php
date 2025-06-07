<?php

namespace Nabik\Gateland\API;

use Nabik\Gateland\Gateways\BaseGateway;
use Nabik\Gateland\Models\Gateway;
use Nabik\Gateland\Services\GatewayService;
use Nabik\GatelandPro\GatelandPro;
use WP_REST_Request;

class GatewayAPI extends RestAPI {

	public function register_routes() {

		register_rest_route( 'gateland/gateway', 'list', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'list' ],
			'permission_callback' => [ $this, 'permission_callback' ],
		] );

		register_rest_route( 'gateland/gateway', 'get-options', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'get_options' ],
			'permission_callback' => [ $this, 'permission_callback' ],
		] );

		register_rest_route( 'gateland/gateway', 'add', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'add' ],
			'permission_callback' => [ $this, 'permission_callback' ],
		] );

		register_rest_route( 'gateland/gateway', 'index', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'index' ],
			'permission_callback' => [ $this, 'permission_callback' ],
		] );

		register_rest_route( 'gateland/gateway', 'sort', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'sort' ],
			'permission_callback' => [ $this, 'permission_callback' ],
		] );

		register_rest_route( 'gateland/gateway', 'change-status', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'change_status' ],
			'permission_callback' => [ $this, 'permission_callback' ],
		] );

	}

	public function list( WP_REST_Request $request ) {

		$gateways = [];

		foreach ( GatewayService::loaded() as $gateway ) {

			$class = str_replace( 'Nabik\\Gateland\\Gateways\\', '', get_class( $gateway ) );

			$features = array_values( class_implements( $gateway ) );
			$features = array_map( function ( $feature ) {
				return str_replace( 'Nabik\\Gateland\\Gateways\\Features\\', '', $feature );
			}, $features );

			$gateways[] = [
				'class'       => $class,
				'name'        => $gateway->name(),
				'description' => $gateway->description(),
				'url'         => $gateway->url(),
				'icon'        => $gateway->icon(),
				'features'    => $features,
			];

		}

		return self::response( true, null, [
			'is_pro_active' => class_exists( GatelandPro::class ) && GatelandPro::is_active(),
			'gateways'      => $gateways,
		] );
	}

	public function get_options( WP_REST_Request $request ) {

		$class = $request->get_param( 'class' );
		$class = 'Nabik\\Gateland\\Gateways\\' . $class;

		$valid_class = collect( GatewayService::loaded() )
			->map( function ( $gateway ) {
				return get_class( $gateway );
			} )
			->search( $class );

		if ( ! $valid_class ) {
			self::response( false, 'درگاه انتخاب شده معتبر نمی‌باشد.' );
		}

		/** @var BaseGateway $gateway */
		$gateway = new $class();

		self::response( true, null, [
			'options' => $gateway->options(),
		] );
	}

	public function add( WP_REST_Request $request ) {

		$class = $request->get_param( 'class' );
		$class = 'Nabik\\Gateland\\Gateways\\' . $class;

		$valid_class = collect( GatewayService::loaded() )
			->map( function ( $gateway ) {
				return get_class( $gateway );
			} )
			->search( $class );

		if ( ! $valid_class ) {
			self::response( false, 'درگاه انتخاب شده معتبر نمی‌باشد.' );
		}

		$data = $request->get_param( 'data' );

		if ( empty( $data ) ) {
			self::response( false, 'تنظیمات درگاه نمی‌تواند خالی باشد.' );
		}

		if ( ! is_array( $data ) ) {
			self::response( false, 'تنظیمات باید به صورت آرایه باشد.' );
		}

		/** @var Gateway $gateway */
		$gateway = Gateway::create( [
			'class'      => $class,
			'status'     => 'active',
			'data'       => wp_json_encode( $data ),
			'currencies' => wp_json_encode( ( new $class )->currencies() ),
		] );

		GatewayService::reset_activated();

		self::response( true, null, [
			'gateway_id' => $gateway->id,
		] );
	}

	public function index( WP_REST_Request $request ) {

		$gateways = Gateway::query()
		                   ->orderBy( 'sort' )
		                   ->get()
		                   ->map( function ( Gateway $gateway ) {

			                   $builder = $gateway->build();

			                   return [
				                   'id'          => $gateway->id,
				                   'name'        => $builder->name(),
				                   'description' => $builder->description(),
				                   'url'         => $builder->url(),
				                   'icon'        => $builder->icon(),
				                   'sort'        => $gateway->sort,
				                   'status'      => $gateway->status,
			                   ];
		                   } )
		                   ->toArray();


		return self::response( true, null, [
			'is_pro_active' => class_exists( GatelandPro::class ) && GatelandPro::is_active(),
			'gateways'      => $gateways,
		] );
	}

	public function sort( WP_REST_Request $request ) {

		$gateway_ids = $request->get_param( 'gateway_ids' );

		if ( ! is_array( $gateway_ids ) ) {
			self::response( false, 'لیست درگاه‌ها معتبر نمی‌باشد.' );
		}

		// @todo check all gateways received

		try {

			collect( $gateway_ids )->each( function ( int $gateway_id, int $key ) {
				Gateway::query()->findOrFail( $gateway_id )->update( [ 'sort' => $key + 1 ] );
			} );

		} catch ( \Exception $e ) {
			return self::response( false, $e->getMessage() );
		}

		return self::response( false, 'درگاه‌ها با موفقیت ذخیره شدند.' );
	}

	public function change_status( WP_REST_Request $request ) {

		$gateway_id = $request->get_param( 'gateway_id' );
		$status     = $request->get_param( 'status' );

		if ( ! in_array( $status, [ 'active', 'inactive' ] ) ) {
			return self::response( false, 'وضعیت وارد شده معتبر نمی‌باشد.' );
		}

		try {

			/** @var Gateway $gateway */
			$gateway = Gateway::query()->findOrFail( $gateway_id );

		} catch ( \Exception $e ) {
			return self::response( false, $e->getMessage() );
		}

		$gateway->status = $status;
		$gateway->save();

		return self::response( true, 'وضعیت با موفقیت ذخیره شد.' );
	}

	public function permission_callback( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}
}