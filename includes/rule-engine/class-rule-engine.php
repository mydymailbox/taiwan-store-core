<?php
namespace Taiwan_Store_Core\Rule_Engine; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Rule engine singleton.
 *
 * Usage:
 *   $engine = Rule_Engine::instance();
 *   $engine->register_condition( new Conditions\Cart_Total() );
 *   $engine->register_action( new Actions\Hide_Payment() );
 *
 *   if ( $engine->has_rules( 'payment' ) ) {
 *       $ctx = new Context();
 *       $engine->evaluate( 'payment', $ctx, $gateways );
 *   }
 *
 * Rules are stored in wp_options:
 *   Taiwan_Store_Core_rules_payment  ??array of serialized Rule arrays
 *   Taiwan_Store_Core_rules_shipping ??array of serialized Rule arrays
 *   Taiwan_Store_Core_rules_cart     ??array of serialized Rule arrays
 */
class Rule_Engine {

	private static ?Rule_Engine $instance = null;

	/** @var array<string, Condition> */
	private array $conditions = [];

	/** @var array<string, Action> */
	private array $actions = [];

	/** @var array<string, Rule[]> */
	private array $rules = [];

	private bool $rules_loaded = false;

	private function __construct() {}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// ?? Registration ??????????????????????????????????????????????????????????

	public function register_condition( Condition $condition ): void {
		$this->conditions[ $condition->id() ] = $condition;
	}

	public function register_action( Action $action ): void {
		$this->actions[ $action->id() ] = $action;
	}

	// ?? Evaluation ????????????????????????????????????????????????????????????

	/**
	 * Returns true if there is at least one enabled rule for $hook.
	 * Use this as a short-circuit check before building a Context.
	 */
	public function has_rules( string $hook ): bool {
		$this->maybe_load_rules();
		return ! empty( $this->rules[ $hook ] );
	}

	/**
	 * Evaluate all rules for $hook and mutate $payload.
	 *
	 * @param string  $hook    'payment' | 'shipping' | 'cart'
	 * @param Context $ctx     Lazy-memoized context.
	 * @param array   $payload Passed by reference; shape depends on hook.
	 */
	public function evaluate( string $hook, Context $ctx, array &$payload ): void {
		$this->maybe_load_rules();
		$rules = $this->rules[ $hook ] ?? [];

		foreach ( $rules as $rule ) {
			if ( ! $rule->enabled ) {
				continue;
			}
			if ( $this->all_conditions_match( $rule->conditions, $ctx, $rule->logic ) ) {
				$this->execute_actions( $rule->actions, $ctx, $payload );
			}
		}
	}

	// ?? Internals ?????????????????????????????????????????????????????????????

	private function maybe_load_rules(): void {
		if ( $this->rules_loaded ) {
			return;
		}

		// ??頛?詨?撌脩???摮?
		$hooks = [ 'payment', 'shipping', 'cart' ];
		
		// 霈隞??隞交憓摰儔???摮?蝔?
		$hooks = apply_filters( 'Taiwan_Store_Core_rule_hooks', $hooks );

		foreach ( $hooks as $hook ) {
			$raw               = (array) get_option( 'Taiwan_Store_Core_rules_' . $hook, [] );
			$this->rules[ $hook ] = array_map(
				[ Rule::class, 'from_array' ],
				array_filter( $raw, 'is_array' )
			);
		}
		$this->rules_loaded = true;
	}

	/**
	 * Evaluate conditions with AND or OR logic.
	 *
	 * AND (default): all conditions must match.
	 * OR: at least one condition must match.
	 */
	private function all_conditions_match( array $conditions, Context $ctx, string $logic = 'AND' ): bool {
		if ( empty( $conditions ) ) {
			return true; // No conditions = always apply.
		}

		$is_or = ( 'OR' === strtoupper( $logic ) );

		foreach ( $conditions as $cond_data ) {
			$type   = (string) ( $cond_data['type'] ?? '' );
			$config = (array) ( $cond_data['config'] ?? [] );
			$cond   = $this->conditions[ $type ] ?? null;
			if ( ! $cond ) {
				// Unknown condition type ??skip (fail-open) but log so admin can diagnose.
				wc_get_logger()->warning(
					"WC TW Core: unknown condition type '{$type}' encountered in rule engine ??skipped.",
					[ 'source' => 'taiwan-store-core' ]
				);
				continue;
			}
			$matched = $cond->matches( $ctx, $config );
			if ( $is_or && $matched ) {
				return true;  // OR short-circuit: one match is enough.
			}
			if ( ! $is_or && ! $matched ) {
				return false; // AND short-circuit: one failure fails all.
			}
		}

		// AND: all matched. OR: none matched.
		return ! $is_or;
	}

	private function execute_actions( array $actions, Context $ctx, array &$payload ): void {
		foreach ( $actions as $act_data ) {
			$type   = (string) ( $act_data['type'] ?? '' );
			$config = (array) ( $act_data['config'] ?? [] );
			$act    = $this->actions[ $type ] ?? null;
			if ( $act ) {
				$act->execute( $ctx, $config, $payload );
			}
		}
	}
}

