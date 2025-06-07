<?php

namespace Nabik\Gateland\Plugins\LearnPress;

class Addon extends \LP_Addon {

	protected function _includes() {
		require_once 'Gateway.php';
	}

	protected function _init_hooks() {
		Gateway::webhook();
		add_filter( 'learn-press/payment-methods', [ $this, 'add_payment' ] );
	}

	public function add_payment( array $methods ): array {

		$methods['gateland'] = Gateway::class;

		return $methods;
	}
}