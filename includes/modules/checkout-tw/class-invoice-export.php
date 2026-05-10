<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * 在後台訂單清單加入「匯出發票資料 (CSV)」批次動作。
 */
class Invoice_Export {

	private const ACTION_GENERIC = 'taiwan_store_core_export_invoice_csv';

	public function boot(): void {
		if ( ! is_admin() ) return;
		add_filter( 'bulk_actions-edit-shop_order', [ $this, 'add_bulk_actions' ] );
		add_filter( 'woocommerce_order_list_table_bulk_actions', [ $this, 'add_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-shop_order', [ $this, 'handle_bulk_actions' ], 10, 3 );
		add_action( 'woocommerce_order_list_table_custom_bulk_action', [ $this, 'handle_hpos_bulk_actions' ], 10, 2 );
	}

	public function add_bulk_actions( array $actions ): array {
		$actions[ self::ACTION_GENERIC ] = __( '匯出發票：通用格式 (CSV)', 'taiwan-store-core' );
		return $actions;
	}

	public function handle_bulk_actions( string $redirect_to, string $action, array $post_ids ): string {
		if ( $action !== self::ACTION_GENERIC ) return $redirect_to;
		$this->output_csv( array_map( 'absint', $post_ids ) );
		exit;
	}

	public function handle_hpos_bulk_actions( string $action, array $order_ids ): void {
		if ( $action !== self::ACTION_GENERIC ) return;
		$this->output_csv( array_map( 'absint', $order_ids ) );
		exit;
	}

	private function output_csv( array $order_ids ): void {
		$filename = 'invoice-export-' . gmdate( 'Ymd-His' ) . '.csv';

		while ( ob_get_level() ) ob_end_clean();

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// UTF-8 BOM
		echo "\xEF\xBB\xBF";

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.File_System_Operations.file_system_operations_fopen

		fputcsv( $out, [
			__( '訂單編號', 'taiwan-store-core' ),
			__( '訂單日期', 'taiwan-store-core' ),
			__( '客戶姓名', 'taiwan-store-core' ),
			__( 'Email', 'taiwan-store-core' ),
			__( '發票類型', 'taiwan-store-core' ),
			__( '載具/捐贈碼', 'taiwan-store-core' ),
			__( '統一編號', 'taiwan-store-core' ),
			__( '公司名稱', 'taiwan-store-core' ),
			__( '訂單金額', 'taiwan-store-core' ),
		] );

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) continue;

			fputcsv( $out, [
				$order->get_order_number(),
				$order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
				trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				$order->get_billing_email(),
				(string) $order->get_meta( '_wctw/invoice-type' ),
				(string) $order->get_meta( '_wctw/carrier-number' ),
				(string) $order->get_meta( '_wctw/company-tax-id' ),
				(string) $order->get_meta( '_wctw/company-title' ),
				$order->get_total(),
			] );
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.File_System_Operations.file_system_operations_fclose
	}
}
