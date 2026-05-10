<?php
/**
 * Plugin Name: Taiwan Store Core
 * Plugin URI:  https://github.com/taiwanstore/taiwan-store-core
 * Description: All-in-one localization solution for WooCommerce in Taiwan. Includes Social Login (LINE/Google/FB), Smart Checkout Fields (Tax ID lookup, Mobile barcode, address cascading), CVS Map integration, Checkout Countdown, and more.
 * Version:           1.0.4
 * Author:            Antigravity AI
 * Author URI:        https://github.com/antigravity
 * License:           GPL-2.0-or-later
 * Text Domain:       taiwan-store-core
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * WC requires at least: 8.0
 * WC tested up to:      9.0
 */

defined( 'ABSPATH' ) || exit;

define( 'TAIWAN_STORE_CORE_VERSION', '1.0.4' );
define( 'TAIWAN_STORE_CORE_FILE',    __FILE__ );
define( 'TAIWAN_STORE_CORE_DIR',     plugin_dir_path( __FILE__ ) );
define( 'TAIWAN_STORE_CORE_URL',     plugin_dir_url( __FILE__ ) );

// HPOS (High-Performance Order Storage) 相容宣告
add_action( 'before_woocommerce_init', static function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
} );

// 在 WooCommerce 載入後啟動外掛
add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', static function () {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Taiwan Store Core requires WooCommerce to be installed and active.', 'taiwan-store-core' ); ?></p>
			</div>
			<?php
		} );
		return;
	}

	require_once TAIWAN_STORE_CORE_DIR . 'includes/class-plugin.php';
	\Taiwan_Store_Core\Plugin::instance()->boot();
}, 20 );
