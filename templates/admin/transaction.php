<?php

use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Enums\Transaction\StatusesEnum;
use Nabik\Gateland\Helper;
use Nabik\Gateland\Models\Transaction;

defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'gateland-custom', GATELAND_URL . 'assets/css/custom.css', [], GATELAND_VERSION );

/** @var Transaction $transaction $data */

$status = StatusesEnum::tryFrom( $transaction->status );

$orderUrl = $transaction->getClientOrderUrl();

$data = [
	'مبلغ'         => Helper::fa_num( CurrenciesEnum::tryFrom( $transaction->currency )->price( $transaction->amount ) ),
	'درگاه'        => $transaction->gateway_label,
	'توضیحات'      => Helper::fa_num( $transaction->description ?? '' ),
	'شماره سفارش'  => "<a href='$orderUrl' target='_blank'>" . Helper::fa_num( $transaction->order_id ) . "</a>",
	'ip'           => Helper::fa_num( $transaction->ip ?? '' ),
	'شماره تراکنش' => Helper::fa_num( $transaction->gateway_trans_id ?? '' ),
	'وضعیت درگاه'  => $transaction->gateway_status,
	'وضعیت'        => "<span style='{$status->style()}' >" . $status->name() . "</span>",
	'شماره پیگیری' => Helper::fa_num( $transaction->gateway_au ?? '' ),
	'شماره کارت'   => $transaction->card_number ? Helper::fa_num( $transaction->card_number ) : '',
	'تلفن همراه'   => Helper::mobile( $transaction->mobile ?? '' ),
	'پذیرنده'      => $transaction->client_label,
	'تاریخ ایجاد'  => Helper::fa_num( Helper::date( $transaction->created_at ) ),
	'تاریخ پرداخت' => $transaction->paid_at ? Helper::fa_num( Helper::date( $transaction->paid_at ) ) : '',
	'تاریخ تایید'  => $transaction->verified_at ? Helper::fa_num( Helper::date( $transaction->verified_at ) ) : 'تایید نشده',
];

?>

<div class="wrap">
	<h2>جزئیات تراکنش <?php echo esc_html( Helper::fa_num( $transaction->id ) ); ?></h2>
	<table class="wp-list-table widefat fixed striped table-view-list form-table">
		<thead>
		<tr>
			<th style="padding: 10px;">عنوان</th>
			<th style="padding: 10px;">مقدار</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $data as $label => $value ): ?>
			<tr>
				<td class="title column-title has-row-actions column-primary page-title">
					<strong><?php echo esc_html( $label ); ?></strong></td>
				<td><?php echo $value; ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>