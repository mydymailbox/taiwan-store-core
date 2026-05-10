<?php
namespace Taiwan_Store_Core\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Condition;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * 撖??董?桀?銝??湔炎?伐??脰??剁??? *
 * Config:
 *   ['compare' => 'country' | 'state']
 *   ?身 'country'?? *
 * ?仿“摰Ｗ??芸‵撖怠???嚗??箔?閫貊嚗?鞈潛頠?畾菔炊?歹??? */
class Address_Mismatch implements Condition {

	public function id(): string {
		return 'address_mismatch';
	}

	public function matches( Context $ctx, array $config ): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
			return false;
		}
		$compare = ( $config['compare'] ?? 'country' ) === 'state' ? 'state' : 'country';

		if ( 'country' === $compare ) {
			$b = (string) WC()->customer->get_billing_country();
			$s = (string) WC()->customer->get_shipping_country();
		} else {
			$b = (string) WC()->customer->get_billing_state();
			$s = (string) WC()->customer->get_shipping_state();
		}

		if ( '' === $b || '' === $s ) {
			return false;
		}
		return $b !== $s;
	}
}

