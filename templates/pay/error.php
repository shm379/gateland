<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title><?php bloginfo( 'name' ); ?> - خطا</title>

	<!-- Bootstrap core CSS -->
	<link href="<?php echo esc_url( GATELAND_URL ) . '/assets/css/bootstrap.min.css' ?>" rel="stylesheet">

	<!-- Custom styles for this template -->
	<link href="<?php echo esc_url( GATELAND_URL ) . '/assets/css/pay.css' ?>" rel="stylesheet">

</head>

<body>
<form class="form-gateland" method="get">
	<h1 class="title"><?php bloginfo( 'name' ); ?></h1>
	<div class="alert alert-<?php echo esc_attr( $alert ?? 'danger' ); ?> text-right" role="alert">
		<?php echo esc_html( $error ); ?>
	</div>
	<a class="btn btn-primary" href="<?php echo esc_url( site_url() ); ?>">بازگشت به سایت</a>

</form>
</body>
</html>
