<?php


namespace Nabik\Gateland\Gateways;


use Exception;
use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Enums\Transaction\StatusesEnum;
use Nabik\Gateland\Exceptions\InquiryException;
use Nabik\Gateland\Exceptions\VerifyException;
use Nabik\Gateland\Gateways\Features\ShaparakFeature;
use Nabik\Gateland\Models\Transaction;

class SamanGateway extends BaseGateway implements \Nabik\Gateland\Gateways\Features\FreeFeature, ShaparakFeature {

	protected string $name = 'بانک سامان';

	protected string $description = 'سامان کیش - sep';

	protected string $url = 'https://l.nabik.net/sep';

	public function request( Transaction $transaction ): void {
		$this->log( $transaction, 'request', [
			'transaction' => $transaction->toArray(),
		] );

		$parameters = [
			'Action'           => 'token',
			'TerminalId'       => $this->options['terminal_id'],
			'Amount'           => $transaction->amount * 10,
			'RedirectUrl'      => $transaction->gateway_callback,
			'ResNum'           => $transaction->id,
			'TokenExpiryInMin' => 60,
		];

		if ( $transaction->mobile ) {
			$parameters['CellNumber'] = str_replace( '+98', '', $transaction->mobile );
		}

		try {

			$url      = 'https://sep.shaparak.ir/onlinepg/onlinepg';
			$response = $this->curl( $url, $parameters );

			$this->log( $transaction, 'paymentRequest', [
				'parameters' => $parameters,
				'response'   => $response,
			] );

		} catch ( Exception $e ) {

			$this->log( $transaction, 'requestFailed', [
				'parameters' => $parameters,
				'error'      => $e->getMessage(),
			] );

			throw new Exception( 'خطا در اتصال به درگاه! لطفا دوباره تلاش کنید.' );
		}

		if ( $response['status'] !== 1 ) {
			throw new Exception( $response['errorDesc'], $response['errorCode'] );
		}

		$transaction->update( [
			'gateway_au' => $response['token'],
		] );

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

		$RefNum = filter_input( INPUT_POST, 'RefNum' );

		if ( empty( $RefNum ) ) {

			$this->log( $transaction, 'verifyFailed', [
				'RefNum' => $RefNum,
			] );

			return false;
		}

		$parameters = [
			'RefNum'             => $RefNum,
			'TerminalNumber'     => $this->options['terminal_id'],
			'CellNumber'         => '',
			'NationalCode'       => '',
			'IgnoreNationalcode' => true,
		];

		if ( $transaction->mobile ) {
			$parameters['CellNumber'] = $transaction->mobile;
		}

		$headers = [
			'Content-Type: application/json',
		];

		try {

			$url      = 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTranscation';
			$response = $this->curl( $url, json_encode( $parameters ), $headers );

			$this->log( $transaction, 'verifyRequest', [
				'parameters' => $parameters,
				'headers'    => $headers,
				'response'   => $response,
			] );

		} catch ( Exception $e ) {

			$this->log( $transaction, 'requestFailed', [
				'parameters' => $parameters,
				'error'      => $e->getMessage(),
			] );

			throw new VerifyException();
		}

		$paid_statuses = [
			0, // Success - First verify
			2, // Success - Duplicate Verify
		];

		$is_paid = in_array( $response['ResultCode'], $paid_statuses );

		if ( $is_paid ) {
			$this->log( $transaction, 'verifySuccess' );

			$transaction->update( [
				'gateway_trans_id' => $response['TransactionDetail']['StraceNo'],
				'gateway_status'   => $response['ResultCode'],
				'status'           => StatusesEnum::STATUS_PAID,
				'card_number'      => $response['TransactionDetail']['MaskedPan'],
				'paid_at'          => \Carbon\Carbon::now(),
			] );

			return true;
		}

		throw new InquiryException( $response['ResultCode'] );
	}

	public function redirect( Transaction $transaction ) {
		$this->log( $transaction, 'redirect', [
			'transaction' => $transaction->toArray(),
		] );

		$url = sprintf( 'https://sep.shaparak.ir/OnlinePG/SendToken?token=%s', $transaction->gateway_au );

		return wp_redirect( $url );
	}

	public function currencies(): array {
		return [
			CurrenciesEnum::IRT,
		];
	}

	public function options(): array {
		return [
			[
				'label' => 'شناسه ترمینال',
				'key'   => 'terminal_id',
			],
		];
	}
}