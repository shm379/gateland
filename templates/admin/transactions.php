<?php

use Carbon\Carbon;
use Nabik\Gateland\Enums\Transaction\StatusesEnum;
use Nabik\Gateland\Helper;
use Nabik\Gateland\Models\Transaction;
use Nabik\Gateland\Services\GatewayService;

defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'select2', GATELAND_URL . 'assets/css/select2.min.css', [], GATELAND_VERSION );
wp_enqueue_style( 'gateland-custom', GATELAND_URL . 'assets/css/custom.css', [], GATELAND_VERSION );
wp_enqueue_style( 'bootstrap', GATELAND_URL . 'assets/css/bootstrap.min.css', [], GATELAND_VERSION );
wp_enqueue_script( 'select2', GATELAND_URL . 'assets/js/select2.min.js', [ 'jquery' ], GATELAND_VERSION, true );

wp_enqueue_script( 'gateland-custom', GATELAND_URL . 'assets/js/custom.js', [ 'jquery' ], GATELAND_VERSION, true );

wp_enqueue_style( 'persian-datepicker', GATELAND_URL . 'assets/css/persian-datepicker.min.css', [], GATELAND_VERSION );
wp_enqueue_script( 'persian-date', GATELAND_URL . 'assets/js/persian-date.min.js', [ 'jquery' ], GATELAND_VERSION, true );
wp_enqueue_script( 'persian-datepicker', GATELAND_URL . 'assets/js/persian-datepicker.min.js', [
	'jquery',
	'persian-date',
],
	GATELAND_VERSION, true );

wp_enqueue_script( 'gateland-transactions', GATELAND_URL . 'templates/admin/transactions.js', [
	'jquery',
	'select2',
	'persian-datepicker',
], GATELAND_VERSION );

$statuses = StatusesEnum::cases();
$clients  = Transaction::getClients();
$gateways = GatewayService::used();

$excelExportUrl = add_query_arg( [
	'action' => 'export_excel',
] );

?>

<div class="wrap">
	<h2>لیست تراکنش‌ها</h2>

	<div class="row d-flex d-in">
		<div class="col-md-6 col-12">
		</div>
		<div class="col-md-6 text-start">
			<button class="btn btn-outline-primary download-excel">
				<a href="<?php echo esc_url( $excelExportUrl ); ?>">
					دانلود خروجی EXCEL
				</a>
			</button>
		</div>
	</div>

	<div>
		<ul class="subsubsub">

			<li class="all">
				<a href="?page=gateland-transactions" <?php echo ! isset( $_GET['status'] ) || count( $_GET['status'] ) === 0 ? 'class="current"' : ''; ?>>
					همه
					<span class="count">(<?php echo esc_html( Helper::fa_num( array_sum( $statusesWithCount ) ) ); ?>)</span>
				</a>
			</li>
			|
			<?php foreach ( $statusesWithCount as $status => $total ): ?>
				<li>
					<a href="?page=gateland-transactions&status[0]=<?php echo esc_attr( $status ); ?>" <?php echo in_array( $status,
						$_GET['status'] ?? [] ) && count( $_GET['status'] ) === 1 ? 'class="current"' : ''; ?>>
						<?php echo esc_html( StatusesEnum::tryFrom( $status )->name() ); ?>
						<span class="count">(<?php echo esc_html( Helper::fa_num( $total ) ); ?>)</span>
					</a>
				</li>
				<?php if ( $status !== array_key_last( $statusesWithCount ) ): ?>
					|
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	</div>

	<div id="poststuff">
		<div id="post-body-content">
			<div class="meta-box-sortablesui-sortable">
				<?php
				$table->prepare_items();
				?>
				<form method="get">

					<input type="hidden" name="page" value="gateland-transactions">

					<div class="row">
						<div class=" col-md-2 col-sm-6 col-12 form-group">
							<label for="status">وضعیت</label>
							<select name="status[]" multiple
									class="my-select2 form-control w-100"
									id="status">
								<?php foreach ( $statuses as $status ): ?>
									<option value="<?php echo esc_attr( $status->value ); ?>"
										<?php echo in_array( $status->value, $_GET['status'] ?? [] ) ? 'selected' : '' ?>>
										<?php echo esc_html( $status->name() ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class=" col-md-2 col-sm-6 col-12 form-group">
							<label for="client">پذیرنده</label>
							<select name="client[]" multiple
									class="my-select2 form-control w-100"
									id="client">
								<?php foreach ( $clients as $value => $label ): ?>
									<option value="<?php echo esc_attr( $value ); ?>"
										<?php echo in_array( $value, $_GET['client'] ?? [] ) ? 'selected' : '' ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class=" col-md-2 col-sm-6 col-12 form-group">
							<label for="gateway">درگاه</label>
							<select name="gateway[]" multiple
									class="my-select2 form-control w-100"
									id="gateway">
								<?php foreach ( $gateways as $gateway ): ?>
									<option value="<?php echo esc_attr( $gateway->id ); ?>"
										<?php echo in_array( $gateway->id, $_GET['gateway'] ?? [] ) ? 'selected' : '' ?>>
										<?php echo esc_html( $gateway->build()->name() ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="col-md-2 col-sm-6 col-12 form-group relative">
							<label for="">جستجو پیشرفته</label>
							<div class="form-control" data-dropdown="advance_filter">

								<?php
								$searchesName = [];
								if ( ! empty( $_GET['card_number'] ) ) {
									$searchesName[] = 'شماره کارت';
								}
								if ( ! empty( $_GET['description'] ) ) {
									$searchesName[] = 'توضیحات';
								}
								if ( ! empty( $_GET['gateway_au'] ) ) {
									$searchesName[] = 'شماره مرجع';
								}
								if ( ! empty( $_GET['min_amount'] ) || ! empty( $_GET['max_amount'] ) ) {
									$searchesName[] = 'مبلغ';
								}
								if ( ! empty( $_GET['ip'] ) ) {
									$searchesName[] = 'آی.پی';
								}
								?>
								<span class="text-right">
									<?php echo ! empty( $searchesName ) ? esc_html( implode( ', ', $searchesName ) ) : 'جستجو پیشرفته' ?>
								</span>
								<span style="float:left;">&#9660;</span>
							</div>

							<div class="position-relative w-100">
								<div id="advance_filter" class="position-absolute dropdown-menu custom-dropdown d-none"
									 style="top: 5px;left: 0;will-change: transform;">
									<ul class="px-1">
										<li class="d-flex flex-column gap-x-2">
											<label for="card_number" class="mb-2 text-right">شماره کارت</label>
											<input class="text-left form-control placeholder-right"
												   type="text"
												   id="card_number"
												   name="card_number"
												   value="<?php echo esc_attr( $_GET['card_number'] ?? '' ); ?>"
												   style="direction: ltr;"
												   placeholder="شماره کارت">
										</li>
										<hr class="border-dark-3 my-2">
										<li class="d-flex flex-column">
											<label for="description " class="mb-2 text-right">توضیحات</label>
											<input class="form-control placeholder-right"
												   type="text"
												   id="description"
												   name="description"
												   value="<?php echo esc_attr( $_GET['description'] ?? '' ); ?>"
												   placeholder="توضیحات">
										</li>
										<hr class="border-dark-3 my-2">
										<li class="d-flex flex-column">
											<label for="gateway_au" class="mb-2 text-right">شماره مرجع</label>
											<input class="text-left form-control placeholder-right"
												   type="text"
												   id="gateway_au"
												   name="gateway_au"
												   value="<?php echo esc_attr( $_GET['gateway_au'] ?? '' ); ?>"
												   style="direction: ltr;"
												   placeholder="شماره مرجع">
										</li>
										<hr class="border-dark-3 my-2">
										<li class="d-flex gap-2">
											<div class="flex-column text-right">
												<label for="min_amount" class="mb-2 ">مبلغ بیشتر از</label>
												<input class="text-left form-control placeholder-right"
													   type="text"
													   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
													   id="min_amount"
													   name="min_amount"
													   value="<?php echo esc_attr( $_GET['min_amount'] ?? '' ); ?>"
													   style="direction: ltr;"
													   placeholder="مبلغ بیشتر از">
											</div>
											<div class="flex-column text-right">
												<label for="max_amount" class="mb-2 ">مبلغ کمتر از</label>
												<input class="text-left form-control placeholder-right"
													   type="text"
													   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
													   id="max_amount"
													   name="max_amount"
													   value="<?php echo esc_attr( $_GET['max_amount'] ?? '' ); ?>"
													   style="direction: ltr;"
													   placeholder="مبلغ کمتر از">
											</div>
										</li>
										<hr class="border-dark-3 my-2">
										<li class="d-flex flex-column">
											<label for="ip" class="mb-2 text-right">آی.پی(ip)</label>
											<input class="text-left form-control placeholder-right"
												   type="text"
												   id="ip"
												   name="ip"
												   value="<?php echo esc_attr( $_GET['ip'] ?? '' ); ?>"
												   style="direction: ltr;"
												   placeholder="آی.پی(ip)">
										</li>
									</ul>
								</div>
							</div>

						</div>

						<div class="col-md-2 col-sm-6 col-12 form-group">

							<label class="block text-sm w-100"
								   for="from_date_picker">از تاریخ</label>
							<input data-date-from data-datepicker-class="datepicker-plot-area"
								   id="from_date_picker"
								   autocomplete="off"
								   value="<?php echo empty( $_GET['from_date'] ) ? '' :
								       esc_attr( Helper::fa_num( Helper::date( Carbon::now()->timestamp( intval( $_GET['from_date'] ) ), 'Y-m-d' ) ) ); ?>"
								   class="d-block w-100 h-50 text-sm border border-2  rounded">

							<input type="hidden" name="from_date" id="from_date"
								   value="<?php echo intval( $_GET['from_date'] ?? '' ); ?>" class="text-orange-6">

						</div>
						<div class="col-md-2 col-sm-6 col-12 form-group">

							<label class="block text-sm w-100"
								   for="to_date_picker">تا تاریخ</label>
							<input data-date-to data-datepicker-class="datepicker-plot-area"
								   id="to_date_picker"
								   autocomplete="off"
								   value="<?php echo empty( $_GET['to_date'] ) ? '' :
								       esc_attr( Helper::fa_num( Helper::date( Carbon::now()->timestamp( intval( $_GET['to_date'] ) ), 'Y-m-d' ) ) ); ?>"
								   class="d-block w-100 h-50 text-sm border border-2  rounded ">

							<input type="hidden" name="to_date" value="<?php echo intval( $_GET['to_date'] ?? '' ); ?>"
								   id="to_date">

						</div>
					</div>
					<input class="btn btn-success mt-4" type="submit" value="جستجو">
					<?php
					$table->display();
					?>

					<input type="hidden" name="_wpnonce" value="">
					<input type="hidden" name="_wp_http_referer" value="">

				</form>
			</div>
		</div>
		<br class="clear">
	</div>
</div>