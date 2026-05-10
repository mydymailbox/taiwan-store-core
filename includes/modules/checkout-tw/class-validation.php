<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * 撽??啁蝯董甈?嚗? Additional Checkout Fields API ??Blocks validate hook嚗? * 甇?hook ??Classic ??Block 蝯董??閫貊嚗? *
 * ?血?靽? woocommerce_checkout_process ?冽撽? billing_phone嚗? additional field嚗? */
class Validation {

	public function boot(): void {
		add_action( 'woocommerce_blocks_validate_location_order_fields', [ $this, 'validate_invoice' ], 10, 2 );
		add_action( 'woocommerce_checkout_process', [ $this, 'validate_invoice_classic' ] );

		// Phone ??both Classic ??Blocks
		add_action( 'woocommerce_checkout_process', [ $this, 'validate_phone_classic' ] );
		add_action( 'woocommerce_blocks_validate_location_address_fields', [ $this, 'validate_phone_blocks' ], 10, 2 );

		// Phone normalization ??strip spaces/dashes??886 ??0
		add_filter( 'woocommerce_checkout_posted_data', [ $this, 'normalize_phone_classic' ] );
		add_filter( 'woocommerce_store_api_address_validate_phone', [ $this, 'normalize_phone_blocks' ], 10, 2 );
	}

	// ?? Invoice fields (additional checkout fields) ??????????????????????????

	/**
	 * @param \WP_Error $errors
	 * @param array     $fields  撌?sanitize ??雿?[ 'wctw/invoice-type' => '...', ... ]
	 */
	public function validate_invoice( $errors, array $fields ): void {
		$validate_tax_id = 'yes' === get_option( 'Taiwan_Store_Core_checkout_tax_id_enabled', 'yes' )
			&& 'yes' === get_option( 'Taiwan_Store_Core_checkout_tax_id_validate', 'yes' );

		$type    = $fields['wctw/invoice-type']   ?? 'personal';
		$carrier = strtoupper( (string) ( $fields['wctw/carrier-number'] ?? '' ) );
		$tax_id  = (string) ( $fields['wctw/company-tax-id'] ?? '' );
		$title   = (string) ( $fields['wctw/company-title'] ?? '' );

		if ( 'company' === $type ) {
			if ( $validate_tax_id && ( '' === $tax_id || ! self::is_valid_tax_id( $tax_id ) ) ) {
				$errors->add( 'wctw_tax_id', __( '蝯曹?蝺刻??澆??炎?亦Ⅳ?航炊嚗?頛詨????8 蝣潛絞銝蝺刻???, 'taiwan-store-core' ) );
			}
			if ( '' === trim( $title ) ) {
				$errors->add( 'wctw_company_title', __( '隢撓?亙??/ 璈??迂??, 'taiwan-store-core' ) );
			}
			return;
		}

		if ( 'carrier_phone' === $type ) {
			if ( '' === $carrier || ! preg_match( '#^/[0-9A-Z+\-.]{7}$#', $carrier ) ) {
				$errors->add( 'wctw_carrier', __( '??璇Ⅳ?澆?嚗? ? + 7 蝣潘??詨??之撖怨?? - .嚗?, 'taiwan-store-core' ) );
			}
		} elseif ( 'carrier_cert' === $type ) {
			if ( '' === $carrier || ! preg_match( '/^[A-Z]{2}\d{14}$/', $carrier ) ) {
				$errors->add( 'wctw_carrier', __( '?芰鈭箸?霅撘?2 蝣澆之撖怨??+ 14 蝣潭摮?, 'taiwan-store-core' ) );
			}
		} elseif ( 'donate' === $type ) {
			if ( ! preg_match( '/^\d{3,7}$/', $carrier ) ) {
				$errors->add( 'wctw_carrier', __( '??蝣潛 3?? 蝣潭摮?, 'taiwan-store-core' ) );
			}
		}
	}

	// ?? Phone validation ?????????????????????????????????????????????????????

	public function validate_invoice_classic(): void {
		if ( 'yes' !== get_option( 'Taiwan_Store_Core_checkout_tax_id_enabled', 'yes' ) ) {
			return;
		}
		$validate_tax_id = 'yes' === get_option( 'Taiwan_Store_Core_checkout_tax_id_validate', 'yes' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified by WC checkout process
		$type    = sanitize_key( wp_unslash( $_POST['billing_wctw_invoice_type'] ?? '' ) );
		$carrier = strtoupper( sanitize_text_field( wp_unslash( $_POST['billing_wctw_carrier_number'] ?? '' ) ) );
		$tax_id  = sanitize_text_field( wp_unslash( $_POST['billing_wctw_company_tax_id'] ?? '' ) );
		$title   = sanitize_text_field( wp_unslash( $_POST['billing_wctw_company_title'] ?? '' ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' === $type ) {
			wc_add_notice( __( '隢?蟡券???, 'taiwan-store-core' ), 'error' );
			return;
		}

		if ( 'company' === $type ) {
			if ( $validate_tax_id && ( '' === $tax_id || ! self::is_valid_tax_id( $tax_id ) ) ) {
				wc_add_notice( __( '蝯曹?蝺刻??澆??炎?亦Ⅳ?航炊嚗?頛詨????8 蝣潛絞銝蝺刻???, 'taiwan-store-core' ), 'error' );
			}
			if ( '' === trim( $title ) ) {
				wc_add_notice( __( '隢撓?亙??/ 璈??迂??, 'taiwan-store-core' ), 'error' );
			}
			return;
		}

		if ( 'carrier_phone' === $type ) {
			if ( '' === $carrier || ! preg_match( '#^/[0-9A-Z+\-.]{7}$#', $carrier ) ) {
				wc_add_notice( __( '??璇Ⅳ?澆?嚗? ? + 7 蝣潘??詨??之撖怨?? - .嚗?, 'taiwan-store-core' ), 'error' );
			}
		} elseif ( 'carrier_cert' === $type ) {
			if ( '' === $carrier || ! preg_match( '/^[A-Z]{2}\d{14}$/', $carrier ) ) {
				wc_add_notice( __( '?芰鈭箸?霅撘?2 蝣澆之撖怨??+ 14 蝣潭摮?, 'taiwan-store-core' ), 'error' );
			}
		} elseif ( 'donate' === $type ) {
			if ( ! preg_match( '/^\d{3,7}$/', $carrier ) ) {
				wc_add_notice( __( '??蝣潛 3?? 蝣潭摮?, 'taiwan-store-core' ), 'error' );
			}
		}
	}

	// ?? Phone validation ?????????????????????????????????????????????????????

	public function validate_phone_classic(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$country = sanitize_key( wp_unslash( $_POST['billing_country'] ?? 'TW' ) );
		if ( 'TW' !== strtoupper( $country ) ) {
			return; // ????頝喲???撽?
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$phone = self::normalize_tw_phone( sanitize_text_field( wp_unslash( $_POST['billing_phone'] ?? '' ) ) );
		if ( '' !== $phone && ! self::is_valid_tw_mobile( $phone ) ) {
			wc_add_notice(
				__( '???Ⅳ?澆?銝迤蝣綽?隢撓?交????啁銵??餉店?Ⅳ嚗?嚗?912345678嚗?, 'taiwan-store-core' ),
				'error'
			);
		}
	}

	public function validate_phone_blocks( $errors, array $fields ): void {
		$country = strtoupper( (string) ( $fields['country'] ?? 'TW' ) );
		if ( 'TW' !== $country ) {
			return;
		}
		$phone = self::normalize_tw_phone( (string) ( $fields['phone'] ?? '' ) );
		if ( '' !== $phone && ! self::is_valid_tw_mobile( $phone ) ) {
			$errors->add(
				'wctw_phone',
				__( '???Ⅳ?澆?銝迤蝣綽?隢撓?交????啁銵??餉店?Ⅳ嚗?嚗?912345678嚗?, 'taiwan-store-core' )
			);
		}
	}

	// ?? Phone normalization ??????????????????????????????????????????????????

	public function normalize_phone_classic( array $data ): array {
		$country = strtoupper( (string) ( $data['billing_country'] ?? 'TW' ) );
		if ( 'TW' === $country && isset( $data['billing_phone'] ) ) {
			$data['billing_phone'] = self::normalize_tw_phone( (string) $data['billing_phone'] );
		}
		$ship_country = strtoupper( (string) ( $data['shipping_country'] ?? $country ) );
		if ( 'TW' === $ship_country && isset( $data['shipping_phone'] ) ) {
			$data['shipping_phone'] = self::normalize_tw_phone( (string) $data['shipping_phone'] );
		}
		return $data;
	}

	public function normalize_phone_blocks( $value, $phone ) {
		// Filter passes already-validated phone; we just normalize the string before storage.
		return self::normalize_tw_phone( (string) $phone );
	}

	/**
	 * ?啁???Ⅳ甇????
	 *   - ?駁蝛箇????敶Ｗ???	 *   - +886 9xxxxxxxx ??09xxxxxxxx
	 *   - 886 9xxxxxxxx  ??09xxxxxxxx
	 */
	public static function normalize_tw_phone( string $phone ): string {
		$cleaned = preg_replace( '/[\s\-()]/', '', $phone );
		if ( '' === $cleaned ) {
			return '';
		}
		// +886 / 886 prefix ??0
		$cleaned = preg_replace( '/^(\+?886)(9\d{8})$/', '0$2', $cleaned );
		return $cleaned;
	}

	// ?? Static validators ????????????????????????????????????????????????????

	/**
	 * ?啁蝯曹?蝺刻? 8 蝣潭炎?亦Ⅳ瞍?瘜?	 * 甈? [1,2,1,2,1,2,4,1]嚗??訾?敺?銋????詨?蝮踝???蝮賣?????	 * ?亦洵 7 蝣潛 7嚗蜇?? 10 ????0 OR (蝮賢?+1) 撠?10 ????0 ???箏?瘜?	 */
	public static function is_valid_tax_id( string $id ): bool {
		if ( strlen( $id ) !== 8 || ! ctype_digit( $id ) ) {
			return false;
		}
		$weights = [ 1, 2, 1, 2, 1, 2, 4, 1 ];
		$sum     = 0;
		for ( $i = 0; $i < 8; $i++ ) {
			$product = (int) $id[ $i ] * $weights[ $i ];
			$sum    += intdiv( $product, 10 ) + ( $product % 10 );
		}
		if ( '7' === $id[6] ) {
			return ( 0 === $sum % 10 ) || ( 0 === ( $sum + 1 ) % 10 );
		}
		return 0 === $sum % 10;
	}

	public static function is_valid_tw_mobile( string $phone ): bool {
		$cleaned = preg_replace( '/[\s\-]/', '', $phone );
		return (bool) preg_match( '/^09\d{8}$/', $cleaned );
	}
}

