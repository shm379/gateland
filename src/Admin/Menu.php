<?php

namespace Nabik\Gateland\Admin;

defined( 'ABSPATH' ) || exit;

class Menu {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 20 );

		new Dashboard();

		Settings::instance();
	}

	public function admin_menu() {
		global $admin_page_hooks;

		$capability = apply_filters( 'nabik_menu_capability', 'manage_options' );

		if ( ! isset( $admin_page_hooks['nabik'] ) ) {
			add_menu_page( 'نابیک', 'نابیک', $capability, 'nabik', null, GATELAND_URL . 'assets/images/nabik.png', '55.9' );
		}

		$capability = apply_filters( 'nabik/gateland/menu_capability', 'manage_options' );

		add_menu_page( 'گیت‌لند', 'گیت‌لند', $capability, 'gateland', null, GATELAND_URL . 'assets/images/gateland.png',
			'55.19' );

		$submenus = [
			10 => [
				'title'      => 'پیشخوان',
				'capability' => $capability,
				'slug'       => 'gateland',
				'callback'   => [ 'Nabik\Gateland\Admin\Dashboard', 'output' ],
			],
			20 => [
				'title'      => 'تراکنش‌ها',
				'capability' => $capability,
				'slug'       => 'gateland-transactions',
				'callback'   => [ 'Nabik\Gateland\Admin\Transactions', 'output' ],
			],
			30 => [
				'title'      => 'درگاه‌ها',
				'capability' => $capability,
				'slug'       => 'gateland-gateways',
				'callback'   => [ 'Nabik\Gateland\Admin\Gateways', 'output' ],
			],
			40 => [
				'title'      => 'تنظیمات',
				'capability' => $capability,
				'slug'       => 'gateland-settings',
				'callback'   => [ 'Nabik\Gateland\Admin\Settings', 'output' ],
			],
		];

		$submenus = apply_filters( 'nabik/gateland/submenus', $submenus );

		foreach ( $submenus as $submenu ) {
			add_submenu_page( 'gateland', $submenu['title'], $submenu['title'], $submenu['capability'],
				$submenu['slug'], $submenu['callback'] );
		}
	}

}
