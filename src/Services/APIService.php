<?php

namespace Nabik\Gateland\Services;

use Nabik\Gateland\API\GatewayAPI;
use Nabik\Gateland\API\PaymentAPI;

class APIService {

	public function __construct() {
		new GatewayAPI();
		new PaymentAPI();
	}

}