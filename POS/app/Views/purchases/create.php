<?php $title = 'إضافة فاتورة شراء'; ?>
<h5 class="mb-3">فاتورة شراء</h5>
<form method="post" action="<?= url('/purchases') ?>" id="purchaseForm">
    <?= csrf_field() ?>
    <input type="hidden" name="items_json" id="purchaseItemsJson">

    <div class="row g-2 mb-3">
        <div class="col-md-4"><label class="form-label">المورد</label><select class="form-select" name="supplier_id" required><?php foreach($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">رقم فاتورة المورد</label><input class="form-control" name="supplier_invoice_no"></div>
        <div class="col-md-2"><label class="form-label">الحالة</label><select class="form-select" name="status"><option value="approved">معتمدة</option><option value="draft">مسودة</option></select></div>
        <div class="col-md-3"><label class="form-label">طريقة الدفع</label><select class="form-select" name="payment_method_id"><?php foreach($paymentMethods as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
    </div>

    <div class="row g-2 mb-2">
        <div class="col-md-6"><label class="form-label">بحث باركود / اسم</label><input class="form-control" id="purchaseBarcode" placeholder="امسح الباركود ثم Enter أو اكتب اسم الصنف"></div>
        <div class="col-md-6 d-flex align-items-end"><div class="text-muted small" id="purchaseSearchHint">يمكنك المسح بالباركود لإضافة الصنف مباشرة</div></div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4"><label class="form-label">الصنف</label><select class="form-select" id="purchaseProduct"><?php foreach($products as $p): ?><option value="<?= (int)$p['id'] ?>" data-name="<?= e($p['name']) ?>" data-price="<?= e($p['purchase_price']) ?>" data-barcode="<?= e($p['barcode']) ?>" data-sell-type="<?= e($p['sell_type'] ?? 'piece') ?>" data-package-type="<?= e($p['package_type'] ?? (($p['sell_type'] ?? 'piece') === 'weight' ? 'kg' : 'piece')) ?>" data-package-size="<?= e($p['package_size'] ?? 1) ?>"><?= e($p['name']) ?><?= $p['barcode'] ? ' - ' . e($p['barcode']) : '' ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label">الوحدة</label><select class="form-select" id="purchaseUnit"></select></div>
        <div class="col-md-2"><label class="form-label">الكمية</label><input type="number" step="0.001" id="purchaseQty" class="form-control" value="1"></div>
        <div class="col-md-2"><label class="form-label">السعر</label><input type="number" step="0.001" id="purchasePrice" class="form-control" value="0"></div>
        <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-primary w-100" id="addPurchaseItem">إضافة سطر</button></div>
    </div>

    <div class="table-wrap table-responsive mb-3">
        <table class="table table-sm align-middle" id="purchaseTable"><thead><tr><th>الصنف</th><th>الوحدة</th><th>كمية</th><th>سعر</th><th>إجمالي</th><th></th></tr></thead><tbody></tbody></table>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3"><label class="form-label">المدفوع</label><input type="number" step="0.001" name="paid_total" id="purchasePaid" class="form-control" value="0"></div>
        <div class="col-md-9"><label class="form-label">ملاحظة</label><input class="form-control" name="note"></div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div>إجمالي الفاتورة: <strong id="purchaseTotal">0.00</strong></div>
        <button class="btn btn-success" type="submit">حفظ الفاتورة</button>
    </div>
</form>

<script>
(function(){
    const productSel = document.getElementById('purchaseProduct');
    const unitSel = document.getElementById('purchaseUnit');
    const qtyInput = document.getElementById('purchaseQty');
    const priceInput = document.getElementById('purchasePrice');
    const barcodeInput = document.getElementById('purchaseBarcode');
    const searchHint = document.getElementById('purchaseSearchHint');
    const tbody = document.querySelector('#purchaseTable tbody');
    const totalEl = document.getElementById('purchaseTotal');
    const jsonInput = document.getElementById('purchaseItemsJson');
    const rows = [];

    function money(v){ return (Math.round(v*1000)/1000).toFixed(2); }
    function defaultMeta(){
        return { sell_type: 'piece', package_type: 'piece', package_size: 1, price: 0 };
    }

    function metaFromOption(op){
        if (!op) return defaultMeta();
        return {
            sell_type: op.dataset.sellType === 'weight' ? 'weight' : 'piece',
            package_type: op.dataset.packageType || 'piece',
            package_size: Math.max(1, parseFloat(op.dataset.packageSize || 1)),
            price: parseFloat(op.dataset.price || 0),
        };
    }

    function unitOptions(meta){
        const isWeight = meta.sell_type === 'weight';
        if (isWeight) {
            const options = [{ key: 'kg', label: 'كجم', factor: 1 }];
            if (meta.package_type === 'sack' && meta.package_size > 1) {
                options.push({ key: 'sack', label: 'شيكارة', factor: meta.package_size });
            }
            return options;
        }
        const options = [{ key: 'piece', label: 'قطعة', factor: 1 }];
        if (meta.package_type === 'box' && meta.package_size > 1) {
            options.push({ key: 'box', label: 'علبة', factor: meta.package_size });
        }
        return options;
    }

    function defaultUnit(meta){
        const options = unitOptions(meta);
        if (options.some((o) => o.key === meta.package_type)) {
            return meta.package_type;
        }
        return options[0].key;
    }

    function unitLabel(key){
        if (key === 'box') return 'علبة';
        if (key === 'sack') return 'شيكارة';
        if (key === 'kg') return 'كجم';
        return 'قطعة';
    }

    function selectedUnitPrice(){
        const factor = parseFloat(unitSel.selectedOptions[0]?.dataset.factor || 1);
        const base = parseFloat(productSel.selectedOptions[0]?.dataset.price || 0);
        return factor * base;
    }

    function rebuildUnitOptions(preferred){
        const meta = metaFromOption(productSel.selectedOptions[0]);
        const options = unitOptions(meta);
        const wanted = preferred && options.some((o) => o.key === preferred) ? preferred : defaultUnit(meta);
        unitSel.innerHTML = options.map((o) => `<option value="${o.key}" data-factor="${o.factor}" ${o.key===wanted?'selected':''}>${o.label}</option>`).join('');
        priceInput.value = selectedUnitPrice();
    }

    productSel.addEventListener('change', ()=> rebuildUnitOptions(''));
    unitSel.addEventListener('change', ()=> {
        priceInput.value = selectedUnitPrice();
    });
    rebuildUnitOptions('');

    function render(){
        tbody.innerHTML = '';
        let total = 0;
        rows.forEach((r, i)=>{
            const line = (r.qty * r.unit_price);
            total += line;
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${r.name}</td><td>${unitLabel(r.purchase_unit)}</td><td>${money(r.qty)}</td><td>${money(r.unit_price)}</td><td>${money(line)}</td><td><button type="button" class="btn btn-sm btn-danger" data-rm="${i}">×</button></td>`;
            tbody.appendChild(tr);
        });
        totalEl.textContent = money(total);
        jsonInput.value = JSON.stringify(rows);
    }

    function addSelectedProduct(){
        const op = productSel.selectedOptions[0];
        const qty = parseFloat(qtyInput.value || 0);
        const price = parseFloat(priceInput.value || 0);
        if (qty <= 0) return;
        rows.push({
            product_id: parseInt(op.value, 10),
            name: op.dataset.name,
            purchase_unit: unitSel.value || defaultUnit(metaFromOption(op)),
            qty,
            unit_price: price
        });
        render();
    }

    async function searchByBarcode(){
        const q = barcodeInput.value.trim();
        if (!q) return;
        searchHint.textContent = 'جاري البحث...';

        try {
            const res = await fetch('<?= url('/purchases/products/search') ?>?q=' + encodeURIComponent(q));
            const payload = await res.json();
            const found = (payload.data || [])[0];
            if (!found) {
                searchHint.textContent = 'لم يتم العثور على الصنف';
                return;
            }

            const wantedId = parseInt(found.id, 10);
            let op = Array.from(productSel.options).find((o) => parseInt(o.value, 10) === wantedId);
            if (!op) {
                op = document.createElement('option');
                op.value = wantedId;
                op.dataset.name = found.name;
                op.dataset.price = found.purchase_price || 0;
                op.dataset.barcode = found.barcode || '';
                op.dataset.sellType = found.sell_type || 'piece';
                op.dataset.packageType = found.package_type || ((found.sell_type || 'piece') === 'weight' ? 'kg' : 'piece');
                op.dataset.packageSize = found.package_size || 1;
                op.textContent = found.name + (found.barcode ? (' - ' + found.barcode) : '');
                productSel.appendChild(op);
            }

            productSel.value = String(wantedId);
            rebuildUnitOptions('');
            qtyInput.value = qtyInput.value || '1';
            addSelectedProduct();

            barcodeInput.value = '';
            searchHint.textContent = 'تمت إضافة الصنف: ' + found.name;
        } catch (e) {
            searchHint.textContent = 'تعذر إتمام البحث الآن';
        }
    }

    document.getElementById('addPurchaseItem').addEventListener('click', addSelectedProduct);

    barcodeInput.addEventListener('keydown', (e)=>{
        if (e.key === 'Enter') {
            e.preventDefault();
            searchByBarcode();
        }
    });

    tbody.addEventListener('click', (e)=>{
        if (!e.target.dataset.rm) return;
        rows.splice(parseInt(e.target.dataset.rm, 10), 1);
        render();
    });

    document.getElementById('purchaseForm').addEventListener('submit', (e)=>{
        if (!rows.length) {
            e.preventDefault();
            alert('أضف أصنافًا للفاتورة');
        }
    });
})();
</script>
