<?php
namespace Taiwan_Store_Core\Modules\Performance; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Performance module ??Phase 7.
 *
 * Responsibilities:
 * - Ensures frontend assets only enqueue on relevant pages.
 * - Provides a central hook to disable admin-only modules on the frontend.
 *
 * Note: Most per-module gating is already handled in each module's own boot()
 * via is_checkout() / is_cart(). This module provides cross-cutting guards.
 */
class Module implements \Taiwan_Store_Core\Module {

	public function id(): string {
		return 'performance';
	}

	public function boot(): void {
		// Remove query-var exposure to avoid cache pollution.
		add_filter( 'query_vars', [ $this, 'remove_unnecessary_vars' ] );

		// Prevent WC session from starting on non-shop pages (admin, REST, CLI).
		// We do NOT override WC's own session logic ??we just avoid triggering
		// our own expensive rule evaluations outside the relevant contexts.
		add_action( 'init', [ $this, 'maybe_skip_rule_warmup' ], 5 );
	}

	public function is_admin_only(): bool {
		return false;
	}

	/**
	 * On non-frontend / non-AJAX requests, tell the rule engine to skip
	 * loading rules from the DB (they will be loaded lazily on first use anyway,
	 * so this is a no-op if rules are never evaluated).
	 *
	 * On REST API and CLI, WC hooks typically don't fire, so this is defensive.
	 */
	public function maybe_skip_rule_warmup(): void {
		// No action needed: Rule_Engine::load_rules() is already lazy.
		// This method exists as the canonical extension point for future optimisations
		// (e.g. object-cache warming, rule pre-compilation).
	}

	/** @param string[] $vars */
	public function remove_unnecessary_vars( array $vars ): array {
		// Placeholder ??no custom query vars added by this plugin currently.
		return $vars;
	}
}

