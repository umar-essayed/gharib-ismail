<?php $title = 'تسويات المخزون'; ?>

<style>
/* ═══════════════════════════════════════════════════════════════
   شاشة تسوية المخزون التفاعلية بالكامل
══════════════════════════════════════════════════════════════════ */
.search-results-item {
    padding: 10px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background .2s;
}
.search-results-item:hover, .search-results-item.active {
    background-color: #f8fafc;
}
.search-results-item:last-child {
    border-bottom: none;
}
.search-results-item .item-details {
    display: flex;
    flex-direction: column;
}
.search-results-item .item-name {
    font-weight: 700;
    color: #1e293b;
    font-size: .9rem;
}
.search-results-item .item-barcode {
    font-size: .75rem;
    color: #64748b;
    font-family: monospace;
    margin-top: 2px;
}
.search-results-item .item-stock {
    font-size: .8rem;
    font-weight: 700;
    color: #2563eb;
    background: #dbeafe;
    padding: 2px 8px;
    border-radius: 20px;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">📦 تسوية مخزون جديدة</h5>
</div>

<form method="post" action="<?= url('/inventory/adjustments') ?>" id="adjustForm">
    <?= csrf_field() ?>
    <input type="hidden" name="items_json" id="adjustItemsJson">

    <!-- المخزن والملاحظة -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 bg-light border rounded-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">🏢 المخزن المستهدف</label>
                    <select class="form-select" name="warehouse_id">
                        <?php foreach($warehouses as $w): ?>
                            <option value="<?= (int)$w['id'] ?>"><?= e($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">📝 ملاحظة التسوية</label>
                    <input class="form-control" name="note" placeholder="مثال: جرد سنوي، تسوية أصناف تالفة، عجز مخزني...">
                </div>
            </div>
        </div>
    </div>

    <!-- البحث الذكي والكمية الجديدة -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-8 position-relative">
                    <label class="form-label fw-bold">🔍 ابحث عن صنف (الاسم بالكامل أو الباركود/QR)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">🔍</span>
                        <input type="text" id="adjSearchInput" class="form-control border-start-0" placeholder="اكتب اسم المنتج أو امسح الباركود بجهاز الاسكانر مباشرة..." autocomplete="off">
                    </div>
                    <div id="searchResults" class="dropdown-menu w-100 shadow-sm" style="max-height: 250px; overflow-y: auto; display: none; position: absolute; top: 100%; left: 0; z-index: 1000; margin-top: 4px;"></div>
                    <input type="hidden" id="selectedProductId">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">📊 الكمية الجديدة</label>
                    <input type="number" step="0.001" id="adjQty" class="form-control text-center fw-bold fs-5" placeholder="0.00" disabled>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 py-2 fw-bold" id="addAdj" type="button" disabled>➕ إضافة للقائمة</button>
                </div>
            </div>

            <!-- بطاقة المنتج المحدد حالياً -->
            <div id="selectedProductCard" class="card bg-light border border-primary border-opacity-25 mt-4 p-3 d-none">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong id="cardProductName" class="text-primary fs-6"></strong>
                        <div class="small text-muted mt-1">🏷️ الباركود: <span id="cardProductBarcode" class="font-monospace fw-bold"></span></div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-secondary px-3 py-2 fs-7" id="cardProductStock">الرصيد الحالي: 0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول بنود التسوية -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="mb-0 fw-bold">📋 البنود المضافة للتسوية</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="adjTable">
                    <thead class="table-light">
                        <tr>
                            <th>الصنف</th>
                            <th class="text-center" style="width: 150px;">الباركود</th>
                            <th class="text-center" style="width: 150px;">الرصيد الحالي</th>
                            <th class="text-center" style="width: 150px;">الكمية الجديدة</th>
                            <th class="text-center" style="width: 150px;">فرق الكمية</th>
                            <th class="text-center" style="width: 80px;">حذف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="emptyRow">
                            <td colspan="6" class="text-center text-muted py-4">
                                لم يتم إضافة أصناف للتسوية بعد. ابحث عن صنف بالأعلى لإضافته.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button class="btn btn-success fw-bold px-4 py-2 fs-6">💾 حفظ وتنفيذ التسوية</button>
</form>

<script>
// جلب قائمة المنتجات كاملة من الـ Controller لتحسين سرعة الجرد دون AJAX
const allProducts = <?= json_encode(array_map(fn($p) => [
    'id' => (int)$p['id'],
    'name' => $p['name'],
    'barcode' => $p['barcode'],
    'current_stock' => (float)$p['current_stock']
], $products)) ?>;

(function(){
    const searchInput = document.getElementById('adjSearchInput');
    const resultsDiv = document.getElementById('searchResults');
    const selectedIdInput = document.getElementById('selectedProductId');
    const qtyInput = document.getElementById('adjQty');
    const addBtn = document.getElementById('addAdj');
    
    const card = document.getElementById('selectedProductCard');
    const cardName = document.getElementById('cardProductName');
    const cardBarcode = document.getElementById('cardProductBarcode');
    const cardStock = document.getElementById('cardProductStock');

    const tbody = document.querySelector('#adjTable tbody');
    const jsonInput = document.getElementById('adjustItemsJson');
    const rows = [];
    
    let activeIndex = -1;
    let filteredList = [];

    // ─── البحث وتصفية المنتجات ───
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase().trim();
        activeIndex = -1;
        
        if (!query) {
            resultsDiv.style.display = 'none';
            return;
        }

        // فلترة بالاسم أو بالباركود بالكامل
        filteredList = allProducts.filter(p => {
            const name = p.name.toLowerCase();
            const barcode = (p.barcode || '').toLowerCase();
            return name.includes(query) || barcode.includes(query);
        }).slice(0, 10); // حد أقصى 10 نتائج للأداء

        renderSearchResults();
    });

    // ─── رسم نتائج البحث في القائمة المنسدلة ───
    function renderSearchResults() {
        resultsDiv.innerHTML = '';
        if (filteredList.length === 0) {
            resultsDiv.innerHTML = '<div class="dropdown-item text-muted text-center py-2">لا يوجد منتج مطابق</div>';
            resultsDiv.style.display = 'block';
            return;
        }

        filteredList.forEach((p, idx) => {
            const item = document.createElement('div');
            item.className = `search-results-item ${idx === activeIndex ? 'active' : ''}`;
            item.innerHTML = `
                <div class="item-details">
                    <span class="item-name">${escapeHtml(p.name)}</span>
                    <span class="item-barcode">🏷️ ${escapeHtml(p.barcode || 'بدون باركود')}</span>
                </div>
                <span class="item-stock">المخزون: ${p.current_stock}</span>
            `;
            item.addEventListener('click', () => selectProduct(p));
            resultsDiv.appendChild(item);
        });

        resultsDiv.style.display = 'block';
    }

    // ─── اختيار منتج محدد ───
    function selectProduct(p) {
        selectedIdInput.value = p.id;
        cardName.textContent = p.name;
        cardBarcode.textContent = p.barcode || '—';
        cardStock.textContent = `الرصيد الحالي: ${p.current_stock}`;
        
        card.classList.remove('d-none');
        qtyInput.removeAttribute('disabled');
        addBtn.removeAttribute('disabled');

        resultsDiv.style.display = 'none';
        searchInput.value = '';
        
        // الانتقال التلقائي لحقل الكمية الجديدة وتهيئته
        qtyInput.value = p.current_stock;
        qtyInput.focus();
        qtyInput.select();
    }

    // ─── معالجة مفاتيح الكيبورد (الباركود سكانر والأسهم) ───
    searchInput.addEventListener('keydown', (e) => {
        const items = resultsDiv.querySelectorAll('.search-results-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = (activeIndex + 1) % filteredList.length;
            renderSearchResults();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = (activeIndex - 1 + filteredList.length) % filteredList.length;
            renderSearchResults();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            
            // 1. لو في عنصر محدد بالأسهم
            if (activeIndex >= 0 && activeIndex < filteredList.length) {
                selectProduct(filteredList[activeIndex]);
            }
            // 2. لو مفيش، لكن القائمة تحتوي على عنصر واحد فقط (مثال: اسكانر الباركود كتب الرقم كاملاً وضغط انتر)
            else if (filteredList.length === 1) {
                selectProduct(filteredList[0]);
            }
        } else if (e.key === 'Escape') {
            resultsDiv.style.display = 'none';
        }
    });

    // إغلاق قائمة البحث عند الضغط خارجها
    document.addEventListener('click', (e) => {
        if (e.target !== searchInput && e.target !== resultsDiv) {
            resultsDiv.style.display = 'none';
        }
    });

    // ─── إضافة بند للتسوية ───
    addBtn.addEventListener('click', () => {
        const id = parseInt(selectedIdInput.value, 10);
        const newQty = parseFloat(qtyInput.value);
        
        if (isNaN(id) || isNaN(newQty) || newQty < 0) {
            alert('يرجى تحديد المنتج وإدخال كمية صحيحة أكبر من أو تساوي الصفر.');
            return;
        }

        const p = allProducts.find(prod => prod.id === id);
        if (!p) return;

        // التحقق مما إذا كان المنتج مضافاً بالفعل
        const existingIdx = rows.findIndex(r => r.product_id === id);
        if (existingIdx >= 0) {
            rows[existingIdx].new_qty = newQty;
        } else {
            rows.push({
                product_id: p.id,
                name: p.name,
                barcode: p.barcode,
                current_stock: p.current_stock,
                new_qty: newQty
            });
        }

        // تفريغ الاختيار الحالي للتحضير للصنف التالي
        card.classList.add('d-none');
        qtyInput.setAttribute('disabled', 'true');
        qtyInput.value = '';
        addBtn.setAttribute('disabled', 'true');
        selectedIdInput.value = '';
        searchInput.focus();

        renderTable();
    });

    // دعم الضغط على Enter في حقل الكمية للإضافة
    qtyInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addBtn.click();
        }
    });

    // ─── رسم جدول البنود المضافة ───
    function renderTable() {
        const emptyRow = document.getElementById('emptyRow');
        
        // تنظيف الصفوف القديمة عدا صف الحالة الفارغة
        const trs = tbody.querySelectorAll('tr:not(#emptyRow)');
        trs.forEach(t => t.remove());

        if (rows.length === 0) {
            emptyRow.classList.remove('d-none');
            jsonInput.value = '[]';
            return;
        }

        emptyRow.classList.add('d-none');

        rows.forEach((r, idx) => {
            const diff = (r.new_qty - r.current_stock).toFixed(3);
            const diffVal = parseFloat(diff);
            
            let diffBadge = '';
            if (diffVal > 0) {
                diffBadge = `<span class="text-success fw-bold">+${diffVal} (زيادة)</span>`;
            } else if (diffVal < 0) {
                diffBadge = `<span class="text-danger fw-bold">${diffVal} (عجز)</span>`;
            } else {
                diffBadge = `<span class="text-muted">0.00 (تطابق)</span>`;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold">${escapeHtml(r.name)}</td>
                <td class="text-center font-monospace small text-muted">${escapeHtml(r.barcode || '—')}</td>
                <td class="text-center font-monospace">${r.current_stock}</td>
                <td class="text-center font-monospace fw-bold text-primary">${r.new_qty}</td>
                <td class="text-center font-monospace">${diffBadge}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 fs-6 fw-bold" onclick="removeRow(${idx})">×</button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        // تحديث حقل الـ JSON لإرساله للسيرفر
        jsonInput.value = JSON.stringify(rows.map(r => ({
            product_id: r.product_id,
            new_qty: r.new_qty
        })));
    }

    // جعل دالة الحذف متاحة خارج النطاق المغلق
    window.removeRow = function(idx) {
        rows.splice(idx, 1);
        renderTable();
        searchInput.focus();
    };

    // منع الحفظ بقائمة فارغة
    document.getElementById('adjustForm').addEventListener('submit', (e) => {
        if (!rows.length) {
            e.preventDefault();
            alert('❌ يرجى إضافة صنف واحد على الأقل للتسوية قبل الحفظ.');
        }
    });

    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
})();
</script>
