/**
 * WC TW Core - 區塊結帳前端入口
 * 這裡將編譯出 build/index.js
 */
import { InvoiceBlock } from './invoice-block';

console.log('📦 WC TW Core React app initialized.');

const settings = window.wcSettings ? window.wcSettings.getSetting('wc-tw-core_data', {}) : {};
console.log('⚙️ Server Settings:', settings);

if ( window.wc && window.wc.blocksCheckout ) {
    const { registerCheckoutBlock } = window.wc.blocksCheckout;
    
    if ( typeof registerCheckoutBlock === 'function' && settings.is_tax_id_enabled === 'yes' ) {
        // 註冊自訂的結帳區塊。
        // 注意：註冊後，站長需要進入「外觀 > 編輯器 > 結帳頁面」將此區塊拖曳到畫面上。
        registerCheckoutBlock({
            name: 'wc-tw-core/invoice-fields',
            metadata: {
                name: 'wc-tw-core/invoice-fields',
                parent: [ 'woocommerce/checkout-contact-information-block' ],
            },
            component: InvoiceBlock
        });
        
        console.log('✅ 發票 React 區塊已成功註冊到 WooCommerce');
    }
}
