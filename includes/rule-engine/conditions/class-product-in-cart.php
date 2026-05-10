<?php
namespace Taiwan_Store_Core\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Condition;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Checks whether specific products exist in the current cart
 * (including any product being added, for add-to-cart validation).
 *
 * Use this to build forbidden-combo rules:
 * - Condition: product_in_cart (products A, B)   ??"ALL of these are in cart"
 * - Action: block_checkout (message)
 *
 * Config:
 *   ['op' => 'all' | 'any', 'products' => int[]]
 *   - 'all': every listed product must be in cart
 *   - 'any': at least one listed product is in cart
 */
class Product_In_Cart implements Condition {

	public function id(): string {
		return 'product_in_cart';
	}

	public function matches( Context $ctx, array $config ): bool {
		$required = array_map( 'intval', (array) ( $config['products'] ?? [] ) );
		if ( ! $required ) {
			return false;
		}

		$op      = (string) ( $config['op'] ?? 'all' );
		$in_cart = $ctx->product_ids();

		if ( 'any' === $op ) {
			return (bool) array_intersect( $required, $in_cart );
		}

		// 'all': every required product must be present.
		foreach ( $required as $pid ) {
			if ( ! in_array( $pid, $in_cart, true ) ) {
				return false;
			}
		}
		return true;
	}
}

