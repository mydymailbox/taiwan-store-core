<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Taiwan checkout locale:
 *   - Registers ISO 3166-2:TW state list (22 蝮??).
 *   - Overrides WooCommerce field labels, required flags, and priorities
 *     for the TW locale so the form follows Taiwan's conventional order:
 *     憪? | ?? ??蝮?? | ?撣? ???菟??????閰喟敦?啣? ???砍嚗憛恬?
 */
class Locale {

	public function boot(): void {
		add_filter( 'woocommerce_states', [ $this, 'register_tw_states' ] );
		add_filter( 'woocommerce_get_country_locale', [ $this, 'tw_locale' ] );
		add_filter( 'woocommerce_checkout_fields', [ $this, 'reorder_checkout_fields' ], 9999 );

		// Classic Checkout invoice fields (parallel to Blocks Additional Fields API)
		add_filter( 'woocommerce_checkout_fields', [ $this, 'add_invoice_fields_classic' ], 20 );
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_invoice_fields_classic' ] );
	}

	// ?? States ????????????????????????????????????????????????????????????????

	public function register_tw_states( array $states ): array {
		$states['TW'] = require Taiwan_Store_Core_DIR . 'includes/modules/checkout-tw/data/tw-states.php';
		return $states;
	}

	// ?? Locale overrides (address fields) ?????????????????????????????????????

	public function tw_locale( array $locale ): array {
		$locale['TW'] = array_merge(
			$locale['TW'] ?? [],
			[
				// ?啁蝧嚗???雿萇銝??雿?(雿輻 last_name 雿銝餉?甈?嚗??first_name)
				'last_name'  => ( 'yes' === get_option( 'Taiwan_Store_Core_checkout_name_consolidate', 'yes' ) ) ? [
					'label'    => __( '憪?', 'taiwan-store-core' ),
					'placeholder' => __( '隢撓?交隞嗡犖?典?', 'taiwan-store-core' ),
					'priority' => 10,
					'class'    => [ 'form-row-wide' ],
				] : [
					'label'    => __( '憪?', 'taiwan-store-core' ),
					'priority' => 10,
					'class'    => [ 'form-row-first' ],
				],
				'first_name' => ( 'yes' === get_option( 'Taiwan_Store_Core_checkout_name_consolidate', 'yes' ) ) ? [
					'label'    => __( '??', 'taiwan-store-core' ),
					'required' => false,
					'priority' => 11,
					'class'    => [ 'form-row-wide', 'wctw-hidden-field' ],
				] : [
					'label'    => __( '??', 'taiwan-store-core' ),
					'priority' => 20,
					'class'    => [ 'form-row-last' ],
				],
				// ????Email
				'phone'      => [
					'label'    => __( '???Ⅳ', 'taiwan-store-core' ),
					'required' => true,
					'priority' => 20,
					'class'    => [ 'form-row-first' ],
				],
				'email'      => [
					'label'    => __( '?餃??萎辣', 'taiwan-store-core' ),
					'priority' => 30,
					'class'    => [ 'form-row-last' ],
				],
				// ?啣??憛?蝮??(撌? | ?菟?????? ???撣?(?典祝)
				'state'      => [
					'label'    => __( '蝮??', 'taiwan-store-core' ),
					'required' => true,
					'priority' => 50,
					'class'    => [ 'form-row-first' ],
				],
				'postcode'   => [
					'label'       => __( '?菟????, 'taiwan-store-core' ),
					'placeholder' => __( '?芸?撣嗅', 'taiwan-store-core' ),
					'required'    => false,
					'priority'    => 55,
					'class'       => [ 'form-row-last' ],
					'autocomplete' => 'postal-code',
				],
				'city'       => [
					'label'    => __( '?撣?', 'taiwan-store-core' ),
					'required' => true,
					'priority' => 60,
					'class'    => [ 'form-row-wide' ],
				],
				'address_1'  => [
					'label'       => __( '閰喟敦?啣?', 'taiwan-store-core' ),
					'placeholder' => __( '銵??楝?挾?毽????', 'taiwan-store-core' ),
					'priority'    => 80,
					'class'       => [ 'form-row-wide' ],
				],
				'address_2'  => [
					'label'       => __( '璅惜 / 摰?, 'taiwan-store-core' ),
					'placeholder' => __( '撟暹??嗾摰扎撖??詨‵嚗?, 'taiwan-store-core' ),
					'priority'    => 90,
					'class'       => [ 'form-row-wide' ],
				],
				'company'    => [
					'label'    => __( '?砍?迂', 'taiwan-store-core' ),
					'required' => false,
					'priority' => 95,
					'class'    => [ 'form-row-wide' ],
				],
			]
		);

		return $locale;
	}

	public function reorder_checkout_fields( array $fields ): array {
		$consolidate = ( 'yes' === get_option( 'Taiwan_Store_Core_checkout_name_consolidate', 'yes' ) );

		// ????甈??梯?
		if ( $consolidate ) {
			if ( isset( $fields['billing']['billing_first_name'] ) ) {
				$fields['billing']['billing_first_name']['class'][] = 'wctw-hidden-field';
				$fields['billing']['billing_first_name']['required'] = false;
				$fields['billing']['billing_first_name']['label'] = '';
			}
			if ( isset( $fields['shipping']['shipping_first_name'] ) ) {
				$fields['shipping']['shipping_first_name']['class'][] = 'wctw-hidden-field';
				$fields['shipping']['shipping_first_name']['required'] = false;
				$fields['shipping']['shipping_first_name']['label'] = '';
			}
		}

		// ?? ??Email嚗蝯∟?閮?嚗憪?銋???銋?嚗?		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['priority'] = 20;
			$fields['billing']['billing_phone']['class']    = [ 'form-row-first' ];
			$fields['billing']['billing_phone']['label']    = __( '???Ⅳ', 'taiwan-store-core' );
			$fields['billing']['billing_phone']['required'] = true;
		}
		if ( isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['priority'] = 30;
			$fields['billing']['billing_email']['class']    = [ 'form-row-last' ];
			$fields['billing']['billing_email']['required'] = true;
		}

		// ?梯??振甈?嚗???摨?閮?TW嚗??雿輻???		if ( isset( $fields['billing']['billing_country'] ) ) {
			$fields['billing']['billing_country']['type']     = 'hidden';
			$fields['billing']['billing_country']['class']    = [ 'hidden' ];
			$fields['billing']['billing_country']['priority'] = 1;
			$fields['billing']['billing_country']['default']  = 'TW';
		}
		if ( isset( $fields['shipping']['shipping_country'] ) ) {
			$fields['shipping']['shipping_country']['type']    = 'hidden';
			$fields['shipping']['shipping_country']['class']   = [ 'hidden' ];
			$fields['shipping']['shipping_country']['priority'] = 1;
			$fields['shipping']['shipping_country']['default'] = 'TW';
		}

		// ?? ?啣?甈????芸? (蝮?? + ?雿菜?) ??
		if ( isset( $fields['billing']['billing_state'] ) ) {
			$fields['billing']['billing_state']['priority'] = 70;
			$fields['billing']['billing_state']['class']    = [ 'form-row-first' ];
		}
		if ( isset( $fields['billing']['billing_city'] ) ) {
			$fields['billing']['billing_city']['priority'] = 71;
			$fields['billing']['billing_city']['class']    = [ 'form-row-last' ];
			$fields['billing']['billing_city']['label']    = __( '?撣?', 'taiwan-store-core' );
		}
		if ( isset( $fields['billing']['billing_postcode'] ) ) {
			$fields['billing']['billing_postcode']['priority'] = 80;
			$fields['billing']['billing_postcode']['class']    = [ 'form-row-wide' ];
			$fields['billing']['billing_postcode']['placeholder'] = __( '?芸?憛怠', 'taiwan-store-core' );
		}
		if ( isset( $fields['billing']['billing_address_1'] ) ) {
			$fields['billing']['billing_address_1']['priority'] = 90;
		}

		return $fields;
	}

	// ?? Classic Checkout: invoice fields ??????????????????????????????????????

	public function add_invoice_fields_classic( array $fields ): array {
		if ( 'yes' !== get_option( 'Taiwan_Store_Core_checkout_tax_id_enabled', 'yes' ) ) {
			return $fields;
		}
		// Skip if Blocks checkout is being rendered (handled by register_invoice_fields)
		if ( function_exists( 'wc_current_theme_is_fse_theme' ) && did_action( 'woocommerce_blocks_loaded' ) ) {
			$page_id = wc_get_page_id( 'checkout' );
			if ( $page_id && has_block( 'woocommerce/checkout', $page_id ) ) {
				return $fields;
			}
		}

		$fields['billing']['billing_wctw_invoice_type'] = [
			'type'     => 'select',
			'label'    => __( '?潛巨憿?', 'taiwan-store-core' ),
			'required' => true,
			'class'    => [ 'form-row-wide' ],
			'priority' => 120,
			'options'  => [
				''              => __( '? 隢???', 'taiwan-store-core' ),
				'personal'      => __( '?犖?餃??潛巨嚗蝡荔?', 'taiwan-store-core' ),
				'carrier_phone' => __( '??璇Ⅳ', 'taiwan-store-core' ),
				'carrier_cert'  => __( '?芰鈭箸?霅?蝣?, 'taiwan-store-core' ),
				'donate'        => __( '??蝣?, 'taiwan-store-core' ),
				'company'       => __( '?砍銝撘??蝯梁楊嚗?, 'taiwan-store-core' ),
			],
		];
		$fields['billing']['billing_wctw_carrier_number'] = [
			'type'        => 'text',
			'label'       => __( '頛 / ??蝣?, 'taiwan-store-core' ),
			'placeholder' => __( '/ABC+123?B12345678901234 ??3?? 蝣潭?韐Ⅳ', 'taiwan-store-core' ),
			'required'    => false,
			'class'       => [ 'form-row-wide' ],
			'priority'    => 130,
		];
		$fields['billing']['billing_wctw_company_tax_id'] = [
			'type'        => 'text',
			'label'       => __( '蝯曹?蝺刻?', 'taiwan-store-core' ),
			'placeholder' => __( '8 蝣潭摮??砍?嗅?憛?, 'taiwan-store-core' ),
			'required'    => false,
			'class'       => [ 'form-row-first' ],
			'maxlength'   => 8,
			'priority'    => 140,
		];
		$fields['billing']['billing_wctw_company_title'] = [
			'type'        => 'text',
			'label'       => __( '?砍?迂', 'taiwan-store-core' ),
			'placeholder' => __( '?砍?嗅?憛恬??舐蝯梁楊?芸?撣嗅嚗?, 'taiwan-store-core' ),
			'required'    => false,
			'class'       => [ 'form-row-last' ],
			'priority'    => 150,
		];

		return $fields;
	}

	public function save_invoice_fields_classic( int $order_id ): void {
		if ( 'yes' !== get_option( 'Taiwan_Store_Core_checkout_tax_id_enabled', 'yes' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$map = [
			'billing_wctw_invoice_type'   => '_wctw/invoice-type',
			'billing_wctw_carrier_number' => '_wctw/carrier-number',
			'billing_wctw_company_tax_id' => '_wctw/company-tax-id',
			'billing_wctw_company_title'  => '_wctw/company-title',
		];
		$saved = false;
		foreach ( $map as $post_key => $meta_key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by WC checkout process
			$raw = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ?? '' ) );
			if ( '' !== $raw ) {
				$order->update_meta_data( $meta_key, $raw );
				$saved = true;
			}
		}
		if ( $saved ) {
			$order->save();
		}
	}
}

