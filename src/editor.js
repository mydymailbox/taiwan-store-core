/**
 * WC TW Core - 區塊後台編輯器 (Gutenberg) 入口
 * 這裡將編譯出 build/editor.js
 */
import { registerBlockType } from '@wordpress/blocks';
import blockJson from './invoice-block/block.json';

// 在後台編輯器中畫出一個示意圖 (Placeholder)
const Edit = () => {
    return (
        <div style={{ padding: '15px', background: '#f8f9fa', border: '1px dashed #ccc', borderRadius: '4px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px' }}>
                <span className="dashicons dashicons-tickets-alt" style={{ color: '#e74c3c' }}></span>
                <strong style={{ fontSize: '14px' }}>台灣發票資訊區塊 (Invoice Fields)</strong>
            </div>
            <p style={{ margin: 0, fontSize: '13px', color: '#666' }}>
                這裡是在結帳頁面前台顯示「發票類型」與「統一編號」的地方。此預覽僅供後台排版參考。
            </p>
        </div>
    );
};

// 由於我們是動態區塊 (Dynamic Block)，而且是在結帳流程內，
// 前端實際渲染是由 React (src/index.js) 接手，所以 save 回傳 null。
const Save = () => null;

registerBlockType( blockJson.name, {
    ...blockJson,
    edit: Edit,
    save: Save,
} );

console.log('✅ WC TW Core Editor block registered.');
