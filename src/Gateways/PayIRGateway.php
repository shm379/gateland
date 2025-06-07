<?php


namespace Nabik\Gateland\Gateways;

use Exception;
use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Gateways\Features\ShaparakFeature;
use Nabik\Gateland\Models\Transaction;

class PayIRGateway extends BaseGateway implements ShaparakFeature {

	protected string $name = 'پی.آی.آر';

	protected string $description = 'pay.ir';

	protected string $url = 'https://l.nabik.net/payir';

	/**
	 * @param Transaction $transaction
	 *
	 * @return bool|mixed
	 * @throws Exception
	 */
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
				'label'       => 'کلید API',
				'key'         => 'api_key',
				'description' => 'برای تست درگاه می توانید از کلید test استفاده کنید.',
			],
		];
	}
}
