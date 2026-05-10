<?php
namespace Taiwan_Store_Core\Rule_Engine\Actions; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

use Taiwan_Store_Core\Rule_Engine\Action;
use Taiwan_Store_Core\Rule_Engine\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a notice to the cart payload, preventing checkout.
 * Used on woocommerce_check_cart_items (cart rules).
 *
 * Config:
 *   ['message' => string]  ??error message to display
 *
 * Payload shape (cart hook):
 *   ['notices' => string[]]
 *
 * The caller (Cart_Rules\Module::check_cart_items) reads $payload['notices']
 * and calls wc_add_notice() for each entry.
 */
class Block_Checkout implements Action {

	public function id(): string {
		return 'block_checkout';
	}

	public function execute( Context $ctx, array $config, array &$payload ): void {
		$message = (string) ( $config['message'] ?? '' );
		if ( '' !== $message ) {
			$payload['notices'][] = $message;
		}
	}
}

