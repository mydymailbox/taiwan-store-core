<?php
/**
 * Uninstall WC TW Core.
 * Runs when the plugin is deleted (not just deactivated) from the WP admin.
 * Removes all Taiwan_Store_Core_* options from the database.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$options = [
	// General
	'Taiwan_Store_Core_debug',
	// Checkout
	'Taiwan_Store_Core_checkout_tax_id_enabled',
	'Taiwan_Store_Core_checkout_tax_id_validate',
	'Taiwan_Store_Core_checkout_taxid_lookup',
	'Taiwan_Store_Core_checkout_postcode_autofill',
	// Order number
	'Taiwan_Store_Core_order_number_enabled',
	'Taiwan_Store_Core_order_number_prefix',
	'Taiwan_Store_Core_order_number_padding',
	// Rules
	'Taiwan_Store_Core_rules_payment',
	'Taiwan_Store_Core_rules_shipping',
	'Taiwan_Store_Core_rules_cart',
];

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
foreach ( $options as $option ) {
	delete_option( $option );
}

// 皜瘥瘚偌??options嚗撘?Taiwan_Store_Core_order_seq_YYYYMMDD嚗?global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall context: no cache needed, direct batch delete required
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wc\_tw\_core\_order\_seq\_%'"
);

