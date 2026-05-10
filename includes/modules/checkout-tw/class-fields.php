<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * 註冊台灣結帳欄位（透過 WC 8.6+ Additional Checkout Fields API，同時支援 Classic + Blocks）
 * 並 enqueue 縣市/鄉鎮/郵遞區號自動帶入的前端腳本。
 *
 * 欄位（namespace = wctw）：
 *   wctw/invoice-type        — select: personal | carrier_phone | carrier_cert | donate | company
 *   wctw/carrier-number      — text  (手機條碼 / 自然人憑證 / 捐贈碼)
 *   wctw/company-tax-id      — text  (8 碼統編)
 *   wctw/company-title       — text  (公司/機構抬頭)
 *
 * 已知限制（A 方案）：API 不支援條件式顯示，所有欄位永遠出現於 Blocks；
 * 在 Classic 仍可由 jQuery 動態切換顯示，留待後續微調。
 */
class Fields {

	public function boot(): void {
		add_action( 'woocommerce_init', [ $this, 'register_invoice_fields' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_checkout_scripts' ] );
		add_action( 'wp_head', [ $this, 'output_checkout_css' ] );

		// AJAX: 統一編號 → 公司名稱查詢（公開，不需登入）
		add_action( 'wp_ajax_taiwan_store_core_lookup_taxid',        [ $this, 'ajax_lookup_taxid' ] );
		add_action( 'wp_ajax_nopriv_taiwan_store_core_lookup_taxid', [ $this, 'ajax_lookup_taxid' ] );

		// AJAX: 超商地圖回傳接收
		add_action( 'wp_ajax_taiwan_store_core_cvs_map_callback',        [ $this, 'ajax_cvs_map_callback' ] );
		add_action( 'wp_ajax_nopriv_taiwan_store_core_cvs_map_callback', [ $this, 'ajax_cvs_map_callback' ] );
	}

	public function register_invoice_fields(): void {
		if ( 'yes' !== get_option( 'taiwan_store_core_checkout_tax_id_enabled', 'yes' ) ) {
			return;
		}
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		woocommerce_register_additional_checkout_field( [
			'id'       => 'wctw/invoice-type',
			'label'    => __( '發票類型', 'taiwan-store-core' ),
			'location' => 'order',
			'type'     => 'select',
			'required' => true,
			'options'  => [
				[ 'value' => 'personal',      'label' => __( '個人電子發票（雲端）', 'taiwan-store-core' ) ],
				[ 'value' => 'carrier_phone', 'label' => __( '手機條碼', 'taiwan-store-core' ) ],
				[ 'value' => 'carrier_cert',  'label' => __( '自然人憑證條碼', 'taiwan-store-core' ) ],
				[ 'value' => 'donate',        'label' => __( '捐贈碼', 'taiwan-store-core' ) ],
				[ 'value' => 'company',       'label' => __( '公司三聯式（需統編）', 'taiwan-store-core' ) ],
			],
		] );

		woocommerce_register_additional_checkout_field( [
			'id'                => 'wctw/carrier-number',
			'label'             => __( '載具 / 捐贈碼（依發票類型填寫）', 'taiwan-store-core' ),
			'location'          => 'order',
			'type'              => 'text',
			'required'          => false,
			'attributes'        => [ 'placeholder' => __( '/ABC+123、AB12345678901234 或 3–7 碼捐贈碼', 'taiwan-store-core' ) ],
			'sanitize_callback' => static fn( $v ) => strtoupper( trim( (string) $v ) ),
		] );

		woocommerce_register_additional_checkout_field( [
			'id'                => 'wctw/company-tax-id',
			'label'             => __( '統一編號（公司戶必填）', 'taiwan-store-core' ),
			'location'          => 'order',
			'type'              => 'text',
			'required'          => false,
			'attributes'        => [ 'maxLength' => '8', 'inputMode' => 'numeric', 'placeholder' => __( '8 碼數字', 'taiwan-store-core' ) ],
			'sanitize_callback' => static fn( $v ) => preg_replace( '/\D/', '', (string) $v ),
		] );

		woocommerce_register_additional_checkout_field( [
			'id'         => 'wctw/company-title',
			'label'      => __( '公司 / 機構名稱（公司戶必填）', 'taiwan-store-core' ),
			'location'   => 'order',
			'type'       => 'text',
			'required'   => false,
			'attributes' => [ 'placeholder' => __( '可由統編自動帶入（Classic Checkout）', 'taiwan-store-core' ) ],
		] );

		// CVS Store fields
		woocommerce_register_additional_checkout_field( [
			'id'       => 'wctw/cvs-store-name',
			'label'    => __( '取貨門市名稱', 'taiwan-store-core' ),
			'location' => 'address',
			'type'     => 'text',
			'required' => false,
			'attributes' => [ 'readOnly' => true, 'placeholder' => __( '請點擊下方按鈕選擇門市', 'taiwan-store-core' ) ],
		] );

		woocommerce_register_additional_checkout_field( [
			'id'       => 'wctw/cvs-store-address',
			'label'    => __( '門市地址', 'taiwan-store-core' ),
			'location' => 'address',
			'type'     => 'text',
			'required' => false,
			'attributes' => [ 'readOnly' => true ],
		] );

		woocommerce_register_additional_checkout_field( [
			'id'       => 'wctw/cvs-store-id',
			'label'    => __( '門市代碼', 'taiwan-store-core' ),
			'location' => 'address',
			'type'     => 'text',
			'required' => false,
			'attributes' => [ 'readOnly' => true, 'style' => 'display:none;' ],
		] );
	}

	public function enqueue_checkout_scripts(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		$districts = require TAIWAN_STORE_CORE_DIR . 'includes/modules/checkout-tw/data/tw-districts.php';
		$postcodes = require TAIWAN_STORE_CORE_DIR . 'includes/modules/checkout-tw/data/tw-postcodes.php';

		$state_data = [];
		foreach ( $districts as $state => $district_list ) {
			$state_data[ $state ] = [];
			foreach ( $district_list as $district ) {
				$state_data[ $state ][ $district ] = $postcodes[ $state ][ $district ] ?? '';
			}
		}

		wp_enqueue_script(
			'taiwan-store-core-checkout',
			TAIWAN_STORE_CORE_URL . 'assets/js/checkout.js',
			[ 'jquery', 'wc-checkout' ],
			TAIWAN_STORE_CORE_VERSION,
			true
		);

		wp_localize_script( 'taiwan-store-core-checkout', 'wcTwCheckout', [
			'states'           => $state_data,
			'autofill'         => 'yes' === get_option( 'taiwan_store_core_checkout_autofill', 'yes' ),
			'nameConsolidate'  => get_option( 'taiwan_store_core_checkout_name_consolidate', 'yes' ),
			'lookupNonce'      => wp_create_nonce( 'taiwan_store_core_lookup_taxid' ),
			'mapUrl'           => 'https://logistics.ecpay.com.tw/Express/map',
			'mapCallbackUrl'   => admin_url( 'admin-ajax.php?action=taiwan_store_core_cvs_map_callback&nonce=' . wp_create_nonce( 'wc_tw_cvs_callback' ) ),
			'merchantId'       => get_option( 'taiwan_store_core_ecpay_merchant_id', '2000132' ),
			'taxidLookup'      => 'yes' === get_option( 'taiwan_store_core_checkout_taxid_lookup', 'yes' ),
			'phoneValidate'    => true,
			'i18n'             => [
				'looking'   => __( '查詢中…', 'taiwan-store-core' ),
				'not_found' => __( '查無此統編', 'taiwan-store-core' ),
				'error'     => __( '查詢失敗，請手動填寫', 'taiwan-store-core' ),
			],
		] );
	}

	public function output_checkout_css(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		$consolidate = ( 'yes' === get_option( 'taiwan_store_core_checkout_name_consolidate', 'yes' ) );
		?>
		<style id="wctw-checkout-overrides">
			<?php if ( $consolidate ) : ?>
			#billing_first_name_field, #shipping_first_name_field, .wctw-hidden-field,
			[data-field-id="billing-first_name"], [data-field-id="shipping-first_name"] {
				display: none !important;
				visibility: hidden !important;
				height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				opacity: 0 !important;
				pointer-events: none !important;
				position: absolute !important;
				overflow: hidden !important;
			}
			<?php endif; ?>
			#billing_country_field, #shipping_country_field,
			[data-field-id="billing-country"], [data-field-id="shipping-country"] {
				display: none !important;
			}
			.wctw-tags-container { margin-top: 5px; }
		</style>
		<?php
	}

	public function ajax_cvs_map_callback(): void {
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'wc_tw_cvs_callback' ) ) {
			wp_die( 'Security check failed: Invalid CVS callback nonce.' );
		}

		$store_id   = isset( $_POST['CVSStoreID'] )   ? sanitize_text_field( wp_unslash( $_POST['CVSStoreID'] ) )   : '';
		$store_name = isset( $_POST['CVSStoreName'] ) ? sanitize_text_field( wp_unslash( $_POST['CVSStoreName'] ) ) : '';
		$store_addr = isset( $_POST['CVSAddress'] )   ? sanitize_text_field( wp_unslash( $_POST['CVSAddress'] ) )   : '';

		if ( ! $store_id || ! $store_name ) {
			wp_die( 'Invalid CVS data received.' );
		}
		?>
		<!DOCTYPE html>
		<html>
		<head><title>CVS Callback</title></head>
		<body>
			<script>
				if ( window.opener ) {
					window.opener.jQuery('#wctw-cvs-store-id-field input').val('<?php echo esc_js( $store_id ); ?>').trigger('change');
					window.opener.jQuery('#wctw-cvs-store-name-field input').val('<?php echo esc_js( $store_name ); ?>').trigger('change');
					window.opener.jQuery('#wctw-cvs-store-address-field input').val('<?php echo esc_js( $store_addr ); ?>').trigger('change');
					window.close();
				} else {
					document.write('請關閉此視窗並返回結帳頁面。');
				}
			</script>
		</body>
		</html>
		<?php
		exit;
	}

	public function ajax_lookup_taxid(): void {
		check_ajax_referer( 'taiwan_store_core_lookup_taxid', 'nonce' );

		$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$rate_key = 'taiwan_store_taxid_rl_' . md5( $ip );
		$hits     = (int) get_transient( $rate_key );
		if ( $hits >= 20 ) {
			wp_send_json_error( [ 'message' => __( '查詢過於頻繁，請稍後再試', 'taiwan-store-core' ) ], 429 );
		}
		set_transient( $rate_key, $hits + 1, MINUTE_IN_SECONDS );

		$tax_id = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['tax_id'] ?? '' ) ) );

		if ( strlen( $tax_id ) !== 8 ) {
			wp_send_json_error( [ 'message' => __( '統編格式錯誤', 'taiwan-store-core' ) ] );
		}

		$cache_key = 'taiwan_store_taxid_' . $tax_id;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			if ( '' === $cached ) {
				wp_send_json_error( [ 'message' => __( '查無此統一編號', 'taiwan-store-core' ) ] );
			}
			wp_send_json_success( [ 'company' => $cached ] );
		}

		$company = $this->query_gcis( $tax_id );

		if ( null === $company ) {
			wp_send_json_error( [ 'message' => __( '查詢失敗，請稍後再試', 'taiwan-store-core' ) ] );
		}

		if ( '' === $company ) {
			set_transient( $cache_key, '', 12 * HOUR_IN_SECONDS );
			wp_send_json_error( [ 'message' => __( '查無此統一編號', 'taiwan-store-core' ) ] );
		}

		set_transient( $cache_key, $company, DAY_IN_SECONDS );
		wp_send_json_success( [ 'company' => $company ] );
	}

	private function query_gcis( string $tax_id ): ?string {
		$gcis = $this->fetch_gcis_company( $tax_id );
		if ( ! empty( $gcis ) ) return $gcis;
		$etax = $this->fetch_etax_nonprofit( $tax_id );
		if ( ! empty( $etax ) ) return $etax;
		$g0v = $this->fetch_g0v( $tax_id );
		if ( ! empty( $g0v ) ) return $g0v;
		return '';
	}

	private function fetch_gcis_company( string $tax_id ): ?string {
		$url = add_query_arg( [ '$format' => 'json', '$filter' => 'Business_Accounting_NO eq ' . $tax_id, '$skip' => '0', '$top' => '1' ], 'https://data.gcis.nat.gov.tw/od/data/api/5F64D864-61CB-4D0D-8AD9-492047CC1EA6' );
		$response = wp_remote_get( $url, [ 'timeout' => 8, 'user-agent' => 'taiwan-store-core/' . TAIWAN_STORE_CORE_VERSION ] );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) return null;
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body ) ) return '';
		return (string) ( $body[0]['Company_Name'] ?? '' );
	}

	private function fetch_etax_nonprofit( string $tax_id ): ?string {
		$ua      = 'Mozilla/5.0 (compatible; taiwan-store-core/' . TAIWAN_STORE_CORE_VERSION . ')';
		$base    = 'https://www.etax.nat.gov.tw';
		$step1 = wp_remote_get( $base . '/etwmain/etw113w5/ban/query', [ 'timeout' => 10, 'user-agent' => $ua ] );
		if ( is_wp_error( $step1 ) ) return null;
		$raw_headers = wp_remote_retrieve_headers( $step1 );
		$set_cookies = $raw_headers->getAll( 'set-cookie' ) ?? [];
		$jar = [];
		foreach ( (array) $set_cookies as $sc ) {
			$pair = trim( explode( ';', $sc )[0] );
			if ( $pair ) $jar[] = $pair;
		}
		$cookies = implode( '; ', $jar );
		$step2 = wp_remote_post( $base . '/etwmain/etw113w5/ban/result', [ 'timeout' => 10, 'user-agent' => $ua, 'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded', 'Referer' => $base . '/etwmain/etw113w5/ban/query', 'Cookie' => $cookies ], 'body' => [ 'ban' => $tax_id ] ] );
		if ( is_wp_error( $step2 ) || 200 !== (int) wp_remote_retrieve_response_code( $step2 ) ) return null;
		$body = wp_remote_retrieve_body( $step2 );
		$json = json_decode( $body, true );
		if ( is_array( $json ) ) {
			foreach ( [ '單位名稱', '機構名稱', '名稱', 'name' ] as $key ) {
				if ( ! empty( $json[ $key ] ) ) return (string) $json[ $key ];
			}
		}
		if ( preg_match( '/單位名稱[\s\S]{0,200}?<[^>]+>([^<]{2,80})<\/[^>]+>/u', $body, $m ) ) return trim( $m[1] );
		return '';
	}

	private function fetch_g0v( string $tax_id ): ?string {
		$url = 'https://company.g0v.ronny.tw/api/show?ban=' . rawurlencode( $tax_id );
		$response = wp_remote_get( $url, [ 'timeout' => 8, 'user-agent' => 'taiwan-store-core/' . TAIWAN_STORE_CORE_VERSION ] );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) return null;
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['data'] ) ) return '';
		$data = $body['data'];
		foreach ( [ '公司名稱', '商業名稱', '名稱', '機構名稱' ] as $key ) {
			if ( ! empty( $data[ $key ] ) ) return (string) $data[ $key ];
		}
		return '';
	}
}
