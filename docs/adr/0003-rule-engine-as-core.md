# ADR 0003 — Rule Engine as Core

**狀態：** 採用  
**日期：** 2025-05  
**作者：** wc-tw-core 開發團隊

---

## 背景

外掛需要在多個地方執行「依條件做某事」的邏輯：

- 付款頁面：依條件隱藏付款方式
- 物流計算：依條件隱藏物流方案
- 購物車：依條件阻擋商品加入或結帳

最直覺的方式是在每個 hook callback 裡各自撰寫 if/else 判斷。但這種方式會讓邏輯分散在各處，難以測試和擴充。

---

## 決策

**建立集中的 Rule Engine，所有規則評估都通過同一套機制。**

```
Rule_Engine (Singleton)
├── Condition[]  (可獨立測試的條件判斷)
├── Action[]     (可獨立測試的動作執行)
└── evaluate(hook, context, payload)
```

---

## 理由

### 1. 邏輯集中，易於測試

不需要為每個模組分別撰寫條件判斷邏輯。煙霧測試可以直接建立 Context，呼叫 `evaluate()`，斷言 payload 的變化，而不需要啟動完整的 WooCommerce 環境。

### 2. 條件可跨模組重用

例如 `cart_total` condition 同時用於付款規則和物流規則。若散落在各模組，同樣的驗證邏輯會重複。

### 3. 後台 UI 一致性

規則以相同的 JSON schema 儲存，後台 JavaScript 可以用同一套 UI 元件處理三種規則類型（只有 action 的選項不同）。

### 4. 擴充點清晰

透過 hook `wc_tw_core_register_rule_components`，第三方可以注入自訂 Condition 和 Action，而不需要修改核心程式碼。

---

## 架構細節

### Context（延遲計算）

```php
class Context {
    private ?float $cart_total = null;
    
    public function cart_total(): float {
        if ( null === $this->cart_total ) {
            $this->cart_total = WC()->cart->get_subtotal();
        }
        return $this->cart_total;
    }
}
```

Context 的所有屬性都 lazy-memoized。如果規則完全不用某個值（例如規則只看金額，不看地址），地址查詢就不會發生。

### 短路最佳化

```php
public function evaluate(string $hook, Context $ctx, array &$payload): void {
    if ( ! $this->has_rules($hook) ) return; // 直接跳過
    // ...
}
```

若該 hook 沒有任何規則，完全跳過，不初始化 Context，不做任何 DB 查詢。

### 條件全部 AND

```php
foreach ($rule->conditions as $condition) {
    if ( ! $condition->matches($ctx, $config) ) {
        continue 2; // 任一條件不符，跳過此規則
    }
}
```

v1 只支援 AND 邏輯（所有條件都必須滿足）。OR 邏輯是後續版本的規劃。

---

## 取捨

| | Rule Engine 集中 | 各模組各自實作 |
|---|---|---|
| 測試難易度 | ✅ 可獨立 unit test | ❌ 需要 WC 環境 |
| 代碼重用 | ✅ Condition 跨模組共用 | ❌ 邏輯重複 |
| 學習曲線 | ⚠️ 需理解 Engine 架構 | ✅ 直接看 hook callback |
| 擴充性 | ✅ 清晰 register 擴充點 | ❌ 需 fork 各模組 |
| 初始複雜度 | ❌ 更多 class 和 interface | ✅ 少幾個 class |

對於預計持續維護、有擴充需求的外掛，集中架構的收益超過初始複雜度的成本。

---

## 後續計畫

v1.1 考慮：
- 條件群組（OR 邏輯）：`{operator: 'or', conditions: [...]}`
- 規則優先級（priority 欄位）
- 規則啟用時間段（date range condition）
