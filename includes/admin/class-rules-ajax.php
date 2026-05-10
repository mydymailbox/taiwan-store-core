<?php
namespace Taiwan_Store_Core\Admin; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * AJAX handlers for the Rule Editor admin UI.
 * All endpoints verify nonce + manage_woocommerce capability.
 */
class Rules_Ajax {

	public function boot(): void {
		add_action( 'wp_ajax_Taiwan_Store_Core_save_rule',      [ $this, 'save_rule' ] );
		add_action( 'wp_ajax_Taiwan_Store_Core_delete_rule',    [ $this, 'delete_rule' ] );
		add_action( 'wp_ajax_Taiwan_Store_Core_import_samples', [ $this, 'import_samples' ] );
		add_action( 'wp_ajax_Taiwan_Store_Core_export_rules',   [ $this, 'export_rules' ] );
		add_action( 'wp_ajax_Taiwan_Store_Core_import_rules',   [ $this, 'import_rules' ] );
	}

	public function save_rule(): void {
		check_ajax_referer( 'Taiwan_Store_Core_rules', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}

		$hook = sanitize_key( wp_unslash( $_POST['hook'] ?? '' ) );
		if ( ! in_array( $hook, [ 'payment', 'shipping', 'cart', 'marketing' ], true ) ) {
			wp_send_json_error( 'invalid_hook' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- value is json_decoded then fully sanitized via sanitize_rule()
		$rule_json = wp_unslash( $_POST['rule'] ?? '' );
		$rule_data = json_decode( $rule_json, true );
		if ( ! is_array( $rule_data ) ) {
			wp_send_json_error( 'invalid_rule' );
		}

		$rule_data = $this->sanitize_rule( $rule_data, $hook );

		$rules = (array) get_option( 'Taiwan_Store_Core_rules_' . $hook, [] );
		$found = false;
		foreach ( $rules as &$existing ) {
			if ( isset( $existing['id'] ) && $existing['id'] === $rule_data['id'] ) {
				$existing = $rule_data;
				$found    = true;
				break;
			}
		}
		unset( $existing );

		if ( ! $found ) {
			$rules[] = $rule_data;
		}

		update_option( 'Taiwan_Store_Core_rules_' . $hook, array_values( $rules ), false );
		wp_send_json_success( [ 'rules' => array_values( $rules ) ] );
	}

	public function delete_rule(): void {
		check_ajax_referer( 'Taiwan_Store_Core_rules', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}

		$hook    = sanitize_key( wp_unslash( $_POST['hook'] ?? '' ) );
		$rule_id = sanitize_text_field( wp_unslash( $_POST['rule_id'] ?? '' ) );
		if ( ! in_array( $hook, [ 'payment', 'shipping', 'cart', 'marketing' ], true ) || ! $rule_id ) {
			wp_send_json_error( 'invalid' );
		}

		$rules = (array) get_option( 'Taiwan_Store_Core_rules_' . $hook, [] );
		$rules = array_values(
			array_filter( $rules, static fn( $r ) => ( $r['id'] ?? '' ) !== $rule_id )
		);
		update_option( 'Taiwan_Store_Core_rules_' . $hook, $rules, false );
		wp_send_json_success( [ 'rules' => $rules ] );
	}

	public function import_samples(): void {
		check_ajax_referer( 'Taiwan_Store_Core_rules', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}

		$hook = sanitize_key( wp_unslash( $_POST['hook'] ?? '' ) );
		if ( ! in_array( $hook, [ 'payment', 'shipping', 'cart', 'marketing' ], true ) ) {
			wp_send_json_error( 'invalid_hook' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via array_map('sanitize_key')
		$keys_raw = wp_unslash( $_POST['keys'] ?? '' );
		$keys     = array_filter( array_map( 'sanitize_key', explode( ',', (string) $keys_raw ) ) );
		if ( ! $keys ) {
			wp_send_json_error( 'no_keys' );
		}

		$catalogue = require Taiwan_Store_Core_DIR . 'includes/admin/data/sample-rules.php';
		$groups    = (array) ( $catalogue[ $hook ] ?? [] );
		$by_key    = [];
		
		foreach ( $groups as $g ) {
			$items = (array) ( $g['items'] ?? [] );
			foreach ( $items as $s ) {
				$by_key[ $s['key'] ] = $s;
			}
		}

		$rules = (array) get_option( 'Taiwan_Store_Core_rules_' . $hook, [] );
		$added = 0;

		foreach ( $keys as $key ) {
			if ( empty( $by_key[ $key ] ) ) {
				continue;
			}
			$s        = $by_key[ $key ];
			$rule     = [
				'id'         => wp_generate_uuid4(),
				'name'       => $s['name'],
				'hook'       => $hook,
				'enabled'    => false,
				'conditions' => $s['conditions'],
				'actions'    => $s['actions'],
			];
			$rules[] = $this->sanitize_rule( $rule, $hook );
			$added++;
		}

		update_option( 'Taiwan_Store_Core_rules_' . $hook, array_values( $rules ), false );
		wp_send_json_success( [ 'rules' => array_values( $rules ), 'added' => $added ] );
	}

	// -------------------------------------------------------------------------
	// Export / Import
	// -------------------------------------------------------------------------

	/**
	 * Export all rules for a hook as a JSON download.
	 * Called via direct GET link with nonce (not standard AJAX JSON response).
	 */
	public function export_rules(): void {
		check_ajax_referer( 'Taiwan_Store_Core_rules', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( '權限不足', 'taiwan-store-core' ), 403 );
		}

		$hook = sanitize_key( wp_unslash( $_GET['hook'] ?? '' ) );
		if ( ! in_array( $hook, [ 'payment', 'shipping', 'cart', 'marketing' ], true ) ) {
			wp_die( esc_html__( '不支援的掛鉤類型', 'taiwan-store-core' ) );
		}

		$rules    = (array) get_option( 'Taiwan_Store_Core_rules_' . $hook, [] );
		$filename = 'taiwan-store-core-rules-' . $hook . '-' . gmdate( 'Ymd' ) . '.json';
		$json     = wp_json_encode( [
			'version'   => Taiwan_Store_Core_VERSION,
			'hook'      => $hook,
			'exported'  => gmdate( 'c' ),
			'rules'     => $rules,
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $json ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $json;
		exit;
	}

	/**
	 * Import rules from a JSON file upload. Merges (by id) with existing rules.
	 */
	public function import_rules(): void {
		check_ajax_referer( 'Taiwan_Store_Core_rules', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}

		$hook = sanitize_key( wp_unslash( $_POST['hook'] ?? '' ) );
		if ( ! in_array( $hook, [ 'payment', 'shipping', 'cart', 'marketing' ], true ) ) {
			wp_send_json_error( 'invalid_hook' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name and size validated below via is_uploaded_file() and size check
		if ( empty( $_FILES['file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
			wp_send_json_error( 'no_file' );
		}

		// Validate MIME / size
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cast to int
		$size = (int) ( $_FILES['file']['size'] ?? 0 );
		if ( $size > 512 * 1024 ) { // 512 KB max
			wp_send_json_error( 'file_too_large' );
		}

		$raw = file_get_contents( $_FILES['file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated via is_uploaded_file() above
		if ( false === $raw ) {
			wp_send_json_error( 'read_error' );
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || ! isset( $data['rules'] ) || ! is_array( $data['rules'] ) ) {
			wp_send_json_error( 'invalid_json' );
		}

		// Validate hook match
		if ( isset( $data['hook'] ) && $data['hook'] !== $hook ) {
			wp_send_json_error( 'hook_mismatch' );
		}

		// Sanitize imported rules
		$incoming = [];
		foreach ( $data['rules'] as $r ) {
			if ( is_array( $r ) ) {
				$incoming[] = $this->sanitize_rule( $r, $hook );
			}
		}

		// Merge by id: imported rules override existing ones with same id; new ids are appended.
		$existing = (array) get_option( 'Taiwan_Store_Core_rules_' . $hook, [] );
		$index    = [];
		foreach ( $existing as $i => $e ) {
			if ( isset( $e['id'] ) ) {
				$index[ $e['id'] ] = $i;
			}
		}
		foreach ( $incoming as $r ) {
			if ( isset( $index[ $r['id'] ] ) ) {
				$existing[ $index[ $r['id'] ] ] = $r;
			} else {
				$existing[] = $r;
			}
		}

		$rules = array_values( $existing );
		update_option( 'Taiwan_Store_Core_rules_' . $hook, $rules, false );
		wp_send_json_success( [ 'rules' => $rules, 'imported' => count( $incoming ) ] );
	}

	// -------------------------------------------------------------------------
	// Sanitize helpers
	// -------------------------------------------------------------------------

	private function sanitize_rule( array $data, string $hook ): array {
		$id = sanitize_text_field( $data['id'] ?? '' );
		if ( ! $id ) {
			$id = wp_generate_uuid4();
		}

		$logic = strtoupper( sanitize_text_field( $data['logic'] ?? 'AND' ) );
		if ( ! in_array( $logic, [ 'AND', 'OR' ], true ) ) {
			$logic = 'AND';
		}

		$out = [
			'id'         => $id,
			'name'       => sanitize_text_field( $data['name'] ?? '' ),
			'hook'       => $hook,
			'enabled'    => ! empty( $data['enabled'] ),
			'logic'      => $logic,
			'conditions' => [],
			'actions'    => [],
		];

		foreach ( (array) ( $data['conditions'] ?? [] ) as $cond ) {
			if ( is_array( $cond ) ) {
				$out['conditions'][] = $this->sanitize_condition( $cond );
			}
		}
		foreach ( (array) ( $data['actions'] ?? [] ) as $act ) {
			if ( is_array( $act ) ) {
				$out['actions'][] = $this->sanitize_action( $act );
			}
		}

		return $out;
	}

	private function sanitize_condition( array $cond ): array {
		$type    = sanitize_key( $cond['type'] ?? '' );
		$allowed = [ 'address', 'cart_total', 'category', 'payment_method', 'product', 'shipping_method', 'address_mismatch', 'order_frequency' ];
		if ( ! in_array( $type, $allowed, true ) ) {
			$type = 'cart_total';
		}

		$c   = (array) ( $cond['config'] ?? [] );
		$cfg = [];

		switch ( $type ) {
			case 'address':
				$cfg['field']  = in_array( $c['field'] ?? '', [ 'country', 'state' ], true ) ? $c['field'] : 'country';
				$cfg['op']     = in_array( $c['op'] ?? '', [ 'in', 'not_in' ], true ) ? $c['op'] : 'in';
				$cfg['values'] = array_map( 'sanitize_text_field', (array) ( $c['values'] ?? [] ) );
				break;
			case 'cart_total':
				$cfg['op']     = in_array( $c['op'] ?? '', [ 'gte', 'lte', 'gt', 'lt', 'eq' ], true ) ? $c['op'] : 'gte';
				$cfg['amount'] = (float) ( $c['amount'] ?? 0 );
				break;
			case 'category':
				$cfg['op']         = in_array( $c['op'] ?? '', [ 'contains', 'not_contains' ], true ) ? $c['op'] : 'contains';
				$cfg['categories'] = array_map( 'absint', (array) ( $c['categories'] ?? [] ) );
				break;
			case 'payment_method':
				$cfg['op']      = in_array( $c['op'] ?? '', [ 'in', 'not_in' ], true ) ? $c['op'] : 'in';
				$cfg['methods'] = array_map( 'sanitize_key', (array) ( $c['methods'] ?? [] ) );
				break;
			case 'product':
				$cfg['op']       = in_array( $c['op'] ?? '', [ 'in', 'not_in' ], true ) ? $c['op'] : 'in';
				$cfg['products'] = array_map( 'absint', (array) ( $c['products'] ?? [] ) );
				break;
			case 'shipping_method':
				$cfg['op']      = in_array( $c['op'] ?? '', [ 'in', 'not_in' ], true ) ? $c['op'] : 'in';
				$cfg['methods'] = array_map( 'sanitize_text_field', (array) ( $c['methods'] ?? [] ) );
				break;
			case 'address_mismatch':
				$cfg['compare'] = in_array( $c['compare'] ?? '', [ 'country', 'state' ], true ) ? $c['compare'] : 'country';
				break;
			case 'order_frequency':
				$cfg['op']    = in_array( $c['op'] ?? '', [ 'gte', 'gt', 'lte', 'lt', 'eq' ], true ) ? $c['op'] : 'gte';
				$cfg['hours'] = max( 1, (int) ( $c['hours'] ?? 24 ) );
				$cfg['count'] = max( 1, (int) ( $c['count'] ?? 2 ) );
				break;
		}

		return [ 'type' => $type, 'config' => $cfg ];
	}

	private function sanitize_action( array $act ): array {
		$type    = sanitize_key( $act['type'] ?? '' );
		$allowed = [ 'hide_payment', 'hide_shipping', 'block_checkout' ];
		if ( ! in_array( $type, $allowed, true ) ) {
			$type = 'block_checkout';
		}

		$c   = (array) ( $act['config'] ?? [] );
		$cfg = [];

		switch ( $type ) {
			case 'hide_payment':
				$cfg['gateways'] = array_map( 'sanitize_key', (array) ( $c['gateways'] ?? [] ) );
				break;
			case 'hide_shipping':
				$cfg['methods'] = array_map( 'sanitize_text_field', (array) ( $c['methods'] ?? [] ) );
				break;
			case 'block_checkout':
				$cfg['message'] = sanitize_text_field( $c['message'] ?? '' );
				break;
		}

		return [ 'type' => $type, 'config' => $cfg ];
	}
}

