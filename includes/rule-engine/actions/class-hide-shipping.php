<?php
namespace Taiwan_Store_Core\Rule_Engine\Actions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Action;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Removes specified shipping rates from the available rates array.
 * Used on the woocommerce_package_rates filter.
 *
 * Config:
 *   ['methods' => string[]]  ??list of rate IDs to hide (e.g. ['flat_rate:1', 'free_shipping:1'])
 */
class Hide_Shipping implements Action {

	public function id(): string {
		return 'hide_shipping';
	}

	public function execute( Context $ctx, array $config, array &$payload ): void {
		$methods = (array) ( $config['methods'] ?? [] );
		foreach ( $methods as $rate_id ) {
			unset( $payload[ $rate_id ] );
		}
	}
}

