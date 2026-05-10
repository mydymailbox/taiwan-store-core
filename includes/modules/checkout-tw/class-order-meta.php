<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * 敺?Additional Checkout Fields API 撖怠????meta 銝剛??蟡刻?閮?
 * 憿舐內?澆??啗??桅??mail ?澆???嚗誑???啜????柴??? *
 * Meta ?蛛???WC ??location='order' ??神?伐??韌摨?嚗?
 *   _wctw/invoice-type
 *   _wctw/carrier-number
 *   _wctw/company-tax-id
 *   _wctw/company-title
 */
class Order_Meta {

	private const INVOICE_LABELS = [
		'personal'      => '?犖?餃??潛巨嚗蝡荔?',
		'carrier_phone' => '??璇Ⅳ',
		'carrier_cert'  => '?芰鈭箸?霅?,
		'donate'        => '??蝣?,
		'company'       => '?砍銝撘?蝯梁楊嚗?,
	];

	public function boot(): void {
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_in_admin' ] );
		add_filter( 'woocommerce_order_formatted_billing_address', [ $this, 'append_to_formatted_address' ], 10, 2 );
		add_action( 'woocommerce_order_details_after_customer_details', [ $this, 'display_on_frontend' ] );
		// 閮蝣箄?靽?/ 蝞∠??靽?		add_action( 'woocommerce_email_customer_details', [ $this, 'display_in_email' ], 20, 4 );

		// 閮皜璅惜 (Classic CPT + HPOS)
		add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_order_tags_column' ], 20 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'add_order_tags_column' ], 20 );
		add_action( 'manage_shop_order_posts_custom_column', [ $this, 'display_order_tags_column' ], 10, 2 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'display_order_tags_column' ], 10, 2 );
		add_action( 'admin_head', [ $this, 'output_tag_styles' ] );
	}

	public function add_order_tags_column( array $columns ): array {
		$new_columns = [];
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'order_number' === $key ) {
				$new_columns['wctw_tags'] = __( '?典??蝐?, 'taiwan-store-core' );
			}
		}
		return $new_columns;
	}

	public function display_order_tags_column( $column, $order_id_or_order ): void {
		if ( 'wctw_tags' !== $column ) {
			return;
		}
		$order = ( $order_id_or_order instanceof \WC_Order ) ? $order_id_or_order : wc_get_order( $order_id_or_order );
		if ( ! $order ) {
			return;
		}

		$tags = [];
		$d    = $this->read( $order );

		// 1. ?潛巨憿?蝐?		if ( 'company' === $d['type'] ) {
			$tags[] = '<span class="wctw-tag wctw-tag-taxid" title="' . esc_attr( $d['tax_id'] ) . '">蝯梁楊</span>';
		} elseif ( in_array( $d['type'], [ 'carrier_phone', 'carrier_cert' ], true ) ) {
			$tags[] = '<span class="wctw-tag wctw-tag-carrier">頛</span>';
		} elseif ( 'donate' === $d['type'] ) {
			$tags[] = '<span class="wctw-tag wctw-tag-donate">??</span>';
		}

		/**
		 * 霈??瑕????嗡?璅∠??臭誑??芾?璅惜 (憒?[皛輸?韐?VIP] 蝑?
		 *
		 * @param array     $tags  HTML 璅惜???
		 * @param \WC_Order $order 閮?拐辣
		 */
		$tags = apply_filters( 'Taiwan_Store_Core_order_list_tags', $tags, $order );

		if ( ! empty( $tags ) ) {
			echo '<div class="wctw-tags-container">' . implode( '', $tags ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function output_tag_styles(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, [ 'edit-shop_order', 'woocommerce_page_wc-orders' ], true ) ) {
			return;
		}
		?>
		<style>
			.wctw-tags-container { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
			.wctw-tag {
				display: inline-block;
				padding: 2px 6px;
				border-radius: 4px;
				font-size: 11px;
				font-weight: 600;
				line-height: 1.2;
				white-space: nowrap;
				color: #fff;
			}
			.wctw-tag-taxid   { background-color: #1e3a5f; }
			.wctw-tag-carrier { background-color: #00a32a; }
			.wctw-tag-donate  { background-color: #f0821b; }
			.wctw-tag-marketing { background-color: #9b59b6; } /* ??蝯西???*/
		</style>
		<?php
	}

	private function read( \WC_Order $order ): array {
		return [
			'type'    => (string) $order->get_meta( '_wctw/invoice-type' ),
			'carrier' => (string) $order->get_meta( '_wctw/carrier-number' ),
			'tax_id'  => (string) $order->get_meta( '_wctw/company-tax-id' ),
			'title'   => (string) $order->get_meta( '_wctw/company-title' ),
		];
	}

	public function display_in_admin( \WC_Order $order ): void {
		$d = $this->read( $order );
		if ( '' === $d['type'] ) {
			return;
		}

		echo '<div style="margin-top:10px;padding:10px 0;border-top:1px solid #eee">';
		echo '<p style="margin:0 0 6px;font-weight:600;color:#3c434a">?屁 ' . esc_html__( '?潛巨鞈?', 'taiwan-store-core' ) . '</p>';

		$type_label = self::INVOICE_LABELS[ $d['type'] ] ?? $d['type'];
		echo '<p style="margin:3px 0"><strong>' . esc_html__( '?潛巨憿?嚗?, 'taiwan-store-core' ) . '</strong>' . esc_html( $type_label ) . '</p>';

		if ( '' !== $d['carrier'] ) {
			$label = match ( $d['type'] ) {
				'carrier_phone' => __( '??璇Ⅳ', 'taiwan-store-core' ),
				'carrier_cert'  => __( '?芰鈭箸?霅?, 'taiwan-store-core' ),
				'donate'        => __( '??蝣?, 'taiwan-store-core' ),
				default         => __( '頛?Ⅳ', 'taiwan-store-core' ),
			};
			echo '<p style="margin:3px 0"><strong>' . esc_html( $label ) . '嚗?/strong>' . esc_html( $d['carrier'] ) . '</p>';
		}
		if ( '' !== $d['tax_id'] ) {
			echo '<p style="margin:3px 0"><strong>' . esc_html__( '蝯曹?蝺刻?嚗?, 'taiwan-store-core' ) . '</strong>' . esc_html( $d['tax_id'] ) . '</p>';
		}
		if ( '' !== $d['title'] ) {
			echo '<p style="margin:3px 0"><strong>' . esc_html__( '?砍/璈?嚗?, 'taiwan-store-core' ) . '</strong>' . esc_html( $d['title'] ) . '</p>';
		}
		echo '</div>';
	}

	public function append_to_formatted_address( array $address, \WC_Order $order ): array {
		$d = $this->read( $order );
		$type_label = self::INVOICE_LABELS[ $d['type'] ] ?? '';
		if ( $type_label ) {
			$address['invoice_type'] = '?潛巨嚗? . $type_label;
		}
		if ( '' !== $d['carrier'] ) {
			$address['carrier_number'] = '頛嚗? . $d['carrier'];
		}
		if ( '' !== $d['tax_id'] ) {
			$address['tax_id'] = '蝯梁楊嚗? . $d['tax_id'];
		}
		if ( '' !== $d['title'] ) {
			$address['company_title'] = $d['title'];
		}
		return $address;
	}

	public function display_on_frontend( \WC_Order $order ): void {
		$d = $this->read( $order );
		if ( '' === $d['type'] ) {
			return;
		}

		echo '<section class="woocommerce-customer-details">';
		echo '<h2 class="woocommerce-column__title">' . esc_html__( '?潛巨鞈?', 'taiwan-store-core' ) . '</h2>';
		echo '<address>';

		$type_label = self::INVOICE_LABELS[ $d['type'] ] ?? $d['type'];
		echo '<p><strong>' . esc_html__( '?潛巨憿?', 'taiwan-store-core' ) . '嚗?/strong>' . esc_html( $type_label ) . '</p>';
		if ( '' !== $d['carrier'] ) {
			echo '<p><strong>' . esc_html__( '頛?Ⅳ', 'taiwan-store-core' ) . '嚗?/strong>' . esc_html( $d['carrier'] ) . '</p>';
		}
		if ( '' !== $d['tax_id'] ) {
			echo '<p><strong>' . esc_html__( '蝯曹?蝺刻?', 'taiwan-store-core' ) . '嚗?/strong>' . esc_html( $d['tax_id'] ) . '</p>';
		}
		if ( '' !== $d['title'] ) {
			echo '<p><strong>' . esc_html__( '?砍?迂', 'taiwan-store-core' ) . '嚗?/strong>' . esc_html( $d['title'] ) . '</p>';
		}

		echo '</address></section>';
	}

	/**
	 * 憿舐內??WooCommerce 撖???桃Ⅱ隤縑?恣?靽～?	 * Hook: woocommerce_email_customer_details (priority 20, after billing/shipping blocks)
	 *
	 * @param \WC_Order $order
	 * @param bool      $sent_to_admin
	 * @param bool      $plain_text
	 * @param mixed     $email  WC_Email instance
	 */
	public function display_in_email( \WC_Order $order, bool $sent_to_admin, bool $plain_text, $email ): void {
		$d = $this->read( $order );
		if ( '' === $d['type'] ) {
			return;
		}

		$type_label = self::INVOICE_LABELS[ $d['type'] ] ?? $d['type'];

		if ( $plain_text ) {
			echo "\n" . esc_html__( '== ?潛巨鞈? ==', 'taiwan-store-core' ) . "\n";
			echo esc_html__( '?潛巨憿?嚗?, 'taiwan-store-core' ) . esc_html( $type_label ) . "\n";
			if ( '' !== $d['carrier'] ) {
				echo esc_html__( '頛?Ⅳ嚗?, 'taiwan-store-core' ) . esc_html( $d['carrier'] ) . "\n";
			}
			if ( '' !== $d['tax_id'] ) {
				echo esc_html__( '蝯曹?蝺刻?嚗?, 'taiwan-store-core' ) . esc_html( $d['tax_id'] ) . "\n";
			}
			if ( '' !== $d['title'] ) {
				echo esc_html__( '?砍?迂嚗?, 'taiwan-store-core' ) . esc_html( $d['title'] ) . "\n";
			}
			return;
		}

		// HTML email
		echo '<div style="margin-bottom:40px">';
		echo '<h2 style="color:#96588a;display:block;font-family:\'Helvetica Neue\',Helvetica,Roboto,Arial,sans-serif;font-size:18px;font-weight:bold;line-height:130%;margin:0 0 18px;text-align:left">';
		echo esc_html__( '?潛巨鞈?', 'taiwan-store-core' );
		echo '</h2>';
		echo '<table cellspacing="0" cellpadding="6" border="0" style="width:100%;font-family:\'Helvetica Neue\',Helvetica,Roboto,Arial,sans-serif">';
		echo '<tbody>';
		echo '<tr><td style="text-align:left;vertical-align:middle;border:1px solid #e5e5e5;padding:12px"><strong>' . esc_html__( '?潛巨憿?', 'taiwan-store-core' ) . '</strong></td><td style="text-align:left;vertical-align:middle;border:1px solid #e5e5e5;padding:12px">' . esc_html( $type_label ) . '</td></tr>';
		if ( '' !== $d['carrier'] ) {
			echo '<tr><td style="text-align:left;vertical-align:middle;border:1px solid #e5e5e5;padding:12px"><strong>' . esc_html__( '頛?Ⅳ', 'taiwan-store-core' ) . '</strong></td><td style="text-align:left;vertical-align:middle;border:1px solid #e5e5e5;padding:12px">' . esc_html( $d['carrier'] ) . '</td></tr>';
		}
		if ( '' !== $d['tax_id'] ) {
			echo '<tr><td style="text-align:left;vertical-align:middle;border:1px solid #e5e5e5;padding:12px"><strong>' . esc_html__( '蝯曹?蝺刻?', 'taiwan-store-core' ) . '</strong></td><td style="text-align:left;vertical-align:middle;border:1px solid #e5e5e5;padding:12px">' . esc_html( $d['tax_id'] ) . '</td></tr>';
		}
		if ( '' !== $d['title'] ) {
			echo '<tr><td style="text-align:left;vertical-align:middle;border:1px solid #e5e5e5;padding:12px"><strong>' . esc_html__( '?砍?迂', 'taiwan-store-core' ) . '</strong></td><td style="text-align:left;vertical-align:middle;border:1px solid #e5e5e5;padding:12px">' . esc_html( $d['title'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '</div>';
	}
}

