<?php
namespace Taiwan_Store_Core\Admin;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-rules-ui.php';

/**
 * WooCommerce Settings - Taiwan Store Localized tab.
 */
class Settings_Page extends \WC_Settings_Page {

	public function __construct() {
		$this->id    = 'tw_core';
		$this->label = __( '台灣在地化', 'taiwan-store-core' );

		add_action( 'admin_head', [ $this, 'inject_settings_styles' ] );

		// Localize SweetAlert2
		add_action( 'admin_enqueue_scripts', function() {
			$screen = get_current_screen();
			$tab    = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
			if ( $screen && strpos( $screen->id, 'wc-settings' ) !== false && 'tw_core' === $tab ) {
				wp_enqueue_script( 'sweetalert2', TAIWAN_STORE_CORE_URL . 'assets/vendor/sweetalert2.all.min.js', [], '11.14.1', true );
			}
		} );

		parent::__construct();
	}

	public function inject_settings_styles(): void {
		$screen = get_current_screen();
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		if ( ! $screen || strpos( $screen->id, 'wc-settings' ) === false ) return;
		if ( 'tw_core' !== $tab ) return;
		?>
		<style>
			.woocommerce .form-table {
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 12px;
				padding: 20px;
				box-shadow: 0 4px 15px rgba(0,0,0,0.03);
				margin-top: 20px;
			}
			.woocommerce h2 { 
				font-size: 20px; 
				font-weight: 700; 
				color: #1d2327; 
				margin-top: 30px;
				border-left: 5px solid #2271b1;
				padding-left: 15px;
			}
			.woocommerce .section-description {
				background: #f0f6fc;
				color: #1d2327;
				padding: 12px 20px;
				border-radius: 8px;
				border-left: 4px solid #2271b1;
				margin-bottom: 20px;
				font-size: 14px;
				line-height: 1.6;
			}
			.form-table th { 
				width: 220px; 
				font-weight: 600; 
				color: #3c434a; 
				padding: 25px 20px !important;
			}
			.form-table td { padding: 20px !important; }
			.form-table input[type="text"], .form-table input[type="password"], .form-table input[type="number"], .form-table select, .form-table textarea {
				border-radius: 6px !important;
				border: 1px solid #8c8f94 !important;
				padding: 8px 12px !important;
				min-width: 300px;
			}
		</style>
		<?php
	}

	public function get_sections(): array {
		$sections = [
			''               => __( '說明', 'taiwan-store-core' ),
			'general'        => __( '一般', 'taiwan-store-core' ),
			'checkout'       => __( '結帳設定', 'taiwan-store-core' ),
			'social_login'   => __( '社群登入', 'taiwan-store-core' ),
			'payment_rules'  => __( '付款規則', 'taiwan-store-core' ),
			'shipping_rules' => __( '運費規則', 'taiwan-store-core' ),
			'cart_rules'     => __( '購物車規則', 'taiwan-store-core' ),
			'logs'           => __( '日誌', 'taiwan-store-core' ),
		];
		return apply_filters( 'taiwan_store_core_settings_sections', $sections );
	}

	public function get_settings( string $section = '' ): array {
		$settings = [];
		switch ( $section ) {
			case 'general':
				$settings = $this->get_general_settings();
				break;
			case 'checkout':
				$settings = $this->get_checkout_settings();
				break;
			case '':
				$settings = $this->get_general_settings();
				break;
		}
		return apply_filters( 'woocommerce_get_settings_tw_core', $settings, $section );
	}

	private function get_general_settings(): array {
		return [
			[ 'title' => __( '自訂訂單編號', 'taiwan-store-core' ), 'type' => 'title', 'id' => 'taiwan_store_core_order_number_options' ],
			[ 'title' => __( '啟用自訂訂單編號', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_order_number_enabled', 'default' => 'no', 'type' => 'checkbox' ],
			[ 'title' => __( '前綴', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_order_number_prefix', 'default' => '', 'type' => 'text', 'placeholder' => 'TW' ],
			[ 'type' => 'sectionend', 'id' => 'taiwan_store_core_order_number_options' ],
			[ 'title' => __( '日誌設定', 'taiwan-store-core' ), 'type' => 'title', 'id' => 'taiwan_store_core_general_options' ],
			[ 'title' => __( '啟用 Debug 日誌', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_debug', 'default' => 'no', 'type' => 'checkbox' ],
			[ 'type' => 'sectionend', 'id' => 'taiwan_store_core_general_options' ],
		];
	}

	private function get_checkout_settings(): array {
		return [
			[ 'title' => __( '結帳欄位設定', 'taiwan-store-core' ), 'type' => 'title', 'id' => 'taiwan_store_core_checkout_options' ],
			[ 'title' => __( '顯示統編欄位', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_checkout_tax_id_enabled', 'default' => 'yes', 'type' => 'checkbox' ],
			[ 'title' => __( '郵遞區號自動填入', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_checkout_postcode_autofill', 'default' => 'yes', 'type' => 'checkbox' ],
			[ 'type' => 'sectionend', 'id' => 'taiwan_store_core_checkout_options' ],
		];
	}

	public function output(): void {
		$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
		if ( has_action( "taiwan_store_core_settings_output_{$section}" ) ) {
			do_action( "taiwan_store_core_settings_output_{$section}" );
			return;
		}
		switch ( $section ) {
			case '':
			case 'about':
				$this->output_about();
				break;
			case 'payment_rules':
				Rules_UI::render( 'payment' );
				break;
			case 'shipping_rules':
				Rules_UI::render( 'shipping' );
				break;
			case 'cart_rules':
				Rules_UI::render( 'cart' );
				break;
			case 'logs':
				$this->output_logs();
				break;
			default:
				do_action( "taiwan_store_core_settings_before_output_{$section}" );
				parent::output();
				break;
		}
	}

	private function output_about(): void {
		$version = TAIWAN_STORE_CORE_VERSION;
		?>
		<style>
			.wc-tw-about { max-width: 1080px; font-size: 15px; line-height: 1.7; margin-top: 24px; color: #1d2327; padding-bottom: 80px; }
			.wc-tw-about ~ .submit, .wc-tw-about .submit { display: none !important; }
			.wc-tw-hero { background: linear-gradient(135deg, #1e3a5f 0%, #2271b1 100%); border-radius: 16px; padding: 40px 48px; margin-bottom: 40px; color: #fff; display: flex; align-items: center; gap: 32px; }
			.wc-tw-hero-icon { width: 80px; height: 80px; background: rgba(255,255,255,.15); border-radius: 20px; display: flex; align-items: center; justify-content: center; }
			.wc-tw-hero-icon .dashicons { font-size: 48px; width: 48px; height: 48px; color: #fff; }
			.wc-tw-hero-text h1 { margin: 0 0 8px; font-size: 2em; font-weight: 800; color: #fff; }
			.wc-tw-hero-text p { margin: 0; font-size: 1.1em; color: rgba(255,255,255,.85); }
			.wc-tw-hero-badge { background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.25); border-radius: 6px; padding: 4px 12px; font-size: 0.85em; color: #fff; }
			.wc-tw-section-title { font-size: 1.35em; font-weight: 800; color: #1d2327; margin: 48px 0 24px; padding-bottom: 14px; border-bottom: 2px solid #e0e0e0; display: flex; align-items: center; gap: 12px; }
			.wc-tw-feature-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
			.wc-tw-card { background: #fff; border: 1px solid #dcdcde; border-radius: 14px; padding: 28px; position: relative; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.3s; }
			.wc-tw-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); }
			.wc-tw-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: var(--card-accent, #2271b1); }
			.card-blue { --card-accent: #2271b1; }
			.card-green { --card-accent: #00a32a; }
			.status-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; margin-left: auto; }
			.status-badge.active { background: #e6f4ea; color: #008a20; }
			.status-badge.missing { background: #f0f0f1; color: #646970; }
		</style>
		<div class="wc-tw-about">
			<div class="wc-tw-hero">
				<div class="wc-tw-hero-icon"><span class="dashicons dashicons-admin-site-alt3"></span></div>
				<div class="wc-tw-hero-text">
					<h1><?php esc_html_e( 'Taiwan Store Core', 'taiwan-store-core' ); ?></h1>
					<p><?php esc_html_e( '最完整的台灣在地化解決方案 — 結帳優化 × 智慧規則引擎 × 專業訂單管理', 'taiwan-store-core' ); ?></p>
					<div class="wc-tw-hero-badges">
						<span class="wc-tw-hero-badge"><?php /* translators: %s: version */ printf( esc_html__( '版本 v%s', 'taiwan-store-core' ), esc_html( $version ) ); ?></span>
						<span class="wc-tw-hero-badge">HPOS 完整支援</span>
					</div>
				</div>
			</div>
			<p class="wc-tw-section-title"><span class="dashicons dashicons-admin-plugins"></span> <?php esc_html_e( '核心內建功能 (Core Features)', 'taiwan-store-core' ); ?></p>
			<div class="wc-tw-feature-grid">
				<div class="wc-tw-card card-blue">
					<h3><?php esc_html_e( '台灣結帳在地化', 'taiwan-store-core' ); ?></h3>
					<p><?php esc_html_e( '專為台灣消費者設計的結帳流程，大幅降低結帳阻力。', 'taiwan-store-core' ); ?></p>
					<ul>
						<li><?php esc_html_e( '縣市 / 鄉鎮區連動下拉選單', 'taiwan-store-core' ); ?></li>
						<li><?php esc_html_e( '自動填入郵遞區號 (3+2 碼)', 'taiwan-store-core' ); ?></li>
						<li><?php esc_html_e( '統一編號智慧查詢與抬頭自動帶入', 'taiwan-store-core' ); ?></li>
					</ul>
				</div>
				<div class="wc-tw-card card-green">
					<h3><?php esc_html_e( '付款規則引擎', 'taiwan-store-core' ); ?></h3>
					<p><?php esc_html_e( '根據訂單條件動態過濾付款方式，保護您的利潤。', 'taiwan-store-core' ); ?></p>
					<ul>
						<li><?php esc_html_e( '依訂單總額隱藏貨到付款', 'taiwan-store-core' ); ?></li>
						<li><?php esc_html_e( '限制特定商品分類僅限信用卡', 'taiwan-store-core' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	private function output_logs(): void {
		$log_dir  = trailingslashit( WC_LOG_DIR );
		$log_file = $log_dir . 'taiwan-store-core-' . sanitize_file_name( wp_date( 'Y-m-d' ) ) . '-*.log';
		$files    = glob( $log_file ) ?: [];
		if ( ! $files ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( '今日尚無日誌記錄。', 'taiwan-store-core' ) . '</p></div>';
			return;
		}
		$latest = end( $files );
		echo '<h2>' . esc_html__( '系統運行日誌', 'taiwan-store-core' ) . '</h2>';
		echo '<pre style="background:#fff; padding:15px; border:1px solid #ccc; max-height:500px; overflow:auto;">' . esc_html( file_get_contents( $latest ) ) . '</pre>';
	}
}
