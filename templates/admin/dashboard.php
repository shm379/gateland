<?php

use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Enums\Transaction\StatusesEnum;
use Nabik\Gateland\Helper;
use Nabik\Gateland\Models\Transaction;

defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'gateland-custom', GATELAND_URL . 'assets/css/custom.css', [], GATELAND_VERSION );
wp_enqueue_style( 'bootstrap', GATELAND_URL . 'assets/css/bootstrap.min.css', [], GATELAND_VERSION );

wp_enqueue_script( 'gateland-custom', GATELAND_URL . 'assets/js/custom.js', [ 'jquery' ], GATELAND_VERSION, true );

wp_enqueue_script( 'gateland-dashboard', GATELAND_URL . 'templates/admin/dashboard.js', [ 'jquery' ], GATELAND_VERSION, true );
wp_localize_script( 'gateland-dashboard', 'gateland_ajax', [
	'url' => admin_url( 'admin-ajax.php' ),
] );

?>

<div id="dashboard_content">
	<div class="wrap">
		<h2>پیشخوان</h2>
		<div class="row">
			<div class="col-md-6 mt-3 fw-bold ">
				<div class="bg-white shadow rounded p-3">
					<p class="mb-4  text-sm">دریافتی <?php echo esc_html( $result['income_period'] ); ?></p>
					<?php foreach ( $result['total_income'] as $currency => $income ) : ?>
						<p class="mb-2 text-2xl"><?php echo esc_html( Helper::fa_num( CurrenciesEnum::tryFrom( $currency )->price( $income ) ) ); ?></p>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="col-md-6 mt-3 fw-bold ">
				<div class="bg-white shadow rounded p-3">
					<p class="mb-4  text-sm">دریافتی امروز</p>
					<?php foreach ( $result['today_income'] as $currency => $income ) : ?>
						<p class="mb-2 text-2xl"><?php echo esc_html( Helper::fa_num( CurrenciesEnum::tryFrom( $currency )->price( $income ) ) ); ?></p>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="col-md-6 mt-3 fw-bold ">
				<div class="bg-white shadow rounded p-3">
					<p class="mb-4  text-sm">گزارش <?php echo esc_html( $result['income_period'] ); ?></p>
					<ul class="text-sm list-border-between">
						<?php foreach ( $result['total_transactions'] as $status => $total ) :
							$status = StatusesEnum::tryFrom( $status );
							?>
							<li class="d-flex flex-row justify-content-between items-justified-center">
								<p class="mb-2" style="<?php echo esc_attr( $status->style() ); ?>">
									<?php echo esc_html( $status->name() ) ?>:
								</p>
								<p class="mb-2"
								   style="<?php echo esc_attr( $status->style() ); ?>">
									<?php echo esc_html( Helper::fa_num( $total ) ); ?> تراکنش
								</p>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<div class="col-md-6 mt-3 fw-bold ">
				<div class="bg-white shadow rounded p-3">
					<p class="mb-4 text-sm">گزارش امروز</p>
					<ul class="text-sm list-border-between">
						<?php foreach ( $result['today_transactions'] as $status => $total ) :
							$status = StatusesEnum::tryFrom( $status );
							?>
							<li class="d-flex flex-row justify-content-between items-justified-center">
								<p class="mb-2" style="<?php echo esc_attr( $status->style() ); ?>">
									<?php echo esc_html( $status->name() ) ?>:
								</p>
								<p class="mb-2"
								   style="<?php echo esc_attr( $status->style() ); ?>">
									<?php echo esc_html( Helper::fa_num( $total ) ); ?> تراکنش
								</p>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
		<!--		@todo add need validation transactions -->
		<div class="row mt-5">
			<div class="col-12 table-responsive">
				<table class="table table-striped border">
					<thead>
					<tr>
						<th>شماره تراکنش</th>
						<th>پذیرنده</th>
						<th>درگاه</th>
						<th>تاریخ ایجاد</th>
						<th>مبلغ</th>
						<th>شناسه سفارش</th>
						<th>شماره موبایل</th>
						<th>وضعیت</th>
						<th>جزئیات</th>
					</tr>
					</thead>
					<tbody>
					<?php /** @var Transaction $transaction */
					foreach ( $result['latest_transactions'] as $transaction ):
						$orderUrl = $transaction->getClientOrderUrl();
						?>
						<tr>
							<td><?php echo esc_html( Helper::fa_num( $transaction->id ) ); ?></td>
							<td><?php echo esc_html( $transaction->client_label ); ?></td>
							<td><?php echo esc_html( $transaction->gateway_label ); ?></td>
							<td><?php echo esc_html( Helper::fa_num( Helper::date( $transaction->created_at, 'Y/m/d H:i' ) ) ); ?></td>
							<td><?php echo esc_html( Helper::fa_num( CurrenciesEnum::tryFrom( $transaction->currency )->price( $transaction->amount ) ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( $orderUrl ); ?>" class="text-decoration-none"
								   target='_blank'>
									<?php echo esc_html( Helper::fa_num( $transaction->order_id ) ); ?>
								</a>
							</td>
							<td><?php echo Helper::mobile( $transaction->mobile ); ?></td>
							<td style="<?php echo esc_attr( StatusesEnum::tryFrom( $transaction->status )->style() ); ?>">
								<?php echo esc_html( StatusesEnum::tryFrom( $transaction->status )->name() ); ?>
							</td>
							<td>
								<a href="<?php echo esc_url( $transaction->getReceiptURL() ); ?>" target="_blank"
								   class="button button-primary">جزئیات</a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>