/* WC TW Core — Rules Admin UI */
(function ($) {
    'use strict';

    const D = window.WCTWRulesData;
    if (!D) return;

    const hook = D.hook;
    let rules = JSON.parse(JSON.stringify(D.rules || []));
    let editingRule = null;

    const HOOK_TITLE = { payment: '付款規則', shipping: '運費規則', cart: '購物車規則', marketing: '行銷活動' };
    const HOOK_ICON  = { payment: 'dashicons-money-alt', shipping: 'dashicons-car', cart: 'dashicons-cart', marketing: 'dashicons-megaphone' };

    const COND_LABELS = {
        address:          '地址 / 縣市',
        cart_total:       '購物車金額',
        category:         '商品分類',
        payment_method:   '付款方式',
        product:          '商品',
        shipping_method:  '配送方式',
        address_mismatch: '帳單／寄送地址不一致',
        order_frequency:  '近期訂單數（防重複下單）',
        user_role:        '會員身分 (User Role)',
        first_purchase:   '首購限定',
        time_range:       '快閃時間限制',
        cart_item_count:  '購物車滿件數',
        days_of_week:     '指定星期幾 (週一~週日)',
    };
    const COND_ICONS = {
        address:          'dashicons-location',
        cart_total:       'dashicons-calculator',
        category:         'dashicons-category',
        payment_method:   'dashicons-money-alt',
        product:          'dashicons-products',
        shipping_method:  'dashicons-car',
        address_mismatch: 'dashicons-warning',
        order_frequency:  'dashicons-backup',
        user_role:        'dashicons-groups',
        first_purchase:   'dashicons-smiley',
        time_range:       'dashicons-clock',
        cart_item_count:  'dashicons-cart',
        days_of_week:     'dashicons-calendar-alt',
    };
    const COND_DESCS = {
        address:          '根據收件地址（縣市/國家）過濾。',
        cart_total:       '檢查購物車商品總金額是否達標。',
        category:         '購物車中是否包含特定分類的商品。',
        payment_method:   '根據顧客選擇的付款方式。',
        product:          '購物車中是否包含特定商品。',
        shipping_method:  '根據顧客選擇的配送方式。',
        address_mismatch: '比對帳單與收件地址是否不同。',
        order_frequency:  '檢查近期下單次數以防止重複下單。',
        user_role:        '限定特定會員等級（如 VIP）才生效。',
        first_purchase:   '僅限從未在商店下單過的新客人。',
        time_range:       '設定促銷的起始與結束日期時間。',
        cart_item_count:  '檢查購物車內的總件數或特定類別件數。',
        days_of_week:     '限定每週的星期幾（如週末）才生效。',
    };
    const ACTION_LABELS = {
        hide_payment:   '隱藏付款方式',
        hide_shipping:  '隱藏配送方式',
        block_checkout: '阻止結帳',
        apply_discount: '滿額折扣',
        add_free_gift:  '滿額贈品',
        free_shipping:  '免運費',
        cart_progress:  '購物車進度提示',
        bundle_discount: '組合優惠 (任選 X 件)',
        addon_deal:      '加價購優惠',
        flash_sale_countdown: '快閃倒數計時',
    };
    const ACTION_DESCS = {
        hide_payment:   '在結帳頁隱藏特定的付款方式。',
        hide_shipping:  '在結帳頁隱藏特定的配送方式。',
        block_checkout: '顯示錯誤訊息並阻止顧客完成結帳。',
        apply_discount: '給予固定金額或比例的購物車折扣。',
        add_free_gift:  '自動將特定贈品加入購物車（$0）。',
        free_shipping:  '將所有運費金額歸零。',
        cart_progress:  '在購物車顯示「還差 $X 免運」進度條。',
        bundle_discount: '實作「任選 3 件 $999」或「2 件 88 折」。',
        addon_deal:      '滿足條件時顯示特價加購商品的區塊。',
        flash_sale_countdown: '顯示動態倒數計時器以營造急迫感。',
    };
    const ACTION_ICONS = {
        hide_payment:   'dashicons-hidden',
        hide_shipping:  'dashicons-hidden',
        block_checkout: 'dashicons-warning',
        apply_discount: 'dashicons-tickets-alt',
        add_free_gift:  'dashicons-cart',
        free_shipping:  'dashicons-car',
        cart_progress:  'dashicons-performance',
        bundle_discount: 'dashicons-grid-view',
        addon_deal:      'dashicons-plus-alt',
        flash_sale_countdown: 'dashicons-clock',
    };

    const HOOK_CONDITIONS = {
        payment:  ['address', 'cart_total', 'category', 'product', 'address_mismatch', 'order_frequency'],
        shipping: ['address', 'cart_total', 'category', 'payment_method', 'product', 'address_mismatch'],
        cart:     ['cart_total', 'category', 'product', 'address_mismatch', 'order_frequency'],
        marketing:['cart_total', 'cart_item_count', 'category', 'product', 'payment_method', 'shipping_method', 'user_role', 'first_purchase', 'time_range', 'days_of_week'],
    };
    const HOOK_ACTIONS = {
        payment:  ['hide_payment',  'block_checkout'],
        shipping: ['hide_shipping', 'block_checkout'],
        cart:     ['block_checkout'],
        marketing:['apply_discount', 'add_free_gift', 'free_shipping', 'cart_progress', 'bundle_discount', 'addon_deal', 'flash_sale_countdown'],
    };

    // ── Helpers ──────────────────────────────────────────────────────────────
    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function toast(msg, type) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        Toast.fire({
            icon: type || 'success',
            title: msg
        });
    }

    // ── Main render ──────────────────────────────────────────────────────────
    function render() {
        const title = HOOK_TITLE[hook] || hook;
        const icon  = HOOK_ICON[hook]  || 'dashicons-admin-settings';

        let listHtml;
        if (rules.length) {
            listHtml = '<div class="wctw-rules-list">' +
                rules.map((r, i) => renderCard(r, i)).join('') +
                '</div>';
        } else {
            listHtml = `
                <div class="wctw-empty-state">
                    <span class="dashicons ${icon}"></span>
                    <p>尚無任何規則</p>
                    <p style="font-size:12px;color:#aaa;margin-bottom:20px">點擊右上角「新增規則」開始設定條件與動作${(D.samples||[]).length ? '，或先匯入範例規則作為起手式' : ''}</p>
                    ${(D.samples||[]).length ? `<button type="button" class="wctw-btn-secondary" id="wctw-empty-samples-btn">
                        <span class="dashicons dashicons-download"></span>載入範例規則
                    </button>` : ''}
                </div>`;
        }

        $('#wc-tw-rules-app').html(`
            <div class="wctw-page-header">
                <h2>
                    <span class="dashicons ${icon}"></span>
                    ${esc(title)}
                    <span class="wctw-badge">${rules.length}</span>
                </h2>
                ${(D.samples||[]).length ? `<button type="button" class="wctw-btn-secondary" id="wctw-samples-btn" style="margin-right:8px">
                    <span class="dashicons dashicons-download"></span>載入範例
                </button>` : ''}
                <button type="button" class="wctw-btn-primary" id="wctw-add-btn">
                    <span class="dashicons dashicons-plus-alt2"></span>新增規則
                </button>
            </div>
            ${listHtml}
        `);

        $('#wctw-add-btn').on('click', () => openModal(null));
        $('#wctw-samples-btn, #wctw-empty-samples-btn').on('click', openSamplesPicker);
        $('.wctw-edit-btn').on('click', function () { openModal(rules[+$(this).data('idx')]); });
        $('.wctw-del-btn').on('click', function () {
            const ruleId = $(this).data('id');
            const ruleName = $(this).data('name');
            Swal.fire({
                title: '確定要刪除嗎？',
                text: '規則「' + ruleName + '」刪除後將無法復原。',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#646970',
                confirmButtonText: '確定刪除',
                cancelButtonText: '取消'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteRule(ruleId);
                }
            });
        });
        $('.wctw-toggle-input').on('change', function () {
            const id  = $(this).data('id');
            const r   = rules.find(x => x.id === id);
            if (r) saveToServer({ ...r, enabled: $(this).is(':checked') }, true);
        });
    }

    // ── Rule card ─────────────────────────────────────────────────────────────
    function renderCard(r, i) {
        const conds   = (r.conditions || []);
        const acts    = (r.actions    || []);
        const enabled = !!r.enabled;

        const condTags = conds.length
            ? conds.map(c => `<span class="wctw-tag cond">
                    <span class="dashicons ${COND_ICONS[c.type] || 'dashicons-filter'}"></span>
                    ${esc(COND_LABELS[c.type] || c.type)}
                </span>`).join('')
            : '<span class="wctw-tag empty">永遠觸發</span>';

        const actTags = acts.length
            ? acts.map(a => `<span class="wctw-tag action">
                    <span class="dashicons ${ACTION_ICONS[a.type] || 'dashicons-admin-settings'}"></span>
                    ${esc(ACTION_LABELS[a.type] || a.type)}
                </span>`).join('')
            : '<span class="wctw-tag empty">無動作</span>';

        return `
            <div class="wctw-rule-card ${enabled ? '' : 'is-disabled'}">
                <div class="wctw-rule-card-header">
                    <label class="wctw-switch" title="${enabled ? '點擊停用' : '點擊啟用'}">
                        <input type="checkbox" class="wctw-toggle-input" data-id="${esc(r.id)}" ${enabled ? 'checked' : ''}>
                        <span class="wctw-switch-slider"></span>
                    </label>
                    <span class="wctw-rule-name">${esc(r.name || '（未命名規則）')}</span>
                    <span class="wctw-pill ${enabled ? 'on' : 'off'}">
                        <span class="dashicons ${enabled ? 'dashicons-yes' : 'dashicons-minus'}"></span>
                        ${enabled ? '啟用中' : '停用'}
                    </span>
                    <div class="wctw-rule-card-actions">
                        <button type="button" class="wctw-btn-ghost wctw-edit-btn" data-idx="${i}" title="編輯規則">
                            <span class="dashicons dashicons-edit"></span>編輯
                        </button>
                        <button type="button" class="wctw-btn-danger wctw-del-btn"
                            data-id="${esc(r.id)}" data-name="${esc(r.name)}" title="刪除規則">
                            <span class="dashicons dashicons-trash"></span>刪除
                        </button>
                    </div>
                </div>
                <div class="wctw-rule-card-body">
                    <div class="wctw-rule-section">
                        <div class="wctw-rule-section-title">
                            <span class="dashicons dashicons-filter"></span>觸發條件
                        </div>
                        <div class="wctw-tags">${condTags}</div>
                    </div>
                    <div class="wctw-rule-section">
                        <div class="wctw-rule-section-title">
                            <span class="dashicons dashicons-controls-forward"></span>執行動作
                        </div>
                        <div class="wctw-tags">${actTags}</div>
                    </div>
                </div>
            </div>`;
    }

    // ── Modal ─────────────────────────────────────────────────────────────────
    function openModal(rule) {
        editingRule = rule
            ? JSON.parse(JSON.stringify(rule))
            : { id: '', name: '', hook, enabled: true, conditions: [], actions: [] };

        if ($('#wctw-overlay').length === 0) {
            $('body').append('<div class="wctw-overlay" id="wctw-overlay"></div>');
        }

        renderModal();
        $('#wctw-overlay').addClass('open');
        $('#wctw-overlay').on('click.wctw-modal', function (e) {
            if ($(e.target).is('#wctw-overlay')) closeModal();
        });
        $(document).on('keydown.wctw-modal', function (e) {
            if (e.key === 'Escape') closeModal();
        });
    }

    function closeModal() {
        $('#wctw-overlay').removeClass('open');
        $(document).off('keydown.wctw-modal');
        editingRule = null;
    }

    function renderModal() {
        const r    = editingRule;
        const isNew = !r.id;

        $('#wctw-overlay').html(`
            <div class="wctw-modal" id="wctw-modal">
                <div class="wctw-modal-head">
                    <h3>
                        <span class="dashicons ${isNew ? 'dashicons-plus-alt2' : 'dashicons-edit'}"></span>
                        ${isNew ? '新增規則' : '編輯規則'}
                    </h3>
                    <button type="button" class="wctw-modal-x" id="wctw-modal-close" title="關閉">✕</button>
                </div>
                <div class="wctw-modal-body">
                    
                    <!-- Rule Summary Box -->
                    <div class="wctw-summary-box">
                        <span class="dashicons dashicons-info"></span>
                        <div class="wctw-summary-content" id="wctw-rule-summary">
                            <span class="wctw-summary-placeholder">正在生成規則摘要...</span>
                        </div>
                    </div>

                    <div class="wctw-name-row">
                        <div class="wctw-form-row">
                            <label>規則名稱 <span class="wctw-required">*</span></label>
                            <input type="text" id="e-name" class="large-text"
                                value="${esc(r.name)}" placeholder="例：台灣以外不提供貨到付款">
                        </div>
                        <div class="wctw-form-row" style="flex-shrink:0">
                            <label>狀態</label>
                            <label style="display:flex;align-items:center;gap:8px;height:30px;margin-top:2px;cursor:pointer">
                                <span class="wctw-switch" style="display:inline-block">
                                    <input type="checkbox" id="e-enabled" ${r.enabled ? 'checked' : ''}>
                                    <span class="wctw-switch-slider"></span>
                                </span>
                                <span id="e-enabled-label" style="font-size:13px;color:#646970">
                                    ${r.enabled ? '啟用' : '停用'}
                                </span>
                            </label>
                        </div>
                    </div>

                    <hr class="wctw-divider">

                    <!-- Conditions -->
                    <div class="wctw-block">
                        <div class="wctw-block-head">
                            <h4 class="wctw-step-title">
                                <span class="wctw-step-num">1</span>
                                <span class="dashicons dashicons-filter"></span>
                                當滿足以下條件 (Conditions)
                                <span class="wctw-block-hint">符合下方全部條件即觸發</span>
                            </h4>
                        </div>
                        <div class="wctw-block-body">
                            <div id="e-conds">${renderCondItems(r.conditions)}</div>
                            <div class="wctw-add-item-row" style="border-top:none; padding-top:0;">
                                <button type="button" class="wctw-btn-secondary" id="e-pick-cond" style="width:100%; justify-content:center; padding:10px; border-style:dashed;">
                                    <span class="dashicons dashicons-plus-alt2"></span>點擊新增觸發條件
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="wctw-block">
                        <div class="wctw-block-head">
                            <h4 class="wctw-step-title">
                                <span class="wctw-step-num">2</span>
                                <span class="dashicons dashicons-controls-forward"></span>
                                則執行這些動作 (Actions)
                            </h4>
                        </div>
                        <div class="wctw-block-body">
                            <div id="e-acts">${renderActItems(r.actions)}</div>
                            <div class="wctw-add-item-row" style="border-top:none; padding-top:0;">
                                <button type="button" class="wctw-btn-secondary" id="e-pick-act" style="width:100%; justify-content:center; padding:10px; border-style:dashed;">
                                    <span class="dashicons dashicons-plus-alt2"></span>點擊新增行銷動作
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="wctw-modal-foot">
                    <button type="button" class="wctw-btn-secondary" id="e-cancel">取消</button>
                    <div class="spacer"></div>
                    <div class="wctw-spinner" id="e-spinner"></div>
                    <button type="button" class="wctw-btn-primary" id="e-save" style="padding:10px 24px;">
                        <span class="dashicons dashicons-saved"></span>儲存並發佈規則
                    </button>
                </div>
            </div>
        `);

        bindModalEvents();
        updateSummary();
    }

    // ── Condition items ───────────────────────────────────────────────────────
    function renderCondItems(conds) {
        if (!conds.length) return '<p class="wctw-block-empty">尚未新增條件。</p>';
        return conds.map((c, i) => `
            <div class="wctw-item-row" data-idx="${i}">
                <span class="wctw-item-type-badge">
                    ${esc(COND_LABELS[c.type] || c.type)}
                </span>
                <div class="wctw-item-fields">${condFields(c)}</div>
                <button type="button" class="wctw-item-rm wctw-rm-cond" data-idx="${i}" title="移除條件">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>`).join('');
    }

    function condFields(cond) {
        const c = cond.config || {};
        const sel = (val, cur) => cur === val ? ' selected' : '';

        switch (cond.type) {
            case 'address': {
                const isState = c.field === 'state';
                const stateOpts = Object.entries(D.states || {}).map(([code, name]) =>
                    `<option value="${esc(code)}"${(c.values||[]).includes(code)?' selected':''}>${esc(name)}</option>`
                ).join('');
                const countryList = [
                    ['TW','台灣'],['US','美國'],['JP','日本'],['CN','中國'],
                    ['HK','香港'],['SG','新加坡'],['KR','韓國'],['AU','澳洲'],
                    ['GB','英國'],['DE','德國'],['FR','法國'],
                ];
                const countryOpts = countryList.map(([code, name]) =>
                    `<option value="${code}"${(c.values||[]).includes(code)?' selected':''}>${name} (${code})</option>`
                ).join('');
                return `
                    <select class="cf" data-key="field" data-role="addr-field">
                        <option value="country"${!isState?' selected':''}>國家/地區</option>
                        <option value="state"${isState?' selected':''}>縣市（台灣）</option>
                    </select>
                    <select class="cf" data-key="op">
                        <option value="in"${sel('in',c.op||'in')}>包含</option>
                        <option value="not_in"${sel('not_in',c.op)}>不包含</option>
                    </select>
                    <div class="cf-grp" data-for="country"${isState?' style="display:none"':''}>
                        <select class="cf" data-key="values" multiple size="4">${countryOpts}</select>
                    </div>
                    <div class="cf-grp" data-for="state"${!isState?' style="display:none"':''}>
                        <select class="cf" data-key="values" multiple size="4">${stateOpts}</select>
                    </div>`;
            }
            case 'cart_total':
                return `
                    <select class="cf" data-key="op">
                        <option value="gte"${sel('gte',c.op||'gte')}>≥ 大於或等於</option>
                        <option value="lte"${sel('lte',c.op)}>≤ 小於或等於</option>
                        <option value="gt"${sel('gt', c.op)}>&gt; 大於</option>
                        <option value="lt"${sel('lt', c.op)}>&lt; 小於</option>
                        <option value="eq"${sel('eq', c.op)}>= 等於</option>
                    </select>
                    <input type="number" class="cf small-text" data-key="amount" data-as="number"
                        min="0" step="1" style="width:90px"
                        value="${Math.round(parseFloat(c.amount)||0)}">
                    <span class="field-unit">元</span>`;
            case 'category': {
                const opts = (D.categories||[]).map(cat =>
                    `<option value="${cat.id}"${(c.categories||[]).includes(cat.id)?' selected':''}>${esc(cat.label)}</option>`
                ).join('');
                return `
                    <select class="cf" data-key="op">
                        <option value="contains"${sel('contains',c.op||'contains')}>包含分類</option>
                        <option value="not_contains"${sel('not_contains',c.op)}>不包含分類</option>
                    </select>
                    <select class="cf" data-key="categories" multiple size="4">
                        ${opts||'<option disabled>（無分類）</option>'}
                    </select>`;
            }
            case 'payment_method': {
                const opts = (D.gateways||[]).map(gw =>
                    `<option value="${esc(gw.id)}"${(c.methods||[]).includes(gw.id)?' selected':''}>${esc(gw.label)}</option>`
                ).join('');
                return `
                    <select class="cf wctw-sum" data-key="op">
                        <option value="in"${sel('in',c.op||'in')}>包含</option>
                        <option value="not_in"${sel('not_in',c.op)}>不包含</option>
                    </select>
                    <select class="cf wctw-sum" data-key="methods" multiple size="4">
                        ${opts||'<option disabled>（無付款方式）</option>'}
                    </select>`;
            }
            case 'product':
                return `
                    <select class="cf wctw-sum" data-key="op">
                        <option value="in"${sel('in',c.op||'in')}>購物車含</option>
                        <option value="not_in"${sel('not_in',c.op)}>購物車不含</option>
                    </select>
                    <input type="text" class="cf regular-text" data-key="products" data-as="ids"
                        value="${esc((c.products||[]).join(','))}" placeholder="商品 ID，逗號分隔">`;
            case 'shipping_method': {
                const opts = (D.shipping||[]).map(m =>
                    `<option value="${esc(m.id)}"${(c.methods||[]).includes(m.id)?' selected':''}>${esc(m.label)}</option>`
                ).join('');
                return `
                    <select class="cf wctw-sum" data-key="op">
                        <option value="in"${sel('in',c.op||'in')}>包含</option>
                        <option value="not_in"${sel('not_in',c.op)}>不包含</option>
                    </select>
                    <select class="cf wctw-sum" data-key="methods" multiple size="4">
                        ${opts||'<option disabled>（無配送方式）</option>'}
                    </select>`;
            }
            case 'address_mismatch':
                return `
                    <span style="color:#646970;font-size:13px">比對</span>
                    <select class="cf wctw-sum" data-key="compare">
                        <option value="country"${sel('country',c.compare||'country')}>國家／地區</option>
                        <option value="state"${sel('state',c.compare)}>縣市 / 州</option>
                    </select>
                    <span style="color:#646970;font-size:13px">不一致時觸發</span>`;
            case 'order_frequency':
                return `
                    <span style="color:#646970;font-size:13px">近</span>
                    <input type="number" class="cf small-text" data-key="hours" data-as="number"
                        min="1" step="1" style="width:70px" value="${parseInt(c.hours)||24}">
                    <span style="color:#646970;font-size:13px">小時內訂單數</span>
                    <select class="cf wctw-sum" data-key="op">
                        <option value="gte"${sel('gte',c.op||'gte')}>≥</option>
                        <option value="gt"${sel('gt',c.op)}>&gt;</option>
                        <option value="lte"${sel('lte',c.op)}>≤</option>
                        <option value="lt"${sel('lt',c.op)}>&lt;</option>
                        <option value="eq"${sel('eq',c.op)}>=</option>
                    </select>
                    <input type="number" class="cf small-text" data-key="count" data-as="number"
                        min="1" step="1" style="width:70px" value="${parseInt(c.count)||2}">
                    <span style="color:#646970;font-size:13px">筆</span>`;
            case 'user_role':
                return `
                    <select class="cf wctw-sum" data-key="roles" multiple size="4">
                        <option value="customer"${(c.roles||[]).includes('customer')?' selected':''}>顧客 (Customer)</option>
                        <option value="subscriber"${(c.roles||[]).includes('subscriber')?' selected':''}>訂閱者 (Subscriber)</option>
                        <option value="administrator"${(c.roles||[]).includes('administrator')?' selected':''}>管理員 (Administrator)</option>
                        <option value="guest"${(c.roles||[]).includes('guest')?' selected':''}>未登入訪客</option>
                    </select>`;
            case 'first_purchase':
                return `<span style="color:#00a32a;font-weight:600;">買家必須為首次購買（歷史訂單為 0 的註冊會員）</span>`;
            case 'time_range':
                return `
                    <span style="color:#646970;font-size:13px">開始時間</span>
                    <input type="datetime-local" class="cf" data-key="start_time" value="${esc(c.start_time||'')}">
                    <span style="color:#646970;font-size:13px;margin-left:8px">結束時間</span>
                    <input type="datetime-local" class="cf" data-key="end_time" value="${esc(c.end_time||'')}">`;
            case 'cart_item_count':
                return `
                    <select class="cf" data-key="op">
                        <option value="gte"${sel('gte',c.op||'gte')}>≥ 大於或等於</option>
                        <option value="lte"${sel('lte',c.op)}>≤ 小於或等於</option>
                        <option value="gt"${sel('gt', c.op)}>&gt; 大於</option>
                        <option value="lt"${sel('lt', c.op)}>&lt; 小於</option>
                        <option value="eq"${sel('eq', c.op)}>= 等於</option>
                    </select>
                    <input type="number" class="cf small-text" data-key="count" data-as="number"
                        min="1" step="1" style="width:70px" value="${Math.round(parseFloat(c.count)||1)}">
                    <span class="field-unit">件</span>
                    <span style="color:#646970;font-size:13px;margin-left:8px">限制分類 (選填)</span>
                    <select class="cf" data-key="categories" multiple size="4">
                        ${(D.categories||[]).map(cat => `<option value="${cat.id}"${(c.categories||[]).includes(cat.id)?' selected':''}>${esc(cat.label)}</option>`).join('')}
                    </select>`;
            case 'days_of_week':
                return `
                    <select class="cf" data-key="days" multiple size="4">
                        <option value="1"${(c.days||[]).includes('1')?' selected':''}>星期一</option>
                        <option value="2"${(c.days||[]).includes('2')?' selected':''}>星期二</option>
                        <option value="3"${(c.days||[]).includes('3')?' selected':''}>星期三</option>
                        <option value="4"${(c.days||[]).includes('4')?' selected':''}>星期四</option>
                        <option value="5"${(c.days||[]).includes('5')?' selected':''}>星期五</option>
                        <option value="6"${(c.days||[]).includes('6')?' selected':''}>星期六</option>
                        <option value="0"${(c.days||[]).includes('0')?' selected':''}>星期日</option>
                    </select>`;
            default: return '';
        }
    }

    // ── Action items ──────────────────────────────────────────────────────────
    function renderActItems(acts) {
        if (!acts.length) return '<p class="wctw-block-empty">尚未新增動作。</p>';
        return acts.map((a, i) => `
            <div class="wctw-item-row wctw-action-row-wrap" data-idx="${i}">
                <span class="wctw-item-type-badge">
                    ${esc(ACTION_LABELS[a.type] || a.type)}
                </span>
                <div class="wctw-item-fields">${actFields(a)}</div>
                <button type="button" class="wctw-item-rm wctw-rm-act" data-idx="${i}" title="移除動作">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>`).join('');
    }

    function actFields(act) {
        const c = act.config || {};
        switch (act.type) {
            case 'hide_payment': {
                const opts = (D.gateways||[]).map(gw =>
                    `<option value="${esc(gw.id)}"${(c.gateways||[]).includes(gw.id)?' selected':''}>${esc(gw.label)}</option>`
                ).join('');
                return `<select class="af wctw-sum" data-key="gateways" multiple size="4">
                    ${opts||'<option disabled>（無可用付款方式）</option>'}
                </select>`;
            }
            case 'hide_shipping': {
                const opts = (D.shipping||[]).map(m =>
                    `<option value="${esc(m.id)}"${(c.methods||[]).includes(m.id)?' selected':''}>${esc(m.label)}</option>`
                ).join('');
                return `<select class="af wctw-sum" data-key="methods" multiple size="4">
                    ${opts||'<option disabled>（無可用配送方式）</option>'}
                </select>`;
            }
            case 'block_checkout':
                return `<input type="text" class="af large-text" data-key="message"
                    value="${esc(c.message||'')}" placeholder="顯示給顧客的錯誤訊息">`;
            case 'apply_discount':
                return `
                    <select class="af wctw-sum" data-key="type">
                        <option value="fixed"${c.type==='fixed'?' selected':''}>定額折抵</option>
                        <option value="percent"${c.type==='percent'?' selected':''}>比例折抵 (%)</option>
                    </select>
                    <input type="number" class="af small-text" data-key="amount"
                        min="1" step="1" style="width:90px" value="${parseFloat(c.amount)||0}" placeholder="額度">
                    <input type="text" class="af regular-text" data-key="name"
                        value="${esc(c.name||'')}" placeholder="顯示名稱 (如: 滿千折百)">`;
            case 'add_free_gift':
                return `<input type="number" class="af regular-text" data-key="product_id"
                    value="${parseInt(c.product_id)||''}" placeholder="請輸入贈品的 商品 ID">`;
            case 'free_shipping':
                return `<span style="color:#00a32a;font-weight:600;">免運費</span>`;
            case 'cart_progress':
                return `
                    <span style="color:#646970;font-size:13px">目標金額</span>
                    <input type="number" class="af small-text" data-key="target_amount"
                        min="1" step="1" style="width:90px" value="${parseFloat(c.target_amount)||0}">
                    <span style="color:#646970;font-size:13px">標籤</span>
                    <input type="text" class="af regular-text" data-key="label"
                        value="${esc(c.label||'')}" placeholder="如：免運">
                    <span style="color:#646970;font-size:13px">訊息範本</span>
                    <input type="text" class="af large-text" data-key="message_pattern"
                        value="${esc(c.message_pattern||'還差 {diff} 即可享有 {label}')}" placeholder="可用 {diff}, {label}, {target}">`;
            case 'bundle_discount':
                return `
                    <span style="color:#646970;font-size:13px">任選</span>
                    <input type="number" class="af small-text" data-key="qty" min="2" style="width:60px" value="${parseInt(c.qty)||3}">
                    <span style="color:#646970;font-size:13px">件，給予</span>
                    <select class="af" data-key="type">
                        <option value="fixed_price"${c.type==='fixed_price'?' selected':''}>組合總價 (元)</option>
                        <option value="percent"${c.type==='percent'?' selected':''}>折扣比例 (%)</option>
                    </select>
                    <input type="number" class="af small-text" data-key="value" style="width:80px" value="${parseFloat(c.value)||0}">
                    <span style="color:#646970;font-size:13px">限制分類 (選填)</span>
                    <select class="af" data-key="categories" multiple size="4">
                        ${(D.categories||[]).map(cat => `<option value="${cat.id}"${(c.categories||[]).includes(cat.id)?' selected':''}>${esc(cat.label)}</option>`).join('')}
                    </select>
                    <input type="text" class="af regular-text" data-key="name" value="${esc(c.name||'組合優惠')}" placeholder="顯示名稱">`;
            case 'addon_deal':
                return `
                    <span style="color:#646970;font-size:13px">加購商品 ID</span>
                    <input type="number" class="af small-text" data-key="product_id" style="width:80px" value="${parseInt(c.product_id)||0}">
                    <span style="color:#646970;font-size:13px">加購價</span>
                    <input type="number" class="af small-text" data-key="addon_price" style="width:80px" value="${parseFloat(c.addon_price)||0}">
                    <span style="color:#646970;font-size:13px">標題</span>
                    <input type="text" class="af regular-text" data-key="title" value="${esc(c.title||'加價購優惠')}" placeholder="如：限時加購">
                    <span style="color:#646970;font-size:13px">按鈕文字</span>
                    <input type="text" class="af small-text" data-key="button_text" value="${esc(c.button_text||'立即加購')}">`;
            case 'flash_sale_countdown':
                return `
                    <span style="color:#646970;font-size:13px">截止時間</span>
                    <input type="datetime-local" class="af" data-key="end_time" value="${esc(c.end_time||'')}">
                    <span style="color:#646970;font-size:13px">訊息文字</span>
                    <input type="text" class="af regular-text" data-key="message" value="${esc(c.message||'優惠即將結束：')}" placeholder="如：限時折扣倒數中">`;
            default: return '';
        }
    }

    // ── Modal events ──────────────────────────────────────────────────────────
    function bindModalEvents() {
        $('#wctw-modal-close, #e-cancel').on('click', closeModal);

        $('#e-enabled').on('change', function () {
            $('#e-enabled-label').text($(this).is(':checked') ? '啟用' : '停用');
        });

        // Address field toggle
        $(document).on('change.wctw', '[data-role="addr-field"]', function () {
            const isState = $(this).val() === 'state';
            const $row = $(this).closest('.wctw-item-row');
            $row.find('.cf-grp[data-for="country"]').toggle(!isState);
            $row.find('.cf-grp[data-for="state"]').toggle(isState);
        });

        $('#e-add-cond').on('click', function () {
            syncConds();
            editingRule.conditions.push({ type: $('#e-cond-type').val(), config: {} });
            $('#e-conds').html(renderCondItems(editingRule.conditions));
        });

        $('#e-add-act').on('click', function () {
            syncActs();
            editingRule.actions.push({ type: $('#e-action-type').val(), config: {} });
            $('#e-acts').html(renderActItems(editingRule.actions));
        });

        $(document).on('click.wctw', '.wctw-rm-cond', function () {
            syncConds();
            editingRule.conditions.splice(+$(this).data('idx'), 1);
            $('#e-conds').html(renderCondItems(editingRule.conditions));
        });

        $(document).on('click.wctw', '.wctw-rm-act', function () {
            syncActs();
            editingRule.actions.splice(+$(this).data('idx'), 1);
            $('#e-acts').html(renderActItems(editingRule.actions));
        });

        $('#e-save').on('click', function () {
            const rule = collectRule();
            if (rule) saveToServer(rule, false);
        });

        $('#e-pick-cond').on('click', () => openPicker('cond'));
        $('#e-pick-act').on('click', () => openPicker('action'));
        
        $(document).on('change.wctw-sum input.wctw-sum', '.cf, .af, #e-name', updateSummary);
    }

    // ── UIUX Optimization Functions ───────────────────────────────────────────
    function openPicker(targetType) {
        const isCond = targetType === 'cond';
        const types  = isCond ? (HOOK_CONDITIONS[hook]||[]) : (HOOK_ACTIONS[hook]||[]);
        const labels = isCond ? COND_LABELS : ACTION_LABELS;
        const descs  = isCond ? COND_DESCS  : ACTION_DESCS;
        const icons  = isCond ? COND_ICONS  : ACTION_ICONS;

        if ($('#wctw-picker-overlay').length === 0) {
            $('body').append('<div class="wctw-picker-overlay" id="wctw-picker-overlay"></div>');
        }

        const itemsHtml = types.map(t => `
            <div class="wctw-picker-item" data-type="${t}">
                <span class="dashicons ${icons[t] || 'dashicons-admin-settings'}"></span>
                <div class="wctw-picker-item-label">${labels[t]}</div>
                <div class="wctw-picker-item-desc">${descs[t] || ''}</div>
            </div>
        `).join('');

        $('#wctw-picker-overlay').html(`
            <div class="wctw-picker-modal">
                <div class="wctw-picker-head">
                    <strong>新增${isCond ? '觸發條件' : '行銷動作'}</strong>
                    <button type="button" class="wctw-modal-x" id="wctw-picker-close">✕</button>
                </div>
                <div class="wctw-picker-grid">${itemsHtml}</div>
            </div>
        `).addClass('open');

        const close = () => $('#wctw-picker-overlay').removeClass('open');
        $('#wctw-picker-close').on('click', close);
        $('#wctw-picker-overlay').on('click', e => { if ($(e.target).is('#wctw-picker-overlay')) close(); });

        $('.wctw-picker-item').on('click', function() {
            const type = $(this).data('type');
            if (isCond) {
                syncConds();
                editingRule.conditions.push({ type, config: {} });
                $('#e-conds').html(renderCondItems(editingRule.conditions));
            } else {
                syncActs();
                editingRule.actions.push({ type, config: {} });
                $('#e-acts').html(renderActItems(editingRule.actions));
            }
            close();
            updateSummary();
        });
    }

    function updateSummary() {
        const r = editingRule;
        if (!r) return;
        
        syncConds();
        syncActs();

        let condText = '隨時';
        if (r.conditions.length) {
            condText = r.conditions.map(c => {
                const cfg = c.config || {};
                const label = COND_LABELS[c.type];
                switch(c.type) {
                    case 'cart_total': return `金額 <span class="wctw-summary-val">${cfg.op||'gte'} ${cfg.amount||0}</span>`;
                    case 'cart_item_count': return `數量 <span class="wctw-summary-val">${cfg.op||'gte'} ${cfg.count||0}</span>`;
                    case 'first_purchase': return `<span class="wctw-summary-val">首購</span>`;
                    case 'user_role': return `身分是 <span class="wctw-summary-val">${(cfg.roles||[]).join(',')}</span>`;
                    default: return `<span class="wctw-summary-val">${label}</span>`;
                }
            }).join(' 且 ');
        }

        let actText = '<span class="wctw-summary-placeholder">尚未設定動作</span>';
        if (r.actions.length) {
            actText = r.actions.map(a => {
                const cfg = a.config || {};
                const label = ACTION_LABELS[a.type];
                switch(a.type) {
                    case 'apply_discount': return `給予 <span class="wctw-summary-val">${cfg.amount||0}${cfg.type==='percent'?'%':'元'}</span> 折扣`;
                    case 'add_free_gift': return `贈送 <span class="wctw-summary-val">商品 ID ${cfg.product_id||'?'}</span>`;
                    case 'free_shipping': return `給予 <span class="wctw-summary-val">免運費</span>`;
                    case 'cart_progress': return `顯示 <span class="wctw-summary-val">${cfg.target_amount||0}元</span> 進度條`;
                    default: return `<span class="wctw-summary-val">${label}</span>`;
                }
            }).join(', ');
        }

        $('#wctw-rule-summary').html(`當 <span class="wctw-summary-val">${condText}</span> 時，${actText}。`);
    }

    // ── Sync helpers ──────────────────────────────────────────────────────────
    function syncConds() {
        editingRule.conditions = editingRule.conditions.map((cond, i) => {
            const $row = $(`#e-conds .wctw-item-row[data-idx="${i}"]`);
            return $row.length ? { type: cond.type, config: collectCondCfg($row) } : cond;
        });
    }
    function syncActs() {
        editingRule.actions = editingRule.actions.map((act, i) => {
            const $row = $(`#e-acts .wctw-item-row[data-idx="${i}"]`);
            return $row.length ? { type: act.type, config: collectActCfg($row) } : act;
        });
    }

    function collectCondCfg($row) {
        const cfg = {};
        $row.find('.cf').not('.cf-grp .cf').each(function () {
            const key = $(this).data('key');
            const as  = $(this).data('as');
            const val = $(this).val();
            if (as === 'number') cfg[key] = parseFloat(val) || 0;
            else if (as === 'ids') cfg[key] = val ? val.split(',').map(s => parseInt(s.trim())).filter(n => n > 0) : [];
            else if ($(this).is('select[multiple]')) cfg[key] = val || [];
            else cfg[key] = val;
        });
        $row.find('.cf-grp:visible .cf').each(function () { cfg['values'] = $(this).val() || []; });
        return cfg;
    }
    function collectActCfg($row) {
        const cfg = {};
        $row.find('.af').each(function () {
            const key = $(this).data('key');
            cfg[key] = $(this).is('select[multiple]') ? ($(this).val() || []) : $(this).val();
        });
        return cfg;
    }

    function collectRule() {
        const name = $('#e-name').val().trim();
        if (!name) {
            toast('請輸入規則名稱', 'error');
            $('#e-name').focus();
            return null;
        }
        syncConds();
        syncActs();
        return {
            id:         editingRule.id,
            name,
            hook,
            enabled:    $('#e-enabled').is(':checked'),
            conditions: editingRule.conditions,
            actions:    editingRule.actions,
        };
    }

    // ── Samples picker ────────────────────────────────────────────────────────
    function openSamplesPicker() {
        const groups = D.samples || [];
        if (!groups.length) return;

        if ($('#wctw-overlay').length === 0) {
            $('body').append('<div class="wctw-overlay" id="wctw-overlay"></div>');
        }

        const itemsHtml = groups.map(g => `
            <div class="wctw-sample-group" style="margin-bottom:20px;">
                <h4 style="margin:0 0 10px 0; padding-bottom:5px; border-bottom:1px solid #eee; font-size:14px; color:#1e293b;">${esc(g.group)}</h4>
                ${(g.items || []).map(s => `
                    <label class="wctw-sample-item" style="display:block;padding:12px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:8px;cursor:pointer;background:#fff;transition:background 0.2s;">
                        <div style="display:flex; align-items:flex-start; gap:10px;">
                            <input type="checkbox" class="wctw-sample-cb" value="${esc(s.key)}" checked style="margin-top:3px;">
                            <div>
                                <strong style="display:block; font-size:13px; color:#1e293b;">${esc(s.name)}</strong>
                                ${s.description ? `<div style="font-size:12px;color:#64748b;margin-top:4px;">${esc(s.description)}</div>` : ''}
                            </div>
                        </div>
                    </label>
                `).join('')}
            </div>
        `).join('');

        $('#wctw-overlay').html(`
            <div class="wctw-modal" id="wctw-modal" style="max-width:560px">
                <div class="wctw-modal-head">
                    <h3><span class="dashicons dashicons-download"></span>載入範例規則</h3>
                    <button type="button" class="wctw-modal-x" id="wctw-modal-close">✕</button>
                </div>
                <div class="wctw-modal-body">
                    <p style="margin-top:0;color:#646970;font-size:13px">勾選要匯入的範例。匯入後規則為「停用」狀態，請依需要編輯動作目標（如付款方式、運送方式）後再啟用。</p>
                    ${itemsHtml}
                </div>
                <div class="wctw-modal-foot">
                    <button type="button" class="wctw-btn-secondary" id="wctw-samples-cancel">取消</button>
                    <div class="spacer"></div>
                    <div class="wctw-spinner" id="wctw-samples-spinner"></div>
                    <button type="button" class="wctw-btn-primary" id="wctw-samples-import">
                        <span class="dashicons dashicons-yes"></span>匯入所選
                    </button>
                </div>
            </div>
        `);
        $('#wctw-overlay').addClass('open');

        const close = () => {
            $('#wctw-overlay').removeClass('open');
            $(document).off('keydown.wctw-samples');
        };
        $('#wctw-modal-close, #wctw-samples-cancel').on('click', close);
        $('#wctw-overlay').on('click.wctw-samples', e => { if ($(e.target).is('#wctw-overlay')) close(); });
        $(document).on('keydown.wctw-samples', e => { if (e.key === 'Escape') close(); });

        $('#wctw-samples-import').on('click', function () {
            const keys = $('.wctw-sample-cb:checked').map(function () { return $(this).val(); }).get();
            if (!keys.length) { toast('請至少勾選一項', 'error'); return; }
            $(this).prop('disabled', true);
            $('#wctw-samples-spinner').addClass('active');
            $.post(D.ajaxUrl, {
                action: 'wc_tw_core_import_samples',
                nonce:  D.nonce,
                hook,
                keys:   keys.join(','),
            })
            .done(function (res) {
                if (res.success) {
                    rules = res.data.rules;
                    close();
                    render();
                    toast('已匯入 ' + res.data.added + ' 筆範例（預設為停用）', 'success');
                } else {
                    toast('匯入失敗：' + (res.data || '未知錯誤'), 'error');
                }
            })
            .fail(() => toast('請求失敗，請重試', 'error'))
            .always(function () {
                $('#wctw-samples-import').prop('disabled', false);
                $('#wctw-samples-spinner').removeClass('active');
            });
        });
    }

    // ── AJAX ──────────────────────────────────────────────────────────────────
    function saveToServer(rule, silent) {
        if (!silent) {
            $('#e-save').prop('disabled', true);
            $('#e-spinner').addClass('active');
        }

        $.post(D.ajaxUrl, {
            action: 'wc_tw_core_save_rule',
            nonce:  D.nonce,
            hook,
            rule:   JSON.stringify(rule),
        })
        .done(function (res) {
            if (res.success) {
                rules = res.data.rules;
                closeModal();
                render();
                toast(silent ? '狀態已更新' : '規則已儲存', 'success');
            } else {
                toast('儲存失敗：' + (res.data || '未知錯誤'), 'error');
            }
        })
        .fail(function () {
            toast('請求失敗，請重試', 'error');
        })
        .always(function () {
            if (!silent) {
                $('#e-save').prop('disabled', false);
                $('#e-spinner').removeClass('active');
            }
        });
    }

    function deleteRule(id) {
        $.post(D.ajaxUrl, {
            action:  'wc_tw_core_delete_rule',
            nonce:   D.nonce,
            hook,
            rule_id: id,
        })
        .done(function (res) {
            if (res.success) {
                rules = res.data.rules;
                render();
                toast('規則已刪除', 'success');
            }
        })
        .fail(function () {
            toast('刪除失敗，請重試', 'error');
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    $(function () { render(); });

})(jQuery);
