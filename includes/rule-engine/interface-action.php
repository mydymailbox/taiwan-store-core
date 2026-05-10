<?php
namespace Taiwan_Store_Core\Rule_Engine; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Action interface.
 * All actions must be registered with Rule_Engine::register_action().
 */
interface Action {

	/**
	 * Stable machine-readable ID (e.g. 'hide_payment').
	 */
	public function id(): string;

	/**
	 * Execute the action by mutating $payload.
	 *
	 * The shape of $payload depends on the hook:
	 *   payment  ??associative array of gateway objects (key = gateway id)
	 *   shipping ??associative array of WC_Shipping_Rate objects (key = rate id)
	 *   cart     ??['notices' => string[]]
	 *
	 * @param Context $ctx     Lazy-memoized request context.
	 * @param array   $config  Sanitized action configuration from the rule.
	 * @param array   $payload Mutable payload passed by reference.
	 */
	public function execute( Context $ctx, array $config, array &$payload ): void;
}

