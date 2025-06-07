<?php


namespace Nabik\Gateland\Gateways;


use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Gateways\Features\ShaparakFeature;
use Nabik\Gateland\Models\Transaction;

class BahamtaGateway extends BaseGateway implements ShaparakFeature {

	protected string $name = 'باهمتا';

	protected string $description = 'bahamta.com';

	protected string $url = 'https://l.nabik.net/bahamta';

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
				'label'       => 'کلید وب سرویس',
				'key'         => 'api_key',
				'description' => 'ساختار درست کلید: webpay:xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx:zzzzzzzz-zzzz-zzzz-zzzz-zzzzzzzzzzzz',
			],
		];
	}
}