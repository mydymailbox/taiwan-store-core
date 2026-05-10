<?php
namespace Taiwan_Store_Core\Modules\Shipping_Rules; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Shipping Rules module ??Phase 5.
 * Stub: hooks registered here once rule engine UI is built.
 */
class Module implements \Taiwan_Store_Core\Module {

	public function id(): string {
		return 'shipping_rules';
	}

	public function boot(): void {
		add_filter( 'woocommerce_package_rates', [ $this, 'filter_rates' ], 100, 2 );
	}

	public function is_admin_only(): bool {
		return false;
	}

	public function filter_rates( array $rates, array $package ): array {
		$engine = \Taiwan_Store_Core\Rule_Engine\Rule_Engine::instance();
		if ( ! $engine->has_rules( 'shipping' ) ) {
			return $rates;
		}
		$ctx = new \Taiwan_Store_Core\Rule_Engine\Context();
		$ctx->set_package( $package );
		$engine->evaluate( 'shipping', $ctx, $rates );
		return $rates;
	}
}

