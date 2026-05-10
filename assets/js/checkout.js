/**
 * WC TW Core — Checkout JS
 *
 * Features:
 *  - Cascading 縣市 → 鄉鎮市區 select + postcode auto-fill
 *  - 發票類型 selector → show/hide 載具/捐贈碼 field and company invoice block
 *  - 統一編號 AJAX lookup (debounced 600ms) + JS checksum pre-check
 *  - Real-time phone validation feedback
 *  - Real-time carrier number format hint
 */
( function ( $ ) {
	'use strict';

	var data = ( typeof wcTwCheckout !== 'undefined' ) ? wcTwCheckout : null;
	if ( ! data || ! data.states ) {
		return;
	}

	var states   = data.states;
	var autofill = !! data.autofill;

	// ── Carrier field config per invoice type ────────────────────────────────

	var CARRIER_CONFIG = {
		carrier_phone: {
			label:       '手機條碼',
			placeholder: '/XXXXXXX（/ 開頭共 8 碼）',
			hint:        '格式：/ 開頭 + 7 位大寫英數字（例：/ABC+123）',
			validate:    function ( v ) { return /^\/[A-Z0-9+\-.]{7}$/.test( v.toUpperCase() ); },
		},
		carrier_cert: {
			label:       '自然人憑證條碼',
			placeholder: 'XX00000000000000（2 字母 + 14 數字）',
			hint:        '格式：2 大寫字母 + 14 位數字，共 16 碼',
			validate:    function ( v ) { return /^[A-Z]{2}[0-9]{14}$/.test( v.toUpperCase() ); },
		},
		donate: {
			label:       '捐贈碼',
			placeholder: '3–7 位數字',
			hint:        '格式：3 至 7 位數字',
			validate:    function ( v ) { return /^[0-9]{3,7}$/.test( v ); },
		},
	};

	// ── Helpers ──────────────────────────────────────────────────────────────

	function isTW( $form, prefix ) {
		var $country = $form.find( '#' + prefix + '_country' );
		return ! $country.length || $country.val() === 'TW';
	}

	function setHint( $field, msg, color ) {
		var id = $field.attr( 'id' ) + '_hint';
		var $hint = $( '#' + id );
		if ( ! $hint.length ) {
			$hint = $( '<span>' ).attr( 'id', id ).css( { 
				display: 'inline-block', 
				marginTop: '6px', 
				fontSize: '12px',
				padding: '2px 8px',
				borderRadius: '4px',
				fontWeight: '500'
			} );
			$field.after( $hint );
		}
		if ( ! msg ) {
			$hint.hide();
		} else {
			var bg = color === '#00a32a' ? '#e6f6eb' : (color === '#d63638' ? '#fbe9e9' : '#f0f0f1');
			$hint.text( msg ).css( { 'color': color || '#666', 'backgroundColor': bg } ).show();
		}
	}

	// ── Name Sync ────────────────────────────────────────────────────────────
	
	function syncFullName() {
		if ( data.nameConsolidate !== 'yes' ) {
			return;
		}
		var $last  = $( '#billing_last_name, #shipping_last_name' );
		$last.on( 'input change', function() {
			var prefix = $(this).attr('id').split('_')[0];
			var $first = $('#' + prefix + '_first_name');
			if ($first.length) {
				$first.val($(this).val());
			}
		});
	}

	// ── Cascading city select ─────────────────────────────────────────────────

	function rebuildCity( $form, prefix ) {
		if ( ! isTW( $form, prefix ) ) {
			return;
		}

		var $state    = $form.find( '#' + prefix + '_state' );
		var $city     = $form.find( '#' + prefix + '_city' );

		if ( ! $state.length || ! $city.length ) {
			return;
		}

		var stateCode   = $state.val();
		var districtMap = states[ stateCode ] || {};
		var districts   = Object.keys( districtMap );
		var current     = $city.val();

		if ( ! districts.length ) {
			if ( $city.is( 'select' ) ) {
				var $input = $( '<input type="text" />' )
					.attr( { id: prefix + '_city', name: prefix + '_city', autocomplete: 'address-level2' } )
					.attr( 'class', $city.attr( 'class' ) || '' )
					.val( current );
				$city.replaceWith( $input );
			}
			return;
		}

		var $select;
		if ( ! $city.is( 'select' ) ) {
			$select = $( '<select></select>' )
				.attr( { id: prefix + '_city', name: prefix + '_city', autocomplete: 'address-level2' } )
				.attr( 'class', $city.attr( 'class' ) || '' );
			$city.replaceWith( $select );
		} else {
			if ( typeof $city.select2 === 'function' ) {
				try { $city.select2( 'destroy' ); } catch ( e ) {}
			}
			$select = $city;
			$select.empty();
		}

		$select.append( '<option value="">請選擇</option>' );
		$.each( districts, function ( _, name ) {
			$select.append( $( '<option>' ).val( name ).text( name ).prop( 'selected', name === current ) );
		} );

		if ( typeof $select.select2 === 'function' ) {
			$select.select2( { placeholder: '請選擇', allowClear: false, width: '100%' } );
		}
	}

	function fillPostcode( $form, prefix ) {
		if ( ! autofill || ! isTW( $form, prefix ) ) {
			return;
		}
		var stateCode = $form.find( '#' + prefix + '_state' ).val();
		var district  = $form.find( '#' + prefix + '_city' ).val();
		var $postcode = $form.find( '#' + prefix + '_postcode' );
		if ( ! stateCode || ! district || ! $postcode.length ) {
			return;
		}
		var code = ( states[ stateCode ] || {} )[ district ];
		if ( code && ! $postcode.data( 'wctw-manual' ) ) {
			$postcode.val( code ).trigger( 'change' );
			$postcode.data( 'wctw-autofilled', true );
		}
	}

	// ── 發票類型 select2 初始化（對齊縣市欄位高度）────────────────────────────

	function initInvoiceSelect2() {
		var $sel = $( '#billing_wctw_invoice_type' );
		if ( ! $sel.length ) return;

		// Use selectWoo (WC's fork) if available, fall back to select2.
		var fn = $.fn.selectWoo ? 'selectWoo' : ( $.fn.select2 ? 'select2' : null );
		if ( ! fn ) return;

		try { $sel[ fn ]( 'destroy' ); } catch ( e ) {}
		$sel[ fn ]( { width: '100%', minimumResultsForSearch: -1 } );

		// Copy computed height from billing_state select2 so they match exactly.
		var $stateContainer = $( '#billing_state' ).next( '.select2-container' );
		if ( $stateContainer.length ) {
			var h = $stateContainer.find( '.select2-selection--single' ).outerHeight();
			if ( h ) {
				$sel.next( '.select2-container' )
					.find( '.select2-selection--single' )
					.css( 'height', h + 'px' );
			}
		}
	}

	// ── 發票類型切換 ──────────────────────────────────────────────────────────

	function syncInvoiceType() {
		var type      = $( '#billing_wctw_invoice_type' ).val();
		var isCompany = ( type === 'company' );
		var cfg       = CARRIER_CONFIG[ type ];

		// 公司統編區塊：顯示/隱藏 carrier + company 欄位
		var $carrierField = $( '#billing_wctw_carrier_number_field' );
		var $taxIdField   = $( '#billing_wctw_company_tax_id_field' );
		var $titleField   = $( '#billing_wctw_company_title_field' );

		// 先全部隱藏
		$carrierField.hide();
		$taxIdField.hide();
		$titleField.hide();

		if ( isCompany ) {
			$taxIdField.show();
			$titleField.show();
		} else if ( cfg ) {
			// carrier_phone / carrier_cert / donate
			$carrierField.show();
			$( '#billing_wctw_carrier_number' )
				.attr( 'placeholder', cfg.placeholder )
				.closest( 'p, .form-row' ).find( 'label' ).first().text( cfg.label );
			setHint( $( '#billing_wctw_carrier_number' ), cfg.hint, '#646970' );
		}

		// 清空不相關欄位
		if ( ! cfg ) {
			$( '#billing_wctw_carrier_number' ).val( '' );
			setHint( $( '#billing_wctw_carrier_number' ), '', '' );
		}
		if ( ! isCompany ) {
			$( '#billing_wctw_company_tax_id, #billing_wctw_company_title' ).val( '' );
			$( '#wctw-taxid-hint' ).text( '' );
		}
	}

	// ── 載具號碼即時格式提示 ───────────────────────────────────────────────────

	function validateCarrierLive() {
		var type = $( '#billing_wctw_invoice_type' ).val();
		var cfg  = CARRIER_CONFIG[ type ];
		var val  = $( '#billing_wctw_carrier_number' ).val().trim();
		var $inp = $( '#billing_wctw_carrier_number' );

		if ( ! cfg || ! val ) {
			setHint( $inp, cfg ? cfg.hint : '', '#646970' );
			return;
		}
		if ( cfg.validate( val ) ) {
			setHint( $inp, '✓ 格式正確', '#00a32a' );
		} else {
			setHint( $inp, '⚠ ' + cfg.hint, '#d63638' );
		}
	}

	// ── 手機號碼即時格式提示 ───────────────────────────────────────────────────

	function validatePhoneLive() {
		if ( ! wcTwCheckout.phoneValidate ) {
			return;
		}
		// 非台灣地址跳過
		var country = $( '#billing_country' ).val();
		if ( country && country !== 'TW' ) {
			setHint( $( '#billing_phone' ), '', '' );
			return;
		}
		var val  = $( '#billing_phone' ).val().replace( /[\s\-]/g, '' );
		var $inp = $( '#billing_phone' );
		if ( ! val ) {
			setHint( $inp, '', '' );
			return;
		}
		if ( /^09\d{8}$/.test( val ) ) {
			setHint( $inp, '✓ 格式正確', '#00a32a' );
		} else {
			setHint( $inp, '⚠ 台灣手機格式：09xxxxxxxx（共 10 碼）', '#d63638' );
		}
	}

	// ── 統一編號 JS Checksum ──────────────────────────────────────────────────

	function taxIdChecksum( id ) {
		if ( id.length !== 8 || !/^\d{8}$/.test( id ) ) {
			return false;
		}
		var weights  = [ 1, 2, 1, 2, 1, 2, 4, 1 ];
		var checksum = 0;
		for ( var i = 0; i < 8; i++ ) {
			var p = parseInt( id[ i ] ) * weights[ i ];
			checksum += Math.floor( p / 10 ) + ( p % 10 );
		}
		if ( checksum % 10 === 0 ) {
			return true;
		}
		// Special rule: 7th digit (index 6) = 7
		if ( id[ 6 ] === '7' ) {
			var d7   = 7 * 4;
			checksum -= Math.floor( d7 / 10 ) + ( d7 % 10 );
			var alt   = 8 * 4;
			checksum += Math.floor( alt / 10 ) + ( alt % 10 );
			return checksum % 10 === 0;
		}
		return false;
	}

	// ── 統一編號 AJAX Lookup ──────────────────────────────────────────────────

	var taxidTimer = null;

	function lookupTaxId( taxId ) {
		var $title = $( '#billing_wctw_company_title' );
		var $hint  = $( '#wctw-taxid-hint' );

		if ( ! $hint.length ) {
			$hint = $( '<span id="wctw-taxid-hint">' ).css( { display: 'block', marginTop: '4px', fontSize: '0.85em' } );
			$( '#billing_wctw_company_tax_id' ).after( $hint );
		}

		$hint.text( data.i18n.looking ).css( 'color', '#646970' );

		$.post( data.ajaxUrl, {
			action:  'wc_tw_core_lookup_taxid',
			nonce:   data.taxidNonce,
			tax_id:  taxId,
		} )
		.done( function ( res ) {
			if ( res.success && res.data && res.data.company ) {
				$title.val( res.data.company ).trigger( 'change' );
				$hint.text( '✓ ' + res.data.company ).css( 'color', '#00a32a' );
			} else {
				var msg = ( res.data && res.data.message ) ? res.data.message : data.i18n.not_found;
				$hint.text( msg ).css( 'color', '#d63638' );
			}
		} )
		.fail( function () {
			$hint.text( data.i18n.error ).css( 'color', '#d63638' );
		} );
	}

	// ── Event Listeners ───────────────────────────────────────────────────────

	// 發票類型切換
	$( document.body ).on( 'change', '#billing_wctw_invoice_type', syncInvoiceType );

	// 載具號碼即時提示
	$( document.body ).on( 'input', '#billing_wctw_carrier_number', validateCarrierLive );

	// 手機號碼即時提示
	$( document.body ).on( 'input', '#billing_phone', validatePhoneLive );

	// 統一編號輸入後觸發查詢
	if ( data.taxidLookup ) {
		$( document.body ).on( 'input', '#billing_wctw_company_tax_id', function () {
			var val = $( this ).val().replace( /\D/g, '' );
			clearTimeout( taxidTimer );
			$( '#wctw-taxid-hint' ).text( '' );

			if ( val.length < 8 ) {
				return;
			}
			// JS checksum pre-check before hitting the API
			if ( ! taxIdChecksum( val ) ) {
				setHint( $( '#billing_wctw_company_tax_id' ), '⚠ 統編加權碼不符，請確認號碼', '#d63638' );
				return;
			}
			taxidTimer = setTimeout( function () { lookupTaxId( val ); }, 600 );
		} );
	}

	// 縣市切換 → 重建鄉鎮市區
	$( document.body ).on( 'change', '#billing_state, #shipping_state', function () {
		var prefix = $( this ).attr( 'id' ).replace( '_state', '' );
		rebuildCity( $( this ).closest( 'form' ), prefix );
	} );

	$( document.body ).on( 'change', '#billing_country, #shipping_country', function () {
		var $form = $( this ).closest( 'form' );
		rebuildCity( $form, 'billing' );
		rebuildCity( $form, 'shipping' );
	} );

	// 鄉鎮市區切換 → 自動帶入郵遞區號
	$( document.body ).on( 'change', '#billing_city, #shipping_city', function () {
		var prefix = $( this ).attr( 'id' ).replace( '_city', '' );
		var $form  = $( this ).closest( 'form' );
		$form.find( '#' + prefix + '_postcode' ).data( 'wctw-manual', false );
		fillPostcode( $form, prefix );
	} );

	$( document.body ).on( 'input', '#billing_postcode, #shipping_postcode', function () {
		$( this ).data( 'wctw-manual', true ).data( 'wctw-autofilled', false );
	} );

	$( document.body ).on( 'updated_checkout', function () {
		var $form = $( 'form.checkout' );
		rebuildCity( $form, 'billing' );
		rebuildCity( $form, 'shipping' );
		fillPostcode( $form, 'billing' );
		fillPostcode( $form, 'shipping' );
		syncInvoiceType();
		initInvoiceSelect2();
		syncFullName();

		// 強制隱藏名字與國家
		if ( data.nameConsolidate === 'yes' ) {
			$( '#billing_first_name_field, #shipping_first_name_field' ).remove();
		}
		$( '#billing_country_field, #shipping_country_field' ).hide();
	} );

	// ── Init ──────────────────────────────────────────────────────────────────

	// ── CVS Map Integration ──────────────────────────────────────────────────
	
	function initCVSMap() {
		var $form = $( 'form.checkout' );
		
		// 監聽物流方式變更
		$( document.body ).on( 'updated_checkout', function() {
			checkShippingForCVS();
		});

		function checkShippingForCVS() {
			var method = $form.find( 'input[name^="shipping_method"]:checked' ).val() || '';
			var isCVS  = /711|fami|hilife|ok|cvs/i.test( method );
			
			var $targetField = $( '#wctw-cvs-store-name-field' );
			if ( ! $targetField.length ) return;

			if ( isCVS ) {
				$targetField.show();
				$( '#wctw-cvs-store-address-field' ).show();
				
				if ( ! $( '#wctw-select-store-btn' ).length ) {
					var $btn = $( '<button type="button" id="wctw-select-store-btn" class="button alt" style="margin-top:10px; width:100%;">' )
						.text( '🔍 選擇取貨門市' );
					$targetField.append( $btn );
					
					$btn.on( 'click', function(e) {
						e.preventDefault();
						openCVSMap( method );
					});
				}
			} else {
				$targetField.hide();
				$( '#wctw-cvs-store-address-field' ).hide();
			}
		}

		function openCVSMap( method ) {
			var subtype = '711';
			if ( /fami/i.test( method ) ) subtype = 'FAMI';
			if ( /hilife/i.test( method ) ) subtype = 'HILIFE';
			if ( /ok/i.test( method ) ) subtype = 'OKMART';

			var params = {
				MerchantID: data.merchantId,
				LogisticsType: 'CVS',
				LogisticsSubType: subtype,
				IsCollection: 'N',
				ServerReplyURL: data.mapCallbackUrl,
				ExtraData: 'wctw_checkout'
			};

			// 建立一個隱藏表單來 POST 到綠界
			var $tempForm = $( '<form method="POST" target="cvs_map_popup">' )
				.attr( 'action', data.mapUrl );
			
			$.each( params, function( key, val ) {
				$tempForm.append( $( '<input type="hidden">' ).attr( 'name', key ).val( val ) );
			});

			$( 'body' ).append( $tempForm );
			
			window.open( '', 'cvs_map_popup', 'width=1000,height=700,scrollbars=yes,resizable=yes' );
			$tempForm.submit().remove();
		}

		checkShippingForCVS(); // 初始檢查
	}

	$( function () {
		// 初始執行
		var $form = $( 'form.checkout' );
		rebuildCity( $form, 'billing' );
		rebuildCity( $form, 'shipping' );
		fillPostcode( $form, 'billing' );
		fillPostcode( $form, 'shipping' );
		syncInvoiceType();
		initInvoiceSelect2();
		syncFullName();
		initCVSMap();

		// 強制隱藏名字與國家
		if ( data.nameConsolidate === 'yes' ) {
			$( '#billing_first_name_field, #shipping_first_name_field' ).remove();
		}
		$( '#billing_country_field, #shipping_country_field' ).hide();
	} );

} )( jQuery );
