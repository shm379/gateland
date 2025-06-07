<?php


namespace Nabik\Gateland\Gateways;


use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Gateways\Features\BNPLFeature;
use Nabik\Gateland\Gateways\Features\InquiryFeature;
use Nabik\Gateland\Models\Transaction;

class SnappPayGateway extends BaseGateway implements BNPLFeature, InquiryFeature {

	protected string $name = 'اسنپ پی';

	protected string $description = 'snapppay.ir';

	protected string $url = 'https://l.nabik.net/snapppay';

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
		return [];
	}
}