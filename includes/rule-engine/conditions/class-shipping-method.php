<?php
namespace Taiwan_Store_Core\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Condition;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Matches on the customer's chosen shipping method(s).
 * Supports partial matching: 'flat_rate' matches 'flat_rate:1'.
 *
 * Config:
 *   ['op' => 'in'|'not_in', 'methods' => string[]]
 *   Methods can be full rate IDs (e.g. 'flat_rate:1') or instance-agnostic
 *   method IDs (e.g. 'flat_rate').
 */
class Shipping_Method implements Condition {

	public function id(): string {
		return 'shipping_method';
	}

	public function matches( Context $ctx, array $config ): bool {
		$methods = (array) ( $config['methods'] ?? [] );
		$op      = (string) ( $config['op'] ?? 'in' );
		$chosen  = $ctx->chosen_shipping_methods();

		$has_match = false;
		foreach ( $methods as $method ) {
			foreach ( $chosen as $chosen_method ) {
				// Full match or prefix match (method_id vs method_id:instance_id).
				if (
					$chosen_method === $method
					|| 0 === strpos( $chosen_method, $method . ':' )
				) {
					$has_match = true;
					break 2;
				}
			}
		}

		return 'not_in' === $op ? ! $has_match : $has_match;
	}
}

