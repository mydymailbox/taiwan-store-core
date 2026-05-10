<?php
namespace Taiwan_Store_Core\Helpers; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Nonce helpers for WC TW Core admin actions.
 */
class Nonce {

	/**
	 * Verify a nonce and die on failure.
	 * Use on all admin POST / AJAX handlers.
	 *
	 * @param string $action  Nonce action name.
	 * @param string $field   $_POST field name. Default '_wpnonce'.
	 */
	public static function verify_or_die( string $action, string $field = '_wpnonce' ): void {
		$nonce = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( esc_html__( '摰?折?霅仃??隢??唳???岫??, 'taiwan-store-core' ), 403 );
		}
	}

	/**
	 * Output a nonce field. Use inside admin forms.
	 */
	public static function field( string $action ): void {
		wp_nonce_field( $action );
	}
}

