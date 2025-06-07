<?php

use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Enums\Transaction\StatusesEnum;
use Nabik\Gateland\Helper;
use Nabik\Gateland\Models\Transaction;

defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'nabik-gateland-order-metabox', GATELAND_URL . 'assets/css/woocommerce-metabox.css', [], GATELAND_VERSION );

?>
<div class="row" id="metabox">
	<div class="col-12 table-responsive">
		<table class="table border">
			<thead style="text-align:right;">
			<tr>
				<th>شماره تراکنش</th>
				<th>تاریخ ایجاد</th>
				<th>مبلغ</th>
				<th>درگاه</th>
				<th>وضعیت</th>
				<th>جزئیات</th>
			</tr>
			</thead>
			<tbody>
			<?php
			/** @var Transaction[] $transactions */
			foreach ( $transactions as $transaction ): ?>
				<tr>
					<td><?php echo esc_html( Helper::fa_num( $transaction->id ) ); ?></td>
					<td><?php echo esc_html( Helper::fa_num( Helper::date( $transaction->created_at, 'Y/m/d H:i' ) ) ); ?></td>
					<td><?php echo esc_html( Helper::fa_num( CurrenciesEnum::tryFrom( $transaction->currency )->price( $transaction->amount ) ) ); ?></td>
					<td><?php
						if ( $transaction->gateway ) {
							echo esc_html( $transaction->gateway->build()->name() );
						} else {
							echo '-';
						}
						?></td>
					<td style="<?php echo esc_attr( StatusesEnum::tryFrom( $transaction->status )->style() ); ?>">
						<?php echo esc_html( StatusesEnum::tryFrom( $transaction->status )->name() ); ?>
					</td>
					<td>
						<?php
						$transactionUrl = menu_page_url( 'gateland-transactions', false );
						$transactionUrl = add_query_arg( [
							'transaction_id' => $transaction->id,
						], $transactionUrl );
						?>
						<a href="<?php echo esc_url( $transactionUrl ); ?>" class="button button-primary"
						   target="_blank">جزئیات</a>
					</td>
				</tr>
			<?php
			endforeach;
			?>
			</tbody>
		</table>
	</div>
</div>
