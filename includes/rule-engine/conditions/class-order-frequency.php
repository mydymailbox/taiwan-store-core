<?php
namespace Taiwan_Store_Core\Rule_Engine\Conditions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Condition;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * ?脤?銴??殷?瑼Ｘ?桀?憿批恥嚗??餃撣唾???billing email嚗????閬??抒?閮?詻? *
 * Config:
 *   ['hours'     => int 閬?撠???(default 24)
 *    'op'        => 'gte'|'gt'|'lte'|'lt'|'eq' (default 'gte')
 *    'count'     => int ?瑼?(default 2)
 *    'statuses'  => string[] 閮????身 ['pending','processing','on-hold','completed']
 *   ]
 *
 * 瘜冽?嚗??芸?敺?email嚗?餃銝??芸‵銵剁???? false 銝孛?潦? */
class Order_Frequency implements Condition {

	public function id(): string {
		return 'order_frequency';
	}

	public function matches( Context $ctx, array $config ): bool {
		$hours = max( 1, (int) ( $config['hours'] ?? 24 ) );
		$op    = (string) ( $config['op'] ?? 'gte' );
		$count = (int) ( $config['count'] ?? 2 );
		$statuses = (array) ( $config['statuses'] ?? [ 'pending', 'processing', 'on-hold', 'completed' ] );

		$email = $this->resolve_email();
		if ( '' === $email ) {
			return false;
		}

		$since = gmdate( 'Y-m-d H:i:s', time() - ( $hours * HOUR_IN_SECONDS ) );

		$orders = wc_get_orders( [
			'limit'        => 50,
			'billing_email' => $email,
			'date_created' => '>=' . $since,
			'status'       => $statuses,
			'return'       => 'ids',
		] );
		$actual = is_array( $orders ) ? count( $orders ) : 0;

		switch ( $op ) {
			case 'gt':  return $actual >  $count;
			case 'lte': return $actual <= $count;
			case 'lt':  return $actual <  $count;
			case 'eq':  return $actual === $count;
			case 'gte':
			default:    return $actual >= $count;
		}
	}

	private function resolve_email(): string {
		if ( is_user_logged_in() ) {
			$u = wp_get_current_user();
			if ( $u && $u->user_email ) {
				return strtolower( $u->user_email );
			}
		}
		// Posted billing email (during checkout submit)
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted = isset( $_POST['billing_email'] ) ? sanitize_email( wp_unslash( $_POST['billing_email'] ) ) : '';
		if ( $posted ) {
			return strtolower( $posted );
		}
		// Customer session
		if ( function_exists( 'WC' ) && WC()->customer ) {
			$email = (string) WC()->customer->get_billing_email();
			if ( $email ) {
				return strtolower( $email );
			}
		}
		return '';
	}
}

