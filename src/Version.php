<?php
/**
 * Developer : MahdiY
 * Web Site  : MahdiY.IR
 * E-Mail    : M@hdiY.IR
 */

namespace Nabik\Gateland;

use Illuminate\Database\Schema\Blueprint;
use Nabik\Gateland\Models\Gateway;
use Nabik_Net_Database;
use Nabik_Net_Version;

defined( 'ABSPATH' ) || exit;

class Version extends Nabik_Net_Version {

	protected string $current_version = GATELAND_VERSION;

	public function updated() {

		flush_rewrite_rules();

	}

	public function update_171() {

		if ( Nabik_Net_Database::schema()->hasColumn( 'gateland_transactions', 'national_code' ) ) {
			return;
		}

		Nabik_Net_Database::schema()->table( 'gateland_transactions', function ( Blueprint $table ) {
			$table->string( 'national_code', 20 )->nullable()->after( 'card_number' );
			$table->json( 'allowed_cards' )->nullable()->after( 'card_number' );
			$table->json( 'meta' )->nullable()->after( 'gateway_id' );
			$table->string( 'email' )->nullable()->after( 'ip' );
		} );

	}

	public function update_200() {

		/** @var Gateway[] $gateways */
		$gateways = Gateway::query()
		                   ->whereIn( 'class', [
			                   'Nabik\GatelandPro\Gateways\PasargadGateway',
			                   'Nabik\GatelandPro\Gateways\SamanGateway',
			                   'Nabik\GatelandPro\Gateways\VandarGateway',
		                   ] )
		                   ->get();

		foreach ( $gateways as $gateway ) {
			$gateway->class = str_replace( 'GatelandPro', 'Gateland', $gateway->class );
			$gateway->save();
		}

	}

	public function update_201() {

		/** @var Gateway[] $gateways */
		$gateways = Gateway::query()
		                   ->whereIn( 'class', [
			                   'Nabik\GatelandPro\Gateways\BitPayGateway',
			                   'Nabik\GatelandPro\Gateways\ShepaGateway',
			                   'Nabik\GatelandPro\Gateways\PayPingGateway',
		                   ] )
		                   ->get();

		foreach ( $gateways as $gateway ) {
			$gateway->class = str_replace( 'GatelandPro', 'Gateland', $gateway->class );
			$gateway->save();
		}

	}

}
