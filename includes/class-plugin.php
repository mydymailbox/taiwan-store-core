<?php
namespace Taiwan_Store_Core; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Plugin bootstrap singleton.
 * Registers the PSR-4-style autoloader and boots all modules.
 */
class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function boot(): void {
		// ?? Autoloader ????????????????????????????????????????????????????????
		spl_autoload_register( function ( string $class ) {
			$prefix = 'Taiwan_Store_Core\\';
			$len    = strlen( $prefix );
			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				return;
			}

			$relative = substr( $class, $len );
			$parts    = explode( '\\', $relative );
			$last     = array_pop( $parts );

			$dir_segments = array_map(
				static fn( string $s ) => strtolower( str_replace( '_', '-', $s ) ),
				$parts
			);

			$file    = strtolower( str_replace( '_', '-', $last ) );
			$dir     = Taiwan_Store_Core_DIR . 'includes'
				. ( $dir_segments ? '/' . implode( '/', $dir_segments ) : '' );
			$class_path     = $dir . '/class-' . $file . '.php';
			$interface_path = $dir . '/interface-' . $file . '.php';

			if ( file_exists( $class_path ) ) {
				require_once $class_path;
			} elseif ( file_exists( $interface_path ) ) {
				require_once $interface_path;
			}
		} );

		// ?? Rule Engine ???????????????????????????????????????????????????????
		$this->register_builtin_rule_components();

		// ?? Modules ???????????????????????????????????????????????????????????
		( new Modules\Checkout_Tw\Module() )->boot();
		( new Modules\Checkout_Tw\Abandoned_Cart() )->boot();
		( new Modules\Checkout_Tw\Order_UI() )->boot();
		( new Modules\Checkout_Tw\Product_UI() )->boot();
		( new Modules\Checkout_Tw\Checkout_Countdown() )->boot();
		( new Modules\Payment_Rules\Module() )->boot();
		( new Modules\Shipping_Rules\Module() )->boot();
		( new Modules\Cart_Rules\Module() )->boot();
		( new Modules\Order_Number_Tw\Module() )->boot();
		( new Modules\Social_Login\Module() )->boot();
		( new Modules\Logs\Module() )->boot();

		// ?? Frontend assets ???????????????????????????????????????????????????
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );

		// ?? Admin ?????????????????????????????????????????????????????????????
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
		add_action( 'admin_notices', [ $this, 'maybe_show_conflict_notice' ] );

		// ?? Text domain ???????????????????????????????????????????????????????
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
		load_plugin_textdomain(
			'taiwan-store-core',
			false,
			dirname( plugin_basename( Taiwan_Store_Core_FILE ) ) . '/languages'
		);
		// en_US is WordPress's default locale and does NOT auto-load .mo files.
		// Force-load our English translation so UI strings are not shown in Chinese
		// when the site/user language is set to any English variant (en_US, en_GB ??.
		if ( ! is_textdomain_loaded( 'taiwan-store-core' ) ) {
			$locale   = determine_locale();
			$mo_file  = Taiwan_Store_Core_DIR . 'languages/taiwan-store-core-' . $locale . '.mo';
			if ( ! file_exists( $mo_file ) ) {
				// Fall back to generic en if no locale-specific file found.
				$lang     = strtok( $locale, '_' );
				$mo_file  = Taiwan_Store_Core_DIR . 'languages/taiwan-store-core-' . $lang . '.mo';
			}
			if ( file_exists( $mo_file ) ) {
				load_textdomain( 'taiwan-store-core', $mo_file );
			}
		}

		// ?? Settings page ?????????????????????????????????????????????????????
		if ( is_admin() ) {
			add_filter( 'woocommerce_get_settings_pages', [ $this, 'register_settings_page' ] );
			$ajax = new Admin\Rules_Ajax();
			$ajax->boot();
			add_action( 'wp_ajax_Taiwan_Store_Core_autosave_settings', [ $this, 'ajax_autosave_settings' ] );
			// 蝯梁楊 / ?砍?迂閮??嚗??CPT ??HPOS ?拍車璅∪?嚗?			add_filter( 'woocommerce_shop_order_search_fields', [ $this, 'add_tax_id_search_fields' ] );
			add_filter( 'woocommerce_order_table_search_query_meta_keys', [ $this, 'add_tax_id_search_fields' ] );
		}
	}

	// ?? Helpers ???????????????????????????????????????????????????????????????

	private function register_builtin_rule_components(): void {
		$engine = Rule_Engine\Rule_Engine::instance();

		$engine->register_condition( new Rule_Engine\Conditions\Cart_Total() );
		$engine->register_condition( new Rule_Engine\Conditions\Product_In_Cart() );
		$engine->register_condition( new Rule_Engine\Conditions\Max_Qty() );
		$engine->register_condition( new Rule_Engine\Conditions\Address() );
		$engine->register_condition( new Rule_Engine\Conditions\Product() );
		$engine->register_condition( new Rule_Engine\Conditions\Category() );
		$engine->register_condition( new Rule_Engine\Conditions\Payment_Method() );
		$engine->register_condition( new Rule_Engine\Conditions\Shipping_Method() );
		$engine->register_condition( new Rule_Engine\Conditions\Address_Mismatch() );
		$engine->register_condition( new Rule_Engine\Conditions\Order_Frequency() );

		$engine->register_action( new Rule_Engine\Actions\Hide_Payment() );
		$engine->register_action( new Rule_Engine\Actions\Hide_Shipping() );
		$engine->register_action( new Rule_Engine\Actions\Block_Checkout() );

		/**
		 * 霈??冽??隞嗅隞亥酉???隞?(Conditions) ??雿?(Actions)??		 *
		 * @param Rule_Engine\Rule_Engine $engine 閬?撘?撖阡?
		 */
		do_action( 'taiwan_store_core_register_rule_components', $engine );
	}

	public function enqueue_styles(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		wp_enqueue_style(
			'taiwan-store-core-checkout',
			Taiwan_Store_Core_URL . 'assets/css/checkout.css',
			[],
			Taiwan_Store_Core_VERSION
		);
	}

	public function enqueue_admin( string $hook_suffix ): void {
		if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
			return;
		}

		// Highlight the Taiwan tab in the WC settings nav (shown on all WC settings pages).
		// Mini Taiwan flag via CSS ::before with SVG background.
		wp_add_inline_style(
			'woocommerce_admin_styles',
			'.wc-tabs li a[href*="tab=tw_core"] {
				background: linear-gradient(135deg, #fef0f0 0%, #fff8f0 100%);
				border-radius: 4px 4px 0 0;
				font-weight: 600;
				color: #c0392b !important;
				border-bottom: 3px solid transparent;
				transition: border-color .2s;
			}
			.wc-tabs li a[href*="tab=tw_core"]::before {
				content: "";
				display: inline-block;
				width: 18px;
				height: 13px;
				margin-right: 5px;
				vertical-align: middle;
				border-radius: 2px;
				background: linear-gradient(to bottom, #000095 50%, #FE0000 50%);
				box-shadow: 0 1px 3px rgba(0,0,0,.3);
				position: relative;
				outline: 1px solid rgba(0,0,0,.1);
			}
			.wc-tabs li.active a[href*="tab=tw_core"] {
				background: #fff;
				border-bottom-color: #c0392b;
				color: #c0392b !important;
			}'
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) ) !== 'tw_core' ) {
			return;
		}

		// Auto-save JS: load on all standard form-based sections.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = sanitize_key( wp_unslash( $_GET['section'] ?? '' ) );
		if ( in_array( $section, [ '', 'checkout', 'social_login', 'tracking' ], true ) ) {
			wp_enqueue_script(
				'taiwan-store-core-settings-autosave',
				Taiwan_Store_Core_URL . 'assets/js/settings-autosave.js',
				[ 'jquery' ],
				Taiwan_Store_Core_VERSION,
				true
			);
			wp_localize_script( 'taiwan-store-core-settings-autosave', 'WCTWSettingsData', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'Taiwan_Store_Core_autosave_settings' ),
				'section' => $section,
			] );
		}

		wp_enqueue_script(
			'taiwan-store-core-rules-admin',
			Taiwan_Store_Core_URL . 'assets/js/rules-admin.js',
			[ 'jquery' ],
			Taiwan_Store_Core_VERSION,
			true
		);
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style(
			'taiwan-store-core-rules-admin',
			Taiwan_Store_Core_URL . 'assets/css/rules-admin.css',
			[ 'dashicons' ],
			Taiwan_Store_Core_VERSION
		);
		wp_localize_script( 'taiwan-store-core-rules-admin', 'wcTwCoreRules', [
			'nonce'   => wp_create_nonce( 'Taiwan_Store_Core_rules' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		] );
	}

	public function maybe_show_conflict_notice(): void {
		// Add any conflicting plugin slug ??name pairs here.
		$candidates = [];
		$conflicting = [];
		foreach ( $candidates as $slug => $name ) {
			if ( is_plugin_active( $slug ) ) {
				$conflicting[] = $name;
			}
		}
		if ( $conflicting ) {
			echo '<div class="notice notice-warning is-dismissible"><p>';
			echo esc_html__( 'WC TW Core ?菜葫?啣?質?蝒?憭?嚗?, 'taiwan-store-core' );
			echo ' ' . esc_html( implode( ', ', $conflicting ) );
			echo '</p></div>';
		}
	}

	// ?? Settings auto-save AJAX ?????????????????????????????????????????????

	public function ajax_autosave_settings(): void {
		ob_start();

		if ( ! check_ajax_referer( 'Taiwan_Store_Core_autosave_nonce', '_autosave_nonce', false ) ) {
			ob_end_clean();
			wp_send_json_error( [ 'msg' => 'nonce_failed' ] );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			ob_end_clean();
			wp_send_json_error( [ 'msg' => 'forbidden' ], 403 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked above
		$section = sanitize_key( $_POST['section'] ?? '' );
		if ( ! $section && isset( $_SERVER['HTTP_REFERER'] ) ) {
			// 摰寥嚗???AJAX 瘝葆 section嚗?靘?蝬脣??岫閫??
			$referer_url = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			$query_str   = wp_parse_url( $referer_url, PHP_URL_QUERY );
			parse_str( (string) $query_str, $query );
			$section = sanitize_key( $query['section'] ?? '' );
		}

		// Field map: id => type. Avoids loading WC_Settings_Page (admin-only class).
		$field_map = [
			'' => [
				'Taiwan_Store_Core_order_number_enabled' => 'checkbox',
				'Taiwan_Store_Core_order_number_prefix'  => 'text',
				'Taiwan_Store_Core_order_number_padding'  => 'number',
				'Taiwan_Store_Core_debug'                => 'checkbox',
			],
			'checkout' => [
				'Taiwan_Store_Core_checkout_tax_id_enabled'     => 'checkbox',
				'Taiwan_Store_Core_checkout_tax_id_validate'    => 'checkbox',
				'Taiwan_Store_Core_checkout_taxid_lookup'       => 'checkbox',
				'Taiwan_Store_Core_checkout_postcode_autofill'  => 'checkbox',
				'Taiwan_Store_Core_checkout_name_consolidate'   => 'checkbox',
			],
			'social_login' => [
				'Taiwan_Store_Core_social_line_enabled'       => 'checkbox',
				'Taiwan_Store_Core_social_line_client_id'     => 'text',
				'Taiwan_Store_Core_social_line_client_secret' => 'text',
				'Taiwan_Store_Core_social_google_enabled'       => 'checkbox',
				'Taiwan_Store_Core_social_google_client_id'     => 'text',
				'Taiwan_Store_Core_social_google_client_secret' => 'text',
				'Taiwan_Store_Core_social_fb_enabled'         => 'checkbox',
				'Taiwan_Store_Core_social_fb_app_id'          => 'text',
				'Taiwan_Store_Core_social_fb_app_secret'      => 'text',
			],
			'tracking' => [
				'wctn_mitake_username' => 'text',
				'wctn_mitake_password' => 'text',
				'wctn_line_token'     => 'text',
				'wctn_admin_line_id'   => 'text',
			],
		];

		$fields = $field_map[ $section ] ?? [];
		foreach ( $fields as $id => $type ) {
			switch ( $type ) {
				case 'checkbox':
					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					update_option( $id, isset( $_POST[ $id ] ) ? 'yes' : 'no' );
					break;
				case 'text':
					if ( isset( $_POST[ $id ] ) ) {
						// phpcs:ignore WordPress.Security.NonceVerification.Missing
						update_option( $id, sanitize_text_field( wp_unslash( $_POST[ $id ] ) ) );
					}
					break;
				case 'number':
					if ( isset( $_POST[ $id ] ) ) {
						// phpcs:ignore WordPress.Security.NonceVerification.Missing
						update_option( $id, absint( $_POST[ $id ] ) );
					}
					break;
			}
		}

		ob_end_clean();
		wp_send_json_success();
	}

	public function register_settings_page( array $pages ): array {
		require_once Taiwan_Store_Core_DIR . 'includes/admin/class-settings-page.php';
		$pages[] = new Admin\Settings_Page();
		return $pages;
	}

	/**
	 * 霈??啗??格?撠??舀隞亦絞銝蝺刻???詨?蝔望?撠??柴?	 * - CPT 璅∪?嚗oocommerce_shop_order_search_fields
	 * - HPOS 璅∪?嚗oocommerce_order_table_search_query_meta_keys
	 *
	 * @param string[] $fields
	 * @return string[]
	 */
	public function add_tax_id_search_fields( array $fields ): array {
		$fields[] = '_wctw/company-tax-id';
		$fields[] = '_wctw/company-title';
		return $fields;
	}
}

