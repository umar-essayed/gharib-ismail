<?php $title = 'شاشة نقطة البيع'; ?>
<style>
.pos-product-item.highlighted {
    background: #e2fbe8 !important;
    border-color: #16a34a !important;
    box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.25) !important;
    transform: translateY(-1px);
}
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">نقطة البيع POS</h5>
    <div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="testPrintPopupBtn">اختبار F1</button>
        <a class="btn btn-sm btn-light" href="<?= url('/pos/suspended') ?>">الفواتير المعلقة</a>
        <?php if (!$openShift): ?><span class="badge bg-danger">لا يوجد شيفت مفتوح</span><?php else: ?><span class="badge bg-success">الشيفت: <?= e($openShift['shift_no']) ?></span><?php endif; ?>
    </div>
</div>

<div class="pos-shell">
    <div class="pos-products">
        <div class="mb-2">
            <input type="text" id="posSearch" class="form-control" placeholder="ابحث بالاسم أو الباركود" autocomplete="off" autocapitalize="off" spellcheck="false">
        </div>
        <div class="pos-products-list" id="posProducts">
            <!-- Products will be loaded dynamically via JS for speed -->
        </div>
        <script>
        window.posAllProducts = <?= json_encode(array_map(static function($p) {
            return [
                'id' => (int) $p['id'],
                'name' => $p['name'],
                'price' => (float) $p['sale_price'],
                'barcode' => $p['barcode'],
                'sku' => $p['sku'] ?? null,
                'internal_code' => $p['internal_code'] ?? null,
                'stock' => (float) ($p['current_stock'] ?? 0),
                'sell_type' => $p['sell_type'] ?? 'piece',
                'allow_scale_barcode' => (int) ($p['allow_scale_barcode'] ?? 0),
                'scale_code' => $p['scale_code'] ?? null,
                'weight_unit' => $p['weight_unit'] ?? 'kg',
                'package_type' => $p['package_type'] ?? 'piece',
                'package_size' => (float) ($p['package_size'] ?? 1),
                'promo_id' => !empty($p['promotion_id']) ? (int) $p['promotion_id'] : null,
                'promo_name' => $p['promotion_name'] ?? null,
                'promo_type' => $p['promotion_discount_type'] ?? null,
                'promo_value' => isset($p['promotion_discount_value']) ? (float) $p['promotion_discount_value'] : null,
                'promo_start' => $p['promotion_start_date'] ?? null,
                'promo_end' => $p['promotion_end_date'] ?? null,
            ];
        }, $products), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '[]' ?>;
        </script>
    </div>

    <div class="pos-cart">
        <form method="post" action="<?= url('/pos/sell') ?>" id="saleForm">
            <?= csrf_field() ?>
            <input type="hidden" name="items_json" id="itemsJson">
            <input type="hidden" name="payment_breakdown_json" id="paymentBreakdownJson">
            <input type="hidden" name="quick_action" id="quickAction" value="">
            <input type="hidden" name="print_transport" id="printTransport" value="">
            <input type="hidden" name="print_job_id" id="printJobId" value="">
            <input type="hidden" name="shift_id" value="<?= e($openShift['id'] ?? '') ?>">

            <div class="mb-2">
                <label class="form-label">العميل</label>
                <select name="customer_id" class="form-select" id="customerSelect">
                    <?php if (empty($customers)): ?>
                        <option value="" disabled selected>لا يوجد عملاء مضافين. أضف عميلًا نقديًا أو مسجلاً</option>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                            <?php $selectedCustomer = isset($resumePayload['customer_id']) && $resumePayload['customer_id'] !== null
                                ? ((int) $resumePayload['customer_id'] === (int) $c['id'])
                                : ((int) $defaultCustomerId === (int) $c['id']); ?>
                            <option value="<?= (int) $c['id'] ?>" data-is-cash="<?= (int) ($c['is_cash_customer'] ?? 0) ?>" <?= $selectedCustomer ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="table-responsive mb-2 pos-cart-table-wrapper">
                <table class="table table-sm align-middle" id="cartTable">
                    <thead><tr><th>الصنف</th><th>الوحدة</th><th>كمية</th><th>سعر</th><th>الإجمالي</th><th></th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="pos-summary mb-2">
                <div class="d-flex justify-content-between"><span>الإجمالي:</span><strong id="sumTotal">0.00</strong></div>
                <div class="d-flex justify-content-between"><span>المدفوع:</span><strong id="sumPaid">0.00</strong></div>
                <div class="d-flex justify-content-between"><span>المتبقي:</span><strong id="sumDue">0.00</strong></div>
                <div class="d-flex justify-content-between"><span>الباقي:</span><strong id="sumChange">0.00</strong></div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label">طريقة الدفع</label>
                    <select name="payment_method_id" class="form-select" id="paymentMethod" required>
                        <?php foreach ($paymentMethods as $m): ?>
                            <option value="<?= (int) $m['id'] ?>" data-code="<?= e($m['code']) ?>" <?= $m['is_default'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">المدفوع</label>
                    <input type="number" step="0.001" name="paid_total" id="paidTotal" value="0" class="form-control" min="0">
                </div>
            </div>

            <div class="card p-2 mb-2 d-none" id="mixedPaymentsBox">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>تفصيل الدفع المختلط</strong>
                    <button class="btn btn-sm btn-primary" type="button" id="addMixedRow">إضافة سطر</button>
                </div>
                <div id="mixedRows"></div>
            </div>

            <div class="mb-2">
                <label class="form-label">ملاحظة</label>
                <input class="form-control" name="note">
            </div>
        </form>

        <form method="post" action="<?= url('/pos/hold') ?>" class="d-none" id="holdForm">
            <?= csrf_field() ?>
            <input type="hidden" name="items_json" id="holdItemsJson">
            <input type="hidden" name="customer_id" id="holdCustomerId">
        </form>

        <div class="row g-2 mt-2">
            <div class="col-8">
                <button class="btn btn-success w-100" type="submit" form="saleForm">دفع وحفظ الفاتورة</button>
            </div>
            <div class="col-4">
                <button class="btn btn-warning w-100" type="submit" form="holdForm">تعليق</button>
            </div>
        </div>

        <iframe id="silentPrintFrame" name="silentPrintFrame" class="d-none" title="silent-print-frame"></iframe>
    </div>
</div>

<script src="<?= url('/assets/vendor/qz-tray/qz-tray.js') ?>"></script>
<script>
(function(){
    const productsWrap = document.getElementById('posProducts');
    const search = document.getElementById('posSearch');
    const tbody = document.querySelector('#cartTable tbody');
    const totalEl = document.getElementById('sumTotal');
    const paidEl = document.getElementById('sumPaid');
    const dueEl = document.getElementById('sumDue');
    const changeEl = document.getElementById('sumChange');
    const itemsJson = document.getElementById('itemsJson');
    const paymentBreakdownJson = document.getElementById('paymentBreakdownJson');
    const quickAction = document.getElementById('quickAction');
    const printTransport = document.getElementById('printTransport');
    const printJobIdInput = document.getElementById('printJobId');
    const holdItemsJson = document.getElementById('holdItemsJson');
    const holdCustomerId = document.getElementById('holdCustomerId');
    const customerSelect = document.getElementById('customerSelect');
    const paymentMethod = document.getElementById('paymentMethod');
    const paidTotal = document.getElementById('paidTotal');
    const saleForm = document.getElementById('saleForm');
    const testPrintPopupBtn = document.getElementById('testPrintPopupBtn');
    const mixedBox = document.getElementById('mixedPaymentsBox');
    const mixedRows = document.getElementById('mixedRows');
    const addMixedRowBtn = document.getElementById('addMixedRow');
    const arabicDigitMap = {
        '٠':'0','١':'1','٢':'2','٣':'3','٤':'4',
        '٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'
    };
    const arabicKeyboardToLatinMap = {
        'ض':'q','ص':'w','ث':'e','ق':'r','ف':'t','غ':'y','ع':'u','ه':'i','خ':'o','ح':'p','ج':'[','د':']',
        'ش':'a','س':'s','ي':'d','ب':'f','ل':'g','ا':'h','ت':'j','ن':'k','م':'l','ك':';','ط':'\'',
        'ئ':'z','ء':'x','ؤ':'c','ر':'v','لا':'b','ى':'n','ة':'m','و':',','ز':'.','ظ':'/',
        '،':',','؟':'?'
    };

    const canModifyPrice = <?= can('pos.modify_price') ? 'true' : 'false' ?>;
    const paymentMethods = <?= json_encode(array_values(array_map(static function($m){
        return ['id' => (int)$m['id'], 'name' => $m['name'], 'code' => $m['code']];
    }, $paymentMethods)), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '[]' ?>;
    const preferredPrinterName = <?= json_encode(\App\Services\SettingsService::get('receipt_printer_name', 'XP-80'), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '""' ?>;
    const posSearchEndpoint = <?= json_encode(url('/pos/search'), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '""' ?>;
    const qzScriptLoaded = typeof window.qz !== 'undefined';
    quickAction.value = '';

    let cart = [];
    let mixedPayments = [];
    let shortcutSubmitting = false;
    let activePrintJobId = '';
    let pendingBrowserPrintPopup = null;
    let qzConnectPromise = null;
    const productsIndex = {};
    const productCards = [];
    const exactScanIndex = Object.create(null);

    function normalizeMojibakeText(value){
        const text = String(value ?? '');
        if (!/[ØÙÃ]/.test(text)) {
            return text;
        }

        try {
            const repaired = decodeURIComponent(escape(text));
            if (/[ء-ي]/.test(repaired)) {
                return repaired;
            }
        } catch (e) {}

        return text;
    }

    function remapArabicKeyboardToLatin(value){
        const text = String(value ?? '');
        let result = '';

        for (const ch of text) {
            result += arabicKeyboardToLatinMap[ch] || ch;
        }

        return result;
    }

    function normalizeScannedQuery(rawValue){
        const value = String(rawValue ?? '').trim();
        if (value === '') return '';

        // Scanner may type latin barcodes while keyboard layout is Arabic.
        return remapArabicKeyboardToLatin(value);
    }

    function normalizeSearchText(value){
        return normalizeMojibakeText(value)
            .replace(/[٠-٩]/g, (d) => arabicDigitMap[d] || d)
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim();
    }

    function escapeHtml(value){
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function addExactScanKey(value, product){
        const key = normalizeSearchText(value);
        if (!key || exactScanIndex[key]) return;
        exactScanIndex[key] = product;
    }

    const allProducts = window.posAllProducts || [];
    allProducts.forEach((p) => {
        try {
            if (p && p.id) {
                p.name = normalizeMojibakeText(p.name);
                productsIndex[p.id] = p;
                addExactScanKey(p.barcode, p);
                addExactScanKey(p.sku, p);
                addExactScanKey(p.internal_code, p);
                const normalizedName = normalizeSearchText(p.name);
                const searchText = [
                    normalizedName,
                    normalizeSearchText(p.barcode),
                    normalizeSearchText(p.sku),
                    normalizeSearchText(p.internal_code)
                ].filter(Boolean).join(' ');
                productCards.push({
                    product: p,
                    normalizedName,
                    searchText,
                });
            }
        } catch (e) {}
    });

    <?php if (!empty($resumePayload['payload_json'])): ?>
    try { cart = JSON.parse(<?= json_encode($resumePayload['payload_json']) ?>) || []; } catch(e){}
    <?php endif; ?>

    function money(v){ return (Math.round(v * 1000) / 1000).toFixed(2); }
    function weightText(v){ return (Math.round(v * 1000) / 1000).toFixed(3); }

    function normalizeNumericText(value){
        return String(value ?? '')
            .replace(/[٠-٩]/g, (d) => arabicDigitMap[d] || d)
            .replace(/[٫،,]/g, '.')
            .replace(/[^\d.\-]/g, '');
    }

    function toNumber(value, fallback = 0){
        const n = parseFloat(normalizeNumericText(value));
        return Number.isFinite(n) ? n : fallback;
    }

    function currentCustomerIsCash(){
        const op = customerSelect.selectedOptions[0];
        return op ? String(op.dataset.isCash || '0') === '1' : false;
    }

    function isDiscreteUnit(unit){
        return unit === 'piece' || unit === 'box';
    }

    function normalizeQtyByUnit(value, unit){
        const n = toNumber(value, 0);
        if (isDiscreteUnit(unit)) {
            return Math.max(1, Math.round(n || 1));
        }
        return Math.max(0.001, Math.round(n * 1000) / 1000);
    }

    function qtyInputValue(item){
        const qty = normalizeQtyByUnit(item.qty, item.sale_unit);
        return isDiscreteUnit(item.sale_unit) ? String(Math.round(qty)) : String(qty);
    }

    function customerAllowedMethod(method){
        if (!currentCustomerIsCash()) return true;
        return method.code !== 'credit';
    }

    function nonMixedMethods(){
        return paymentMethods.filter((m) => m.code !== 'mixed' && customerAllowedMethod(m));
    }

    function selectedMethodCode(){
        const op = paymentMethod.selectedOptions[0];
        return op ? (op.dataset.code || '') : '';
    }

    function renderPaymentMethodOptions(){
        const current = parseInt(paymentMethod.value || '0', 10);
        const allowed = paymentMethods.filter(customerAllowedMethod);
        paymentMethod.innerHTML = allowed.map((m) => `<option value="${m.id}" data-code="${m.code}">${m.name}</option>`).join('');
        const fallback = allowed.find((m) => m.is_default) || allowed[0] || null;
        if (!fallback) return;
        const keep = allowed.find((m) => m.id === current);
        paymentMethod.value = String((keep || fallback).id);
    }

    function activePromotion(product){
        if (!product || !product.promo_id || !product.promo_type) return null;
        return {
            id: parseInt(product.promo_id, 10),
            name: product.promo_name || '',
            type: product.promo_type,
            value: parseFloat(product.promo_value || 0)
        };
    }

    function applyPromotion(price, promotion){
        const raw = Math.max(0, parseFloat(price || 0));
        if (!promotion) return raw;

        if (promotion.type === 'percent') {
            const pct = Math.max(0, Math.min(100, parseFloat(promotion.value || 0)));
            return Math.max(0, raw - ((raw * pct) / 100));
        }
        if (promotion.type === 'fixed') {
            const fixed = Math.max(0, parseFloat(promotion.value || 0));
            return Math.max(0, raw - fixed);
        }
        if (promotion.type === 'price') {
            return Math.max(0, parseFloat(promotion.value || 0));
        }
        return raw;
    }

    function unitOptionsFor(product){
        const isWeight = product.sell_type === 'weight';
        const size = Math.max(1, parseFloat(product.package_size || 1));
        const basePrice = parseFloat(product.price || 0);
        const promotion = activePromotion(product);
        const options = [];

        if (isWeight) {
            // Weight-priced items can be sold by kilogram or sack depending on package type.
            const kgPrice = product.package_type === 'sack' ? (basePrice / size) : basePrice;
            options.push({key: 'kg', label: 'كجم', factor: 1, defaultPrice: applyPromotion(kgPrice, promotion)});

            if (product.package_type === 'sack' && size > 1) {
                const sackPrice = product.package_type === 'sack' ? basePrice : (basePrice * size);
                options.push({key: 'sack', label: 'شيكارة', factor: size, defaultPrice: applyPromotion(sackPrice, promotion)});
            }
        } else {
            // Piece-priced items can be sold by piece or box depending on package type.
            const piecePrice = product.package_type === 'box' ? (basePrice / size) : basePrice;
            options.push({key: 'piece', label: 'قطعة', factor: 1, defaultPrice: applyPromotion(piecePrice, promotion)});

            if (product.package_type === 'box' && size > 1) {
                const boxPrice = product.package_type === 'box' ? basePrice : (basePrice * size);
                options.push({key: 'box', label: 'علبة', factor: size, defaultPrice: applyPromotion(boxPrice, promotion)});
            }
        }
        return options;
    }

    function defaultUnitFor(product){
        if (product.package_type === 'box' || product.package_type === 'sack') {
            return product.package_type;
        }
        return product.sell_type === 'weight' ? 'kg' : 'piece';
    }

    function unitMeta(item){
        const options = unitOptionsFor(item.product_meta || {});
        const selected = options.find((o) => o.key === item.sale_unit) || options[0];
        return {options, selected};
    }

    function hydrateCartFromCatalog(){
        cart = cart.map((item) => {
            const productMeta = productsIndex[item.product_id] || item.product_meta || {};
            const options = unitOptionsFor(productMeta);
            const fallbackUnit = options.length ? options[0].key : defaultUnitFor(productMeta);
            const saleUnit = item.sale_unit || fallbackUnit;
            const selected = options.find((o) => o.key === saleUnit) || options[0];
            const normalizedQty = normalizeQtyByUnit(item.qty, saleUnit);

            return {
                ...item,
                qty: normalizedQty,
                unit_price: parseFloat(item.unit_price || (selected ? selected.defaultPrice : (productMeta.price || 0))),
                sale_unit: saleUnit,
                product_meta: productMeta,
            };
        });
    }

    function addMixedRow(defaultMethodId, defaultAmount){
        const methods = nonMixedMethods();
        if (!methods.length) return;
        mixedPayments.push({
            payment_method_id: defaultMethodId || methods[0].id,
            amount: toNumber(defaultAmount, 0)
        });
        renderMixedRows();
        updateTotals();
    }

    function renderMixedRows(){
        mixedRows.innerHTML = '';
        const methods = nonMixedMethods();
        mixedPayments.forEach((row, i) => {
            const rowEl = document.createElement('div');
            rowEl.className = 'row row-cols-1 g-2 mb-2';
            rowEl.innerHTML = `
                <div class="col-7">
                    <select class="form-select form-select-sm" data-mi="${i}" data-k="payment_method_id">
                        ${methods.map((m) => `<option value="${m.id}" ${parseInt(row.payment_method_id, 10)===m.id?'selected':''}>${m.name}</option>`).join('')}
                    </select>
                </div>
                <div class="col-4">
                    <input type="number" min="0" step="0.001" class="form-control form-control-sm" data-mi="${i}" data-k="amount" value="${money(row.amount || 0)}">
                </div>
                <div class="col-1 d-flex align-items-center">
                    <button class="btn btn-sm btn-danger" type="button" data-mrm="${i}" title="حذف سطر الدفع">حذف</button>
                </div>
            `;
            mixedRows.appendChild(rowEl);
        });
        paymentBreakdownJson.value = JSON.stringify(mixedPayments.filter((p) => toNumber(p.amount, 0) > 0));
    }

    function syncCartPayload(){
        const payload = cart.map((item) => ({
            product_id: item.product_id,
            name: item.name,
            qty: parseFloat(item.qty || 0),
            sale_unit: item.sale_unit,
            unit_price: parseFloat(item.unit_price || 0),
            scanned_barcode: item.scanned_barcode || null,
            is_scale_item: item.is_scale_item ? 1 : 0,
            scale_weight: item.scale_weight !== undefined && item.scale_weight !== null ? parseFloat(item.scale_weight) : null,
            scale_price: item.scale_price !== undefined && item.scale_price !== null ? parseFloat(item.scale_price) : null,
            discount_amount: 0,
            tax_amount: 0,
        }));

        itemsJson.value = JSON.stringify(payload);
        holdItemsJson.value = JSON.stringify(payload);
        holdCustomerId.value = customerSelect.value || '';
    }

    function lineTotal(item){
        const qty = toNumber(item.qty, 0);
        const unitPrice = toNumber(item.unit_price, 0);
        return qty * unitPrice;
    }

    function render(){
        tbody.innerHTML = '';
        cart.forEach((item, i) => {
            const line = lineTotal(item);
            const meta = unitMeta(item);
            const productName = escapeHtml(normalizeMojibakeText(item.name));
            const promoNameRaw = item.product_meta && item.product_meta.promo_name ? item.product_meta.promo_name : 'عرض خاص';
            const promotionName = escapeHtml(normalizeMojibakeText(promoNameRaw));
            const promoText = item.product_meta && item.product_meta.promo_id
                ? `<div class="small text-warning fw-bold">${promotionName}</div>`
                : '';
            const scaleText = item.is_scale_item
                ? `<div class="small text-primary fw-semibold">${weightText(item.qty)} كجم × ${money(item.unit_price)} = ${money(line)}</div>`
                : '';
            const unitInput = meta.options.length > 1
                ? `<select class="form-select form-select-sm" data-i="${i}" data-k="sale_unit">${meta.options.map((o) => `<option value="${o.key}" ${o.key===item.sale_unit?'selected':''}>${o.label}</option>`).join('')}</select>`
                : `<span class="badge text-bg-light">${meta.selected.label}</span>`;

            const tr = document.createElement('tr');
            const qtyStep = isDiscreteUnit(item.sale_unit) ? '1' : '0.001';
            const qtyMin = isDiscreteUnit(item.sale_unit) ? '1' : '0.001';
            const qtyMode = isDiscreteUnit(item.sale_unit) ? 'numeric' : 'decimal';
            const qtyReadonly = item.is_scale_item ? 'readonly' : '';
            const unitReadonly = item.is_scale_item ? '<span class="badge text-bg-info">كجم</span>' : unitInput;
            tr.innerHTML = `
                <td>${productName}${promoText}${scaleText}</td>
                <td>${unitReadonly}</td>
                <td><input type="text" inputmode="${qtyMode}" class="form-control form-control-sm" value="${qtyInputValue(item)}" data-i="${i}" data-k="qty" data-min="${qtyMin}" data-step="${qtyStep}" ${qtyReadonly}></td>
                <td><input type="number" min="0" step="0.001" class="form-control form-control-sm" value="${item.unit_price}" data-i="${i}" data-k="unit_price" ${canModifyPrice ? '' : 'disabled'}></td>
                <td data-line-total="${i}">${money(line)}</td>
                <td><button class="btn btn-sm btn-danger" type="button" data-rm="${i}" title="حذف الصنف">حذف</button></td>`;
            tbody.appendChild(tr);
        });

        syncCartPayload();
        updateTotals();
    }

    function updateTotals(){
        let sum = 0;
        cart.forEach((item) => {
            sum += lineTotal(item);
        });

        const code = selectedMethodCode();
        const isMixed = code === 'mixed';

        if (code === 'cash' || code === 'card') {
            paidTotal.value = money(sum);
        } else if (code === 'credit') {
            if (paidTotal.value === '' || isNaN(toNumber(paidTotal.value, 0))) {
                paidTotal.value = '0';
            }
        }

        let paid = toNumber(paidTotal.value, 0);

        if (isMixed) {
            paid = mixedPayments.reduce((acc, row) => acc + (toNumber(row.amount, 0) || 0), 0);
            paidTotal.value = money(paid);
        }

        const due = Math.max(0, sum - paid);
        const change = Math.max(0, paid - sum);

        totalEl.textContent = money(sum);
        paidEl.textContent = money(paid);
        dueEl.textContent = money(due);
        changeEl.textContent = money(change);

        paymentBreakdownJson.value = JSON.stringify(mixedPayments.filter((p) => toNumber(p.amount, 0) > 0));
    }

    function cartTotal(){
        let sum = 0;
        cart.forEach((item) => {
            sum += lineTotal(item);
        });
        return sum;
    }

    function qzAvailable(){
        if (!(qzScriptLoaded && typeof window.qz === 'object' && !!window.qz.websocket)) {
            return false;
        }
        try {
            return typeof window.qz.websocket.isActive === 'function' && window.qz.websocket.isActive();
        } catch (e) {
            return false;
        }
    }

    async function ensureQzConnected(){
        if (!qzAvailable()) {
            throw new Error('QZ Tray غير متاح داخل الصفحة');
        }
        if (window.qz.websocket.isActive()) {
            return;
        }
        if (!qzConnectPromise) {
            qzConnectPromise = window.qz.websocket.connect({
                retries: 1,
                delay: 0
            }).catch((err) => {
                qzConnectPromise = null;
                throw err;
            });
        }
        await qzConnectPromise;
    }

    async function resolveQzPrinter(){
        const hint = String(preferredPrinterName || '').trim();
        if (hint) {
            try {
                return await window.qz.printers.find(hint);
            } catch (e) {}
        }

        let allPrinters = [];
        try {
            allPrinters = await window.qz.printers.find();
        } catch (e) {}

        if (Array.isArray(allPrinters) && allPrinters.length > 0) {
            const xpPrinter = allPrinters.find((name) => /xp[-\s]?80/i.test(String(name)));
            if (xpPrinter) {
                return xpPrinter;
            }
            const thermalPrinter = allPrinters.find((name) => /(thermal|receipt|pos|xprinter|epson)/i.test(String(name)));
            if (thermalPrinter) {
                return thermalPrinter;
            }
            return allPrinters[0];
        }

        return await window.qz.printers.getDefault();
    }

    async function silentPrintWithQz(payload){
        if (!payload || !payload.printUrl) {
            throw new Error('بيانات الطباعة غير مكتملة');
        }

        await ensureQzConnected();
        const printer = await resolveQzPrinter();
        if (!printer) {
            throw new Error('لم يتم العثور على طابعة متاحة');
        }

        const absolutePrintUrl = new URL(String(payload.printUrl), window.location.origin).href;
        const printResponse = await fetch(absolutePrintUrl, {
            credentials: 'same-origin',
            cache: 'no-store'
        });
        if (!printResponse.ok) {
            throw new Error('تعذر تحميل محتوى الفاتورة للطباعة');
        }
        let htmlContent = await printResponse.text();
        htmlContent = htmlContent.replace(/<script[\s\S]*?<\/script>/gi, '');
        if (!/<base\s/i.test(htmlContent)) {
            htmlContent = htmlContent.replace(
                /<head([^>]*)>/i,
                `<head$1><base href="${window.location.origin}/">`
            );
        }

        const config = window.qz.configs.create(printer, {
            jobName: `POS-${payload.invoiceNo || payload.invoiceId || Date.now()}`,
            units: 'mm',
            margins: 0,
            // Keep original size to avoid blur from automatic scaling.
            scaleContent: false,
            rasterize: false
        });
        const data = [{
            type: 'pixel',
            format: 'html',
            flavor: 'plain',
            data: htmlContent
        }];

        await window.qz.print(config, data);
    }

    async function silentPrintWithBrowserPopup(payload){
        if (!payload || !payload.printUrl) {
            throw new Error('بيانات الطباعة غير مكتملة');
        }

        const absolutePrintUrl = new URL(String(payload.printUrl), window.location.origin);
        absolutePrintUrl.searchParams.set('autoprint', '1');
        absolutePrintUrl.searchParams.set('self_close', '1');

        let popup = pendingBrowserPrintPopup;
        pendingBrowserPrintPopup = null;

        if (!popup || popup.closed) {
            const popupName = `pos-print-${payload.invoiceId || Date.now()}`;
            const popupFeatures = [
                'popup=yes',
                'width=460',
                'height=760',
                'left=80',
                'top=40',
                'resizable=yes',
                'scrollbars=yes',
                'toolbar=no',
                'location=no',
                'menubar=no',
                'status=no'
            ].join(',');
            popup = window.open('about:blank', popupName, popupFeatures);
        }
        if (!popup) {
            throw new Error('المتصفح منع نافذة الطباعة. اسمح بالنوافذ المنبثقة للموقع.');
        }

        popup.location.replace(absolutePrintUrl.href);
        try { popup.focus(); } catch (e) {}

        await new Promise((resolve) => {
            const startedAt = Date.now();
            const timer = window.setInterval(() => {
                if (popup.closed) {
                    window.clearInterval(timer);
                    resolve();
                    return;
                }
                if (Date.now() - startedAt > 180000) {
                    window.clearInterval(timer);
                    resolve();
                }
            }, 500);
        });
    }

    function prepareShortcutPayment(){
        const total = cartTotal();
        if (total <= 0) {
            return false;
        }

        if (selectedMethodCode() === 'mixed') {
            const methods = nonMixedMethods();
            if (!methods.length) {
                alert('لا توجد طريقة دفع صالحة لإتمام الحفظ السريع');
                return false;
            }
            mixedPayments = [];
            renderMixedRows();
            paymentMethod.value = String(methods[0].id);
            paymentMethod.dispatchEvent(new Event('change'));
        }

        paidTotal.value = money(total);
        updateTotals();
        return true;
    }

    function resetShortcutState(){
        shortcutSubmitting = false;
        quickAction.value = '';
        printTransport.value = '';
        printJobIdInput.value = '';
        activePrintJobId = '';
        saleForm.target = '';
        pendingBrowserPrintPopup = null;
    }

    function finalizeSuccessfulPrint(){
        resetShortcutState();
        cart = [];
        mixedPayments = [];
        renderMixedRows();
        paidTotal.value = '0';
        render();
        keepSearchReady(true);
    }

    function submitShortcut(action){
        if (shortcutSubmitting) {
            return;
        }
        if (cart.length === 0) {
            alert('أضف أصنافًا للفاتورة أولًا');
            return;
        }
        if (!prepareShortcutPayment()) {
            return;
        }

        shortcutSubmitting = true;
        quickAction.value = action;
        if (action === 'print') {
            activePrintJobId = `print-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
            printJobIdInput.value = activePrintJobId;
            const useQz = qzAvailable();
            printTransport.value = useQz ? 'qz' : 'popup';
            setTimeout(() => {
                if (shortcutSubmitting && quickAction.value === 'print') {
                    resetShortcutState();
                }
            }, 45000);
        } else {
            activePrintJobId = '';
            printJobIdInput.value = '';
            printTransport.value = '';
        }
        if (typeof saleForm.requestSubmit === 'function') {
            saleForm.requestSubmit();
            return;
        }
        const event = new Event('submit', { cancelable: true, bubbles: true });
        saleForm.dispatchEvent(event);
    }

    function updateRowLineTotal(index){
        const row = tbody.querySelector(`tr:nth-child(${parseInt(index, 10) + 1})`);
        if (!row || !cart[index]) return;
        const cell = row.querySelector('[data-line-total]');
        if (!cell) return;
        cell.textContent = money(lineTotal(cart[index]));
    }

    function addItem(product, options = {}){
        const isScaleItem = !!options.is_scale_item;
        const defaultUnit = isScaleItem ? 'kg' : defaultUnitFor(product);
        let idx = -1;

        if (!isScaleItem) {
            idx = cart.findIndex(i => i.product_id === product.id && i.sale_unit === defaultUnit && !i.is_scale_item);
            if (idx < 0) {
                idx = cart.findIndex(i => i.product_id === product.id && !i.is_scale_item);
            }
        }

        if (idx >= 0) {
            const addQty = toNumber(options.qty, 1);
            const oldQty = (toNumber(cart[idx].qty, 0) || 0) + addQty;
            cart[idx].qty = normalizeQtyByUnit(oldQty, cart[idx].sale_unit);
            render();
            return idx;
        }

        const unit = defaultUnit;
        const optionsByUnit = unitOptionsFor(product);
        const selected = optionsByUnit.find((o) => o.key === unit) || optionsByUnit[0] || { key: unit, defaultPrice: parseFloat(product.price || 0) };
        const qty = isScaleItem ? Math.max(0.001, toNumber(options.qty, 0.001)) : 1;
        const unitPrice = options.unit_price !== undefined && options.unit_price !== null
            ? toNumber(options.unit_price, parseFloat(selected.defaultPrice || product.price || 0))
            : parseFloat(selected.defaultPrice || product.price || 0);
        cart.unshift({
            product_id: product.id,
            name: product.name,
            qty,
            sale_unit: selected.key,
            unit_price: unitPrice,
            is_scale_item: isScaleItem ? 1 : 0,
            scanned_barcode: options.scanned_barcode || null,
            scale_weight: isScaleItem ? qty : null,
            scale_price: isScaleItem && options.scale_price !== undefined ? toNumber(options.scale_price, 0) : null,
            product_meta: product,
        });
        render();
        return 0;
    }

    function focusQtyInput(index){
        if (index < 0) return;
        setTimeout(() => {
            const input = tbody.querySelector(`input[data-i="${index}"][data-k="qty"]`);
            if (!input) return;
            input.focus();
            input.select();
        }, 0);
    }

    function filterProductsList(){
        const q = normalizeSearchText(search.value);
        let count = 0;
        let html = '';
        
        for (let i = 0; i < productCards.length; i++) {
            const entry = productCards[i];
            const match = q === '' || entry.searchText.includes(q);
            if (match) {
                const p = entry.product;
                const hasPromo = !!p.promo_id;
                let promoBadge = '';
                if (hasPromo) {
                    if (p.promo_type === 'percent') {
                        promoBadge = 'خصم ' + p.promo_value + '%';
                    } else if (p.promo_type === 'fixed') {
                        promoBadge = 'خصم ثابت ' + money(p.promo_value);
                    } else {
                        promoBadge = 'سعر عرض';
                    }
                }
                const badgeHtml = promoBadge ? `<span class="badge rounded-pill text-bg-warning">${escapeHtml(promoBadge)}</span>` : '';
                
                html += `
                    <div class="pos-product-item" data-product-id="${p.id}">
                        <div class="pos-product-title">${escapeHtml(p.name)}</div>
                        <div class="pos-product-meta">
                            <span>باركود: ${escapeHtml(p.barcode || '-')}</span>
                            <span>السعر: ${money(p.price)}</span>
                            <span>المخزون: ${money(p.stock)}</span>
                            ${badgeHtml}
                        </div>
                    </div>
                `;
                count++;
                if (count >= 100) break;
            }
        }
        productsWrap.innerHTML = html || '<div class="p-3 text-center text-muted small">لا توجد منتجات مطابقة</div>';
    }

    function findProductForScan(query){
        const q = normalizeSearchText(query);
        if (!q) return null;
        if (exactScanIndex[q]) {
            return exactScanIndex[q];
        }
        const exactName = productCards.filter((entry) => entry.normalizedName === q);
        if (exactName.length === 1) {
            return exactName[0].product;
        }
        return null;
    }

    function findProductById(id){
        const n = parseInt(id, 10);
        if (!Number.isFinite(n)) return null;
        return productsIndex[n] || null;
    }

    async function lookupProductFromServer(rawQuery){
        const query = String(rawQuery || '').trim();
        if (!query) return null;

        const response = await fetch(`${posSearchEndpoint}?q=${encodeURIComponent(query)}`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });

        let payload = {};
        try {
            payload = await response.json();
        } catch (e) {
            payload = {};
        }

        if (!response.ok) {
            throw new Error(payload.error || 'تعذر قراءة الباركود الآن');
        }

        const rows = Array.isArray(payload.data) ? payload.data : [];
        payload.data = rows.map((row) => ({
            ...row,
            id: parseInt(row.id, 10),
            name: normalizeMojibakeText(row.name || ''),
            price: toNumber(row.sale_price, toNumber(row.price, 0)),
            stock: toNumber(row.current_stock, toNumber(row.stock, 0)),
            package_size: toNumber(row.package_size, 1),
            allow_scale_barcode: parseInt(row.allow_scale_barcode || 0, 10),
            sell_type: row.sell_type || 'piece',
            package_type: row.package_type || 'piece',
            weight_unit: row.weight_unit || 'kg'
        }));

        return payload;
    }

    function payloadHasUsableMatch(payload){
        if (!payload || typeof payload !== 'object') {
            return false;
        }
        const rows = Array.isArray(payload.data) ? payload.data : [];
        const scale = payload.scale || null;
        return rows.length > 0 || (scale && scale.is_scale);
    }

    async function lookupProductWithFallback(rawQuery){
        const firstQuery = String(rawQuery || '').trim();
        const fallbackQuery = normalizeScannedQuery(firstQuery);
        let firstPayload = null;
        let firstError = null;

        try {
            firstPayload = await lookupProductFromServer(firstQuery);
            if (payloadHasUsableMatch(firstPayload)) {
                return firstPayload;
            }
        } catch (err) {
            firstError = err;
        }

        if (fallbackQuery !== '' && fallbackQuery !== firstQuery) {
            const fallbackPayload = await lookupProductFromServer(fallbackQuery);
            if (payloadHasUsableMatch(fallbackPayload)) {
                return fallbackPayload;
            }
            if (!firstPayload) {
                return fallbackPayload;
            }
        }

        if (firstPayload) {
            return firstPayload;
        }

        if (firstError) {
            throw firstError;
        }

        return {data: []};
    }

    function keepSearchReady(clearValue = false){
        if (clearValue) {
            search.value = '';
            filterProductsList();
        }
        setTimeout(() => {
            search.focus();
        }, 0);
    }

    paymentMethod.addEventListener('change', () => {
        const isMixed = selectedMethodCode() === 'mixed';
        mixedBox.classList.toggle('d-none', !isMixed);
        paidTotal.readOnly = isMixed;

        const code = selectedMethodCode();
        if (code === 'cash' || code === 'card') {
            paidTotal.value = money(cartTotal());
        } else if (code === 'credit') {
            paidTotal.value = '0';
        }

        if (isMixed && mixedPayments.length === 0) {
            addMixedRow();
        } else {
            updateTotals();
        }
    });

    addMixedRowBtn.addEventListener('click', () => addMixedRow());

    mixedRows.addEventListener('input', (e) => {
        const i = parseInt(e.target.dataset.mi || '-1', 10);
        const k = e.target.dataset.k;
        if (i < 0 || !k || !mixedPayments[i]) return;
        mixedPayments[i][k] = k === 'payment_method_id' ? parseInt(e.target.value || '0', 10) : toNumber(e.target.value, 0);
        renderMixedRows();
        updateTotals();
    });

    mixedRows.addEventListener('change', (e) => {
        const i = parseInt(e.target.dataset.mi || '-1', 10);
        const k = e.target.dataset.k;
        if (i < 0 || !k || !mixedPayments[i]) return;
        mixedPayments[i][k] = k === 'payment_method_id' ? parseInt(e.target.value || '0', 10) : toNumber(e.target.value, 0);
        renderMixedRows();
        updateTotals();
    });

    mixedRows.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('[data-mrm]');
        if (!removeBtn) return;
        mixedPayments.splice(parseInt(removeBtn.dataset.mrm, 10), 1);
        renderMixedRows();
        updateTotals();
    });

    productsWrap.addEventListener('click', (e) => {
        const item = e.target.closest('.pos-product-item');
        if (!item) return;
        const pid = parseInt(item.dataset.productId, 10);
        const product = productsIndex[pid];
        if (product) {
            const idx = addItem(product);
            focusQtyInput(idx);
        }
    });

    tbody.addEventListener('input', (e) => {
        const i = e.target.dataset.i;
        const k = e.target.dataset.k;
        if (i === undefined || !k) return;
        if (k === 'sale_unit') return;
        if (k === 'unit_price' && !canModifyPrice) {
            return;
        }
        if (k === 'qty') {
            // Allow typing quantity values freely; normalize after input.
            cart[i][k] = toNumber(e.target.value, 0);
        } else {
            cart[i][k] = toNumber(e.target.value, 0);
        }
        updateRowLineTotal(i);
        syncCartPayload();
        updateTotals();
    });

    tbody.addEventListener('change', (e) => {
        const i = e.target.dataset.i;
        const k = e.target.dataset.k;
        if (i === undefined || !k) return;

        if (k === 'qty') {
            cart[i].qty = normalizeQtyByUnit(e.target.value, cart[i].sale_unit);
            e.target.value = qtyInputValue(cart[i]);
            syncCartPayload();
            updateTotals();
            return;
        }

        if (k === 'sale_unit') {
            const item = cart[i];
            item.sale_unit = e.target.value;
            const meta = unitMeta(item);
            const selected = meta.options.find((o) => o.key === item.sale_unit) || meta.options[0];
            item.unit_price = parseFloat(selected.defaultPrice || 0);
            item.qty = normalizeQtyByUnit(item.qty, item.sale_unit);
            render();
            return;
        }
    });

    tbody.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('[data-rm]');
        if (!removeBtn) return;
        cart.splice(parseInt(removeBtn.dataset.rm, 10), 1);
        render();
    });

    search.addEventListener('input', () => {
        filterProductsList();
    });

    search.addEventListener('click', () => {
        search.select();
    });

    search.addEventListener('keydown', async (e) => {
        // Arrow key navigation through search results
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const items = Array.from(productsWrap.querySelectorAll('.pos-product-item'));
            if (items.length === 0) return;

            const currentIndex = items.findIndex(item => item.classList.contains('highlighted'));
            let nextIndex = -1;

            if (e.key === 'ArrowDown') {
                nextIndex = (currentIndex + 1) % items.length;
            } else {
                nextIndex = (currentIndex - 1 + items.length) % items.length;
            }

            items.forEach(item => item.classList.remove('highlighted'));
            const targetItem = items[nextIndex];
            targetItem.classList.add('highlighted');
            
            // Automatically scroll the container to keep highlighted item in view
            targetItem.scrollIntoView({ block: 'nearest' });
            return;
        }

        if (e.key !== 'Enter') return;

        // Check if there is a highlighted item first
        const highlightedItem = productsWrap.querySelector('.pos-product-item.highlighted');
        if (highlightedItem) {
            e.preventDefault();
            const pid = parseInt(highlightedItem.dataset.productId, 10);
            const product = productsIndex[pid];
            if (product) {
                addItem(product);
                keepSearchReady(true);
            }
            return;
        }

        const rawQuery = String(search.value || '').trim();
        const query = normalizeSearchText(rawQuery);
        const fallbackQuery = normalizeSearchText(normalizeScannedQuery(rawQuery));
        if (query === '') {
            e.preventDefault();
            submitShortcut('save');
            return;
        }

        e.preventDefault();
        const found = findProductForScan(query) || (fallbackQuery !== query ? findProductForScan(fallbackQuery) : null);
        if (found) {
            addItem(found);
            keepSearchReady(true);
            return;
        }

        const visible = productCards.filter((entry) => query === '' || entry.searchText.includes(query));
        if (visible.length === 1) {
            addItem(visible[0].product);
            keepSearchReady(true);
            return;
        }

        try {
            const payload = await lookupProductWithFallback(rawQuery);
            const rows = Array.isArray(payload.data) ? payload.data : [];
            const scale = payload.scale || null;

            if (scale && scale.is_scale) {
                const product = findProductById(scale.product_id) || rows[0] || null;
                if (!product) {
                    alert('كود الميزان غير مربوط بأي صنف');
                    keepSearchReady(true);
                    return;
                }

                if (!productsIndex[product.id]) {
                    productsIndex[product.id] = product;
                    addExactScanKey(product.barcode, product);
                    addExactScanKey(product.sku, product);
                    addExactScanKey(product.internal_code, product);
                }

                addItem(product, {
                    qty: scale.qty,
                    unit_price: scale.unit_price,
                    is_scale_item: 1,
                    scanned_barcode: scale.barcode,
                    scale_price: scale.scale_price
                });
                keepSearchReady(true);
                return;
            }

            if (rows.length === 1) {
                const product = rows[0];
                if (!productsIndex[product.id]) {
                    productsIndex[product.id] = product;
                    addExactScanKey(product.barcode, product);
                    addExactScanKey(product.sku, product);
                    addExactScanKey(product.internal_code, product);
                }
                addItem(product);
                keepSearchReady(true);
                return;
            }

            // Product not found or multiple matches returned from server
            alert('الصنف غير موجود أو يوجد أكثر من تطابق');
            keepSearchReady(true);
        } catch (err) {
            alert((err && err.message) ? err.message : 'تعذر قراءة الباركود');
            keepSearchReady(true);
        }
    });

    paidTotal.addEventListener('input', updateTotals);

    customerSelect.addEventListener('change', () => {
        renderPaymentMethodOptions();
        mixedPayments = mixedPayments.filter((row) => {
            const method = paymentMethods.find((m) => m.id === parseInt(row.payment_method_id, 10));
            return method ? customerAllowedMethod(method) : false;
        });
        renderMixedRows();
        paymentMethod.dispatchEvent(new Event('change'));
        render();
    });

    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) {
            return;
        }

        const payload = event.data || {};
        const payloadJobId = typeof payload.jobId === 'string' ? payload.jobId : '';
        const isPrintType = payload.type && (
            payload.type.startsWith('pos-print-')
            || payload.type === 'pos-qz-print'
            || payload.type === 'pos-browser-print'
        );

        if (isPrintType) {
            if (!activePrintJobId || payloadJobId !== activePrintJobId) {
                return;
            }
        }

        if (payload.type === 'pos-qz-print') {
            silentPrintWithQz(payload)
                .then(() => {
                    finalizeSuccessfulPrint();
                })
                .catch((err) => {
                    finalizeSuccessfulPrint();
                    const details = err && err.message ? `\n${err.message}` : '';
                    const invoiceNo = payload.invoiceNo ? `\nرقم الفاتورة: ${payload.invoiceNo}` : '';
                    alert('تم حفظ الفاتورة لكن تعذر الطباعة الصامتة عبر QZ Tray.' + invoiceNo + '\nيمكنك إعادة الطباعة من شاشة المبيعات بعد التأكد من تشغيل QZ Tray.' + details);
                    keepSearchReady();
                });
            return;
        }

        if (payload.type === 'pos-browser-print') {
            silentPrintWithBrowserPopup(payload)
                .then(() => {
                    finalizeSuccessfulPrint();
                })
                .catch((err) => {
                    resetShortcutState();
                    const details = err && err.message ? `\n${err.message}` : '';
                    alert('تم حفظ الفاتورة لكن تعذر فتح نافذة الطباعة.' + details);
                    keepSearchReady();
                });
            return;
        }

        if (payload.type === 'pos-print-complete') {
            finalizeSuccessfulPrint();
            return;
        }

        if (payload.type === 'pos-print-error') {
            resetShortcutState();
            alert(payload.message || 'تعذر حفظ أو طباعة الفاتورة');
            keepSearchReady();
        }
    });

    function showTemporaryAlert(message, type = 'success') {
        let alertDiv = document.getElementById('pos-floating-alert');
        if (!alertDiv) {
            alertDiv = document.createElement('div');
            alertDiv.id = 'pos-floating-alert';
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%) translateY(-20px);
                background: linear-gradient(135deg, #16a34a, #22c55e);
                color: #fff;
                padding: 12px 24px;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                font-family: 'Cairo', 'Tajawal', sans-serif;
                font-size: 1rem;
                font-weight: bold;
                z-index: 9999;
                transition: all 0.3s ease;
                opacity: 0;
                display: flex;
                align-items: center;
                gap: 8px;
            `;
            document.body.appendChild(alertDiv);
        }
        
        if (type === 'error') {
            alertDiv.style.background = 'linear-gradient(135deg, #dc2626, #ef4444)';
        } else {
            alertDiv.style.background = 'linear-gradient(135deg, #16a34a, #22c55e)';
        }
        
        alertDiv.textContent = message;
        alertDiv.style.display = 'flex';
        
        alertDiv.offsetHeight; // reflow
        
        alertDiv.style.opacity = '1';
        alertDiv.style.transform = 'translateX(-50%) translateY(0)';
        
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            alertDiv.style.transform = 'translateX(-50%) translateY(-20px)';
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 300);
        }, 3000);
    }

    saleForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const isPrintShortcut = shortcutSubmitting && quickAction.value === 'print';
        const isMixed = selectedMethodCode() === 'mixed';
        const paid = toNumber(paidTotal.value, 0);

        if (cart.length === 0) {
            resetShortcutState();
            alert('أضف أصنافًا للفاتورة أولًا');
            return;
        }

        if (paid < 0) {
            resetShortcutState();
            alert('قيمة المدفوع غير صحيحة');
            return;
        }

        if (isMixed) {
            const validRows = mixedPayments.filter((p) => toNumber(p.amount, 0) > 0 && parseInt(p.payment_method_id || 0, 10) > 0);
            if (!validRows.length) {
                resetShortcutState();
                alert('أدخل تفاصيل الدفع المختلط');
                return;
            }
            paymentBreakdownJson.value = JSON.stringify(validRows);
        }

        const formData = new FormData(saleForm);
        
        // Ensure shortcut variables are correctly sent via AJAX
        if (isPrintShortcut) {
            formData.set('quick_action', 'print');
            formData.set('print_transport', printTransport.value);
            formData.set('print_job_id', activePrintJobId);
        }

        const submitButtons = document.querySelectorAll('button[form="saleForm"], button[type="submit"]');
        submitButtons.forEach(btn => btn.disabled = true);
        
        fetch(saleForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'حدث خطأ أثناء حفظ الفاتورة'); });
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                showTemporaryAlert(data.message, 'success');
                
                if (isPrintShortcut) {
                    // Construct the exact, clean direct printing link
                    const printUrl = `${window.location.origin}/sales/${data.invoice_id}/print?autoprint=1&self_close=1`;
                    
                    if (window.electronAPI && typeof window.electronAPI.logPrint === 'function') {
                        window.electronAPI.logPrint('F1 Print initiated from POS screen. invoiceId: ' + data.invoice_id + ', printUrl: ' + printUrl);
                    }
                    
                    if (qzAvailable()) {
                        const payload = {
                            printUrl: '<?= url('/sales/') ?>' + data.invoice_id + '/print',
                            invoiceId: data.invoice_id,
                            invoiceNo: data.invoice_no
                        };
                        silentPrintWithQz(payload)
                            .then(() => { finalizeSuccessfulPrint(); })
                            .catch((err) => {
                                finalizeSuccessfulPrint();
                                alert('تم حفظ الفاتورة لكن تعذر الطباعة عبر QZ Tray.');
                                keepSearchReady();
                            });
                    } else if (window.electronAPI && typeof window.electronAPI.printUrl === 'function') {
                        // THE CRITICAL FIX: Bypass the renderer popup completely and stream the URL directly to the Main Process
                        window.electronAPI.printUrl(printUrl);
                        finalizeSuccessfulPrint();
                    } else {
                        // Standard browser printing fallback if outside Electron environment
                        window.open(printUrl, `pos-print-${data.invoice_id}`, 'width=460,height=760');
                        finalizeSuccessfulPrint();
                    }
                } else {
                    finalizeSuccessfulPrint();
                }
            } else {
                alert(data.message || 'فشل حفظ الفاتورة');
                resetShortcutState();
            }
        })
        .catch(err => {
            alert(err.message || 'حدث خطأ غير متوقع');
            resetShortcutState();
        })
        .finally(() => {
            submitButtons.forEach(btn => btn.disabled = false);
        });
    });

    // Load user's keyboard shortcuts
    let userKeyboardShortcuts = {};
    fetch('<?= url('/api/cashier-keyboard') ?>', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .catch(() => ({}))
        .then(data => {
            if (data.shortcuts && Array.isArray(data.shortcuts)) {
                data.shortcuts.forEach(s => {
                    userKeyboardShortcuts[s.key_code.toLowerCase()] = s;
                });
            }
        });

    // Helper function to normalize key code from keyboard event
    function normalizeKeyCode(e) {
        if (['Control', 'Shift', 'Alt', 'Meta'].includes(e.key)) {
            return '';
        }

        const parts = [];
        if (e.ctrlKey || e.metaKey) parts.push('Ctrl');
        if (e.altKey) parts.push('Alt');
        if (e.shiftKey) parts.push('Shift');

        let keyName = '';
        if (/^Key[A-Z]$/.test(e.code || '')) {
            keyName = e.code.slice(3);
        } else if (/^Digit[0-9]$/.test(e.code || '')) {
            keyName = e.code.slice(5);
        } else if (/^Numpad/.test(e.code || '')) {
            keyName = e.code.replace('Numpad', 'Num');
        } else if (e.key === ' ') {
            keyName = 'Space';
        } else if (e.key === 'Escape') {
            keyName = 'Esc';
        } else {
            keyName = e.key || e.code || '';
        }

        if (!keyName) {
            return '';
        }

        parts.push(keyName.length === 1 ? keyName.toUpperCase() : keyName);
        return parts.join('+');
    }

    // Execute keyboard shortcut action
    function executeKeyboardShortcut(shortcut) {
        const actionType = shortcut.action_type;
        
        switch (actionType) {
            case 'add_product':
                if (shortcut.reference_id) {
                    const product = findProductById(shortcut.reference_id);
                    if (product) {
                        addItem(product);
                        focusQtyInput(0);
                    }
                }
                break;
            
            case 'execute_payment':
                if (cart.length > 0) {
                    submitShortcut('save');
                }
                break;
            
            case 'apply_discount':
                const discountInput = document.querySelector('input[name="total_discount"]');
                if (discountInput) {
                    discountInput.focus();
                }
                break;
            
            case 'print_receipt':
                if (cart.length > 0) {
                    submitShortcut('print');
                }
                break;
            
            case 'clear_cart':
                if (cart.length > 0 && confirm('هل تريد مسح جميع الأصناف من السلة؟')) {
                    cart = [];
                    render();
                }
                break;
            
            case 'suspend_invoice':
                if (cart.length > 0) {
                    const holdForm = document.getElementById('holdForm');
                    if (holdForm) {
                        holdForm.submit();
                    }
                }
                break;
            
            case 'open_invoice':
                if (search) {
                    search.focus();
                    search.value = '';
                }
                break;
            
            default:
                console.log('Unknown keyboard action:', actionType);
        }
    }

    document.addEventListener('keydown', (e) => {
        // Check user keyboard shortcuts first (only if not typing in search)
        if (e.target !== search) {
            const keyCode = normalizeKeyCode(e);
            const shortcut = userKeyboardShortcuts[keyCode.toLowerCase()];
            
            if (shortcut && Number(shortcut.is_active) === 1) {
                e.preventDefault();
                executeKeyboardShortcut(shortcut);
                return;
            }
        }

        if (e.key === 'F1' || e.code === 'F1') {
            e.preventDefault();
            submitShortcut('print');
            return;
        }

        if (e.key !== 'Enter') {
            return;
        }

        if (e.shiftKey || e.ctrlKey || e.altKey || e.metaKey) {
            return;
        }

        const target = e.target;
        const tag = String(target?.tagName || '').toLowerCase();
        if (tag === 'textarea') {
            return;
        }

        if (target === search) {
            return;
        }

        e.preventDefault();
        submitShortcut('save');
    }, true);

    if (testPrintPopupBtn) {
        testPrintPopupBtn.addEventListener('click', () => {
            const popupName = `pos-print-test-${Date.now()}`;
            const popupFeatures = [
                'popup=yes',
                'width=460',
                'height=760',
                'left=80',
                'top=40',
                'resizable=yes',
                'scrollbars=yes',
                'toolbar=no',
                'location=no',
                'menubar=no',
                'status=no'
            ].join(',');

            const popup = window.open('about:blank', popupName, popupFeatures);
            if (!popup) {
                alert('فشل الاختبار: المتصفح يمنع النوافذ المنبثقة. اسمح بـ Pop-ups للموقع.');
                return;
            }

            try {
                popup.document.write('<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>اختبار</title><style>body{font-family:Tahoma,Arial,sans-serif;padding:16px}</style></head><body><h4>نجح اختبار الطباعة</h4><p>النوافذ المنبثقة مفعلة ويمكن استخدام F1 للطباعة.</p></body></html>');
                popup.document.close();
                popup.focus();
            } catch (e) {}

            setTimeout(() => {
                try { popup.close(); } catch (e) {}
            }, 1200);

            alert('نجح الاختبار: النوافذ المنبثقة مسموحة.');
        });
    }

    document.getElementById('holdForm').addEventListener('submit', (e) => {
        e.preventDefault();
        
        if (cart.length === 0) {
            alert('لا يوجد أصناف للتعليق');
            return;
        }
        
        const holdForm = document.getElementById('holdForm');
        const formData = new FormData(holdForm);
        const holdBtn = document.querySelector('button[form="holdForm"]');
        if (holdBtn) holdBtn.disabled = true;
        
        fetch(holdForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'حدث خطأ أثناء تعليق الفاتورة'); });
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                showTemporaryAlert(data.message, 'success');
                // Clear cart and stay on page without reload
                cart = [];
                mixedPayments = [];
                renderMixedRows();
                paidTotal.value = '0';
                render();
                keepSearchReady(true);
            } else {
                alert(data.message || 'فشل تعليق الفاتورة');
            }
        })
        .catch(err => {
            alert(err.message || 'حدث خطأ غير متوقع');
        })
        .finally(() => {
            if (holdBtn) holdBtn.disabled = false;
        });
    });

    renderPaymentMethodOptions();
    paymentMethod.dispatchEvent(new Event('change'));
    hydrateCartFromCatalog();
    filterProductsList();
    render();
    keepSearchReady();
})();
</script>



