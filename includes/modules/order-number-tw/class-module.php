<?php
namespace Taiwan_Store_Core\Modules\Order_Number_Tw; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * ?芾?閮蝺刻?嚗?韌}YYYYMMDD-NNNN嚗??仿?蝵格?瘞渲?嚗? *
 * 銝耨?孵?憪?WP post ID嚗POS order id嚗?刻??桀遣蝡????芾?蝺刻?撖怠閮 meta嚗? * 銝阡? woocommerce_order_number filter ?典?敺憿舐內??隞?? *
 * ?賊?嚗? *   Taiwan_Store_Core_order_number_enabled  (default 'no')
 *   Taiwan_Store_Core_order_number_prefix   (default '')
 *   Taiwan_Store_Core_order_number_padding  (default 4)
 */
class Module implements \Taiwan_Store_Core\Module {

	private const META_KEY = '_wctw_order_number';
	private const SEQ_OPT  = 'Taiwan_Store_Core_order_seq_'; // suffix: YYYYMMDD

	public function id(): string {
		return 'order_number_tw';
	}

	public function boot(): void {
		add_action( 'woocommerce_new_order', [ $this, 'assign_number' ], 10, 2 );
		add_filter( 'woocommerce_order_number', [ $this, 'filter_display' ], 10, 2 );
	}

	public function is_admin_only(): bool {
		return false;
	}

	// ?????????????????????????????????????????????????????????????????????????

	private function enabled(): bool {
		return 'yes' === get_option( 'Taiwan_Store_Core_order_number_enabled', 'no' );
	}

	public function assign_number( int $order_id, $order = null ): void {
		if ( ! $this->enabled() ) {
			return;
		}
		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order || $order->get_meta( self::META_KEY ) ) {
			return;
		}

		$date    = wp_date( 'Ymd' );
		$prefix  = (string) get_option( 'Taiwan_Store_Core_order_number_prefix', '' );
		$padding = max( 1, (int) get_option( 'Taiwan_Store_Core_order_number_padding', 4 ) );

		$seq    = $this->next_sequence( $date );
		$number = $prefix . $date . '-' . str_pad( (string) $seq, $padding, '0', STR_PAD_LEFT );

		$order->update_meta_data( self::META_KEY, $number );
		$order->save();
	}

	public function filter_display( $number, $order ) {
		$custom = $order instanceof \WC_Order ? $order->get_meta( self::META_KEY ) : '';
		return $custom !== '' ? $custom : $number;
	}

	/**
	 * ???批?敺?乩?銝??瘞渲?嚗蝙??DB UPSERT ?踹?擃蒂銵???	 */
	private function next_sequence( string $date ): int {
		global $wpdb;

		$key = self::SEQ_OPT . $date;

		// INSERT 蝚砌?甈⊥??1嚗?敺?ON DUPLICATE KEY UPDATE 霈?DB ???芸???		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
				 VALUES (%s, 1, 'no')
				 ON DUPLICATE KEY UPDATE option_value = option_value + 1",
				$key
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				$key
			)
		);
	}
}

