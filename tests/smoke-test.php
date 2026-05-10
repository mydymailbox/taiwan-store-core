<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI test file, no HTTP output
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- standalone test runner globals
// phpcs:disable WordPress.PHP.DevelopmentFunctions -- var_export allowed in test assertions
// phpcs:disable PluginCheck.CodeAnalysis.NoDirectFileAccess -- this file bootstraps ABSPATH intentionally
/**
 * WC TW Core ??Smoke Tests
 *
 * ??砍???Ｘ芋撘?皜祈岫 Rule Engine ?詨??摩嚗??閬?WordPress ??WooCommerce ?啣??? *
 * ?瑁??孵?嚗? *   php tests/smoke-test.php
 *
 * ??頛詨嚗??PASS
 */

// ??? ?撠? stub ????????????????????????????????????????????????????????????

// 霈? defined('ABSPATH') || exit; ??獢隞亥???if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

// WordPress helper stubs
function wp_generate_uuid4(): string {
	return sprintf(
		'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff ),
		random_int( 0, 0x0fff ) | 0x4000,
		random_int( 0, 0x3fff ) | 0x8000,
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff )
	);
}

// WC Logger stub嚗?撖阡?撖急?嚗?if ( ! class_exists( 'WC_Logger' ) ) {
	class WC_Logger {
		public function debug( string $msg, array $ctx = [] ): void {}
		public function info( string $msg, array $ctx = [] ): void {}
		public function warning( string $msg, array $ctx = [] ): void {}
		public function error( string $msg, array $ctx = [] ): void {}
	}
}

// ??? 頛 Rule Engine ????????????????????????????????????????????????????????

$base = __DIR__ . '/../includes/rule-engine/';

require_once $base . 'interface-condition.php';
require_once $base . 'interface-action.php';
require_once $base . 'class-context.php';
require_once $base . 'class-rule.php';
require_once $base . 'class-rule-engine.php';
require_once $base . 'conditions/class-cart-total.php';
require_once $base . 'conditions/class-product-in-cart.php';
require_once $base . 'conditions/class-max-qty.php';
require_once $base . 'conditions/class-address.php';
require_once $base . 'conditions/class-payment-method.php';
require_once $base . 'conditions/class-shipping-method.php';
require_once $base . 'conditions/class-product.php';
require_once $base . 'conditions/class-category.php';
require_once $base . 'actions/class-hide-payment.php';
require_once $base . 'actions/class-hide-shipping.php';
require_once $base . 'actions/class-block-checkout.php';

use Taiwan_Store_Core\Rule_Engine\Rule_Engine;
use Taiwan_Store_Core\Rule_Engine\Rule;
use Taiwan_Store_Core\Rule_Engine\Context;

// ??? 皜祈岫獢嚗凝?????????????????????????????????????????????????????????

$tests_run    = 0;
$tests_passed = 0;
$tests_failed = 0;

function assert_true( string $test_name, bool $value ): void {
	global $tests_run, $tests_passed, $tests_failed;
	$tests_run++;
	if ( $value ) {
		$tests_passed++;
		echo "  \033[32m??PASS\033[0m  {$test_name}\n";
	} else {
		$tests_failed++;
		echo "  \033[31m??FAIL\033[0m  {$test_name}\n";
	}
}

function assert_false( string $test_name, bool $value ): void {
	assert_true( $test_name, ! $value );
}

function assert_equals( string $test_name, $expected, $actual ): void {
	global $tests_run, $tests_passed, $tests_failed;
	$tests_run++;
	if ( $expected === $actual ) {
		$tests_passed++;
		echo "  \033[32m??PASS\033[0m  {$test_name}\n";
	} else {
		$tests_failed++;
		$e = var_export( $expected, true );
		$a = var_export( $actual, true );
		echo "  \033[31m??FAIL\033[0m  {$test_name} (expected {$e}, got {$a})\n";
	}
}

function test_section( string $name ): void {
	echo "\n\033[1m{$name}\033[0m\n";
	echo str_repeat( '-', strlen( $name ) ) . "\n";
}

// ??? ?冽撱箇?憛怠? cart_total ??Context helper ??????????????????????????????

function make_context_with_total( float $total ): Context {
	$ctx = new Context();
	// ??reflection ?湔撖怠 cache嚗葫閰血??剁?蝜? WC() 靘陷嚗?	$r = new ReflectionProperty( Context::class, 'cache' );
	$r->setAccessible( true );
	$r->setValue( $ctx, [ 'cart_total' => $total ] );
	return $ctx;
}

function make_context_with_state( string $state ): Context {
	$ctx = new Context();
	$r   = new ReflectionProperty( Context::class, 'cache' );
	$r->setAccessible( true );
	$r->setValue( $ctx, [ 'shipping_country' => 'TW', 'shipping_state' => $state ] );
	return $ctx;
}

function make_context_with_products( array $product_ids ): Context {
	$ctx = new Context();
	$r   = new ReflectionProperty( Context::class, 'cache' );
	$r->setAccessible( true );
	$r->setValue( $ctx, [ 'product_ids' => $product_ids, 'category_ids' => [] ] );
	return $ctx;
}

// ??? ????Engine ???????????????????????????????????????????????????????????

$engine = Rule_Engine::instance();
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Cart_Total() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Product_In_Cart() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Max_Qty() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Address() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Payment_Method() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Shipping_Method() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Product() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Category() );
$engine->register_action( new Taiwan_Store_Core\Rule_Engine\Actions\Hide_Payment() );
$engine->register_action( new Taiwan_Store_Core\Rule_Engine\Actions\Hide_Shipping() );
$engine->register_action( new Taiwan_Store_Core\Rule_Engine\Actions\Block_Checkout() );

// ??? T01: Cart_Total Condition ???????????????????????????????????????????????

test_section( 'T01 ??Cart_Total Condition' );

$cond = new Taiwan_Store_Core\Rule_Engine\Conditions\Cart_Total();

$ctx_500 = make_context_with_total( 500.0 );
$ctx_100 = make_context_with_total( 100.0 );
$ctx_100_exact = make_context_with_total( 100.0 );

assert_true(  'gte: 500 >= 500',  $cond->matches( $ctx_500, [ 'op' => 'gte', 'amount' => 500.0 ] ) );
assert_true(  'gte: 500 >= 100',  $cond->matches( $ctx_500, [ 'op' => 'gte', 'amount' => 100.0 ] ) );
assert_false( 'gte: 100 >= 500',  $cond->matches( $ctx_100, [ 'op' => 'gte', 'amount' => 500.0 ] ) );
assert_true(  'lte: 100 <= 500',  $cond->matches( $ctx_100, [ 'op' => 'lte', 'amount' => 500.0 ] ) );
assert_false( 'lte: 500 <= 100',  $cond->matches( $ctx_500, [ 'op' => 'lte', 'amount' => 100.0 ] ) );
assert_true(  'gt: 500 > 100',   $cond->matches( $ctx_500, [ 'op' => 'gt',  'amount' => 100.0 ] ) );
assert_false( 'gt: 100 > 100',   $cond->matches( $ctx_100, [ 'op' => 'gt',  'amount' => 100.0 ] ) );
assert_true(  'lt: 100 < 500',   $cond->matches( $ctx_100, [ 'op' => 'lt',  'amount' => 500.0 ] ) );
assert_false( 'lt: 500 < 100',   $cond->matches( $ctx_500, [ 'op' => 'lt',  'amount' => 100.0 ] ) );
assert_true(  'eq: 100 == 100',  $cond->matches( $ctx_100_exact, [ 'op' => 'eq', 'amount' => 100.0 ] ) );
assert_false( 'eq: 100 != 500',  $cond->matches( $ctx_100, [ 'op' => 'eq', 'amount' => 500.0 ] ) );

// 蝚西??澆?銋??舀嚗?銝摰對?
assert_true(  'symbol >=: 500 >= 100', $cond->matches( $ctx_500, [ 'op' => '>=', 'amount' => 100.0 ] ) );
assert_true(  'symbol <=: 100 <= 500', $cond->matches( $ctx_100, [ 'op' => '<=', 'amount' => 500.0 ] ) );

// ??? T02: Address Condition ??????????????????????????????????????????????????

test_section( 'T02 ??Address Condition' );

$addr_cond  = new Taiwan_Store_Core\Rule_Engine\Conditions\Address();
$ctx_taipei = make_context_with_state( 'TPE' );

assert_true(
	'state in [TPE, NWT]',
	$addr_cond->matches( $ctx_taipei, [ 'field' => 'state', 'op' => 'in', 'values' => [ 'TPE', 'NWT' ] ] )
);
assert_false(
	'state not in [TPE, NWT] ??should fail for TPE',
	$addr_cond->matches( $ctx_taipei, [ 'field' => 'state', 'op' => 'not_in', 'values' => [ 'TPE', 'NWT' ] ] )
);

// ??? T03: Rule Engine evaluate ??hide_payment ????????????????????????????????

test_section( 'T03 ??Rule_Engine evaluate: hide_payment when total >= 1000' );

// ??瘜典閬?嚗???get_option嚗?$rule_data = [
	'id'         => 'test-rule-1',
	'name'       => '????000 ?梯? COD',
	'hook'       => 'payment',
	'enabled'    => true,
	'conditions' => [
		[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 1000.0 ] ],
	],
	'actions' => [
		[ 'type' => 'hide_payment', 'config' => [ 'gateways' => [ 'cod' ] ] ],
	],
];

// ?湔??reflection 瘜典閬???engine嚗??get_option 靘陷
$rules_prop = new ReflectionProperty( Rule_Engine::class, 'rules' );
$rules_prop->setAccessible( true );
$rules_loaded_prop = new ReflectionProperty( Rule_Engine::class, 'rules_loaded' );
$rules_loaded_prop->setAccessible( true );

$rules_loaded_prop->setValue( $engine, true );
$rules_prop->setValue( $engine, [
	'payment' => [ Rule::from_array( $rule_data ) ],
	'shipping' => [],
	'cart'     => [],
] );

// 鞈潛頠?= 1500嚗?閰脰孛?潘?
$ctx_high = make_context_with_total( 1500.0 );
$gateways = [ 'cod' => 'Cash on Delivery', 'bacs' => 'Bank Transfer' ];
$engine->evaluate( 'payment', $ctx_high, $gateways );
assert_false( 'cod 鋡恍??銝 payload嚗?, array_key_exists( 'cod', $gateways ) );
assert_true(  'bacs 隞嚗鋡恍??', array_key_exists( 'bacs', $gateways ) );

// 鞈潛頠?= 500嚗?閫貊嚗?$ctx_low = make_context_with_total( 500.0 );
$gateways2 = [ 'cod' => 'Cash on Delivery', 'bacs' => 'Bank Transfer' ];
$engine->evaluate( 'payment', $ctx_low, $gateways2 );
assert_true(  'cod ?芾◤?梯?嚗?憿?頞喉?', array_key_exists( 'cod', $gateways2 ) );

// ??? T04: Rule Engine evaluate ??block_checkout ??????????????????????????????

test_section( 'T04 ??Rule_Engine evaluate: block_checkout' );

$rule_block = [
	'id'         => 'test-rule-block',
	'name'       => '鞈潛頠?>= 5000 ??撣?,
	'hook'       => 'cart',
	'enabled'    => true,
	'conditions' => [
		[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 5000.0 ] ],
	],
	'actions' => [
		[ 'type' => 'block_checkout', 'config' => [ 'message' => '頞???' ] ],
	],
];

$rules_prop->setValue( $engine, [
	'payment' => [],
	'shipping' => [],
	'cart'     => [ Rule::from_array( $rule_block ) ],
] );

$ctx_high_cart = make_context_with_total( 6000.0 );
$payload = [ 'notices' => [] ];
$engine->evaluate( 'cart', $ctx_high_cart, $payload );
assert_true( 'block_checkout notice ?箇', count( $payload['notices'] ) > 0 );
assert_true( 'notice ?閮', strpos( $payload['notices'][0] ?? '', '頞???' ) !== false );

// ??? T05: Short-circuit ?雿喳? ???????????????????????????????????????????????

test_section( 'T05 ??Short-circuit when no rules' );

$rules_prop->setValue( $engine, [
	'payment'  => [],
	'shipping' => [],
	'cart'     => [],
] );

assert_false( 'payment has_rules ? false', $engine->has_rules( 'payment' ) );
assert_false( 'shipping has_rules ? false', $engine->has_rules( 'shipping' ) );
assert_false( 'cart has_rules ? false', $engine->has_rules( 'cart' ) );

// ??? T06: Cart_Total ?身 op ??gte ?????????????????????????????????????????

test_section( 'T06 ??Cart_Total ?芣?摰?op ??閮?gte' );

$cond_def = new Taiwan_Store_Core\Rule_Engine\Conditions\Cart_Total();
$ctx_200 = make_context_with_total( 200.0 );
// 瘝? op 甈?嚗?閮?gte嚗?00 >= 100 ??true
assert_true(
	'??op ?身 gte: 200 >= 100',
	$cond_def->matches( $ctx_200, [ 'amount' => 100.0 ] )
);

// ??? 蝯??? ????????????????????????????????????????????????????????????????

echo "\n" . str_repeat( '=', 50 ) . "\n";
echo "蝯?: {$tests_passed}/{$tests_run} ??";
if ( $tests_failed > 0 ) {
	echo "嚗033[31m{$tests_failed} 憭望?\033[0m";
} else {
	echo "嚗033[32m?券??\033[0m";
}
echo "\n" . str_repeat( '=', 50 ) . "\n";

exit( $tests_failed > 0 ? 1 : 0 );

