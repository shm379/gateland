<?php

namespace Nabik\Gateland\Admin;

use Carbon\Carbon;
use Nabik\Gateland\Enums\Transaction\StatusesEnum;
use Nabik\Gateland\Gateland;
use Nabik\Gateland\Models\Transaction;

defined( 'ABSPATH' ) || exit;

class Dashboard {

	public function __construct() {
		add_action( 'wp_ajax_gateland_dashboard', [ $this, 'ajaxResponse' ] );
	}

	public function ajaxResponse() {

		$capability = apply_filters( 'nabik_menu_capability', 'manage_options' );

		if ( ! current_user_can( $capability ) ) {
			wp_send_json( [] );
			exit();
		}

		ob_start();
		self::output();
		$result = ob_get_clean();

		$response = [
			'result' => $result,
		];

		wp_send_json( $response );
		exit();
	}

	public static function output() {
		$result = self::getResponse();
		include GATELAND_DIR . '/templates/admin/dashboard.php';
	}

	public static function getResponse(): array {

		$timezone     = wp_timezone_string();
		$incomePeriod = Gateland::get_option( 'general.dashboard_income_period', 'this_year' );

		$totalIncome = Transaction::query()
		                          ->selectRaw( 'currency, SUM(amount) as income' )
		                          ->where( 'status', StatusesEnum::STATUS_PAID )
		                          ->groupBy( 'currency' );

		$totalTransactions = Transaction::query()
		                                ->selectRaw( 'status, COUNT(*) as total' )
		                                ->groupBy( 'status' )
		                                ->orderByDesc( 'total' );

		if ( $incomePeriod === 'this_year' ) {

			$startOfYear = verta()
				->timezone( $timezone )
				->startYear()
				->toCarbon()
				->utc();

			$totalTransactions->where( 'created_at', '>=', $startOfYear );
			$totalIncome->where( 'created_at', '>=', $startOfYear );

		} elseif ( $incomePeriod === 'last_year' ) {

			$lastYear = verta()
				->timezone( $timezone )
				->subYear()
				->toCarbon()
				->utc();

			$totalTransactions->where( 'created_at', '>=', $lastYear );
			$totalIncome->where( 'created_at', '>=', $lastYear );
		}

		$totalIncome = $totalIncome->get()
		                           ->pluck( 'income', 'currency' )
		                           ->toArray();

		$today = verta()
			->timezone( $timezone )
			->startDay()
			->toCarbon()
			->utc();

		$todayIncome = Transaction::query()
		                          ->selectRaw( 'currency, SUM(amount) as income' )
		                          ->where( 'status', StatusesEnum::STATUS_PAID )
		                          ->where( 'created_at', '>=', $today )
		                          ->groupBy( 'currency' )
		                          ->get()
		                          ->pluck( 'income', 'currency' )
		                          ->toArray();

		$totalTransactions = $totalTransactions->get()
		                                       ->pluck( 'total', 'status' )
		                                       ->toArray();

		$todayTransactions = Transaction::query()
		                                ->selectRaw( 'status, COUNT(*) as total' )
		                                ->where( 'created_at', '>=', $today )
		                                ->groupBy( 'status' )
		                                ->orderByDesc( 'total' )
		                                ->get()
		                                ->pluck( 'total', 'status' )
		                                ->toArray();

		$latestTransactions = Transaction::query()
		                                 ->orderByDesc( 'created_at' )
		                                 ->limit( 10 )
		                                 ->get();

		$incomePeriods = [
			'all'       => 'کل',
			'this_year' => 'امسال',
			'last_year' => 'یکسال گذشته',
		];

		return [
			'total_income'        => self::fill_total( $totalIncome ),
			'today_income'        => self::fill_total( $todayIncome ),
			'total_transactions'  => self::fill_status( $totalTransactions ),
			'today_transactions'  => self::fill_status( $todayTransactions ),
			'latest_transactions' => $latestTransactions,
			'income_period'       => $incomePeriods[ $incomePeriod ],
		];
	}

	public static function fill_total( $total ) {

		if ( empty( $total ) ) {
			$total['IRT'] = 0;
		}

		return $total;
	}

	public static function fill_status( $transactions ) {

		foreach ( StatusesEnum::cases() as $status => $count ) {

			if ( ! isset( $transactions[ $status ] ) ) {
				$transactions[ $status ] = 0;
			}

		}

		return $transactions;
	}

}
