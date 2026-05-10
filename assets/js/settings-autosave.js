/* WC TW Core — Settings Auto-save */
(function ($) {
    'use strict';

    const D = window.WCTWSettingsData;
    if (!D) return;

    let timer = null;
    let $status;

    function init() {
        const $wrap = $('.wc-tw-settings-wrap');
        if (!$wrap.length) return;

        // 狀態文字顯示在儲存按鈕旁邊
        $status = $('<span class="wctw-autosave-bar" style="margin-left:15px; vertical-align:middle; font-size:13px; color:#666;"></span>');
        $('.woocommerce-save-button').after($status);

        // 監聽變動
        $wrap.on('change', 'input, select, textarea', schedule);
        $wrap.on('input',  'input[type="text"], input[type="number"], textarea', schedule);
    }

    function schedule() {
        clearTimeout(timer);
        showStatus('saving');
        timer = setTimeout(save, 1000);
    }

    function showStatus(state) {
        const map = {
            saving: '<span class="dashicons dashicons-update-alt wctw-as-spin"></span> 正在同步...',
            saved:  '<span class="dashicons dashicons-yes-alt" style="color:green"></span> 已自動同步',
            error:  '',
        };
        $status.html(map[state] || '');
    }

    function save() {
        const $form = $('.wc-tw-settings-wrap').closest('form');
        if (!$form.length) return;

        const data = $form.serialize()
            + '&action=wc_tw_core_autosave_settings'
            + '&_autosave_nonce=' + encodeURIComponent(D.nonce)
            + '&section=' + encodeURIComponent(D.section);

        $.post(D.ajaxUrl, data)
            .done(function (res) {
                if (res && res.success) {
                    showStatus('saved');
                    setTimeout(function () { $status.html(''); }, 2000);
                } else {
                    showStatus('error');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: '同步失敗',
                            text: '設定無法自動儲存，請檢查網路連線或重新登入。',
                            confirmButtonColor: '#2271b1'
                        });
                    }
                }
            })
            .fail(function() {
                showStatus('error');
                if (typeof Swal !== 'undefined') {
                    Swal.fire('同步失敗', '伺服器連線異常，請稍後再試。', 'error');
                }
            });
    }

    $(init);

}(jQuery));
