<?php


namespace Nabik\Gateland\Gateways;

use Exception;
use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Enums\Transaction\StatusesEnum;
use Nabik\Gateland\Exceptions\InquiryException;
use Nabik\Gateland\Exceptions\VerifyException;
use Nabik\Gateland\Gateways\Features\InquiryFeature;
use Nabik\Gateland\Gateways\Features\RefundFeature;
use Nabik\Gateland\Gateways\Features\ShaparakFeature;
use Nabik\Gateland\Models\Transaction;

class ZibalGateway extends BaseGateway implements \Nabik\Gateland\Gateways\Features\FreeFeature, InquiryFeature, RefundFeature, ShaparakFeature {

	protected string $name = 'زیبال';

	protected string $description = 'zibal.ir';

	protected string $url = 'https://l.nabik.net/zibal';

	/**
	 * @param Transaction $transaction
	 *
	 * @return void
	 * @throws Exception
	 */
	public function request( Transaction $transaction ): void {
		$this->log( $transaction, 'request', [
			'transaction' => $transaction->toArray(),
		] );

		$parameters = [
			'merchant'    => $this->options['merchant'],
			'callbackUrl' => $transaction->gateway_callback,
			'amount'      => intval( $transaction->amount * 10 ),
			'orderId'     => $transaction->id,
			'description' => $transaction->description ?? '',
			'reseller'    => 'woocommerce',
		];

		if ( $transaction->mobile ) {
			$parameters['mobile'] = str_replace( '+98', '0', $transaction->mobile );

			if ( $this->options['checkMobileWithCard'] ) {
				$parameters['checkMobileWithCard'] = true;
			}
		}

		if ( $transaction->allowed_cards ) {
			$parameters['allowedCards'] = $transaction->allowed_cards;
		}

		if ( ! empty( trim( $this->options['multiplexing'] ) ) ) {
			$parameters['percentMode']       = 1;
			$parameters['feeMode']           = 0;
			$parameters['multiplexingInfos'] = json_decode( $this->options['multiplexing'] );
		}

		$headers = [
			'Content-Type: application/json',
		];

		try {

			$url      = sprintf( 'https://gateway.zibal.%s/v1/request', $this->get_tld() );
			$response = $this->curl( $url, json_encode( $parameters ), $headers );

			$this->log( $transaction, 'paymentRequest', [
				'parameters' => $parameters,
				'headers'    => $headers,
				'response'   => $response,
			] );

		} catch ( Exception $e ) {

			$this->log( $transaction, 'requestFailed', [
				'parameters' => $parameters,
				'headers'    => $headers,
				'error'      => $e->getMessage(),
			] );

			throw new Exception( 'خطا در اتصال به درگاه! لطفا دوباره تلاش کنید.' );
		}

		if ( $response['result'] == 100 ) {

			$transaction->update( [
				'gateway_au' => strval( $response['trackId'] ),
			] );

			return;
		}

		throw new Exception( $response['message'] );
	}

	/**
	 * @param Transaction $transaction
	 *
	 * @return bool
	 * @throws InquiryException
	 * @throws VerifyException
	 */
	public function inquiry( Transaction $transaction ): bool {
		$this->log( $transaction, 'inquiry', [
			'transaction' => $transaction->toArray(),
		] );

		$parameters = [
			'merchant' => $this->options['merchant'],
			'trackId'  => $transaction->gateway_au,
		];

		$headers = [
			'Content-Type: application/json',
		];

		try {
			$url      = sprintf( 'https://gateway.zibal.%s/v1/verify', $this->get_tld() );
			$response = $this->curl( $url, json_encode( $parameters ), $headers );

			$this->log( $transaction, 'verifyRequest', [
				'parameters' => $parameters,
				'headers'    => $headers,
				'response'   => $response,
			] );

		} catch ( Exception $e ) {

			$this->log( $transaction, 'requestFailed', [
				'parameters' => $parameters,
				'headers'    => $headers,
				'error'      => $e->getMessage(),
			] );

			throw new VerifyException();
		}

		$paid_statuses = [
			100, // Success - First verify
			201, // Success - Duplicate Verify
		];

		$is_paid = in_array( $response['result'], $paid_statuses );

		if ( $is_paid ) {
			$this->log( $transaction, 'verifySuccess' );

			$transaction->update( [
				'gateway_trans_id' => $response['refNumber'] ?? null,
				'gateway_status'   => $response['result'],
				'status'           => StatusesEnum::STATUS_PAID,
				'card_number'      => $response['cardNumber'] ?? null,
				'paid_at'          => \Carbon\Carbon::now(),
			] );

			return true;
		}

		throw new InquiryException( $response['result'] );
	}

	public function redirect( Transaction $transaction ) {
		$this->log( $transaction, 'redirect', [
			'transaction' => $transaction->toArray(),
		] );

		$url = sprintf( 'https://gateway.zibal.%s/start/%s', $this->get_tld(), $transaction->gateway_au );

		return wp_redirect( $url );
	}

	public function refund( Transaction $transaction, string $description = null, ?int $amount = null ) {
		$this->log( $transaction, 'refund', [
			'transaction' => $transaction->toArray(),
			'description' => $description,
		] );

		if ( empty( $this->options['api_token'] ) ) {
			throw new Exception( 'توکن وب سرویس تنظیم نشده است. از منو درگاه‌ها، تنظیمات درگاه زیبال آن را تنظیم کنید.' );
		}

		$parameters = [
			'trackId'     => $transaction->gateway_au,
			'description' => $description,
		];

		if ( $this->options['refund_wallet_id'] ) {
			$parameters['walletId'] = $this->options['refund_wallet_id'];
			$parameters['wageMode'] = 3;
		}

		if ( $amount ) {
			$parameters['amount'] = $amount * 10;
		}

		if ( $transaction->amount > 10000000 || $amount > 10000000 ) {
			$parameters['split'] = true;
		}

		try {
			$headers = [
				'Accept: application/json',
				'Authorization: Bearer ' . $this->options['api_token'],
			];

			$url = sprintf( 'https://api.zibal.%s/v1/wallet/refund', $this->get_tld() );

			$response = $this->curl( $url, $parameters, $headers );

			$this->log( $transaction, 'refundRequest', [
				'parameters' => $parameters,
				'response'   => json_encode( $response ),
			] );

		} catch ( Exception $exception ) {

			$this->log( $transaction, 'requestFailed', [
				'parameters' => $parameters,
				'error'      => $exception->getMessage(),
			] );

			throw new Exception( 'خطایی در زمان اتصال به درگاه زیبال رخ داده است.' );
		}

		if ( $response['result'] != 1 ) {
			throw new Exception( $response['message'] );
		}

		// Success refund

		$transaction->status = StatusesEnum::STATUS_REFUND;
		$transaction->save();

		$transaction->refund()->create( [
			'amount'      => $transaction->amount,
			'description' => $description,
			'refund_id'   => $response['refundId'],
		] );
	}

	/**
	 * @return CurrenciesEnum[]
	 */
	public function currencies(): array {
		return [
			CurrenciesEnum::IRT,
		];
	}

	public function options(): array {
		return [
			[
				'label'       => 'کلید API',
				'key'         => 'merchant',
				'description' => 'برای تست درگاه می توانید از کلید zibal استفاده کنید.',
			],
			[
				'label'       => 'تسهیم',
				'key'         => 'multiplexing',
				'type'        => 'textarea',
				'placeholder' => '[
	{"amount": 20, "bankAccount": "IR540560004120008723456756"},
	{"amount": 80, "bankAccount": "IR670560004120003456572344"}
]',
				'description' => 'آیتم تسهیم را طبق مستندات وارد کنید در غیر اینصورت خالی بگذارید.',
				'style'       => 'direction: ltr;',
			],
			[
				'label'       => 'تطبیق شماره موبایل با کارت',
				'key'         => 'checkMobileWithCard',
				'type'        => 'checkbox',
				'description' => 'در صورت تمایل به تطبیق مالک شماره موبایل و مالک کارت تیک بزنید.',
			],
			[
				'label'       => 'هاست خارج از ایران',
				'key'         => 'non-iran-host',
				'type'        => 'checkbox',
				'description' => 'در صورتی که هاست میزبانی شما خارج از ایران است، جهت اتصال بهتر تیک بزنید.',
			],
			[
				'label'       => 'عودت وجه',
				'key'         => 'refund',
				'type'        => 'section',
				'description' => 'از این بخش می‌توانید قابلیت استرداد را تنظیم و فعال نمایید.',
			],
			[
				'label'       => 'توکن وب سرویس عودت وجه',
				'key'         => 'api_token',
				'description' => 'کلید وب سرویس را از از پنل زیبال منو «توسعه‌دهندگان» دریافت کنید.',
			],
			[
				'label'       => 'شناسه کیف پول عودت وجه',
				'key'         => 'refund_wallet_id',
				'description' => 'در صورت تمایل شناسه کیف پول را وارد نمایید، در غیر اینصورت از کیف پول کارمزد عودت داده می‌شود.',
			],
		];
	}

	private function get_tld(): string {
		return ( $this->options['non-iran-host'] ?? false ) ? 'io' : 'ir';
	}
}
