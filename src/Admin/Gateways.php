<?php

namespace Nabik\Gateland\Admin;

use Nabik\Gateland\Enums\Gateway\StatusesEnum;
use Nabik\Gateland\Gateland;
use Nabik\Gateland\Gateways\BaseGateway;
use Nabik\Gateland\Gateways\Features\FreeFeature;
use Nabik\Gateland\Helper;
use Nabik\Gateland\Models\Gateway;
use Nabik\Gateland\Services\GatewayService;
use Nabik\GatelandPro\GatelandPro;
use WP_List_Table;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Gateways extends WP_List_Table {

	public static int $index = 1;

	public function __construct( $args = [] ) {

		$this->handleStatusAction();
		$this->handleAction();
		$this->handleNewGateway();

		parent::__construct( [
			'singular' => 'درگاه',
			'plural'   => 'درگاه‌ها',
			'ajax'     => false,
		] );
	}

	public static function handleUpdate() {

		if ( ! isset( $_GET['gateway_id'], $_POST['update_gateway_nonce'], $_POST['options'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['update_gateway_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'gateland_update_gateway' ) ) {
			return;
		}

		$gateway = Gateway::find( intval( $_GET['gateway_id'] ) );

		if ( is_null( $gateway ) ) {
			return;
		}

		$options = array_map( 'sanitize_textarea_field', $_POST['options'] );

		$gateway->update( [
			'data' => $options,
		] );

		$singleGatewayUrl = add_query_arg( [
			'gateway_id' => $gateway->id,
		] );

		$_SESSION['success'] = true;

		Gateland::redirect( $singleGatewayUrl );
	}

	public function handleStatusAction() {

		if ( ! isset( $_GET['gateway_id'], $_GET['status_action'] ) ) {
			return;
		}

		$validActions = [ 'activate', 'deactivate' ];

		if ( ! in_array( $_GET['status_action'], $validActions ) ) {
			return;
		}

		$action = sanitize_text_field( $_GET['status_action'] );

		/** @var Gateway $gateway */
		$gateway = Gateway::find( intval( $_GET['gateway_id'] ) );

		if ( is_null( $gateway ) ) {
			return;
		}

		$gateway->update( [
			'status' => $action === 'activate' ? StatusesEnum::STATUS_ACTIVE : StatusesEnum::STATUS_INACTIVE,
		] );

		GatewayService::reset_activated();

		$url = admin_url( sprintf( 'admin.php?%s', http_build_query( array_merge( $_GET, [
			'gateway_id'    => null,
			'status_action' => null,
		] ) ) ) );

		Gateland::redirect( $url );
	}

	/**
	 * @return void
	 */
	public function handleAction(): void {

		if ( ! isset( $_GET['gateway_id'], $_GET['action'] ) ) {
			return;
		}

		$validActions = [ 'raise', 'reduce' ];

		if ( ! in_array( $_GET['action'], $validActions ) ) {
			return;
		}

		$action = sanitize_text_field( $_GET['action'] );

		/** @var Gateway $gateway */
		$gateway = Gateway::find( intval( $_GET['gateway_id'] ) );

		if ( is_null( $gateway ) ) {
			return;
		}

		if ( $action === 'raise' ) {
			$operator  = '<';
			$direction = 'desc';
		} else {
			$operator  = '>';
			$direction = 'asc';
		}

		/** @var Gateway $siblingGateway */
		$siblingGateway = Gateway::query()
		                         ->where( 'sort', $operator, $gateway->sort )
		                         ->orderBy( 'sort', $direction )
		                         ->first();

		if ( ! is_null( $siblingGateway ) ) {

			$swapSort = $siblingGateway->sort;

			$siblingGateway->update( [
				'sort' => $gateway->sort,
			] );

			$gateway->update( [
				'sort' => $swapSort,
			] );
		}

		$url = admin_url( sprintf( 'admin.php?%s', http_build_query( array_merge( $_GET, [
			'gateway_id' => null,
			'action'     => null,
		] ) ) ) );

		wp_redirect( $url );
		die();
	}

	public static function handleNewGateway() {

		if ( ! isset( $_GET['add-gateway'], $_GET['_wpnonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'gateland-add-gateway' ) ) {
			return;
		}

		$gateway = base64_decode( $_GET['add-gateway'] );

		if ( ! is_a( $gateway, BaseGateway::class, true ) ) {
			return;
		}

		Gateway::create( [
			'class'      => $gateway,
			'status'     => 'active',
			'data'       => wp_json_encode( [] ),
			'currencies' => wp_json_encode( ( new $gateway )->currencies() ),
		] );

		GatewayService::reset_activated();

		wp_redirect( admin_url( 'admin.php?page=gateland-gateways' ) );
		die();
	}

	public function no_items() {
		echo 'اولین درگاه خود را از لیست درگاه‌های زیر اضافه کنید.';
	}

	public function get_columns(): array {
		return [
			'sort'         => 'ردیف',
			'logo'         => 'لوگو',
			'gateway_name' => 'نام درگاه',
			'sort_button'  => 'اولویت بندی',
			'status'       => 'وضعیت',
			'operation'    => '',
		];
	}

	/**
	 * @param Gateway $item
	 * @param         $column_name
	 *
	 * @return int|string|void
	 * @throws \Exception
	 */
	protected function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'sort':
				return Helper::fa_num( self::$index );
			case 'logo':
				$this->getGatewayLogo( $item );
				break;
			case 'gateway_name':
				$this->getGatewayNameAndDescription( $item );
				break;
			case 'sort_button':
				$this->getSortButtons( $item->id, self::$index ++ );
				break;
			case 'status':
				$this->getStatusCell( $item->status );
				break;
			case 'operation':
				$this->getOperationFiled( $item );
				break;
			default:
				print_r( $item );
		}
	}

	private function getStatusCell( $status ) {
		$status = StatusesEnum::tryFrom( $status );

		printf( '<span class="%s">%s</span>', esc_attr( $status->class() ), esc_html( $status->name() ) );
	}

	/**
	 * @param Gateway $item
	 *
	 * @return void
	 */
	private function getOperationFiled( Gateway $item ) {

		if ( ! is_a( $item->class, FreeFeature::class, true ) ) {

			if ( ! class_exists( GatelandPro::class ) ) {

				$this->upgradeButton( $item->build() );

				return;

			} elseif ( ! GatelandPro::is_active() ) {

				$this->activateButton( $item->build() );

				return;
			}

		}

		$gateway_id = $item->id;
		$status     = $item->status;

		$singleGatewayUrl = add_query_arg( [
			'gateway_id' => $gateway_id,
		] );

		$toggleStatusUrl = add_query_arg( [
			'gateway_id'    => $gateway_id,
			'status_action' => $status == StatusesEnum::STATUS_ACTIVE ? 'deactivate' : 'activate',
		] )

		?>
		<div class="d-flex d-inline-flex text-center gap-2 form-group relative">

			<div>
				<a class="btn btn-primary font-size-12"
				   href="<?php echo esc_url( $singleGatewayUrl ); ?>">ویرایش تنظیمات</a>
			</div>

			<div>
				<?php if ( $status == StatusesEnum::STATUS_ACTIVE ): ?>
					<a class="btn btn-danger pointer  font-size-12"
					   href="<?php echo esc_url( $toggleStatusUrl ); ?>">غیرفعال کردن</a>
				<?php else: ?>
					<a class="btn btn-success font-size-12"
					   href="<?php echo esc_url( $toggleStatusUrl ); ?>">فعال کردن</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private function upgradeButton( BaseGateway $gateway ) {
		?>
		<div class="d-flex d-inline-flex text-center gap-2 form-group relative">
			<a class="btn btn-danger pointer font-size-12" target="_blank"
			   href="https://l.nabik.net/gateland-pro/?utm_campaign=gateland-upgrade&utm_medium=free-license&utm_source=website&utm_content=<?php echo urlencode( $gateway->description() ); ?>">
				ارتقا به نسخه حرفه‌ای
			</a>
		</div>
		<?php
	}

	private function activateButton( BaseGateway $gateway ) {
		?>
		<div class="d-flex d-inline-flex text-center gap-2 form-group relative">
			<a class="btn btn-danger pointer font-size-12" target="_blank"
			   href="<?php echo esc_url( admin_url( 'admin.php?page=gateland-settings' ) ); ?>">
				فعالسازی نسخه حرفه‌ای
			</a>
		</div>
		<?php
	}

	public function prepare_items() {
		$this->_column_headers = [ $this->get_columns(), [], [] ];

		$this->items = $this->get_gateways();
	}

	/**
	 * @return array
	 */
	public function get_gateways(): array {
		return Gateway::query()
		              ->orderBy( 'sort' )
		              ->get()
		              ->all();
	}

	public static function output() {

		$used_gateways = GatewayService::used( false );
		$used_gateways = collect( $used_gateways )->map( function ( Gateway $gateway ) {
			return $gateway->class;
		} )->toArray();

		$gateways = [];

		foreach ( GatewayService::loaded() as $gateway ) {

			if ( ! in_array( get_class( $gateway ), $used_gateways ) ) {
				$gateways[] = $gateway;
			}

		}

		$gatewayId = intval( $_GET['gateway_id'] ?? null );

		if ( $gatewayId && ( ! isset( $_GET['action'] ) && ! isset( $_GET['status_action'] ) ) ) {
			self::handleUpdate();
			self::singleGateway( $gatewayId );
		} else {
			$table = new self();
			include GATELAND_DIR . '/templates/admin/gateways.php';
		}
	}

	public function getGatewayLogo( Gateway $gateway ) {
		$gatewayBuilder = $gateway->build();
		?>
		<div>
			<img src="<?php echo esc_url( $gatewayBuilder->icon() ); ?>"
				 alt="<?php echo esc_attr( $gatewayBuilder->name() ); ?>"
				 style="width: 48px;">
			<p></p>
		</div>
		<?php
	}

	public function getGatewayNameAndDescription( Gateway $gateway ) {
		$gatewayBuilder = $gateway->build();
		?>
		<div>
			<p><?php echo esc_html( $gatewayBuilder->name() ); ?></p>
			<span class="text-sm text-secondary"><?php echo esc_html( $gatewayBuilder->description() ); ?></span>
		</div>
		<?php
	}

	public function getSortButtons( int $gatewayId, int $index ) {

		$isLastRow = $index === count( $this->items );

		$raiseUrl = add_query_arg( [
			'gateway_id' => $gatewayId,
			'action'     => 'raise',
		] );

		$reduceUrl = add_query_arg( [
			'gateway_id' => $gatewayId,
			'action'     => 'reduce',
		] );

		?>

		<div class="flex flex-row items-center gap-1">

			<?php if ( $index !== 1 ): ?>
				<a href="<?php echo esc_url( $raiseUrl ) ?>">
					<img class="w-6 lg:w-8"
						 src="<?php echo esc_url( GATELAND_URL . '/assets/images/chevron-up-circle.svg' ); ?>"
						 alt="Up">
				</a>

			<?php endif; ?>

			<?php if ( ! $isLastRow ): ?>
				<a href="<?php echo esc_url( $reduceUrl ) ?>">
					<img class="w-6 lg:w-8"
						 src="<?php echo esc_url( GATELAND_URL . '/assets/images/chevron-down-circle.svg' ); ?>"
						 alt="Down">
				</a>
			<?php endif; ?>

		</div>
		<?php
	}


	public static function singleGateway( $gatewayId ) {

		$gateway = Gateway::find( $gatewayId );

		if ( is_null( $gateway ) ) {
			wp_redirect( admin_url( '/' ) );
		}

		include GATELAND_DIR . '/templates/admin/gateway.php';
	}
}
