<?php $title = 'إنشاء مرتجع بيع'; ?>
<h5 class="mb-3">مرتجع بيع</h5>
<form method="post" action="<?= url('/returns/sales') ?>" id="salesReturnForm">
    <?= csrf_field() ?>
    <input type="hidden" name="items_json" id="salesReturnItemsJson">
    <input type="hidden" name="shift_id" value="<?= e($openShift['id'] ?? '') ?>">

    <div class="row g-2 mb-3">
        <div class="col-md-5"><label class="form-label">فاتورة البيع</label><select class="form-select" name="sales_invoice_id" id="salesInvoiceSelect" required><option value="">اختر فاتورة</option><?php foreach($invoices as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['invoice_no']) ?> - <?= e($s['customer_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">طريقة الدفع</label><select class="form-select" name="payment_method_id"><?php foreach($paymentMethods as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label">مبلغ رد نقدي</label><input type="number" step="0.001" class="form-control" name="refund_total" id="refundTotal" value="0"></div>
        <div class="col-md-2"><label class="form-label">ملاحظة</label><input class="form-control" name="note"></div>
    </div>

    <div class="table-wrap table-responsive mb-3">
        <table class="table table-sm align-middle" id="salesReturnTable"><thead><tr><th>الصنف</th><th>مباع</th><th>مرتجع سابق</th><th>المتاح</th><th>كمية مرتجعة الآن</th><th>سعر</th><th>إجمالي</th></tr></thead><tbody></tbody></table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div>إجمالي المرتجع: <strong id="salesReturnTotal">0.00</strong></div>
        <button class="btn btn-success">حفظ المرتجع</button>
    </div>
</form>

<script>
(function(){
    const sel = document.getElementById('salesInvoiceSelect');
    const tbody = document.querySelector('#salesReturnTable tbody');
    const totalEl = document.getElementById('salesReturnTotal');
    const jsonInput = document.getElementById('salesReturnItemsJson');
    const rows = [];

    function money(v){ return (Math.round(v*1000)/1000).toFixed(2); }

    function render(){
        tbody.innerHTML = '';
        let total = 0;
        rows.forEach((r, i)=>{
            const line = (r.qty_return || 0) * r.unit_price;
            total += line;
            const isScaleItem = parseInt(r.is_scale_item || 0, 10) === 1;
            const qtyStep = isScaleItem ? '0.001' : '1';
            const qtyLabel = isScaleItem ? ' كجم' : '';
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${r.product_name}</td><td>${money(r.qty)}${qtyLabel}</td><td>${money(r.returned_before)}${qtyLabel}</td><td>${money(r.available)}${qtyLabel}</td><td><input type="number" min="0" max="${r.available}" step="${qtyStep}" class="form-control form-control-sm" data-i="${i}" value="${r.qty_return || 0}"></td><td>${money(r.unit_price)}</td><td>${money(line)}</td>`;
            tbody.appendChild(tr);
        });
        totalEl.textContent = money(total);
        const payload = rows.filter(r => (r.qty_return || 0) > 0).map(r => ({sales_invoice_item_id: r.id, qty: r.qty_return}));
        jsonInput.value = JSON.stringify(payload);
        document.getElementById('refundTotal').value = money(total);
    }

    sel.addEventListener('change', ()=>{
        rows.length = 0;
        tbody.innerHTML = '';
        if (!sel.value) return;
        fetch('<?= url('/returns/sales/items') ?>/' + sel.value)
            .then(r => r.json())
            .then(res => {
                (res.data || []).forEach(item => {
                    const soldQty = parseFloat(item.qty);
                    const returnedBefore = parseFloat(item.returned_before || 0);
                    const availableQty = parseFloat(item.available_qty || (soldQty - returnedBefore));
                    rows.push({
                        id: parseInt(item.id,10),
                        product_name: item.product_name,
                        qty: soldQty,
                        returned_before: returnedBefore,
                        available: availableQty,
                        qty_return: 0,
                        unit_price: parseFloat(item.unit_price),
                        is_scale_item: parseInt(item.is_scale_item || 0, 10)
                    });
                });
                render();
            });
    });

    tbody.addEventListener('input', (e)=>{
        if (e.target.dataset.i === undefined) return;
        const idx = parseInt(e.target.dataset.i, 10);
        rows[idx].qty_return = Math.max(0, parseFloat(e.target.value || 0));
        if (rows[idx].is_scale_item !== 1) {
            rows[idx].qty_return = Math.round(rows[idx].qty_return);
        } else {
            rows[idx].qty_return = Math.round(rows[idx].qty_return * 1000) / 1000;
        }
        if (rows[idx].qty_return > rows[idx].available) rows[idx].qty_return = rows[idx].available;
        render();
    });

    document.getElementById('salesReturnForm').addEventListener('submit', (e)=>{
        const payload = JSON.parse(jsonInput.value || '[]');
        if (!payload.length) {
            e.preventDefault();
            alert('حدد أصنافًا وكميات للمرتجع');
        }
    });
})();
</script>
