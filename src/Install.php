<?php

namespace Nabik\Gateland;

use Illuminate\Database\Schema\Blueprint;
use Nabik\Gateland\Models\Gateway;
use Nabik_Net_Database;

defined( 'ABSPATH' ) || exit;

class Install extends \Nabik_Net_Install {

	public function tasks() {
		self::create_tables();
		self::fix_class_names();
	}

	public static function create_tables() {

		if ( ! Nabik_Net_Database::Schema()->hasTable( 'gateland_gateways' ) ) {

			Nabik_Net_Database::Schema()->create( 'gateland_gateways', function ( Blueprint $table ) {
				$table->id();
				$table->string( 'class', 128 );
				$table->string( 'status', 64 );
				$table->integer( 'sort' );
				$table->text( 'data' );
				$table->string( 'currencies' );
				$table->timestamps();

				$table->index( 'status' );
				$table->index( 'sort' );
				$table->index( 'class' );
			} );

		}

		if ( ! Nabik_Net_Database::Schema()->hasTable( 'gateland_transactions' ) ) {

			Nabik_Net_Database::Schema()->create( 'gateland_transactions', function ( Blueprint $table ) {
				$table->id()->startingValue( 100000 );
				$table->double( 'amount', 12, 2 );
				$table->string( 'currency' );
				$table->text( 'callback' );
				$table->text( 'description' )->nullable();
				$table->integer( 'order_id' );
				$table->string( 'ip' )->nullable();
				$table->string( 'email' )->nullable();
				$table->string( 'gateway_trans_id' )->nullable();
				$table->string( 'gateway_au' )->nullable();
				$table->string( 'gateway_status' )->nullable();
				$table->string( 'status' )->default( 'pending' );
				$table->string( 'card_number' )->nullable();
				$table->json( 'allowed_cards' )->nullable();
				$table->string( 'national_code', 20 )->nullable();
				$table->string( 'mobile' )->nullable();
				$table->foreignId( 'user_id' )->nullable();
				$table->string( 'client' );
				$table->foreignId( 'gateway_id' )->nullable();
				$table->json( 'meta' )->nullable();
				$table->timestamps();
				$table->dateTime( 'paid_at' )->nullable();
				$table->dateTime( 'verified_at' )->nullable();

				$table->index( 'status' );
				$table->index( 'client' );
			} );

		}

		if ( ! Nabik_Net_Database::Schema()->hasTable( 'gateland_logs' ) ) {

			Nabik_Net_Database::Schema()->create( 'gateland_logs', function ( Blueprint $table ) {
				$table->id();
				$table->string( 'ray_id' );
				$table->string( 'event' );
				$table->foreignId( 'transaction_id' )->nullable();
				$table->json( 'data' )->nullable();
				$table->timestamp( 'created_at' );
			} );

		}

	}

	/**
	 * Remove in version 2.0.0
	 *
	 * @return void
	 */
	public static function fix_class_names() {

		/** @var Gateway[] $gateways */
		$gateways = Gateway::query()
		                   ->select( 'class' )
		                   ->get();

		foreach ( $gateways as $gateway ) {

			if ( str_contains( $gateway->class, 'Includes\Gateways' ) ) {

				$gateway->class = str_replace( 'Includes\Gateways', 'Nabik\Gateland\Gateways', $gateway->class );

				$gateway->update( [
					'class' => $gateway->class,
				] );

			}

		}

	}
}

new Install();
