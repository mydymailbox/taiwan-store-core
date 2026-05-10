<?php
namespace Taiwan_Store_Core\Modules\Payment_Rules; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Payment Rules module ??Phase 4.
 * Stub: hooks registered here once rule engine UI is built.
 */
class Module implements \Taiwan_Store_Core\Module {

	public function id(): string {
		return 'payment_rules';
	}

	public function boot(): void {
		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filter_gateways' ], 100 );
	}

	public function is_admin_only(): bool {
		return false;
	}

	public function filter_gateways( array $gateways ): array {
		$engine = \Taiwan_Store_Core\Rule_Engine\Rule_Engine::instance();
		if ( ! $engine->has_rules( 'payment' ) ) {
			return $gateways;
		}
		$ctx = new \Taiwan_Store_Core\Rule_Engine\Context();
		$engine->evaluate( 'payment', $ctx, $gateways );
		return $gateways;
	}
}

