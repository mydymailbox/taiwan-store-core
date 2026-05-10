<?php
namespace Taiwan_Store_Core\Rule_Engine; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Condition interface.
 * All conditions must be registered with Rule_Engine::register_condition().
 */
interface Condition {

	/**
	 * Stable machine-readable ID (e.g. 'cart_total').
	 */
	public function id(): string;

	/**
	 * Returns true when the condition is satisfied in the given context.
	 *
	 * @param Context $ctx    Lazy-memoized request context.
	 * @param array   $config Sanitized condition configuration from the rule.
	 */
	public function matches( Context $ctx, array $config ): bool;
}

