<?php
/**
 * Plugin Name: گیت‌لند
 * Plugin URI: https://wordpress.org/plugins/gateland
 * Description: درگاه پرداخت جامع، ایمن و هوشمند وردپرس برای تمامی درگاه‌های پرداخت با قابلیت اتصال به همه افزونه‌های وردپرس
 * Version: 2.1.1
 * Author: نابیک [Nabik.Net]
 * Author URI: https://Nabik.Net
 *
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 * License:      GPLv3
 *
 * WC requires at least: 7.0.0
 * WC tested up to: 9.8.5
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'GATELAND_VERSION' ) ) {
	define( 'GATELAND_VERSION', '2.1.1' );
}

if ( ! defined( 'GATELAND_DIR' ) ) {
	define( 'GATELAND_DIR', __DIR__ );
}

if ( ! defined( 'GATELAND_FILE' ) ) {
	define( 'GATELAND_FILE', __FILE__ );
}

if ( ! defined( 'GATELAND_URL' ) ) {
	define( 'GATELAND_URL', plugin_dir_url( __FILE__ ) );
}

require 'vendor/autoload.php';

new \Nabik\Gateland\Install();
new \Nabik\Gateland\Notice();
new \Nabik\Gateland\Version();

add_action( 'plugins_loaded', function () {
	\Nabik\Gateland\Gateland::instance();

	Nabik\Gateland\Plugins\CF7\Load::instance();
	Nabik\Gateland\Plugins\EDD\Load::instance();
	Nabik\Gateland\Plugins\GF\Load::instance();
	Nabik\Gateland\Plugins\LearnDash\Load::instance();
	Nabik\Gateland\Plugins\MyCred\Load::instance();
	Nabik\Gateland\Plugins\PMP\Load::instance();
	Nabik\Gateland\Plugins\RCP\Load::instance();
	Nabik\Gateland\Plugins\TeraWallet\Load::instance();
//	Nabik\Gateland\Plugins\WPForms\Load::instance();
	Nabik\Gateland\Plugins\WPUF\Load::instance();
} );

add_action( 'plugins_loaded', function () {
//	Nabik\Gateland\Plugins\Give\Load::instance();
}, 0 );

add_action( 'learn-press/ready', function () {
	Nabik\Gateland\Plugins\LearnPress\Load::instance();
} );

add_action( 'woocommerce_loaded', function () {
	\Nabik\Gateland\Plugins\Woocommerce\Load::instance();
} );

register_activation_hook( GATELAND_FILE, function () {
	file_put_contents( GATELAND_DIR . '/.activated', '' );
} );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
	}
} );