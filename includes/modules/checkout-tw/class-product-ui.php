<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * ????UI 憓撥璅∠?
 * 撖虫???????翰?頃鞎瑕?????桀??隞嗚? */
class Product_UI {

	public function boot(): void {
		// 瑼Ｘ?臬?
		if ( 'yes' !== get_option( 'Taiwan_Store_Core_product_sticky_bar_enabled', 'yes' ) ) {
			return;
		}

		// 1. ?典???摨??豢筑鞈潸眺??		add_action( 'woocommerce_after_single_product', [ $this, 'display_sticky_add_to_cart' ] );
		
		// 2. 瘜典?芾?璅??
		add_action( 'wp_head', [ $this, 'output_product_ui_css' ] );
	}

	public function display_sticky_add_to_cart(): void {
		global $product;
		if ( ! $product || ! is_product() ) return;

		$image_url = get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' );
		$price_html = $product->get_price_html();
		$product_id = $product->get_id();

		?>
		<div id="wctw-sticky-cart" class="wctw-sticky-cart-wrap">
			<div class="wctw-sticky-cart-container">
				<div class="wctw-sticky-info">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="product thumb">
					<div class="wctw-sticky-text">
						<span class="wctw-sticky-title"><?php echo esc_html( $product->get_name() ); ?></span>
						<span class="wctw-sticky-price"><?php echo wp_kses_post( $price_html ); ?></span>
					</div>
				</div>
				<div class="wctw-sticky-action">
					<button type="button" class="wctw-sticky-btn" onclick="document.querySelector('.single_add_to_cart_button').click();">
						?? 蝡鞈潸眺
					</button>
				</div>
			</div>
		</div>

		<script>
			(function($) {
				$(window).scroll(function() {
					var $btn = $('.single_add_to_cart_button');
					if (!$btn.length) return;
					
					var btnTop = $btn.offset().top + $btn.outerHeight();
					if ($(window).scrollTop() > btnTop) {
						$('#wctw-sticky-cart').addClass('is-visible');
					} else {
						$('#wctw-sticky-cart').removeClass('is-visible');
					}
				});
			})(jQuery);
		</script>
		<?php
	}

	public function output_product_ui_css(): void {
		if ( ! is_product() ) return;
		?>
		<style>
			.wctw-sticky-cart-wrap { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; box-shadow: 0 -4px 15px rgba(0,0,0,0.1); z-index: 9999; transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); padding: 12px 15px; border-top: 1px solid #eee; }
			.wctw-sticky-cart-wrap.is-visible { transform: translateY(0); }
			
			.wctw-sticky-cart-container { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
			
			.wctw-sticky-info { display: flex; align-items: center; gap: 12px; }
			.wctw-sticky-info img { width: 45px; height: 45px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
			.wctw-sticky-text { display: flex; flex-direction: column; line-height: 1.3; }
			.wctw-sticky-title { font-size: 14px; font-weight: 600; color: #1d2327; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
			.wctw-sticky-price { font-size: 15px; color: #d63638; font-weight: bold; }
			.wctw-sticky-price .woocommerce-Price-currencySymbol { font-size: 12px; margin-right: 2px; }

			.wctw-sticky-btn { background: #d63638; color: #fff; border: none; padding: 12px 30px; border-radius: 30px; font-weight: bold; font-size: 16px; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 10px rgba(214,54,56,0.3); }
			.wctw-sticky-btn:hover { background: #b32d2f; transform: scale(1.03); }

			@media (max-width: 480px) {
				.wctw-sticky-title { max-width: 120px; }
				.wctw-sticky-btn { padding: 10px 20px; font-size: 15px; }
			}
		</style>
		<?php
	}
}

