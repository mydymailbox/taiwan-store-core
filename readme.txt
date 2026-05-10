=== Taiwan Store Core ===
Contributors: taiwanstore
Tags: woocommerce, taiwan, checkout, invoice, shipping
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

專業的台灣 WooCommerce 在地化解決方案。具備結帳優化、統編查詢、郵遞區號自動帶入及視覺化規則引擎。

Professional localization solution for WooCommerce in Taiwan. Features checkout optimization, tax ID lookup, postcode auto-fill, and a visual rule engine.

== Description ==

Taiwan Store Core 是專為台灣電商市場設計的專業擴充插件。它提供流暢的在地化結帳體驗，以及強大的商業規則引擎，協助店主輕鬆管理複雜的在地需求。

Taiwan Store Core is a professional extension designed specifically for the Taiwan e-commerce market. It provides a seamless localized checkout experience and a powerful business rule engine to help store owners manage complex local requirements effortlessly.

= 核心功能 Core Features =

* **台灣結帳優化 (Taiwan Checkout Optimization)** — 縣市/鄉鎮市區二級聯動下拉選單，支援 3+2 碼郵遞區號自動帶入。
* **發票資訊整合 (Tax ID Integration)** — 支援公司統編、抬頭、手機條碼、自然人憑證及捐贈碼。
* **智慧統編查詢 (Smart Tax ID Lookup)** — 輸入 8 碼統編自動從官方 API (GCIS) 抓取公司名稱。
* **視覺化規則引擎 (Visual Rule Engine)** — 透過現代化的視覺界面管理支付、運送及購物車規則，無需程式碼。
* **自定義訂單編號 (Custom Order Numbers)** — 專業的流水號格式（例如：前綴 + YYYYMMDD + 流水號）。
* **結帳倒數計時 (Checkout Countdown)** — 透過可自定義的計時器創造急迫感並提升轉換率。
* **行動裝置置底列 (Mobile Sticky Bar)** — 置底的「立即購買」按鈕，提升行動裝置使用者體驗。
* **社群登入 (Social Login)** — 支援 LINE、Google 及 Facebook 一鍵登入。
* **HPOS Ready** — 完整相容 WooCommerce 高效能訂單儲存 (High-Performance Order Storage)。

= 擴充模組 Extension Modules =

透過我們的 Pro 擴充插件解鎖更多功能：

* **電子發票 Pro (E-Invoice Pro)** — 全自動發票開立、預覽、PDF 下載及中獎通知。
* **行銷引擎 (Marketing Engine)** — 滿額贈品、階梯折扣、加價購及快閃倒數。
* **智慧追蹤 (Smart Tracking)** — 自動化物流狀態偵測及 LINE 通知。

== Installation ==

1. 將 `taiwan-store-core` 資料夾上傳至 `/wp-content/plugins/` 目錄。
2. 透過 WordPress 的「外掛」選單啟用。
3. 在 WooCommerce -> 設定 -> 台灣在地化 進行設定。

1. Upload the `taiwan-store-core` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure settings under WooCommerce -> Settings -> Taiwan Store.

== Frequently Asked Questions ==

= 系統需求為何？ (What are the system requirements?) =

需要 PHP 8.1+、WordPress 6.5+ 及 WooCommerce 8.0+。
Requires PHP 8.1+, WordPress 6.5+, and WooCommerce 8.0+.

= 是否相容於其他結帳插件？ (Is it compatible with other checkout plugins?) =

本插件使用標準的 WooCommerce Additional Checkout Fields API，相容於大多數現代佈景主題與插件。若遇到問題，請檢查日誌分頁。
It uses the standard WooCommerce Additional Checkout Fields API, making it compatible with most modern themes and plugins. If you encounter issues, please check the logs tab.

== Changelog ==

= 1.0.4 =
* 優化：根據 WordPress.org 規範將 SweetAlert2 在地化。
* 優化：將規則管理通知升級為 SweetAlert2。
* 修正：移除日誌頁面冗餘的儲存按鈕。
* 修正：清理所有使用者輸入並強化 Nonce 驗證。
* Improvement: Localized SweetAlert2 for WordPress.org compliance.
* Improvement: Upgraded rule management notifications to SweetAlert2.
* Fix: Removed redundant save buttons on log pages.
* Fix: Sanitized all user inputs and improved nonce verification.

= 1.0.3 =
* 新增：社群登入模組 (LINE / Google / Facebook)。
* 新增：結帳倒數計時器。
* 新增：行動裝置置底購買列。
* Added: Social Login module (LINE / Google / Facebook).
* Added: Checkout countdown timer.
* Added: Mobile sticky buy bar.

= 1.0.0 =
* 首次發布 (Initial release).
