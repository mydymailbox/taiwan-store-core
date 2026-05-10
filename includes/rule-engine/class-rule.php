<?php
namespace Taiwan_Store_Core\Rule_Engine; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- Taiwan_Store_Core is the plugin prefix

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object representing a single rule.
 *
 * Rules are stored as plain arrays in wp_options:
 *   option key:  Taiwan_Store_Core_rules_{hook}
 *   option value: array of serialized Rule arrays
 */
class Rule {

	public readonly string $id;
	public readonly string $name;
	public readonly string $hook;
	public readonly bool   $enabled;

	/**
	 * Condition logic operator: 'AND' (all must match) or 'OR' (any must match).
	 */
	public readonly string $logic;

	/** @var array<int, array{type: string, config: array<string,mixed>}> */
	public readonly array $conditions;

	/** @var array<int, array{type: string, config: array<string,mixed>}> */
	public readonly array $actions;

	private function __construct(
		string $id,
		string $name,
		string $hook,
		bool $enabled,
		string $logic,
		array $conditions,
		array $actions
	) {
		$this->id         = $id;
		$this->name       = $name;
		$this->hook       = $hook;
		$this->enabled    = $enabled;
		$this->logic      = $logic;
		$this->conditions = $conditions;
		$this->actions    = $actions;
	}

	/**
	 * Construct a Rule from a plain array (e.g. from get_option()).
	 */
	public static function from_array( array $data ): self {
		$logic = strtoupper( (string) ( $data['logic'] ?? 'AND' ) );
		if ( ! in_array( $logic, [ 'AND', 'OR' ], true ) ) {
			$logic = 'AND';
		}
		return new self(
			(string) ( $data['id'] ?? wp_generate_uuid4() ),
			(string) ( $data['name'] ?? '' ),
			(string) ( $data['hook'] ?? 'payment' ),
			(bool) ( $data['enabled'] ?? true ),
			$logic,
			(array) ( $data['conditions'] ?? [] ),
			(array) ( $data['actions'] ?? [] )
		);
	}

	/**
	 * Serialize back to a plain array for storage.
	 */
	public function to_array(): array {
		return [
			'id'         => $this->id,
			'name'       => $this->name,
			'hook'       => $this->hook,
			'enabled'    => $this->enabled,
			'logic'      => $this->logic,
			'conditions' => $this->conditions,
			'actions'    => $this->actions,
		];
	}
}

