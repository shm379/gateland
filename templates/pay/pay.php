<?php

use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Helper;

defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title><?php bloginfo( 'name' ); ?> - پرداخت</title>

	<!-- Bootstrap core CSS -->
	<link href="<?php echo esc_url( GATELAND_URL ) . '/assets/css/bootstrap.min.css' ?>" rel="stylesheet">

	<!-- Custom styles for this template -->
	<link href="<?php echo esc_url( GATELAND_URL ) . '/assets/css/pay.css' ?>" rel="stylesheet">

</head>

<body>
<form class="form-gateland" method="get" action="<?php echo esc_url( $transaction->getPayURl() ); ?>">
	<h1 class="title"><?php bloginfo( 'name' ); ?></h1>
	<?php if ( isset( $message ) && ! empty( $message ) ): ?>
		<div class="alert alert-danger text-right" role="alert">
			<?php echo esc_html( $message ); ?>
		</div>
	<?php endif; ?>
	<ul class="list-group mb-3 pe-0">
		<li class="list-group-item d-flex justify-content-between">
			<span>مبلغ</span>
			<span><?php echo esc_html( Helper::fa_num( CurrenciesEnum::tryFrom( $transaction->currency )->price( $transaction->amount ) ) ); ?></span>
		</li>
		<li class="list-group-item d-flex justify-content-between">
			<span>کد پیگیری</span>
			<span><?php echo esc_html( Helper::fa_num( $transaction->id ) ); ?></span>
		</li>
		<li class="list-group-item d-flex justify-content-between">
			<span>تاریخ</span>
			<span><?php echo esc_html( Helper::fa_num( verta( $transaction->created_at )->format( 'Y/m/d' ) ) ); ?></span>
		</li>
		<li class="list-group-item d-flex justify-content-between text-right">
			<div>
				<span>توضیحات</span><br>
				<small class="text-muted"><?php echo esc_html( Helper::fa_num( $transaction->description ?? '' ) ); ?></small>
			</div>
		</li>
	</ul>
	<button class="btn btn-primary btn-block" type="submit">تلاش مجدد</button>
</form>
</body>
</html>
