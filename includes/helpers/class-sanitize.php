<?php
namespace Taiwan_Store_Core\Helpers; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Input sanitization helpers for WC TW Core.
 * Always validate at system boundaries; use the right escaping function for each context.
 */
class Sanitize {

	/**
	 * Sanitize a plain text string (no HTML).
	 */
	public static function text( string $value ): string {
		return sanitize_text_field( wp_unslash( $value ) );
	}

	/**
	 * Sanitize an 8-digit Taiwan business number.
	 */
	public static function tax_id( string $value ): string {
		return preg_replace( '/[^\d]/', '', wp_unslash( $value ) );
	}

	/**
	 * Sanitize a key / slug (alphanumeric + underscore + hyphen).
	 */
	public static function key( string $value ): string {
		return sanitize_key( wp_unslash( $value ) );
	}

	/**
	 * Sanitize a positive integer (e.g. product/post ID).
	 */
	public static function absint( $value ): int {
		return absint( $value );
	}
}

