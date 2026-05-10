<?php
namespace Taiwan_Store_Core\Rule_Engine; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Lazy-memoized request context.
 *
 * All methods read from WooCommerce globals on first call, then cache the result.
 * In unit tests, inject values via ReflectionProperty on $cache.
 */
class Context {

	/** @var array<string,mixed> */
	private array $cache = [];

	/** @var array<string,mixed>|null Package data for shipping filter context. */
	private ?array $package = null;

	// ?? Setters ???????????????????????????????????????????????????????????????

	/**
	 * Set the shipping package (used by shipping-rules filter).
	 */
	public function set_package( array $package ): void {
		$this->package = $package;
	}

	/**
	 * Inject the product being added to cart (used by validate_add_to_cart).
	 */
	public function set_adding_product( int $product_id, int $qty = 1 ): void {
		$this->cache['adding_product_id'] = $product_id;
		$this->cache['adding_product_qty'] = $qty;
	}

	// ?? Accessors ?????????????????????????????????????????????????????????????

	public function adding_product_id(): int {
		return (int) ( $this->cache['adding_product_id'] ?? 0 );
	}

	public function adding_product_qty(): int {
		return (int) ( $this->cache['adding_product_qty'] ?? 1 );
	}

	/**
	 * Returns the WC cart object, or null outside a cart/checkout context.
	 */
	public function cart(): ?\WC_Cart {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return null;
		}
		return WC()->cart;
	}

	/**
	 * Current cart subtotal (displayed, including tax if shown).
	 */
	public function cart_total(): float {
		if ( isset( $this->cache['cart_total'] ) ) {
			return (float) $this->cache['cart_total'];
		}
		$cart = $this->cart();
		if ( ! $cart ) {
			return 0.0;
		}
		$total = (float) $cart->get_displayed_subtotal();
		$this->cache['cart_total'] = $total;
		return $total;
	}

	/**
	 * Customer's shipping country code (ISO 3166-1 alpha-2).
	 */
	public function shipping_country(): string {
		if ( isset( $this->cache['shipping_country'] ) ) {
			return (string) $this->cache['shipping_country'];
		}
		if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
			return '';
		}
		$country = (string) WC()->customer->get_shipping_country();
		$this->cache['shipping_country'] = $country;
		return $country;
	}

	/**
	 * Customer's shipping state/province code (ISO 3166-2 sub-code).
	 * For Taiwan: TPE, NWT, TAO, TXG, TNN, KHH, ??	 */
	public function shipping_state(): string {
		if ( isset( $this->cache['shipping_state'] ) ) {
			return (string) $this->cache['shipping_state'];
		}
		if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
			return '';
		}
		$state = (string) WC()->customer->get_shipping_state();
		$this->cache['shipping_state'] = $state;
		return $state;
	}

	/**
	 * All product IDs currently in cart, including the product being added.
	 * Variation IDs are included alongside their parent product IDs.
	 *
	 * @return int[]
	 */
	public function product_ids(): array {
		if ( isset( $this->cache['product_ids'] ) ) {
			$ids = (array) $this->cache['product_ids'];
		} else {
			$cart = $this->cart();
			if ( ! $cart ) {
				$ids = [];
			} else {
				$ids = [];
				foreach ( $cart->get_cart() as $item ) {
					$ids[] = (int) ( $item['product_id'] ?? 0 );
					if ( ! empty( $item['variation_id'] ) ) {
						$ids[] = (int) $item['variation_id'];
					}
				}
				$this->cache['product_ids'] = $ids;
			}
		}

		// Merge in the product being added (for validate_add_to_cart context).
		$adding = $this->adding_product_id();
		if ( $adding > 0 ) {
			$ids[] = $adding;
		}

		return $ids;
	}

	/**
	 * All product category IDs (including ancestor terms) for cart products.
	 *
	 * @return int[]
	 */
	public function category_ids(): array {
		if ( isset( $this->cache['category_ids'] ) ) {
			return (array) $this->cache['category_ids'];
		}
		$cat_ids = [];
		foreach ( $this->product_ids() as $pid ) {
			$terms = function_exists( 'get_the_terms' )
				? get_the_terms( $pid, 'product_cat' )
				: false;
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$cat_ids[] = (int) $term->term_id;
					$ancestors = get_ancestors( $term->term_id, 'product_cat' );
					foreach ( $ancestors as $anc ) {
						$cat_ids[] = (int) $anc;
					}
				}
			}
		}
		$this->cache['category_ids'] = array_values( array_unique( $cat_ids ) );
		return $this->cache['category_ids'];
	}

	/**
	 * Array of chosen shipping method rate IDs.
	 *
	 * In a shipping package filter context, returns the rate IDs from the package.
	 * In a cart/checkout context, returns WC session's chosen_shipping_methods.
	 *
	 * @return string[]
	 */
	public function chosen_shipping_methods(): array {
		if ( isset( $this->cache['chosen_shipping_methods'] ) ) {
			return (array) $this->cache['chosen_shipping_methods'];
		}

		if ( $this->package ) {
			// We are inside woocommerce_package_rates filter.
			$methods = array_keys( $this->package['rates'] ?? [] );
			$this->cache['chosen_shipping_methods'] = $methods;
			return $methods;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return [];
		}
		$chosen = (array) WC()->session->get( 'chosen_shipping_methods', [] );
		$this->cache['chosen_shipping_methods'] = $chosen;
		return $chosen;
	}
}

