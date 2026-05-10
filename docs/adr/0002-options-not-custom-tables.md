# ADR 0002 — Options, Not Custom Tables

**狀態：** 採用  
**日期：** 2025-05  
**作者：** wc-tw-core 開發團隊

---

## 背景

外掛需要持久化三類規則（付款規則、物流規則、購物車規則）以及全域設定。儲存方案的選擇影響部署複雜度、資料存取方式與升級成本。

---

## 決策

**使用 `wp_options` 儲存所有規則與設定。不建立自訂資料表。**

| Option key | 用途 |
|---|---|
| `wc_tw_core_options` | 全域設定（啟用 debug log 等） |
| `wc_tw_core_rules_payment` | 付款規則陣列（JSON 序列化） |
| `wc_tw_core_rules_shipping` | 物流規則陣列 |
| `wc_tw_core_rules_cart` | 購物車規則陣列 |

---

## 理由

### 1. 規則數量有限

台灣商家實際使用的規則通常少於 20 條，以 JSON 序列化存在 options 中完全沒有效能問題（一次 `get_option` 即取出所有規則）。

自訂資料表在規則數量達到數千條以上才有明顯效益，對本外掛的使用場景是過度設計。

### 2. 零遷移成本

`wp_options` 方案：
- 安裝時不需執行 `dbDelta()`
- 升級版本不需處理資料庫 schema 遷移
- 移除外掛時 `uninstall.php` 只需 `delete_option()` 幾個 key

自訂資料表方案會引入：
- `register_activation_hook` + `dbDelta()`
- 版本追蹤（`wc_tw_core_db_version` option）
- 升級邏輯（`ALTER TABLE` 或資料遷移）

### 3. WP.org 相容性

部分 WordPress 主機的資料庫帳號沒有 `CREATE TABLE` 權限。使用 options 避免安裝失敗問題。

### 4. 備份與遷移友善

`wp_options` 在任何標準 WordPress 備份工具（UpdraftPlus、All-in-One WP Migration）都會被包含。

---

## 取捨

| | wp_options | Custom Tables |
|---|---|---|
| 安裝複雜度 | ✅ 零成本 | ❌ dbDelta + schema |
| 查詢彈性 | ⚠️ 全取再 PHP 篩選 | ✅ SQL WHERE |
| 規模上限 | ⚠️ ~1000 條開始有感 | ✅ 無上限 |
| 遷移困難度 | ✅ 低 | ❌ 需 schema 版本管理 |
| WP.org 相容 | ✅ | ⚠️ 需 CREATE TABLE 權限 |

---

## 後續計畫

若未來需求超出 options 適用範圍（例如：規則數量超過 100 條、需要複雜 query），可以評估遷移至自訂表，並透過 `dbDelta` 在升級時建立。屆時 `uninstall.php` 也需要 `DROP TABLE`。
