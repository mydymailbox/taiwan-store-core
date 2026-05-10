# Security & Performance Checklist

生成時間：2025-05  
版本：1.0.0  
評估方式：靜態分析 + 手動 code review

---

## 安全性

### ✅ S01 — CSRF 保護（Nonce）

**要求：** 所有 admin state-changing 請求都要驗證 nonce。

| 位置 | 驗證方式 |
|---|---|
| `includes/admin/class-rules-ajax.php` `save_rule()` | `check_ajax_referer('wc_tw_core_rules', 'nonce')` |
| `includes/admin/class-rules-ajax.php` `delete_rule()` | `check_ajax_referer('wc_tw_core_rules', 'nonce')` |
| `includes/admin/class-settings-page.php` `save()` | WC Settings API 內建 nonce |

驗證指令：
```
grep -rn "check_ajax_referer\|wp_verify_nonce" includes/admin/
```

---

### ✅ S02 — 授權檢查（Capability）

**要求：** 所有 admin 操作都需要 `manage_woocommerce`。

| 位置 | 驗證方式 |
|---|---|
| `includes/admin/class-rules-ajax.php` `save_rule()` | `current_user_can('manage_woocommerce')` |
| `includes/admin/class-rules-ajax.php` `delete_rule()` | `current_user_can('manage_woocommerce')` |
| WC Settings 頁 | WC 框架內建 capability 保護 |

驗證指令：
```
grep -rn "current_user_can" includes/admin/
```

---

### ✅ S03 — 輸出跳脫（XSS 防護）

**要求：** 所有前台輸出都要 `esc_html`/`esc_attr`/`wp_kses`。

**已套用位置：**
- `includes/modules/checkout-tw/class-locale.php`：所有 `echo` 都用 `esc_attr`/`esc_html`
- `includes/admin/class-rules-ui.php`：後台 HTML 輸出套用 `esc_html`/`esc_attr`
- 規則 action `block_checkout`：`wc_add_notice()` 傳入前套用 `wp_kses_post()`

驗證指令：
```
grep -rn "echo \$" includes/
```
預期只找到套有 `esc_*` 的輸出。

---

### ✅ S04 — 輸入清理（Sanitization）

**要求：** 所有 `$_POST`/`$_GET`/`$_REQUEST` 輸入都要清理。

| 位置 | 清理方式 |
|---|---|
| `includes/admin/class-rules-ajax.php` `sanitize_rule()` | `sanitize_text_field`, `absint`, `floatval`, `in_array` 白名單 |
| `includes/admin/class-rules-ajax.php` `sanitize_condition()` | op 欄位用 `in_array` 白名單驗證 |
| `includes/modules/checkout-tw/class-validation.php` | `isset` 取值後傳入驗證函數 |
| `includes/helpers/class-sanitize.php` | 集中清理工具類別 |

---

### ✅ S05 — SQL 注入防護

**要求：** 不直接撰寫含用戶輸入的 SQL。

本外掛不使用自訂資料表，全部透過：
- `get_option`/`update_option`（WordPress 內建 prepared queries）
- WooCommerce CRUD API（Order, Product meta）

驗證指令：
```
grep -rn "wpdb" includes/
```
預期：無直接 `$wpdb->query` 含用戶輸入。

---

### ✅ S06 — 檔案存取控制

所有 PHP 檔案都有直接存取防護：

```php
if ( ! defined( 'ABSPATH' ) ) exit;
```

驗證指令：
```
grep -rL "defined.*ABSPATH" includes/
```
預期：空結果（所有 .php 都有保護）。

---

### ✅ S07 — 統一編號驗證安全性

`includes/modules/checkout-tw/class-validation.php`：
- 輸入格式驗證（8 碼、數字）在 checksum 計算前先檢查
- 不直接用正規表示式用戶輸入做危險操作
- Server-side 驗證，前端 JS 只是 UX 提示

---

## 效能

### ✅ P01 — 前台 JS/CSS 條件載入

`checkout.js`/`checkout.css` 只在 `is_checkout()` 時載入。

| 位置 | 條件 |
|---|---|
| `includes/modules/checkout-tw/class-module.php` | `if ( is_checkout() )` |

驗證：在 shop/product 頁面查看 page source，不應有 `checkout.js` 載入。

---

### ✅ P02 — Rule Engine 短路最佳化

`includes/rule-engine/class-rule-engine.php`：
- `has_rules(string $hook)` — 無規則時直接 return，不初始化 Context
- Context 所有屬性都 lazy-memoized（只在被 condition 需要時計算）

---

### ✅ P03 — 無不必要的 DB 查詢

- 規則以 `wp_options` 一次取出整個陣列（一次 query）
- `Context::product_ids()` 從已在記憶體的 `WC()->cart->get_cart()` 讀取，不觸發額外 DB query
- `Context::category_ids()` 使用 `wp_get_object_terms()` 加 WP object cache

---

### ✅ P04 — Admin JS 只在規則頁面載入

`includes/admin/class-rules-ui.php`：
- `wp_enqueue_script('wc-tw-core-rules-admin', ...)` 只在 WC Settings → 台灣在地化 tab 的規則 section 執行

---

### ✅ P05 — 無 N+1 查詢

購物車規則評估：
- `product_ids()` 一次取出所有 product ID
- `category_ids()` 一次取出所有商品的分類（使用 `wp_get_object_terms(product_ids)` 批次查詢）

---

## 手動驗證步驟

```bash
# S01: Nonce 確認
grep -rn "check_ajax_referer\|wp_verify_nonce" includes/admin/

# S02: 授權確認
grep -rn "current_user_can" includes/admin/

# S03: 未保護的 echo
grep -rn "echo \$" includes/

# S04: post_meta 直接存取（應無訂單 post meta）
grep -rn "get_post_meta\|update_post_meta" includes/

# S05: 直接 SQL
grep -rn "wpdb->" includes/

# S06: ABSPATH 保護缺失
grep -rL "defined.*ABSPATH" includes/**/*.php
```
