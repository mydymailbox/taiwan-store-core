<?php
namespace Taiwan_Store_Core\Modules\Cart_Rules; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Cart Rules module ??Phase 6.
 * Stub: hooks registered here once rule engine UI is built.
 */
class Module implements \Taiwan_Store_Core\Module {

	public function id(): string {
		return 'cart_rules';
	}

	public function boot(): void {
		add_action( 'woocommerce_check_cart_items', [ $this, 'check_cart_items' ] );
		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_add_to_cart' ], 10, 5 );
		
		// ?憛?撣?(Store API) 撠?頃?抵?撽?
		add_action( 'woocommerce_store_api_cart_errors', [ $this, 'validate_cart_blocks' ] );
	}

	public function is_admin_only(): bool {
		return false;
	}

	public function check_cart_items(): void {
		$engine = \Taiwan_Store_Core\Rule_Engine\Rule_Engine::instance();
		if ( ! $engine->has_rules( 'cart' ) ) {
			return;
		}
		$ctx     = new \Taiwan_Store_Core\Rule_Engine\Context();
		$notices = [];
		$engine->evaluate( 'cart', $ctx, $notices );
		foreach ( $notices as $msg ) {
			wc_add_notice( esc_html( $msg ), 'error' );
		}
	}

	public function validate_cart_blocks( \WP_Error $errors ): void {
		$engine = \Taiwan_Store_Core\Rule_Engine\Rule_Engine::instance();
		if ( ! $engine->has_rules( 'cart' ) ) {
			return;
		}
		$ctx     = new \Taiwan_Store_Core\Rule_Engine\Context();
		$notices = [];
		$engine->evaluate( 'cart', $ctx, $notices );
		foreach ( $notices as $index => $msg ) {
			// ?典?憛?撣喃葉嚗??????航炊閮撖怠 WP_Error ?拐辣嚗?蝡舀????箇?摨隤斗?銝阡甇Ｙ?撣?			$errors->add( 'wctw_cart_rule_error_' . $index, esc_html( $msg ) );
		}
	}

	public function validate_add_to_cart( bool $passed, int $product_id, int $qty, int $variation_id = 0, array $variations = [] ): bool {
		$engine = \Taiwan_Store_Core\Rule_Engine\Rule_Engine::instance();
		if ( ! $engine->has_rules( 'cart' ) ) {
			return $passed;
		}
		$ctx = new \Taiwan_Store_Core\Rule_Engine\Context();
		$ctx->set_adding_product( $product_id, $qty );
		$notices = [];
		$engine->evaluate( 'cart', $ctx, $notices );
		foreach ( $notices as $msg ) {
			wc_add_notice( esc_html( $msg ), 'error' );
			$passed = false;
		}
		return $passed;
	}
}

