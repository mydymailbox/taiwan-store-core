<?php
namespace Taiwan_Store_Core\Rule_Engine\Actions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Action;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Removes specified payment gateways from the available gateways array.
 * Used on the woocommerce_available_payment_gateways filter.
 *
 * Config:
 *   ['gateways' => string[]]  ??list of gateway IDs to hide (e.g. ['cod', 'bacs'])
 */
class Hide_Payment implements Action {

	public function id(): string {
		return 'hide_payment';
	}

	public function execute( Context $ctx, array $config, array &$payload ): void {
		$gateways = (array) ( $config['gateways'] ?? [] );
		foreach ( $gateways as $id ) {
			unset( $payload[ $id ] );
		}
	}
}

