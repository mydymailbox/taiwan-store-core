<?php
namespace Taiwan_Store_Core\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Condition;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Checks whether specific products are (or are not) in the cart.
 *
 * Config:
 *   ['op' => 'in'|'not_in', 'products' => int[]]
 *   - 'in'     : at least one of the listed product IDs is in cart
 *   - 'not_in' : none of the listed product IDs is in cart
 */
class Product implements Condition {

	public function id(): string {
		return 'product';
	}

	public function matches( Context $ctx, array $config ): bool {
		$required = array_map( 'intval', (array) ( $config['products'] ?? [] ) );
		if ( ! $required ) {
			return false;
		}

		$op      = (string) ( $config['op'] ?? 'in' );
		$in_cart = $ctx->product_ids();
		$has     = (bool) array_intersect( $required, $in_cart );

		return 'not_in' === $op ? ! $has : $has;
	}
}

