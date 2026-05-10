<?php
namespace Taiwan_Store_Core\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Condition;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Matches on shipping address field values.
 *
 * Config:
 *   ['field' => 'country'|'state', 'op' => 'in'|'not_in', 'values' => string[]]
 *
 * For Taiwan states use ISO 3166-2:TW codes:
 *   TPE, NWT, TAO, TXG, TNN, KHH, KEE, HSZ, HSQ, MIA, CHA, NAN,
 *   YUN, CYI, CYQ, IUH, TTT, HUA, ILA, PEN, KIN, LIE
 */
class Address implements Condition {

	public function id(): string {
		return 'address';
	}

	public function matches( Context $ctx, array $config ): bool {
		$field  = (string) ( $config['field'] ?? 'country' );
		$op     = (string) ( $config['op'] ?? 'in' );
		$values = (array) ( $config['values'] ?? [] );

		$actual  = 'state' === $field ? $ctx->shipping_state() : $ctx->shipping_country();
		$in_list = in_array( $actual, $values, true );

		return 'not_in' === $op ? ! $in_list : $in_list;
	}
}

