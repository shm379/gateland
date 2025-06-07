<?php


use Carbon\Carbon;
use Nabik\Gateland\Enums\Transaction\CurrenciesEnum;
use Nabik\Gateland\Enums\Transaction\StatusesEnum;
use Nabik\Gateland\Models\Transaction;
use Nabik\Gateland\Pay;

class PMProGateway_gateland extends PMProGateway {

	public function __construct( ...$a ) {

		add_filter( 'pmpro_payment_options', [ $this, 'payment_options' ] );
		add_filter( 'pmpro_payment_option_fields', [ $this, 'payment_option_fields', ], 10, 2 );

		$gateway = pmpro_getGateway();

		if ( $gateway == 'gateland' ) {
			add_action( 'pmpro_checkout_before_change_membership_level', [ $this, 'request', ], 10, 2 );
			add_action( 'init', [ $this, 'callback' ] );
			add_filter( 'pmpro_include_billing_address_fields', '__return_false' );
			add_filter( 'pmpro_include_payment_information_fields', '__return_false' );
			add_filter( 'pmpro_required_billing_fields', [ $this, 'required_billing_fields', ] );
		}
	}

	/**
	 * Get a list of payment options that the Zarinpal gateway needs/supports.
	 *
	 * @since 1.0
	 */
	public static function getGatewayOptions() {
		return [
			'gateland_gateway',
			'currency',
			'tax_state',
			'tax_rate',
		];
	}

	public static function payment_options( $options ) {
		return array_merge( self::getGatewayOptions(), $options );
	}

	public static function required_billing_fields( $fields ) {
		unset( $fields['bfirstname'] );
		unset( $fields['blastname'] );
		unset( $fields['baddress1'] );
		unset( $fields['bcity'] );
		unset( $fields['bstate'] );
		unset( $fields['bzipcode'] );
		unset( $fields['bphone'] );
		unset( $fields['bemail'] );
		unset( $fields['bcountry'] );
		unset( $fields['CardType'] );
		unset( $fields['AccountNumber'] );
		unset( $fields['ExpirationMonth'] );
		unset( $fields['ExpirationYear'] );
		unset( $fields['CVV'] );

		return $fields;
	}

	public static function payment_option_fields( $values, $gateway ) {

		$values = array_filter( $values );

		$gateway_id = $values['gateland_gateway'] ?? '';

		?>
		<tr class="pmpro_settings_divider gateway gateway_gateland"
			<?php self::display_none( $gateway ); ?>>
			<td colspan="2">
				<hr/>
				<h2>تنظیمات پرداخت با گیت‌لند</h2>
			</td>
		</tr>
		<tr class="gateway gateway_gateland" <?php self::display_none( $gateway ); ?>>
			<th scope="row" valign="top">
				<label for="gateland_gateway">انتخاب درگاه</label>
			</th>
			<td>
				<select name="gateland_gateway" id="gateland_gateway">
					<option value="">درگاه پرداخت آنلاین هوشمند</option>
					<?php
					foreach ( \Nabik\Gateland\Services\GatewayService::activated() as $_gateway_id => $_gateway ) {
						printf(
							'<option value="%s" %s>%s</option>>',
							esc_attr( $_gateway_id ),
							selected( $gateway_id, $_gateway_id, false ),
							esc_html( $_gateway['name'] )
						);
					}
					?>
				</select>
				<p class="description">انتخاب کنید که پرداخت از چه درگاهی انجام شود. پیشفرض: درگاه پرداخت آنلاین
					هوشمند</p>
			</td>
		</tr>
		<?php
	}

	public static function display_none( string $gateway ): void {
		echo $gateway != 'gateland' ? 'style="display: none;"' : '';
	}

	public static function request( $user_id, MemberOrder $order ) {
		global $discount_code_id, $pmpro_currency;

		$order->gateway_environment = 'live';
		$order->saveOrder();

		//save discount code use
		if ( ! empty( $discount_code_id ) ) {
			Nabik_Net_Database::DB()
			                  ->table( 'pmpro_discount_codes_uses' )
			                  ->insert( [
				                  'code_id'   => $discount_code_id,
				                  'user_id'   => $user_id,
				                  'order_id'  => $order->id,
				                  'timestamp' => Carbon::now(),
			                  ] );
		}

		$amount = intval( $order->total );

		if ( $pmpro_currency == 'IRR' ) {
			$amount /= 10;
		}

		$callback = add_query_arg( [
			'pmp_pay_method' => 'gateland',
			'order_id'       => $order->id,
			'secret'         => hash( 'crc32', $order->id . AUTH_KEY ),
		], site_url() );

		$data = [
			'amount'      => $amount,
			'client'      => Transaction::CLIENT_PMP,
			'user_id'     => $order->user_id,
			'order_id'    => $order->id,
			'callback'    => $callback,
			'description' => 'پرداخت تست',
			'currency'    => CurrenciesEnum::IRT,
		];

		$gateway_id = pmpro_getOption( 'gateland_gateway' );

		if ( $gateway_id ) {
			$data['gateway_id'] = $gateway_id;
		}

		try {
			$response = Pay::request( $data );
		} catch ( \Exception $e ) {
			wp_die( 'خطایی در زمان ارتباط با درگاه پرداخت رخ داده است.', 'error' );
		}

		if ( ! $response['success'] ) {
			wp_die( esc_html( 'خطایی در زمان ارتباط با درگاه پرداخت رخ داده است. ' . $response['message'] ?? '' ), 'error' );
		}

		$order->updateStatus( 'pending' );
		$order->payment_transaction_id = $response['data']['authority'];
		$order->saveOrder();

		wp_redirect( $response['data']['payment_link'] );
		exit;
	}

	public static function callback() {

		if ( ( $_GET['pmp_pay_method'] ?? '' ) != 'gateland' ) {
			return;
		}

		$order_id = intval( $_GET['order_id'] ?? 0 );
		$secret   = sanitize_text_field( $_GET['secret'] ?? null );

		if ( $secret !== hash( 'crc32', $order_id . AUTH_KEY ) ) {
			wp_die( 'کلید امنیتی صحیح نمی‌باشد.' );
		}

		try {
			$order = new MemberOrder( $order_id );
			$order->getMembershipLevel();
			$order->getUser();
		} catch ( Exception $exception ) {
			die( 'سفارش یافت نشد.' );
		}

		if ( $order->status != 'pending' ) {
			wp_die( 'سفارش قبلا پردازش شده است.' );
		}

		$response = Pay::verify( $order->payment_transaction_id, Transaction::CLIENT_PMP );

		if ( $response['data']['status'] == StatusesEnum::STATUS_PAID ) {

			self::do_level_up( $order );

			wp_redirect( pmpro_url( 'confirmation', '?level=' . $order->membership_level->id ) );
			exit;
		}

		$order->cancel();
		$order->notes = sprintf( 'تراکنش %s ناموفق بود.', $order->payment_transaction_id );
		$order->saveOrder();

		wp_redirect( pmpro_url() );
		exit;
	}

	public static function do_level_up( &$order ): bool {

		$enddate = null;

		if ( ! empty( $order->membership_level->expiration_number ) ) {
			$enddate = date( 'Y-m-d', strtotime( '+ ' . $order->membership_level->expiration_number . ' ' . $order->membership_level->expiration_period, current_time( 'timestamp' ) ) );
		}

		$order->getDiscountCode();

		if ( ! empty( $order->discount_code ) ) {
			$order->getMembershipLevel( true );
			$discount_code_id = $order->discount_code->id;
		} else {
			$discount_code_id = '';
		}

		$custom_level = [
			'user_id'         => $order->user_id,
			'membership_id'   => $order->membership_level->id,
			'code_id'         => $discount_code_id,
			'initial_payment' => $order->membership_level->initial_payment,
			'billing_amount'  => $order->membership_level->billing_amount,
			'cycle_number'    => $order->membership_level->cycle_number,
			'cycle_period'    => $order->membership_level->cycle_period,
			'billing_limit'   => $order->membership_level->billing_limit,
			'trial_amount'    => $order->membership_level->trial_amount,
			'trial_limit'     => $order->membership_level->trial_limit,
			'startdate'       => current_time( 'mysql' ),
			'enddate'         => $enddate,
		];

		$change_membership_level = pmpro_changeMembershipLevel( $custom_level, $order->user_id );

		if ( ! $change_membership_level ) {
			return false;
		}

		$order->status                      = 'success';
		$order->subscription_transaction_id = '';
		$order->saveOrder();

		if ( ! empty( $discount_code ) && ! empty( $use_discount_code ) ) {
			Nabik_Net_Database::DB()
			                  ->table( 'pmpro_discount_codes_uses' )
			                  ->insert( [
				                  'code_id'   => $discount_code_id,
				                  'user_id'   => $order->user_id,
				                  'order_id'  => $order->id,
				                  'timestamp' => Carbon::now(),
			                  ] );
		}

		do_action( 'pmpro_after_checkout', $order->user_id, $order );

		$user                   = get_userdata( intval( $order->user_id ) );
		$user->membership_level = $order->membership_level;

		$pmproemail = new PMProEmail();
		$pmproemail->sendCheckoutEmail( $user, $order );

		//send email to admin
		$pmproemail = new PMProEmail();
		$pmproemail->sendCheckoutAdminEmail( $user, $order );

		return true;
	}
}