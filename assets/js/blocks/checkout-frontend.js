/**
 * WC TW Core - Blocks Checkout Frontend (開發佔位符)
 * 
 * 目前這是一個原生的 JS 檔案作為佔位符。
 * 未來您需要在這裡（或是 src 目錄下）撰寫 React 程式碼，並使用 @wordpress/scripts 將其編譯。
 */

console.log('📦 WC TW Core: 區塊結帳前端腳本已載入 (Blocks Checkout detected)!');

// 測試取得從 PHP 傳過來的設定資料 (class-blocks-integration.php 中定義的資料)
const settings = window.wcSettings ? window.wcSettings.getSetting('wc-tw-core_data', {}) : {};
console.log('⚙️ WC TW Core Settings from PHP:', settings);

/**
 * 未來實作指引：
 * 您會在這裡使用 `@woocommerce/blocks-registry` 提供的 API：
 * 
 * import { registerCheckoutBlock } from '@woocommerce/blocks-registry';
 * import MyCustomTaxIdBlock from './components/tax-id-block';
 * 
 * registerCheckoutBlock({
 *     name: 'wc-tw-core/tax-id',
 *     component: MyCustomTaxIdBlock
 * });
 */
