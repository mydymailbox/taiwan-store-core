<?php
namespace Taiwan_Store_Core\Modules\Checkout_Tw; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit;

/**
 * ?憛?撣單??(Blocks Integration)
 * 鞎痊??WooCommerce ?憛?撣喃葉閮餃??垢 React ?單??撩?霈?? */
class Blocks_Integration implements IntegrationInterface {

	public function get_name(): string {
		return 'taiwan-store-core';
	}

	public function initialize(): void {
		// 頛 wp-scripts 蝺刻陌敺?? asset 瑼? (?批??? dependencies)
		$asset_path = Taiwan_Store_Core_DIR . 'build/index.asset.php';
		$asset_url  = Taiwan_Store_Core_URL . 'build/index.js';

		$dependencies = [ 'wp-element', 'wp-i18n', 'wc-blocks-registry' ];
		$version      = Taiwan_Store_Core_VERSION;

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$dependencies = $asset['dependencies'] ?? $dependencies;
			$version      = $asset['version'] ?? $version;
		}

		wp_register_script(
			'taiwan-store-core-blocks-frontend',
			$asset_url,
			$dependencies,
			$version,
			true
		);
	}

	public function get_script_handles(): array {
		// ?閬?蝯董???亦? script handle
		return [ 'taiwan-store-core-blocks-frontend' ];
	}

	public function get_editor_script_handles(): array {
		// ?亙敺?日辰?∠楊頛臬鋆∩??閬?閬賣?閮?憛??冽迨?
		return [ 'taiwan-store-core-blocks-frontend' ];
	}

	public function get_script_data(): array {
		// ?ㄐ?臭誑??PHP ???賂?憒??身摰腦撣??擗萇策?垢 React 雿輻
		// ?垢?臭誑?? wcSettings.getSetting('taiwan-store-core_data') ??
		return [
			'is_tax_id_enabled' => get_option( 'Taiwan_Store_Core_checkout_tax_id_enabled', 'yes' ),
			'is_taxid_lookup'   => get_option( 'Taiwan_Store_Core_checkout_taxid_lookup', 'yes' ),
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'taxidNonce'        => wp_create_nonce( 'Taiwan_Store_Core_lookup_taxid' ),
			'is_postcode_auto'  => get_option( 'Taiwan_Store_Core_checkout_postcode_autofill', 'yes' ),
			'name_consolidate'  => get_option( 'Taiwan_Store_Core_checkout_name_consolidate', 'yes' ),
		];
	}
}

