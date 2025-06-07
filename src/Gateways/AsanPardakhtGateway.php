<?php


namespace Nabik\Gateland\Gateways;

use Exception;
use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Gateways\Features\ShaparakFeature;
use Nabik\Gateland\Models\Transaction;

class AsanPardakhtGateway extends BaseGateway implements ShaparakFeature {

	protected string $name = 'آسان پرداخت';

	protected string $description = 'asanpardakht.ir';

	protected string $url = 'https://l.nabik.net/asanpardakht';

	public function request( Transaction $transaction ): void {
		throw new \Exception( sprintf( "جهت استفاده از درگاه «%s» به نسخه حرفه‌ای ارتقا دهید.", esc_attr( $this->name ) ) );
	}

	public function inquiry( Transaction $transaction ): bool {
		return false;
	}

	public function redirect( Transaction $transaction ) {
	}

	public function currencies(): array {
		return [
			CurrenciesEnum::IRT,
		];
	}

	public function options(): array {
		return [
			[
				'label' => 'نام کاربری',
				'key'   => 'username',
			],
			[
				'label' => 'رمز عبور',
				'key'   => 'password',
			],
			[
				'label' => 'کد پیکربندی',
				'key'   => 'merchant',
			],
		];
	}

	/**
	 * @throws Exception
	 */
	public function AsanPardakhtCurl( string $url, array $data = null ) {
		$curl = curl_init( $url );

		curl_setopt( $curl, CURLOPT_POST, 1 );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $data ) );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 8 );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $this->getHeaders() );

		$response = curl_exec( $curl );

		$error = curl_error( $curl );

		if ( $error ) {
			throw new Exception( $error );
		}

		$statusCode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		if ( ! in_array( $statusCode, [ 200, 472 ] ) ) {
			throw new Exception( sprintf( "خطا «%s» در زمان اتصال به درگاه رخ داده است.", $statusCode ) );
		}

		return json_decode( $response, true );
	}
}
