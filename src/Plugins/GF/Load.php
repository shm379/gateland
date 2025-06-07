<?php

namespace Nabik\Gateland\Plugins\GF;

use GF_Field;
use GF_Field_Email;
use GF_Field_Name;
use GF_Field_Phone;
use GF_Field_Text;

class Load {

	protected static ?Load $_instance = null;

	public static function instance(): ?Load {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		new Gateway();

		add_filter( 'gform_form_settings_fields', [ $this, 'settings_fields' ], 100, 2 );
		add_filter( 'gform_currencies', [ $this, 'gform_currencies' ], 10, 1 );
		add_filter( 'gform_common_currencies', [ $this, 'common_currencies' ], 10, 1 );
		add_action( 'gform_payment_details', [ $this, 'payment_details' ], 10, 2 );
	}

	public function settings_fields( array $fields, array $form ): array {

		/** @var GF_Field[] $fields */
		$form_fields = $form['fields'];

		$gateways = [
			[
				'value' => 0,
				'label' => 'درگاه پرداخت هوشمند آنلاین',
			],
		];

		foreach ( \Nabik\Gateland\Services\GatewayService::activated() as $gateway_id => $gateway ) {
			$gateways[] = [
				'value' => $gateway_id,
				'label' => $gateway['name'],
			];
		}

		$email_fields =
		$phone_fields =
		$name_fields = [
			[
				'value' => '___',
				'label' => 'هیچ‌کدام',
			],
		];

		foreach ( $form_fields as $field ) {

			if ( in_array( get_class( $field ), [ GF_Field_Text::class, GF_Field_Email::class ] ) ) {
				$email_fields[] = [
					'value' => $field->id,
					'label' => $field->label,
				];
			}

			if ( in_array( get_class( $field ), [ GF_Field_Text::class, GF_Field_Phone::class ] ) ) {
				$phone_fields[] = [
					'value' => $field->id,
					'label' => $field->label,
				];
			}

			if ( in_array( get_class( $field ), [ GF_Field_Text::class, GF_Field_Name::class ] ) ) {
				$name_fields[] = [
					'value' => $field->id,
					'label' => $field->label,
				];
			}

		}

		$new_fields['gateland'] = [
			'title'  => 'گیت‌لند',
			'fields' => [
				/*
				[
					'name'    => 'gateland_enable',
					'type'    => 'checkbox',
					'label'   => 'فعالسازی گیت‌لند',
					'choices' => [
						[
							'name'  => 'gateland_enable',
							'label' => 'با فعالسازی این گزینه کاربر پس از ثبت فرم به درگاه پرداخت منتقل می‌شود.',
						],
					],
				],
				*/
				[
					'name'          => 'gateland_gateway',
					'type'          => 'select',
					'label'         => 'انتخاب کنید پرداخت از طریق کدام درگاه انجام شود؟',
					'default_value' => 0,
					'choices'       => $gateways,
				],
				[
					'name'          => 'gateland_first_name_field',
					'type'          => 'select',
					'label'         => 'مقدار نام از کدام ورودی به درگاه ارسال شود؟',
					'default_value' => '___',
					'choices'       => $name_fields,
				],
				[
					'name'          => 'gateland_last_name_field',
					'type'          => 'select',
					'label'         => 'مقدار نام خانوادگی از کدام ورودی به درگاه ارسال شود؟',
					'default_value' => '___',
					'choices'       => $name_fields,
				],
				[
					'name'          => 'gateland_phone_field',
					'type'          => 'select',
					'label'         => 'مقدار تلفن همراه از کدام ورودی به درگاه ارسال شود؟',
					'default_value' => '___',
					'choices'       => $phone_fields,
				],
				[
					'name'          => 'gateland_email_field',
					'type'          => 'select',
					'label'         => 'مقدار ایمیل از کدام ورودی به درگاه ارسال شود؟',
					'default_value' => '___',
					'choices'       => $email_fields,
				],
			],
		];

		return array_merge( array_slice( $fields, 0, 1 ), $new_fields, array_slice( $fields, 1 ) );
	}

	public function gform_currencies( array $currencies ): array {

		$currencies['IRT'] = [
			'name'               => 'تومان ایران',
			'symbol_left'        => '',
			'symbol_right'       => 'تومان',
			'symbol_padding'     => ' ',
			'thousand_separator' => ',',
			'decimal_separator'  => '.',
			'decimals'           => 0,
			'code'               => 'IRT',
		];

		return $currencies;
	}

	public function common_currencies( array $currencies ): array {

		$currencies[] = 'IRT';

		return $currencies;
	}

	public function payment_details( $form_id, $entry ) {

		$authority = $entry['transaction_id'] ?? 0;

		if ( ! $authority ) {
			return;
		}

		$path = "admin.php?page=gateland-transactions&transaction_id=" . $authority;

		printf( '<a href="%s" target="_blank">مشاهده جزئیات تراکنش</a>', admin_url( $path ) );
	}

}
