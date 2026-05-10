const { useState, useEffect } = window.wp.element;

export const InvoiceBlock = ( { checkoutExtensionData } ) => {
    if ( ! checkoutExtensionData || ! checkoutExtensionData.setExtensionData ) {
        return null;
    }

    const { setExtensionData } = checkoutExtensionData;
    const settings = window.wcSettings ? window.wcSettings.getSetting('wc-tw-core_data', {}) : {};
    const { is_taxid_lookup, ajaxUrl, taxidNonce } = settings;
    
    const [ invoiceType, setInvoiceType ] = useState('personal');
    const [ taxId, setTaxId ] = useState('');
    const [ companyTitle, setCompanyTitle ] = useState('');
    const [ isLoading, setIsLoading ] = useState(false);
    const [ lookupError, setLookupError ] = useState('');

    useEffect(() => {
        setExtensionData( 'wc-tw-core', 'invoice-type', invoiceType );
        if ( invoiceType === 'company' ) {
            setExtensionData( 'wc-tw-core', 'company-tax-id', taxId );
            setExtensionData( 'wc-tw-core', 'company-title', companyTitle );
        } else {
            setExtensionData( 'wc-tw-core', 'company-tax-id', '' );
            setExtensionData( 'wc-tw-core', 'company-title', '' );
        }
    }, [ invoiceType, taxId, companyTitle, setExtensionData ]);

    const handleLookup = async () => {
        if ( taxId.length !== 8 ) {
            setLookupError('請輸入完整的 8 碼統編');
            return;
        }
        setIsLoading(true);
        setLookupError('');

        try {
            const formData = new URLSearchParams();
            formData.append('action', 'wc_tw_core_lookup_taxid');
            formData.append('nonce', taxidNonce);
            formData.append('tax_id', taxId);

            const response = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();

            if ( data.success ) {
                setCompanyTitle( data.data.company );
            } else {
                setLookupError( data.data.message || '查無此公司' );
            }
        } catch (error) {
            setLookupError('連線錯誤，請重試');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="wc-tw-core-invoice-block" style={{ padding: '20px', background: '#f8f9fa', borderRadius: '8px', border: '1px solid #ddd', marginTop: '20px' }}>
            <h3 style={{ fontSize: '1.2em', margin: '0 0 15px 0' }}>發票資訊 (React 區塊版)</h3>
            
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>發票類型</label>
                <select 
                    value={invoiceType} 
                    onChange={(e) => setInvoiceType(e.target.value)}
                    style={{ width: '100%', padding: '10px', borderRadius: '4px', border: '1px solid #ccc' }}
                >
                    <option value="personal">個人二聯式</option>
                    <option value="company">公司三聯式 (統編)</option>
                </select>
            </div>

            { invoiceType === 'company' && (
                <>
                    <div style={{ marginBottom: '15px' }}>
                        <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>統一編號</label>
                        <div style={{ display: 'flex', gap: '8px' }}>
                            <input 
                                type="text" 
                                value={taxId} 
                                onChange={(e) => setTaxId(e.target.value)}
                                style={{ flex: 1, padding: '10px', borderRadius: '4px', border: '1px solid #ccc' }}
                                placeholder="請輸入 8 碼統編"
                                maxLength="8"
                            />
                            { is_taxid_lookup === 'yes' && (
                                <button 
                                    type="button" 
                                    onClick={handleLookup}
                                    disabled={isLoading || taxId.length !== 8}
                                    style={{ 
                                        padding: '0 15px', 
                                        background: '#2271b1', 
                                        color: '#fff', 
                                        border: 'none', 
                                        borderRadius: '4px',
                                        cursor: (isLoading || taxId.length !== 8) ? 'not-allowed' : 'pointer',
                                        opacity: (isLoading || taxId.length !== 8) ? 0.6 : 1
                                    }}
                                >
                                    { isLoading ? '查詢中...' : '帶入公司名' }
                                </button>
                            ) }
                        </div>
                        { lookupError && <div style={{ color: '#d63638', fontSize: '13px', marginTop: '5px' }}>{lookupError}</div> }
                    </div>
                    <div style={{ marginBottom: '15px' }}>
                        <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>公司抬頭</label>
                        <input 
                            type="text" 
                            value={companyTitle} 
                            onChange={(e) => setCompanyTitle(e.target.value)}
                            style={{ width: '100%', padding: '10px', borderRadius: '4px', border: '1px solid #ccc' }}
                            placeholder="請輸入公司完整名稱"
                        />
                    </div>
                </>
            ) }
        </div>
    );
};
