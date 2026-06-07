<?php $title = 'إنشاء مرتجع شراء'; ?>
<h5 class="mb-3">مرتجع شراء</h5>
<form method="post" action="<?= url('/returns/purchases') ?>" id="purchaseReturnForm">
    <?= csrf_field() ?>
    <input type="hidden" name="items_json" id="purchaseReturnItemsJson">

    <div class="row g-2 mb-3">
        <div class="col-md-5"><label class="form-label">فاتورة الشراء</label><select class="form-select" name="purchase_invoice_id" id="purchaseInvoiceSelect" required><option value="">اختر فاتورة</option><?php foreach($invoices as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['invoice_no']) ?> - <?= e($s['supplier_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">طريقة الاسترداد</label><select class="form-select" name="payment_method_id"><?php foreach($paymentMethods as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label">مبلغ محصل</label><input type="number" step="0.001" class="form-control" name="refund_total" id="pRefundTotal" value="0"></div>
        <div class="col-md-2"><label class="form-label">ملاحظة</label><input class="form-control" name="note"></div>
    </div>

    <div class="table-wrap table-responsive mb-3">
        <table class="table table-sm align-middle" id="purchaseReturnTable"><thead><tr><th>الصنف</th><th>كمية أصلية</th><th>كمية مرتجعة</th><th>المتاح</th><th>مرتجع الآن</th><th>السعر</th><th>إجمالي</th></tr></thead><tbody></tbody></table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div>إجمالي المرتجع: <strong id="purchaseReturnTotal">0.00</strong></div>
        <button class="btn btn-success">حفظ المرتجع</button>
    </div>
</form>

<script>
(function(){
    const sel = document.getElementById('purchaseInvoiceSelect');
    const tbody = document.querySelector('#purchaseReturnTable tbody');
    const totalEl = document.getElementById('purchaseReturnTotal');
    const jsonInput = document.getElementById('purchaseReturnItemsJson');
    const rows = [];

    function money(v){ return (Math.round(v*1000)/1000).toFixed(2); }
    function render(){
        tbody.innerHTML = '';
        let total = 0;
        rows.forEach((r, i)=>{
            const line = (r.qty_return || 0) * r.unit_price;
            total += line;
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${r.product_name}</td><td>${money(r.qty)}</td><td>${money(r.returned_before)}</td><td>${money(r.available)}</td><td><input type="number" min="0" max="${r.available}" step="0.001" class="form-control form-control-sm" data-i="${i}" value="${r.qty_return||0}"></td><td>${money(r.unit_price)}</td><td>${money(line)}</td>`;
            tbody.appendChild(tr);
        });
        totalEl.textContent = money(total);
        jsonInput.value = JSON.stringify(rows.filter(r => (r.qty_return||0)>0).map(r => ({purchase_invoice_item_id:r.id, qty:r.qty_return})));
        document.getElementById('pRefundTotal').value = money(total);
    }

    sel.addEventListener('change', ()=>{
        rows.length = 0;
        if (!sel.value) { render(); return; }
        fetch('<?= url('/returns/purchases/items') ?>/' + sel.value)
            .then(r=>r.json())
            .then(res=>{
                (res.data||[]).forEach(item=> rows.push({id:parseInt(item.id,10), product_name:item.product_name, qty:parseFloat(item.qty), returned_before:0, available:parseFloat(item.qty), qty_return:0, unit_price:parseFloat(item.unit_price)}));
                render();
            });
    });

    tbody.addEventListener('input', (e)=>{
        if (e.target.dataset.i === undefined) return;
        const idx = parseInt(e.target.dataset.i, 10);
        rows[idx].qty_return = Math.max(0, parseFloat(e.target.value || 0));
        if (rows[idx].qty_return > rows[idx].available) rows[idx].qty_return = rows[idx].available;
        render();
    });

    document.getElementById('purchaseReturnForm').addEventListener('submit', (e)=>{
        const payload = JSON.parse(jsonInput.value || '[]');
        if (!payload.length) {
            e.preventDefault();
            alert('حدد أصنافًا وكميات للمرتجع');
        }
    });
})();
</script>
