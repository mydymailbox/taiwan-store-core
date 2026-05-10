<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw;

defined( 'ABSPATH' ) || exit;

/**
 * LINE 璉?賢?璅∠?
 * ??撌脩?亦?嗥?鞈潛頠???銝行璉????LINE ??? */
class Abandoned_Cart {

	private const META_KEY = '_wctw_last_cart_activity';

	public function boot(): void {
		// 1. ??鞈潛頠???		add_action( 'woocommerce_add_to_cart', [ $this, 'update_cart_activity' ] );
		add_action( 'woocommerce_cart_item_removed', [ $this, 'update_cart_activity' ] );
		
		// 2. 閮餃?瘥??銵???隞餃?
		if ( ! wp_next_scheduled( 'Taiwan_Store_Core_check_abandoned_carts' ) ) {
			wp_schedule_event( time(), 'hourly', 'Taiwan_Store_Core_check_abandoned_carts' );
		}
		add_action( 'Taiwan_Store_Core_check_abandoned_carts', [ $this, 'process_abandoned_carts' ] );
	}

	/**
	 * ?嗉眺摰嗆?雿頃?抵???蝝??敺暑????	 */
	public function update_cart_activity(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) return;

		// ?芣??瑕? LINE ID ??嗆??閬蕭頩歹????賡? LINE ?賢?嚗?		$line_user_id = get_user_meta( $user_id, '_wctw_line_user_id', true );
		if ( ! $line_user_id ) return;

		update_user_meta( $user_id, self::META_KEY, time() );
	}

	/**
	 * ??璉嚗??曇???1 撠??芰?撣喃??芷???冽
	 */
	public function process_abandoned_carts(): void {
		global $wpdb;

		// 撠?敺暑? 1 撠???撠 24 撠??抒??冽
		$one_hour_ago = time() - HOUR_IN_SECONDS;
		$one_day_ago  = time() - DAY_IN_SECONDS;

		$users = get_users( [
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => self::META_KEY,
					'value'   => [ $one_day_ago, $one_hour_ago ],
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				],
				[
					'key'     => '_wctw_line_user_id',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_wctw_abandoned_notified',
					'compare' => 'NOT EXISTS', // 蝣箔??芷銝甈?				],
			],
		] );

		if ( empty( $users ) ) return;

		foreach ( $users as $user ) {
			// 瑼Ｘ鞈潛頠?衣????梯正嚗?閰脩?嗅??芸??閮嚗?			// 瘜冽?嚗銝?陛???斗嚗祕???舀蝎暹?撠 WC_Session
			$this->send_recovery_message( $user );
			update_user_meta( $user->ID, '_wctw_abandoned_notified', time() );
		}
	}

	private function send_recovery_message( \WP_User $user ): void {
		$line_user_id = get_user_meta( $user->ID, '_wctw_line_user_id', true );
		if ( ! $line_user_id ) return;

		$checkout_url = wc_get_checkout_url();
		$site_name    = get_bloginfo( 'name' );

		$message = "?? ?典末 {$user->display_name}嚗n\n??暹?頃?抵?銝剝???????撠摰?蝯董嚗n\n?亥?憟賣镼踵?韏唬?嚗?刻???撣喳?舐?典??翰?鞎剁?\n{$checkout_url}\n\n???活?箸??嚗???{$site_name}";

		// 隤輻 Taiwan Store Notifier ???頛荔?憒?摮嚗?		if ( class_exists( 'Taiwan_Store_Notifier\Plugin' ) ) {
			$notifier = new \Taiwan_Store_Notifier\Plugin();
			// ? Notifier 銝剝?閬???public ??瘜???湔隤輻??API
			$this->trigger_line_notification( $line_user_id, $message );
		}
	}

	private function trigger_line_notification( string $to, string $message ): void {
		$token = get_option( 'wctn_line_token' );
		if ( ! $token ) return;

		wp_remote_post( 'https://api.line.me/v2/bot/message/push', [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $token,
			],
			'body' => json_encode( [
				'to'       => $to,
				'messages' => [
					[ 'type' => 'text', 'text' => $message ],
				],
			] ),
		] );
	}
}

