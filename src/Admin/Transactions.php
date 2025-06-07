<?php

namespace Nabik\Gateland\Admin;

use Carbon\Carbon;
use Exception;
use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Enums\Transaction\StatusesEnum;
use Nabik\Gateland\Helper;
use Nabik\Gateland\Models\Gateway;
use Nabik\Gateland\Models\Transaction;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use WP_List_Table;
use WP_User;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Transactions extends WP_List_Table {

	public function __construct( $args = [] ) {

		$this->handleExportExcel();

		parent::__construct( [
			'singular' => 'تراکنش',
			'plural'   => 'تراکنش‌ها',
			'ajax'     => false,
		] );
	}

	private function handleExportExcel() {

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'export_excel' ) {
			return;
		}

		/** @var Transaction[] $transactions */
		$transactions = $this->get_table_data()
		                     ->get()
		                     ->all();

		if ( empty( $transactions ) ) {

			if ( session_status() !== PHP_SESSION_ACTIVE ) {
				session_start();
			}

			$_SESSION['errors'] = [
				'message' => 'تراکنشی یافت نشد',
			];

			$url = add_query_arg( [
				'action' => null,
			] );

			wp_redirect( $url );
			die();
		}

		$date = verta()->format( "Y-m-d" );

		$current_user = wp_get_current_user();

		if ( $current_user instanceof WP_User ) {
			$user_name = $current_user->display_name;
		}

		$exportData = [
			'Exported at' => Helper::date( \Illuminate\Support\Carbon::now() ),
			'Exported by' => $user_name ?? null,
		];

		if ( ! empty( $_GET['from_date'] ) ) {
			$exportData['Start At'] = Helper::date( Carbon::createFromTimestamp( intval( $_GET['from_date'] ) ),
				'Y/m/d' );
		}
		if ( ! empty( $_GET['to_date'] ) ) {
			$exportData['End At'] = Helper::date( Carbon::createFromTimestamp( intval( $_GET['to_date'] ) ), 'Y/m/d' );
		}

		$searchKeys = [
			'status',
			'currency',
			'client',
			'gateway',
			'card_number',
			'description',
			'gateway_au',
			'min_amount',
			'max_amount',
			'ip',
			'transaction_id',
			'created_at',
			'amount',
			'order_id',
			'mobile',
		];

		$filters = [];

		foreach ( $_GET as $key => $value ) {
			if ( ! in_array( $key, $searchKeys ) || empty( $value ) ) {
				continue;
			}
			$filters[] = $this->getKeyName( $key ) . ': ';
			$filters[] = $this->getRequestValues( $key, $value );
		}

		// todo check pro version and Spreadsheet class
		$spreadsheet = new Spreadsheet();
		$sheet       = $spreadsheet->getActiveSheet();

		$sheet->fromArray( $filters, null, 'A1' );

		$index = 2;

		foreach ( $exportData as $label => $value ) {
			$sheet->fromArray( [ $label, $value ], null, 'A' . ++ $index );
		}

		$sheet->fromArray( $this->mapTransactionsForExport( $transactions ), null, 'A' . ( $index + 2 ) );

		$rowRange = 'A' . ( $index + 2 ) . ':' . $sheet->getHighestColumn() . ( $index + 2 );
		$style    = $sheet->getStyle( $rowRange );
		$font     = $style->getFont();
		$font->setBold( true );

		$upload_dir = wp_upload_dir();
		$directory  = $upload_dir['basedir'] . '/gateland/exports/';
		$filename   = 'transactions-' . $date . '.xlsx';

		if ( ! is_dir( $directory ) ) {
			mkdir( $directory, 0777, true );
		}

		try {
			$writer = IOFactory::createWriter( $spreadsheet, 'Xlsx' );
			$writer->save( $directory . '/' . $filename );
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );

			if ( session_status() !== PHP_SESSION_ACTIVE ) {
				session_start();
			}

			$_SESSION['errors'] = [
				'message' => 'مشکلی رخ داده است.',
			];

			$url = add_query_arg( [
				'action' => null,
			] );

			wp_redirect( $url );
			die();
		}
		ob_clean();

		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: max-age=0' );

		// Send the file to the browser for download
		readfile( $directory . '/' . $filename );
		wp_delete_file( $directory . '/' . $filename );

		exit;
	}

	/**
	 * @param Transaction[] $transactions
	 *
	 * @return array|array[]
	 * @throws Exception
	 */
	private function mapTransactionsForExport( array $transactions ): array {
		$header = [
			'id',
			'order_id',
			'mobile',
			'client',
			'gateway',
			'status',
			'amount',
			'card_number',
			'ip',
			'gateway_trans_id',
			'gateway_au',
			'gateway_status',
			'created_at',
			'paid_at',
			'verified_at',
			'callback',
			'description',
		];

		$transactions = array_map( function ( $transaction ) {
			return [
				$transaction->id,
				$transaction->order_id,
				$transaction->mobile,
				$transaction->client,
				$transaction->gateway ? $transaction->gateway->build()->name() : '',
				StatusesEnum::tryFrom( $transaction->status )->name(),
				$transaction->amount,
				$transaction->card_number,
				$transaction->ip,
				$transaction->gateway_trans_id,
				$transaction->gateway_au,
				$transaction->gateway_status,
				$transaction->created_at,
				$transaction->paid_at,
				$transaction->verified_at,
				$transaction->callback,
				$transaction->description,
			];
		}, $transactions );

		array_unshift( $transactions, $header );

		return $transactions;
	}

	public function get_columns(): array {
		return [
			'transaction_id' => 'شماره تراکنش',
			'client'         => 'پذیرنده',
			'gateway'        => 'درگاه',
			'created_at'     => 'تاریخ ایجاد',
			'amount'         => 'مبلغ',
			'order_id'       => 'شناسه سفارش',
			'mobile'         => 'شماره موبایل',
			'status'         => 'وضعیت',
			'details'        => 'جزئیات',
		];
	}

	public function print_column_headers( $with_id = true ) {

		if ( ! $with_id ) {
			parent::print_column_headers( $with_id );

			return;
		}

		$columns = $this->get_columns();

		$integerInputs = [ 'transaction_id', 'amount', 'order_id' ];
		$textInputs    = [ 'mobile' ];
		$dateInputs    = [ 'created_at' ];

		foreach ( $columns as $key => $label ) {
			if ( in_array( $key, $textInputs ) ) {
				$this->getInput( $key, $label );
			} elseif ( in_array( $key, $integerInputs ) ) {
				$this->getInput( $key, $label, true );
			} elseif ( in_array( $key, $dateInputs ) ) {
				$this->getDateInput( $key, $label );
			} else {
				printf( '<th scope="col" class="manage-column column-transaction_id column-primary"><strong>%s</strong></th>', esc_html( $label ) );
			}
		}
	}

	private function getInput( $key, $label, $isNumber = false ) {
		?>
		<th class="px-4 py-3">
			<div class="d-flex flex-row gap-2 position-relative align-items-center">
				<div class="w-15 rounded" data-dropdown="<?php echo esc_attr( $key ); ?>">
					<?php if ( ! empty( $_GET[ $key ] ) ): ?>
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
							 stroke-width="1.5" stroke="black" class="w-6 h-6">
							<path stroke-linecap="round" stroke-linejoin="round"
								  d="M4.5 12.75l6 6 9-13.5"/>
						</svg>
					<?php else: ?>
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
							 stroke-width="1.5" stroke="black" class="w-6 h-6">
							<path stroke-linecap="round" stroke-linejoin="round"
								  d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
						</svg>
					<?php endif; ?>
				</div>
				<div
						id="<?php echo esc_attr( $key ); ?>"
						class="position-absolute custom-dropdown dropdown-menu d-none">
					<div class="d-flex flex-column px-1">
						<input class="form-control me-0"
							   type="text"
							<?php if ( $isNumber ): ?>
								oninput="this.value = this.value.replace(/[^0-9]/g, '')"
							<?php endif; ?>
							   id="<?php echo esc_attr( $key ); ?>"
							   name="<?php echo esc_attr( $key ); ?>"
							   value="<?php echo esc_attr( $_GET[ $key ] ?? '' ); ?>"
							   style="direction: ltr;"
							   placeholder="<?php echo esc_attr( $label ); ?>">
					</div>
				</div>
				<span><strong><?php echo esc_html( $label ); ?></strong></span>
			</div>
		</th>
		<?php
	}

	private function getDateInput( $key, $label ) {

		$value = intval( $_GET[ $key ] ?? 0 );

		?>
		<th class="px-4 py-3">
			<div class="d-flex flex-row gap-2 position-relative align-items-center">
				<div class="w-15 rounded" data-dropdown="<?php echo esc_attr( $key ); ?>">
					<?php
					if ( ! empty( $value ) ): ?>
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
							 stroke-width="1.5" stroke="black" class="w-6 h-6">
							<path stroke-linecap="round" stroke-linejoin="round"
								  d="M4.5 12.75l6 6 9-13.5"/>
						</svg>
					<?php else: ?>
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
							 stroke-width="1.5" stroke="black" class="w-6 h-6">
							<path stroke-linecap="round" stroke-linejoin="round"
								  d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
						</svg>
					<?php endif; ?>
				</div>
				<div id="<?php echo esc_attr( $key ); ?>"
					 class="position-absolute custom-dropdown dropdown-menu d-none">
					<div class="d-flex flex-column px-1">
						<input data-<?php echo esc_attr( str_replace( '_', '-', $key ) ); ?>
							   data-datepicker-class="datepicker-plot-area"
							   id="<?php echo esc_attr( $key . '_picker' ); ?>"
							   autocomplete="off"
							   value="<?php echo esc_attr( empty( $value ) ? '' : Helper::date( Carbon::now()->timestamp( $value ), 'Y-m-d' ) ); ?>"
							   class="block w-full h-8 text-sm border border-dark-5 focus:border-dark-9 rounded px-4 placeholder:font-normal">

						<input type="hidden"
							   name="<?php echo esc_attr( $key ); ?>"
							   id="<?php echo esc_attr( $key . '_value' ); ?>"
							   value="<?php echo intval( $value ?? '' ); ?>">
					</div>
				</div>
				<span><strong><?php echo esc_html( $label ); ?></strong></span>
			</div>
		</th>
		<?php
	}

	/**
	 * @param Transaction $item
	 * @param string      $column_name
	 *
	 * @return int|string|void
	 * @throws \Exception
	 */
	protected function column_default( $item, $column_name ) {

		$orderUrl = $item->getClientOrderUrl();
		$status   = StatusesEnum::tryFrom( $item->status );

		switch ( $column_name ) {
			case 'transaction_id':
				return Helper::fa_num( $item->id );
			case 'client':
				return $item->client_label;
			case 'gateway':
				return $item->gateway_label;
			case 'created_at':
				return Helper::fa_num( Helper::date( $item->created_at, 'Y/m/d H:i' ) );
			case 'amount':
				return Helper::fa_num( CurrenciesEnum::tryFrom( $item->currency )->price( $item->amount ) );
			case 'order_id':
				return "<a href='$orderUrl' target='_blank'>" . Helper::fa_num( $item->order_id ) . "</a>";
			case 'status':
				return sprintf( "<span style='%s'>%s</span>", $status->style(), $status->name() );
			case 'mobile':
				return Helper::mobile( $item->mobile );
			case 'details':
				return $this->getDetailsButton( $item->id );
			default:
				return print_r( $item, true );
		}
	}

	public function getDetailsButton( int $transaction_id ): string {
		$transactionUrl = menu_page_url( 'gateland-transactions', false );
		$transactionUrl = add_query_arg( [
			'transaction_id' => $transaction_id,
		], $transactionUrl );

		return sprintf( '<a href="%s" class="button button-primary">جزئیات</a>', esc_url( $transactionUrl ) );
	}

	public function prepare_items() {
		$this->_column_headers = [ $this->get_columns(), [], [] ];

		$per_page = $this->get_items_per_page( 'transactions_per_page', 20 );

		$data = $this->get_table_data();

		$this->set_pagination_args( [
			'total_items' => $data->count(),
			'per_page'    => $per_page,
		] );
		$this->items = $data->forPage( $this->get_pagenum(), $per_page )->get();
	}

	private function get_table_data() {

		$transactions = Transaction::query()
		                           ->with( 'gateway' )
		                           ->orderByDesc( 'created_at' );

		$timezone = wp_timezone_string();

		if ( ! empty( $_GET['client'] ) ) {
			$clients = array_map( 'intval', $_GET['client'] );
			$transactions->whereIn( 'client', $clients );
		}

		if ( ! empty( $_GET['status'] ) ) {
			$statuses = array_map( 'sanitize_text_field', $_GET['status'] );
			$transactions->whereIn( 'status', $statuses );
		}

		if ( ! empty( $_GET['gateway'] ) ) {
			$gateways = array_map( 'intval', $_GET['gateway'] );
			$transactions->whereIn( 'gateway_id', $gateways );
		}

		if ( ! empty( $_GET['card_number'] ) ) {
			$cardNumber = sanitize_text_field( $_GET['card_number'] );
			$transactions->where( 'card_number', 'LIKE', "%{$cardNumber}%" );
		}

		if ( ! empty( $_GET['description'] ) ) {
			$description = sanitize_text_field( $_GET['description'] );
			$transactions->where( 'description', 'LIKE', "%{$description}%" );
		}

		if ( ! empty( $_GET['gateway_au'] ) ) {
			$gatewayAu = sanitize_text_field( $_GET['gateway_au'] );
			$transactions->where( 'gateway_au', 'LIKE', "%{$gatewayAu}%" );
		}

		if ( ! empty( $_GET['ip'] ) ) {
			$ip = sanitize_text_field( $_GET['ip'] );
			$transactions->where( 'ip', 'LIKE', "%{$ip}%" );
		}

		if ( ! empty( $_GET['mobile'] ) ) {
			$mobile = sanitize_text_field( $_GET['mobile'] );
			$transactions->where( 'mobile', 'LIKE', "%{$mobile}%" );
		}

		if ( ! empty( $_GET['transaction_id'] ) ) {
			$transactionId = intval( $_GET['transaction_id'] );
			$transactions->where( 'id', $transactionId );
		}

		if ( ! empty( $_GET['amount'] ) ) {
			$amount = intval( $_GET['amount'] );
			$transactions->where( 'amount', $amount );
		}

		if ( ! empty( $_GET['order_id'] ) ) {
			$orderId = intval( $_GET['order_id'] );
			$transactions->where( 'order_id', $orderId );
		}

		if ( ! empty( $_GET['min_amount'] ) ) {
			$minAmount = intval( $_GET['min_amount'] );
			$transactions->where( 'amount', '>=', $minAmount );
		}

		if ( ! empty( $_GET['max_amount'] ) ) {
			$maxAmount = intval( $_GET['max_amount'] );
			$transactions->where( 'amount', '<=', $maxAmount );
		}

		if ( ! empty( $_GET['created_at'] ) ) {

			$created_at = verta()
				->timestamp( intval( $_GET['created_at'] ) )
				->timezone( $timezone )
				->startDay()
				->toCarbon()
				->utc();

			$transactions->whereBetween( 'created_at', [
				$created_at,
				$created_at->clone()->addDay()->subSecond(),
			] );

		}

		if ( ! empty( $_GET['from_date'] ) ) {

			$from_date = verta()
				->timestamp( intval( $_GET['from_date'] ) )
				->timezone( $timezone )
				->startDay()
				->toCarbon()
				->utc();

			$transactions->where( 'created_at', '>=', $from_date );
		}

		if ( ! empty( $_GET['to_date'] ) ) {

			$to_date = verta()
				->timestamp( intval( $_GET['to_date'] ) )
				->timezone( $timezone )
				->endDay()
				->toCarbon()
				->utc();

			if ( ! isset( $from_date ) || $from_date->diffInMinutes( $to_date, false ) > 0 ) {
				$transactions->where( 'created_at', '<=', $to_date );
			}

		}

		return $transactions;
	}

	public static function output() {

		$transaction_id = intval( $_GET['transaction_id'] ?? null );

		if ( $transaction_id ) {

			self::singleTransaction( $transaction_id );

		} else {

			$table = new self();

			$statusesWithCount = Transaction::query()
			                                ->selectRaw( 'status, count(*) as total' )
			                                ->groupBy( 'status' )
			                                ->get()
			                                ->pluck( 'total', 'status' )
			                                ->toArray();

			include GATELAND_DIR . '/templates/admin/transactions.php';
		}
	}

	public static function singleTransaction( $transactionId ) {

		$transaction = Transaction::query()
		                          ->with( 'gateway' )
		                          ->find( $transactionId );

		if ( is_null( $transaction ) ) {
			wp_redirect( admin_url( '/' ) );
		}

		include GATELAND_DIR . '/templates/admin/transaction.php';
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	private function getKeyName( string $key ): string {
		switch ( $key ) {
			case 'currency' :
				return 'ارزها';
			case 'client' :
				return 'پذیرنده‌ها';
			case 'status' :
				return 'وضعیت';
			case 'gateway' :
				return 'درگاه‌ها';
			case 'error' :
				return 'تراکنش‌های خطا دار';
			case 'card_number' :
				return 'شماره کارت';
			case 'description' :
				return 'توضیحات';
			case 'gateway_au' :
				return 'شماره مرجع';
			case 'min_amount' :
				return 'حداقل مبلغ';
			case 'max_amount' :
				return 'حداکثر مبلغ';
			case 'ip' :
				return 'آی.پی';
			case 'transaction_id' :
				return 'شماره تراکنش';
			case 'created_time' :
				return 'تاریج ایجاد';
			case 'amount' :
				return 'مبلغ';
			case 'order_id' :
				return 'شناسه سفارش';
			case 'mobile' :
				return 'شماره موبایل';
			default :
				return $key;
		}
	}

	private function getRequestValues( string $key, $values ): string {
		if ( $key === 'status' ) {

			$values = array_map( function ( $status ) {
				return StatusesEnum::tryFrom( $status )->name();
			}, $values );

		} elseif ( $key === 'currency' ) {

			$values = array_map( function ( $currency ) {
				return CurrenciesEnum::tryFrom( $currency )->value;
			}, $values );

		} elseif ( $key === 'gateway' ) {

			$values = Gateway::query()
			                 ->whereIn( 'id', $values )
			                 ->get()
			                 ->map( function ( Gateway $gateway ) {
				                 return $gateway->build()->name();
			                 } )
			                 ->toArray();

		}

		return is_array( $values ) ? implode( ', ', $values ) : $values;
	}
}