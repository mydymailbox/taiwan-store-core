<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Checkout TW module ??coordinates locale, fields, and validation sub-classes.
 */
class Module implements \Taiwan_Store_Core\Module {

	public function id(): string {
		return 'checkout_tw';
	}

	public function boot(): void {
		( new Locale() )->boot();
		( new Fields() )->boot();
		( new Validation() )->boot();
		( new Order_Meta() )->boot();
		( new Invoice_Export() )->boot();

		// 閮餃??憛?撣?(Blocks) ???舀
		add_action( 'woocommerce_blocks_loaded', [ $this, 'register_blocks_integration' ] );
		
		// 閮餃? Gutenberg 敺?憛?
		add_action( 'init', [ $this, 'register_gutenberg_block' ] );
	}

	public function register_gutenberg_block(): void {
		// ?? block.json ??函???見 WordPress 撠梁???典??啁楊頛臬頛 editorScript
		register_block_type( Taiwan_Store_Core_DIR . 'src/invoice-block' );
	}

	public function register_blocks_integration(): void {
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' ) ) {
			add_action( 'woocommerce_blocks_checkout_block_registration', function( $registry ) {
				require_once __DIR__ . '/class-blocks-integration.php';
				$registry->register( new Blocks_Integration() );
			} );
		}
	}

	public function is_admin_only(): bool {
		return false;
	}
}

