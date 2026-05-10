<?php
defined( 'ABSPATH' ) || exit;

/**
 * 預設範例規則資料 (Grouped)
 */
return [
	'payment' => [
		[
			'group' => '物流與區域限制',
			'items' => [
				[
					'key'         => 'pay_tw_outer_islands',
					'name'        => '離島地區不支援貨到付款',
					'description' => '當收件地址為澎湖、金門、馬祖時，隱藏貨到付款選項。',
					'conditions'  => [
						[ 'type' => 'address', 'config' => [ 'field' => 'state', 'op' => 'in', 'values' => [ 'PH', 'KM', 'MZ' ] ] ],
					],
					'actions'     => [
						[ 'type' => 'hide_payment', 'config' => [ 'gateways' => [ 'cod' ] ] ],
					],
				],
			],
		],
	],
	'shipping' => [
		[
			'group' => '滿額免運建議',
			'items' => [
				[
					'key'         => 'ship_free_over_2000',
					'name'        => '全館滿 $2,000 免運費',
					'description' => '最基本的免運規則設定。',
					'conditions'  => [
						[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 2000 ] ],
					],
					'actions'     => [
						[ 'type' => 'free_shipping', 'config' => [] ],
					],
				],
			],
		],
	],
	'cart' => [
		[
			'group' => '安全與防護',
			'items' => [
				[
					'key'         => 'cart_block_high_freq',
					'name'        => '防止惡意下單：24小時內限下2單',
					'description' => '偵測到同一買家頻繁下單時，暫時阻止結帳。',
					'conditions'  => [
						[ 'type' => 'order_frequency', 'config' => [ 'hours' => 24, 'op' => 'gte', 'count' => 2 ] ],
					],
					'actions'     => [
						[ 'type' => 'block_checkout', 'config' => [ 'message' => '您的下單頻率過高，請稍後再試。' ] ],
					],
				],
			],
		],
	],
	'marketing' => [
		[
			'group' => '熱門促銷優惠',
			'items' => [
				[
					'key'         => 'mkt_fixed_discount',
					'name'        => '全館滿 $1,000 折 $100',
					'description' => '最經典的促銷方式，直接給予固定金額折抵。',
					'conditions'  => [
						[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 1000 ] ],
					],
					'actions'     => [
						[ 'type' => 'apply_discount', 'config' => [ 'type' => 'fixed', 'amount' => 100, 'name' => '滿額折 $100' ] ],
					],
				],
				[
					'key'         => 'mkt_bundle_3_999',
					'name'        => '指定分類任選 3 件 $999',
					'description' => '適合女裝、美妝或季節性組合促銷。',
					'conditions'  => [
						[ 'type' => 'cart_item_count', 'config' => [ 'op' => 'gte', 'count' => 3 ] ],
					],
					'actions'     => [
						[ 'type' => 'bundle_discount', 'config' => [ 'qty' => 3, 'type' => 'fixed_price', 'value' => 999, 'name' => '3 件 $999 組合價' ] ],
					],
				],
			],
		],
		[
			'group' => '轉單提升利器',
			'items' => [
				[
					'key'         => 'mkt_progress_free_ship',
					'name'        => '視覺提示：滿 $2,000 免運進度條',
					'description' => '在購物車上方顯示進度，強力誘導消費者湊單。',
					'conditions'  => [
						[ 'type' => 'cart_total', 'config' => [ 'op' => 'lt', 'amount' => 2000 ] ],
					],
					'actions'     => [
						[ 'type' => 'cart_progress', 'config' => [ 'target_amount' => 2000, 'label' => '免運費', 'message_pattern' => '再買 {diff} 就能享受 {label} 囉！' ] ],
					],
				],
				[
					'key'         => 'mkt_addon_deal_v2',
					'name'        => '滿額加價購：超值商品推薦',
					'description' => '結帳前最後一推！滿額即可加購特選商品。',
					'conditions'  => [
						[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 500 ] ],
					],
					'actions'     => [
						[ 'type' => 'addon_deal', 'config' => [ 'product_id' => '', 'addon_price' => 99, 'title' => '限時加購', 'button_text' => '立即加購' ] ],
					],
				],
				[
					'key'         => 'mkt_midnight_flash',
					'name'        => '快閃倒數：午夜驚喜優惠',
					'description' => '配合倒數計時器，製造限時搶購的緊迫感。',
					'conditions'  => [
						[ 'type' => 'time_range', 'config' => [ 'start' => '', 'end' => '' ] ],
					],
					'actions'     => [
						[ 'type' => 'flash_sale_countdown', 'config' => [ 'message' => '限時折扣倒數：', 'end_time' => '' ] ],
						[ 'type' => 'apply_discount', 'config' => [ 'type' => 'percent', 'amount' => 10, 'name' => '午夜快閃 9 折' ] ],
					],
				],
			],
		],
		[
			'group' => '新客與會員限定',
			'items' => [
				[
					'key'         => 'mkt_first_purchase_gift',
					'name'        => '新朋友首購禮：現折 $50',
					'description' => '吸引新客下單，提升新客轉化率。',
					'conditions'  => [
						[ 'type' => 'first_purchase', 'config' => [] ],
					],
					'actions'     => [
						[ 'type' => 'apply_discount', 'config' => [ 'type' => 'fixed', 'amount' => 50, 'name' => '首購禮' ] ],
					],
				],
				[
					'key'         => 'mkt_vip_free_shipping',
					'name'        => 'VIP 專屬優惠：全館免運',
					'description' => '經營熟客，僅限具備 Administrator 或 VIP 身份的會員。',
					'conditions'  => [
						[ 'type' => 'user_role', 'config' => [ 'roles' => [ 'administrator', 'vip' ] ] ],
					],
					'actions'     => [
						[ 'type' => 'free_shipping', 'config' => [] ],
					],
				],
			],
		],
	],
];
