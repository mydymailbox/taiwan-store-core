<?php
namespace Taiwan_Store_Core\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Condition;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Checks if any single product's quantity in cart exceeds a cap.
 *
 * Config:
 *   ['max' => int, 'products' => int[]]
 *   - max: maximum allowed quantity per product line item
 *   - products: if non-empty, only these product IDs are checked; empty = all products
 */
class Max_Qty implements Condition {

	public function id(): string {
		return 'max_qty';
	}

	public function matches( Context $ctx, array $config ): bool {
		$max      = (int) ( $config['max'] ?? 1 );
		$limit_to = array_map( 'intval', (array) ( $config['products'] ?? [] ) );

		$cart = $ctx->cart();
		if ( ! $cart ) {
			return false;
		}

		foreach ( $cart->get_cart() as $item ) {
			$pid = (int) ( $item['product_id'] ?? 0 );
			$qty = (int) ( $item['quantity'] ?? 0 );

			if ( $limit_to && ! in_array( $pid, $limit_to, true ) ) {
				continue;
			}

			if ( $qty > $max ) {
				return true;  // Condition matches = rule fires.
			}
		}

		// Also check the product currently being added.
		$adding     = $ctx->adding_product_id();
		$adding_qty = $ctx->adding_product_qty();
		if ( $adding > 0 ) {
			if ( $limit_to && ! in_array( $adding, $limit_to, true ) ) {
				return false;
			}
			// Total qty would be existing qty + incoming qty.
			$existing = 0;
			foreach ( $cart->get_cart() as $item ) {
				if ( (int) $item['product_id'] === $adding ) {
					$existing += (int) $item['quantity'];
				}
			}
			return ( $existing + $adding_qty ) > $max;
		}

		return false;
	}
}

