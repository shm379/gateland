<?php

namespace Nabik\Gateland\Plugins\GF;

use GFAPI;
use GFCommon;
use GFFormDisplay;
use GFPersian_Payments;
use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Enums\Transaction\StatusesEnum;
use Nabik\Gateland\Models\Transaction;
use Nabik\Gateland\Pay;
use RGFormsModel;

defined( 'ABSPATH' ) || exit;

class Gateway {

	public function __construct() {
		add_filter( 'gform_confirmation', [ $this, 'request' ], 1000, 4 );
		add_filter( 'init', [ $this, 'verify' ], 10 );
	}

	public function request( $confirmation, array $form, array $entry, bool $ajax ) {

		/*
		if ( ! $form['gateland_enable'] ) {
			return $confirmation;
		}
		*/

		if ( isset( $_GET['gateway'], $_GET['entry_id'] ) ) {
			return $confirmation;
		}

		$amount = GFCommon::get_order_total( $form, $entry );
		$amount = intval( $amount );

		if ( $amount <= 0 ) {
			return $confirmation;
		}

		if ( ! class_exists( GFPersian_Payments::class ) ) {
			return $confirmation;
		}

		$entry_id = $entry['id'];
		$user_id  = get_current_user_id();

		$callback = add_query_arg( [
			'gateway'  => 'gateland',
			'entry_id' => $entry_id,
		], $entry['source_url'] );

		$data = [
			'amount'      => GFPersian_Payments::amount( $amount, 'IRT', $form, $entry ),
			'client'      => Transaction::CLIENT_GF,
			'user_id'     => $user_id,
			'order_id'    => $entry_id,
			'callback'    => $callback,
			'description' => $form['title'],
			'mobile'      => $entry[ $form['gateland_phone_field'] ] ?? null,
			'currency'    => CurrenciesEnum::IRT,
		];

		if ( $form['gateland_gateway'] ) {
			$data['gateway_id'] = $form['gateland_gateway'];
		}

		try {
			$response = Pay::request( $data );
		} catch ( \Exception $e ) {

			$message = 'خطایی در زمان ارتباط با درگاه پرداخت رخ داده است.';

			RGFormsModel::add_note( $entry_id, $user_id, 'کاربر', $message );

			return $this->confirmation( $form, $message );
		}

		if ( ! $response['success'] ) {

			$message = sprintf( 'خطایی در زمان ارتباط با درگاه پرداخت رخ داده است: %s', $response['message'] );

			RGFormsModel::add_note( $entry_id, $user_id, 'کاربر', $message );

			return $this->confirmation( $form, $message );
		}

		$entry['payment_status'] = 'Pending';
		$entry['payment_date']   = '';
		$entry['transaction_id'] = $response['data']['authority'];
		$entry['payment_amount'] = $amount;

		GFAPI::update_entry( $entry );

		return [ 'redirect' => $response['data']['payment_link'] ];
	}

	public function verify() {

		if ( ! isset( $_GET['gateway'], $_GET['entry_id'] ) ) {
			return;
		}

		$entry_id = intval( $_GET['entry_id'] );

		$entry = GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			wp_die( 'فرم یافت نشد.' );
		}

		$tags = [
			'{authority}' => $entry['transaction_id'],
			'{amount}'    => $entry['payment_amount'],
		];

		$response = Pay::verify( $entry['transaction_id'], Transaction::CLIENT_GF );

		if ( $response['success'] || $response['data']['status'] == StatusesEnum::STATUS_PAID ) {

			$status                       = 'Paid';
			$entry['payment_date']        = $response['data']['verified_at'];
			$entry['payment_gateway']     = $response['data']['gateway'];
			$entry['payment_card_number'] = $response['data']['card_number'];

			$message = self::tags( __( 'پرداخت شما با شماره پیگیری {authority} با موفقیت ثبت شد.' ), $tags );

			do_action( 'gform_post_payment_action', $entry, 'complete_payment' );
		} else {

			$status = 'Failed';

			$message = self::tags( __( 'پرداخت شما با شماره پیگیری {authority} ناموفق شد. لطفا مجددا تلاش کنید.' ), $tags );

			do_action( 'gform_post_payment_action', $entry, 'fail_payment' );
		}

		if ( $entry['payment_status'] == 'Pending' ) {
			RGFormsModel::add_note( $entry['id'], get_current_user_id(), 'کاربر', $message );
			do_action( 'gform_post_payment_action', $entry, 'add_pending_payment' );
		}

		$entry['payment_status'] = $status;

		GFAPI::update_entry( $entry, $entry_id );

		$form = GFAPI::get_form( $entry['form_id'] );

		if ( class_exists( GFPersian_Payments::class ) ) {
			GFPersian_Payments::notification( $form, $entry );
			GFPersian_Payments::confirmation( $form, $entry, $message );
		} else {
			wp_die( 'افزونه گراویتی فرمز فارسی را نصب کنید.' );
		}
	}

	public static function tags( string $message, array $tags ) {
		return str_replace(
			array_keys( $tags ),
			array_values( $tags ),
			$message
		);
	}

	public function confirmation( $form, string $message ) {
		$css_class  = esc_attr( rgar( $form, 'cssClass' ) );
		$form_theme = "data-form-theme='" . GFFormDisplay::get_form_theme_slug( $form ) . "'";

		$confirmation = "<div id='gform_confirmation_wrapper_{$form['id']}' class='form_saved_message_sent gform_confirmation_wrapper {$css_class} gform_wrapper' role='alert' {$form_theme}>{$message}</div>";
		$nl2br        = ! rgar( $form['confirmation'], 'disableAutoformat' );

		return GFCommon::replace_variables( $confirmation, $form, false, true, true, $nl2br );
	}
}