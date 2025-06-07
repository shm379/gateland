<?php

namespace Nabik\Gateland\Gateways\Features;

use Exception;
use Nabik\Gateland\Models\Transaction;

interface RefundFeature {

	/**
	 * @param Transaction $transaction
	 * @param string|null $description
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function refund( Transaction $transaction, string $description = null, ?int $amount = null );

}