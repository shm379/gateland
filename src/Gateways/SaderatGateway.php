<?php


namespace Nabik\Gateland\Gateways;


use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Gateways\Features\ShaparakFeature;
use Nabik\Gateland\Models\Transaction;

class SaderatGateway extends BaseGateway implements ShaparakFeature {

	protected string $name = 'بانک صادرات';

	protected string $description = 'پرداخت الکترونیک سپهر - pas';

	protected string $url = 'https://l.nabik.net/sepehrpay';

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
				'label' => 'ترمینال',
				'key'   => 'terminal_id',
			],
		];
	}
}