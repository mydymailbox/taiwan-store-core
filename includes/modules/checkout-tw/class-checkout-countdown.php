<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * 蝯董?閮?璅∠?
 * ?函?撣喲??ａ??券＊蝷箏閮??剁??亥翰?誑??頧??? */
class Checkout_Countdown {

	public function boot(): void {
		// 瑼Ｘ?臬?
		if ( 'yes' !== get_option( 'Taiwan_Store_Core_checkout_countdown_enabled', 'yes' ) ) {
			return;
		}

		// 1. ?函?撣喲??ａ??冽??亥??
		add_action( 'woocommerce_before_checkout_form', [ $this, 'display_countdown_timer' ], 5 );
		
		// 2. 瘜典?芾?璅??
		add_action( 'wp_head', [ $this, 'output_countdown_css' ] );
	}

	public function display_countdown_timer(): void {
		$minutes = (int) get_option( 'Taiwan_Store_Core_checkout_countdown_minutes', 15 );
		?>
		<div id="wctw-checkout-timer" class="wctw-timer-banner">
			<div class="wctw-timer-inner">
				<span class="wctw-timer-icon">??/span>
				<span class="wctw-timer-text">
					?函?閮撌脖???隢 <span id="wctw-countdown-clock"><?php echo esc_html( sprintf( '%02d:00', $minutes ) ); ?></span> ?批???撣喃誑靽?摨怠???				</span>
			</div>
		</div>

		<script>
			(function($) {
				var minutes = <?php echo (int) $minutes; ?>;
				var seconds = 0;
				var timer = setInterval(function() {
					if (seconds === 0) {
						if (minutes === 0) {
							clearInterval(timer);
							$('#wctw-checkout-timer').fadeOut();
							return;
						}
						minutes--;
						seconds = 59;
					} else {
						seconds--;
					}
					
					var displayM = minutes < 10 ? '0' + minutes : minutes;
					var displayS = seconds < 10 ? '0' + seconds : seconds;
					$('#wctw-countdown-clock').text(displayM + ':' + displayS);
					
					// ?敺?3 ??霈?
					if (minutes < 3) {
						$('#wctw-checkout-timer').addClass('is-urgent');
					}
				}, 1000);
			})(jQuery);
		</script>
		<?php
	}

	public function output_countdown_css(): void {
		if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) return;
		?>
		<style>
			.wctw-timer-banner { background: #fffbe6; border: 1px solid #ffe58f; border-radius: 8px; padding: 15px; margin-bottom: 25px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); animation: pulse-border 2s infinite; }
			.wctw-timer-banner.is-urgent { background: #fff1f0; border-color: #ffa39e; color: #cf1322; }
			.wctw-timer-banner.is-urgent #wctw-countdown-clock { color: #cf1322; font-weight: 800; }
			
			.wctw-timer-inner { display: flex; align-items: center; justify-content: center; gap: 10px; }
			.wctw-timer-icon { font-size: 20px; }
			.wctw-timer-text { font-size: 15px; font-weight: 500; color: #856404; }
			.wctw-timer-banner.is-urgent .wctw-timer-text { color: #cf1322; }
			
			#wctw-countdown-clock { font-family: monospace; font-size: 18px; font-weight: bold; background: rgba(0,0,0,0.05); padding: 2px 8px; border-radius: 4px; margin: 0 5px; color: #d48806; }

			@keyframes pulse-border {
				0% { box-shadow: 0 0 0 0 rgba(255, 229, 143, 0.4); }
				70% { box-shadow: 0 0 0 10px rgba(255, 229, 143, 0); }
				100% { box-shadow: 0 0 0 0 rgba(255, 229, 143, 0); }
			}
		</style>
		<?php
	}
}

