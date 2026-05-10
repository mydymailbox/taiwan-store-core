<?php
namespace Taiwan_Store_Core\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Condition;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Matches on the customer's currently selected payment method.
 *
 * Config:
 *   ['op' => 'in'|'not_in', 'methods' => string[]]
 *   Methods are gateway IDs (e.g. 'cod', 'bacs', 'paypal').
 */
class Payment_Method implements Condition {

	public function id(): string {
		return 'payment_method';
	}

	public function matches( Context $ctx, array $config ): bool {
		$methods = (array) ( $config['methods'] ?? [] );
		$op      = (string) ( $config['op'] ?? 'in' );

		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return false;
		}

		$chosen = (string) WC()->session->get( 'chosen_payment_method', '' );
		if ( '' === $chosen ) {
			$chosen = (string) get_option( 'woocommerce_default_gateway', '' );
		}

		$in_list = in_array( $chosen, $methods, true );

		return 'not_in' === $op ? ! $in_list : $in_list;
	}
}

