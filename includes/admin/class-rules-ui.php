<?php
namespace Taiwan_Store_Core\Admin; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Renders the Rule Editor HTML container and localises data for rules-admin.js.
 */
class Rules_UI {

	private static array $gw_cache = [];
	private static array $sh_cache = [];

	/**
	 * @param string $hook  'payment' | 'shipping' | 'cart'
	 */
	public static function render( string $hook ): void {
		$data = [
			'hook'       => $hook,
			'nonce'      => wp_create_nonce( 'Taiwan_Store_Core_rules' ),
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'rules'      => array_values( (array) get_option( 'Taiwan_Store_Core_rules_' . $hook, [] ) ),
			'gateways'   => self::get_gateways(),
			'shipping'   => self::get_shipping_methods(),
			'categories' => self::get_categories(),
			'states'     => require Taiwan_Store_Core_DIR . 'includes/modules/checkout-tw/data/tw-states.php',
			'samples'    => self::get_samples_for_hook( $hook ),
		];

		wp_localize_script( 'taiwan-store-core-rules-admin', 'WCTWRulesData', $data );

		$export_url = add_query_arg( [
			'action' => 'Taiwan_Store_Core_export_rules',
			'hook'   => $hook,
			'nonce'  => wp_create_nonce( 'Taiwan_Store_Core_rules' ),
		], admin_url( 'admin-ajax.php' ) );

		// Hide the WC "Save Changes" button ??rules are saved via AJAX.
		echo '<style>.woocommerce-save-button{display:none!important}</style>';
		echo '<div style="display:flex;align-items:center;gap:8px;margin-top:20px;margin-bottom:12px">';
		echo '<a href="' . esc_url( $export_url ) . '" class="button button-secondary" style="display:inline-flex;align-items:center;gap:4px"><span class="dashicons dashicons-download" style="margin-top:3px;font-size:16px;width:16px;height:16px"></span>' . esc_html__( '?臬閬? (JSON)', 'taiwan-store-core' ) . '</a>';
		echo '<label class="button button-secondary" style="display:inline-flex;align-items:center;gap:4px;cursor:pointer"><span class="dashicons dashicons-upload" style="margin-top:3px;font-size:16px;width:16px;height:16px"></span>' . esc_html__( '?臬閬? (JSON)', 'taiwan-store-core' ) . '<input type="file" id="wc-tw-import-file" accept=".json" style="display:none"></label>';
		echo '</div>';
		echo '<div id="wc-tw-rules-app"></div>';
		echo '<script>
		(function(){
			var inp = document.getElementById("wc-tw-import-file");
			if(!inp) return;
			inp.addEventListener("change", function(){
				var file = this.files[0];
				if(!file) return;
				var fd = new FormData();
				fd.append("action","Taiwan_Store_Core_import_rules");
				fd.append("nonce","' . esc_js( wp_create_nonce( 'Taiwan_Store_Core_rules' ) ) . '");
				fd.append("hook","' . esc_js( $hook ) . '");
				fd.append("file", file);
				fetch(' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ',{method:"POST",body:fd,credentials:"same-origin"})
					.then(function(r){return r.json();})
					.then(function(d){
						if(d.success){
							alert("?????臬 " + d.data.imported + " 璇?????喳???渡???);
							location.reload();
						} else {
							alert("???臬憭望?嚗? + JSON.stringify(d.data));
						}
					})
					.catch(function(e){ alert("??蝬脰楝?航炊嚗? + e); });
				inp.value="";
			});
		})();
		</script>';
	}

	private static function get_gateways(): array {
		if ( self::$gw_cache ) {
			return self::$gw_cache;
		}
		$out = [];
		foreach ( WC()->payment_gateways()->payment_gateways() as $id => $gw ) {
			$out[] = [ 'id' => $id, 'label' => $gw->get_method_title() ?: $gw->get_title() ];
		}
		return self::$gw_cache = $out;
	}

	private static function get_shipping_methods(): array {
		if ( self::$sh_cache ) {
			return self::$sh_cache;
		}
		$out = [];
		foreach ( \WC_Shipping_Zones::get_zones() as $zone_data ) {
			$zone = new \WC_Shipping_Zone( $zone_data['id'] );
			foreach ( $zone->get_shipping_methods() as $method ) {
				$out[] = [
					'id'    => $method->get_rate_id(),
					'label' => $zone_data['zone_name'] . ': ' . $method->get_title(),
				];
			}
		}
		return self::$sh_cache = $out;
	}

	private static function get_samples_for_hook( string $hook ): array {
		$all = require Taiwan_Store_Core_DIR . 'includes/admin/data/sample-rules.php';
		return (array) ( $all[ $hook ] ?? [] );
	}

	private static function get_categories(): array {
		$terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );
		if ( is_wp_error( $terms ) ) {
			return [];
		}
		$out = [];
		foreach ( $terms as $term ) {
			$out[] = [ 'id' => $term->term_id, 'label' => $term->name ];
		}
		return $out;
	}
}

