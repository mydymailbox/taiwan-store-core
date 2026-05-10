# WC TW Core — Development Book

## 架構概覽

```
wc-tw-core/
├── wc-tw-core.php          # 主外掛檔案：bootstrap、HPOS 宣告、WC 相依檢查
├── uninstall.php           # 刪除時清除所有 wc_tw_core_* options
├── readme.txt              # WP.org 格式
├── languages/
│   └── wc-tw-core.pot      # i18n POT 模板
├── assets/
│   ├── css/checkout.css    # 結帳頁樣式（只在 is_checkout() 載入）
│   ├── css/rules-admin.css # 規則編輯器後台樣式
│   ├── js/checkout.js      # 縣市→鄉鎮市區→郵遞區號邏輯
│   └── js/rules-admin.js   # 規則編輯器前端（jQuery）
├── includes/
│   ├── class-plugin.php    # Singleton bootstrap + autoloader
│   ├── interface-module.php
│   ├── admin/
│   │   ├── class-settings-page.php
│   │   ├── class-rules-ui.php
│   │   └── class-rules-ajax.php
│   ├── helpers/
│   │   ├── class-sanitize.php
│   │   └── class-nonce.php
│   ├── rule-engine/
│   │   ├── class-rule-engine.php   # Singleton evaluator
│   │   ├── class-rule.php          # Value object
│   │   ├── class-context.php       # Lazy-memoized cart/customer context
│   │   ├── conditions/             # 8 條件類別
│   │   └── actions/                # 3 動作類別
│   └── modules/
│       ├── checkout-tw/            # 台灣結帳在地化
│       ├── payment-rules/          # 付款規則
│       ├── shipping-rules/         # 物流規則
│       ├── cart-rules/             # 購物車規則
│       ├── performance/            # 資產載入最佳化
│       └── logs/                   # WC Logger 包裝
└── docs/
    ├── development-book.md  ← 本檔案
    ├── security-performance-checklist.md
    └── adr/
```

---

## 模組系統

每個模組實作 `WC_TW_Core\Module` interface：

```php
interface Module {
    public function id(): string;       // 唯一識別符
    public function boot(): void;       // 掛載 WP/WC hooks
    public function is_admin_only(): bool;
}
```

模組在 `Plugin::boot()` 中依序啟動，所有模組都在 `plugins_loaded` priority 20 後執行（WC 已就緒）。

---

## Rule Engine

### 設計理念

Rule Engine 是核心，三個模組（Payment/Shipping/Cart）都使用同一套評估邏輯。

```
Rule = {conditions: Condition[], actions: Action[]}
條件全部滿足（AND）→ 執行所有 Action
```

### 使用方式

```php
$engine = Rule_Engine::instance();
$ctx    = new Context();
$payload = &$gateways; // 或 $rates、[]
$engine->evaluate('payment', $ctx, $payload);
```

### 短路最佳化

`has_rules(string $hook): bool` — 如果該 hook 沒有任何規則，直接跳過避免初始化 Context。

### 規則儲存格式

```json
// option: wc_tw_core_rules_payment
[
  {
    "id": "uuid-v4",
    "name": "隱藏 COD",
    "hook": "payment",
    "enabled": true,
    "conditions": [
      {"type": "cart_total", "config": {"op": "lte", "amount": 100}}
    ],
    "actions": [
      {"type": "hide_payment", "config": {"gateways": ["cod"]}}
    ]
  }
]
```

---

## 內建 Conditions

| Class | id | Config |
|---|---|---|
| `Cart_Total` | `cart_total` | `op` (gte/lte/gt/lt/eq), `amount` (float) |
| `Product` | `product` | `op` (in/not_in), `products` (int[]) |
| `Category` | `category` | `op` (contains/not_contains), `categories` (int[]) |
| `Address` | `address` | `field` (country/state), `op` (in/not_in), `values` (string[]) |
| `Payment_Method` | `payment_method` | `op` (in/not_in), `methods` (string[]) |
| `Shipping_Method` | `shipping_method` | `op` (in/not_in), `methods` (string[]) |
| `Product_In_Cart` | `product_in_cart` | `op` (all/any), `products` (int[]) |
| `Max_Qty` | `max_qty` | `max` (int), `products` (int[]) |

---

## 內建 Actions

| Class | id | Config | Payload 操作 |
|---|---|---|---|
| `Hide_Payment` | `hide_payment` | `gateways` (string[]) | `unset($payload[$id])` |
| `Hide_Shipping` | `hide_shipping` | `methods` (string[]) | `unset($payload[$rate_id])` |
| `Block_Checkout` | `block_checkout` | `message` (string) | `$payload['notices'][] = $message` |

---

## 如何新增自訂 Condition

1. 建立 class 實作 `WC_TW_Core\Rule_Engine\Condition`：
   ```php
   class My_Condition implements Condition {
       public function id(): string { return 'my_condition'; }
       public function matches(Context $ctx, array $config): bool {
           // 取值、判斷、回傳 bool
       }
   }
   ```
2. 透過 hook 註冊：
   ```php
   add_action('wc_tw_core_register_rule_components', function($engine) {
       $engine->register_condition(new My_Condition());
   });
   ```
3. 在 `rules-admin.js` 的 `HOOK_CONDITIONS` 加入對應 UI 定義。

---

## 如何新增自訂 Action

同 Condition 流程，但實作 `Action` interface 並呼叫 `$engine->register_action()`。

---

## Autoloader

PSR-4 kebab-case 對應：

```
WC_TW_Core\Modules\Payment_Rules\Module
→ includes/modules/payment-rules/class-module.php
```

轉換規則：
- namespace separator `\` → `/`
- CamelCase → kebab-case（`_` 也轉 `-`）
- 加上 `class-` prefix

---

## Context — 可用方法

| 方法 | 說明 | Memoized |
|---|---|---|
| `cart_total(): float` | 購物車小計（不含運費） | ✅ |
| `shipping_country(): string` | 配送國家代碼 | ✅ |
| `shipping_state(): string` | 配送縣市代碼（ISO） | ✅ |
| `product_ids(): array` | 購物車內所有商品 ID | ✅ |
| `category_ids(): array` | 購物車商品的所有分類 ID | ✅ |
| `chosen_shipping_methods(): array` | 已選物流方式 rate_id | ✅ |
| `set_adding_product(int $id, int $qty)` | 加入購物車驗證時注入 | — |
| `set_package(array $package)` | 物流規則評估時注入 | — |

---

## 台灣縣市資料

`includes/modules/checkout-tw/data/`：

- `tw-states.php`：`['TPE' => '臺北市', 'NWT' => '新北市', ...]`（22 縣市）
- `tw-districts.php`：`['TPE' => ['中正區', '大同區', ...], ...]`
- `tw-postcodes.php`：`['TPE' => ['中正區' => '100', ...], ...]`

ISO 3166-2:TW 代碼映射：TPE, NWT, TAO, TXG, TNN, KHH, KEE, HSZ, HSQ, MIA, CHA, NAN, YUN, CYI, CYQ, IUH, TTT, HUA, ILA, PEN, KIN, LIE

---

## 安全性注意事項

- 所有 admin AJAX 都驗證 `wc_tw_core_rules` nonce + `manage_woocommerce` capability
- 所有用戶輸入都透過 `Helpers\Sanitize` 或 `sanitize_*` 函數處理
- 前台輸出都套用 `esc_html`/`esc_attr`
- 不使用 `get_post_meta`/`update_post_meta` 操作訂單（HPOS 相容）
- 不直接 `$wpdb->query()`（無自訂 SQL）

---

## 已知限制（v1）

1. **購物車頁使用 WC Blocks**：`woocommerce_check_cart_items` 不在 Block 購物車觸發，`validate_add_to_cart` 仍可攔截加入購物車動作。
2. **物流規則快取**：WC 快取物流費率，規則變更後需等下次購物車重新計算才生效。
3. **Rule Engine singleton 快取**：同一 PHP request 內規則只載入一次（`rules_loaded` flag），AJAX 規則更新後下一個 request 才生效。
