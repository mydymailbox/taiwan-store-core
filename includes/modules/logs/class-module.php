<?php
namespace Taiwan_Store_Core\Modules\Logs; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Logs module ??thin wrapper around wc_get_logger().
 * Debug mode is toggled via WooCommerce ???啁?典????銝????? Debug ?亥?.
 */
class Module implements \Taiwan_Store_Core\Module {

	private static bool $debug = false;

	public function id(): string {
		return 'logs';
	}

	public function boot(): void {
		self::$debug = 'yes' === get_option( 'Taiwan_Store_Core_debug', 'no' );
	}

	public function is_admin_only(): bool {
		return false;
	}

	public static function info( string $msg, array $ctx = [] ): void {
		if ( ! self::$debug ) {
			return;
		}
		wc_get_logger()->info( $msg, array_merge( [ 'source' => 'taiwan-store-core' ], $ctx ) );
	}

	public static function error( string $msg, array $ctx = [] ): void {
		wc_get_logger()->error( $msg, array_merge( [ 'source' => 'taiwan-store-core' ], $ctx ) );
	}
}

