<?php
namespace Taiwan_Store_Core; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

interface Module {

	/**
	 * Stable identifier, e.g. 'checkout_tw'.
	 */
	public function id(): string;

	/**
	 * Register hooks. Called once by the plugin bootstrap.
	 */
	public function boot(): void;

	/**
	 * Whether this module should only be loaded in wp-admin context.
	 */
	public function is_admin_only(): bool;
}

