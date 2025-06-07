<?php

namespace Nabik\Gateland\Admin;

use Nabik\Gateland\Gateland;
use Nabik\GatelandPro\GatelandPro;
use Nabik_Net_Settings;

defined( 'ABSPATH' ) || exit;

class Settings extends Nabik_Net_Settings {

	protected static $_instance = null;

	public static function output() {

		$instance = self::instance();

		echo '<div class="wrap">';

		$instance->init();
		$instance->show_navigation();
		$instance->show_forms();

		echo '</div>';
	}

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function get_sections(): array {
		$sections = [
			[
				'id'    => 'gateland_general',
				'title' => 'تنظیمات',
			],
			[
				'id'    => 'gateland_sms',
				'title' => 'پیامک',
			],
			[
				'id'    => 'gateland_proxy',
				'title' => 'پروکسی',
			],
			class_exists( GatelandPro::class ) ? [
				'id'    => 'gateland_license',
				'title' => 'لایسنس',
			] : [],
		];

		return array_filter( $sections );
	}

	public function get_fields(): array {

		$color   = '';
		$message = '';

		if ( class_exists( GatelandPro::class ) ) {

			if ( GatelandPro::is_active() ) {

				$color   = 'green';
				$message = 'افزونه با موفقیت فعال شده است.' . GatelandPro::$message;

			} else {

				$color   = 'red';
				$message = GatelandPro::$message;

			}

		}

		$message = sprintf( '<span style="color: %s">%s</span>', $color, $message );

		return [
			'gateland_general'      => [
				[
					'id'      => 'dashboard_income_period',
					'label'   => 'دوره پرداخت پیشخوان',
					'default' => 'this_year',
					'type'    => 'select',
					'options' => [
						'all'       => 'کل',
						'this_year' => 'امسال',
						'last_year' => 'یکسال گذشته',
					],
					'desc'    => 'بازه زمانی دریافتی کل در پیشخوان',
				],
				[
					'id'      => 'gateway_order',
					'label'   => 'نوع انتخاب درگاه',
					'default' => 'sort',
					'type'    => 'select',
					'options' => [
						'sort'         => 'ترتیب',
//						'random'       => 'تصادفی',
						'amount'       => 'تقسیم بر ا‌ساس مبلغ',
						'transactions' => 'تقسیم بر اساس تعداد تراکنش',
					],
					'desc'    => 'نحوه اولویت بندی درگاه‌ها',
				],
				[
					'id'      => 'iran_access',
					'label'   => 'ایران اکسس',
					'default' => '0',
					'type'    => 'checkbox',
					'desc'    => 'در صورت فعالسازی این گزینه، پرداخت صرفا از طریق آی.پی‌های ایرانی امکان پذیر خواهد بود.',
				],
			],
			'gateland_sms'          => [
				[
					'id'    => 'shortcode',
					'label' => 'راهنما',
					'desc'  => '۱. تنظیمات درگاه پیامک را از منو‌ <b>نابیک > پیامک</b> انجام دهید.<br>
					۲. برای ارسال نکردن پیامک در هر رویدادی، آن را خالی بگذارید.<br>
					۳. برای تنظیم متن پیامک می‌توانید از متغیرهای زیر استفاده کنید:
					<ul>
								<li><strong>{pay_url}</strong> آدرس پرداخت</li>
								<li><strong>{first_name}</strong> نام مشتری</li>
								<li><strong>{last_name}</strong> نام خانوادگی مشتری</li>
								<li><strong>{order_id}</strong> شناسه سفارش</li>
								<li><strong>{transaction_id}</strong> شناسه تراکنش</li>
								<li><strong>{description}</strong> توضیحات تراکنش</li>
								<li><strong>{amount}</strong> مبلغ تراکنش</li>
								</ul>',
					'type'  => 'html',
				],
				[
					'label'   => 'آدرس پرداخت',
					'id'      => 'pay_link',
					'default' => 'pay',
					'type'    => 'text',
					'desc'    => sprintf( 'لینک پرداختی که برای کاربر ارسال می‌شود.
					</br>
					برای مثال اگر شما pay‌ وارد کنید، آدرس پرداخت می‌شود: %s', site_url( 'pay' ) ),
				],
				[
					'label'   => 'پیامک ایجاد تراکنش',
					'id'      => 'transaction_created_sms',
					'default' => '',
					'type'    => 'textarea',
					'desc'    => 'پس از ساخته شدن تراکنش، این پیامک به کاربر ارسال می‌شود.',
				],
				[
					'label'   => 'پیامک تغییر وضعیت تراکنش به ناموفق',
					'id'      => 'transaction_failed_sms',
					'default' => '',
					'type'    => 'textarea',
					'desc'    => 'پس از تغییر وضعیت تراکنش به <strong>ناموفق</strong>، این پیامک به کاربر ارسال می‌شود.',
				],
				[
					'label'   => 'پیامک تغییر وضعیت تراکنش به پرداخت شده',
					'id'      => 'transaction_paid_sms',
					'default' => '',
					'type'    => 'textarea',
					'desc'    => 'پس از تغییر وضعیت تراکنش به <strong>پرداخت شده</strong>، این پیامک به کاربر ارسال می‌شود.',
				],
//				[
//					'label'   => 'پیامک تغییر وضعیت تراکنش به استرداد شده',
//					'id'      => 'transaction_refund_sms',
//					'default' => '',
//					'type'    => 'textarea',
//					'desc'    => 'پس از تغییر وضعیت تراکنش به <strong>استرداد شده</strong>، این پیامک به کاربر ارسال می‌شود.',
//				],

			],
			'gateland_proxy'        => [
				[
					'id'      => 'enable',
					'label'   => 'فعالسازی پروکسی',
					'default' => '0',
					'type'    => 'checkbox',
					'desc'    => 'برای فعالسازی استفاده از پروکسی برای اتصال به درگاه‌ها، تیک بزنید.',
				],
				[
					'id'      => 'type',
					'label'   => 'پروتکل',
					'default' => 'http',
					'type'    => 'select',
					'options' => [
						'http'    => 'http',
						'socks4'  => 'socks4',
						'socks4a' => 'socks4a',
						'socks5'  => 'socks5',
					],
				],
				[
					'id'      => 'host',
					'label'   => 'میزبان',
					'default' => null,
					'type'    => 'text',
					'desc'    => 'Host - در صورت استفاده از پروکسی، IP پروکسی را به درگاه اعلام کنید.',
				],
				[
					'id'      => 'port',
					'label'   => 'پورت',
					'default' => null,
					'type'    => 'text',
					'desc'    => 'Port',
				],
				[
					'id'      => 'username',
					'label'   => 'نام کاربری',
					'default' => null,
					'type'    => 'text',
					'desc'    => 'Username',
				],
				[
					'id'      => 'password',
					'label'   => 'کلمه عبور',
					'default' => null,
					'type'    => 'text',
					'desc'    => 'Password',
				],
			],
			'gateland_license'      => [
				[
					'label' => 'وضعیت',
					'id'    => 'status',
					'desc'  => $message,
					'type'  => 'html',
				],
				[
					'label'       => 'کلید لایسنس',
					'id'          => 'key',
					'default'     => '',
					'type'        => 'text',
					'placeholder' => 'NGL-PRO-',
					'desc'        => 'کلید لایسنس خود را از بخش حساب کاربری <a href="https://nabik.net/my-account/licenses/" target="_blank">نابیک</a> دریافت کنید.',
				],
			],
		];
	}

	function admin_init() {
		parent::admin_init();

		Gateland::addRewriteRules();
		// Flush rules for custom urls
		flush_rewrite_rules();
	}

}
