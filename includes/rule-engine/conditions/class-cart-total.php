<?php
namespace Taiwan_Store_Core\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Condition;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Compares the cart subtotal against a threshold.
 *
 * Config:
 *   ['op' => 'gte'|'lte'|'gt'|'lt'|'eq'|'>='|'<='|'>'|'<'|'=', 'amount' => float]
 *   Defaults to op = 'gte' when omitted.
 */
class Cart_Total implements Condition {

	/** @var array<string,string> Symbol ??word aliases. */
	private const ALIASES = [
		'>=' => 'gte',
		'<=' => 'lte',
		'>'  => 'gt',
		'<'  => 'lt',
		'='  => 'eq',
	];

	public function id(): string {
		return 'cart_total';
	}

	public function matches( Context $ctx, array $config ): bool {
		$raw_op = (string) ( $config['op'] ?? 'gte' );
		$op     = self::ALIASES[ $raw_op ] ?? $raw_op;
		$amount = (float) ( $config['amount'] ?? 0 );
		$total  = $ctx->cart_total();

		switch ( $op ) {
			case 'gte':
				return $total >= $amount;
			case 'lte':
				return $total <= $amount;
			case 'gt':
				return $total > $amount;
			case 'lt':
				return $total < $amount;
			case 'eq':
				return abs( $total - $amount ) < 0.001;
			default:
				return $total >= $amount; // Default: gte.
		}
	}
}

