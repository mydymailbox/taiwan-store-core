<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * 閮?垢 UI 憓撥璅∠?
 * ?具??董??-> ?亦?閮???Ｗ??亥?閬箏??脣漲璇??拇?鞈??? */
class Order_UI {

	public function boot(): void {
		// 1. ?刻??株底?????脣漲璇?		add_action( 'woocommerce_view_order', [ $this, 'display_order_timeline' ], 5 );
		
		// 2. 瘜典?芾?璅??
		add_action( 'wp_head', [ $this, 'output_timeline_css' ] );
	}

	public function display_order_timeline( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) return;

		$status  = $order->get_status();
		$carrier = $order->get_meta( '_wctn_carrier' );
		$number  = $order->get_meta( '_wctn_tracking_number' );
		$arrived = $order->get_meta( '_wctn_notified_arrived' );

		// 摰儔?脣漲暺?		$steps = [
			'on-hold'    => [ 'label' => '撌脫??, 'icon' => '??', 'active' => true ],
			'processing' => [ 'label' => '皞?銝?, 'icon' => '?', 'active' => false ],
			'shipping'   => [ 'label' => '?葉', 'icon' => '??', 'active' => false ],
			'arrived'    => [ 'label' => '鞎典?撣?, 'icon' => '?', 'active' => false ],
			'completed'  => [ 'label' => '撌脣?鞎?, 'icon' => '??, 'active' => false ],
		];

		// ?寞???身摰暑??		if ( in_array( $status, [ 'processing', 'shipping', 'completed' ] ) ) $steps['processing']['active'] = true;
		if ( in_array( $status, [ 'shipping', 'completed' ] ) || $number ) $steps['shipping']['active'] = true;
		if ( $arrived || $status === 'completed' ) $steps['arrived']['active'] = true;
		if ( $status === 'completed' ) $steps['completed']['active'] = true;

		?>
		<div class="wctw-order-timeline-wrap">
			<h3>??閮?脣漲餈質馱</h3>
			<div class="wctw-timeline">
				<?php foreach ( $steps as $key => $step ) : ?>
					<div class="wctw-step <?php echo $step['active'] ? 'is-active' : ''; ?>">
						<div class="wctw-step-icon"><?php echo esc_html( $step['icon'] ); ?></div>
						<div class="wctw-step-label"><?php echo esc_html( $step['label'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $number ) : ?>
				<div class="wctw-tracking-info-card">
					<div class="wctw-tracking-main">
						<strong>?拇???</strong> <?php echo esc_html( strtoupper( $carrier ) ); ?> | 
						<strong>?株?嚗?/strong> <code><?php echo esc_html( $number ); ?></code>
					</div>
					<p class="wctw-tracking-tip">? 暺?銝???航歲頧?拇?摰雯?亥岷??啣???/p>
					
					<a href="https://line.me/R/oaMessage/@YOUR_LINE_ID/??典末嚗??唾岷???潸???20#<?php echo (int) $order_id; ?>%20??憿? target="_blank" class="wctw-line-inquiry-btn">
						? ?? LINE 摰Ｘ?閰Ｗ?甇方???					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function output_timeline_css(): void {
		if ( ! is_account_page() || ! is_wc_endpoint_url( 'view-order' ) ) return;
		?>
		<style>
			.wctw-order-timeline-wrap { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 25px; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
			.wctw-order-timeline-wrap h3 { margin-top: 0; font-size: 18px; color: #2271b1; margin-bottom: 25px; border-left: 4px solid #2271b1; padding-left: 12px; }
			
			.wctw-timeline { display: flex; justify-content: space-between; position: relative; margin-bottom: 30px; }
			.wctw-timeline::before { content: ''; position: absolute; top: 20px; left: 10%; right: 10%; height: 2px; background: #eee; z-index: 1; }
			
			.wctw-step { position: relative; z-index: 2; text-align: center; flex: 1; }
			.wctw-step-icon { width: 40px; height: 40px; background: #f8f9fa; border: 2px solid #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 20px; transition: all 0.3s ease; }
			.wctw-step-label { font-size: 13px; color: #8c8f94; font-weight: 500; }
			
			.wctw-step.is-active .wctw-step-icon { background: #e6f4ea; border-color: #008a20; transform: scale(1.1); box-shadow: 0 0 10px rgba(0,138,32,0.2); }
			.wctw-step.is-active .wctw-step-label { color: #1d2327; font-weight: 600; }
			
			.wctw-tracking-info-card { background: #f0f6fc; border-radius: 8px; padding: 15px; margin-top: 20px; border: 1px solid #c3d9f0; }
			.wctw-tracking-main { font-size: 15px; color: #1d2327; }
			.wctw-tracking-main code { background: #fff; padding: 2px 6px; border: 1px solid #ddd; border-radius: 4px; }
			.wctw-tracking-tip { margin: 8px 0 15px; font-size: 12px; color: #646970; }

			.wctw-line-inquiry-btn { display: inline-block; background: #06C755; color: #fff !important; padding: 10px 20px; border-radius: 6px; text-decoration: none !important; font-weight: 600; font-size: 14px; transition: all 0.2s ease; margin-top: 5px; }
			.wctw-line-inquiry-btn:hover { background: #05b34c; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(6,199,85,0.3); }

			@media (max-width: 600px) {
				.wctw-timeline::before { display: none; }
				.wctw-timeline { flex-direction: column; gap: 15px; align-items: flex-start; padding-left: 20px; }
				.wctw-step { display: flex; align-items: center; gap: 15px; text-align: left; }
				.wctw-step-icon { margin: 0; width: 32px; height: 32px; font-size: 16px; }
			}
		</style>
		<?php
	}
}

