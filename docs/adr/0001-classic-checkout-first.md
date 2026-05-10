# ADR 0001 — Classic Checkout First

**狀態：** 採用  
**日期：** 2025-05  
**作者：** wc-tw-core 開發團隊

---

## 背景

WooCommerce 從 8.x 開始推廣 Blocks Checkout（基於 React/Gutenberg）。台灣市場現有商家大多使用 Classic Checkout（shortcode），且大量現有外掛的 hooks 基於 Classic Checkout 架構。

---

## 決策

**v1 僅支援 Classic Checkout。**

結帳頁使用 `[woocommerce_checkout]` shortcode，對應 post ID=8。

Blocks Checkout 支援延至 v1.1。

---

## 理由

### 1. Hooks 成熟度

Classic Checkout 提供大量已穩定多年的 hooks：

```php
// 欄位注入
add_action('woocommerce_before_checkout_billing_form', ...)
add_filter('woocommerce_checkout_fields', ...)
add_action('woocommerce_after_checkout_billing_form', ...)

// 驗證
add_action('woocommerce_checkout_process', ...)
add_action('woocommerce_after_checkout_validation', ...)

// 儲存
add_action('woocommerce_checkout_update_order_meta', ...)
```

Blocks Checkout 的對應 hooks 在 WC 9.x 仍處於 `@experimental` 狀態。

### 2. 台灣縣市級聯的 JS 實作

Classic Checkout 允許直接在表單 DOM 操作，`checkout.js` 監聽 `change` 事件即可更新鄉鎮市區與郵遞區號。

Blocks Checkout 使用 React 管理表單狀態，需要實作 `registerCheckoutFilters` 或 `@woocommerce/blocks-checkout` slot/fill API，複雜度顯著更高。

### 3. 外掛衝突偵測

目前台灣最常用的物流/金流外掛（綠界、藍新）多數仍依賴 Classic hooks，v1 確保相容性。

### 4. 開發時間與穩定性

v1 的目標是把核心功能做穩，而不是追求 Blocks 相容性。

---

## 取捨

| | Classic Checkout | Blocks Checkout |
|---|---|---|
| Hook 成熟度 | ✅ 多年穩定 | ⚠️ 部分 experimental |
| 縣市級聯 JS 實作 | ✅ 直接 DOM | ❌ React 狀態管理 |
| 台灣金流外掛相容性 | ✅ 高 | ⚠️ 各家差異 |
| 未來趨勢 | ⚠️ WC 官方推 Blocks | ✅ 長期方向 |

---

## 後續計畫

v1.1 計畫：
- 實作 `woocommerce/checkout` block 的 `additionalFields` API
- 縣市選單改用 `registerCheckoutFilters` + React component
- 郵遞區號用 `@woocommerce/blocks-checkout` `ExperimentalOrderShippingPackages` slot
