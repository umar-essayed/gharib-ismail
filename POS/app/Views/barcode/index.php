<?php $title = 'ملصقات الباركود'; ?>

<style>
/* ═══════════════════════════════════════════════════════════════
   شاشة طباعة ملصقات الباركود التفاعلية
══════════════════════════════════════════════════════════════════ */
.barcode-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 15px;
}

@media (max-width: 992px) {
    .barcode-layout {
        grid-template-columns: 1fr;
    }
}

.barcode-panel {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 15px rgba(0,0,0,.05);
    display: flex;
    flex-direction: column;
    height: 600px;
}

.panel-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

/* ─── قائمة المنتجات ─────────────────────────────────────── */
.search-wrapper {
    position: relative;
    margin-bottom: 15px;
}
.search-input {
    width: 100%;
    padding: 10px 40px 10px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: .92rem;
    transition: all .2s;
}
.search-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.15);
    outline: none;
}
.search-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}

.product-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 10px;
    transition: all .2s;
    cursor: pointer;
}
.product-item:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}
.product-info h6 {
    margin: 0 0 4px;
    font-size: .92rem;
    font-weight: 700;
    color: #1e293b;
}
.product-info span {
    font-size: .78rem;
    color: #64748b;
    font-family: monospace;
}

/* ─── قائمة انتظار الطباعة ───────────────────────────────── */
.queue-table {
    width: 100%;
    border-collapse: collapse;
}
.queue-table th {
    font-size: .8rem;
    font-weight: 700;
    color: #64748b;
    padding: 10px;
    border-bottom: 2px solid #e2e8f0;
}
.queue-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #f1f5f9;
    font-size: .88rem;
}
.qty-input {
    width: 70px;
    text-align: center;
    border: 1.5px solid #cbd5e1;
    border-radius: 6px;
    padding: 4px;
    font-weight: bold;
}
.remove-btn {
    color: #ef4444;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    transition: transform .2s;
}
.remove-btn:hover {
    transform: scale(1.2);
}

.empty-queue {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #94a3b8;
}
.empty-queue-icon {
    font-size: 3.5rem;
    margin-bottom: 12px;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">🏷️ طباعة ملصقات الباركود</h5>
</div>

<div class="barcode-layout">

    <!-- ── لوحة المنتجات المتاحة ── -->
    <div class="barcode-panel">
        <div class="panel-header">
            <h6 class="mb-0 fw-bold">📦 المنتجات المتوفرة</h6>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-xs btn-outline-primary fw-bold" style="font-size:11px; padding:2px 8px;" onclick="addAllToQueue()">➕ إضافة الكل</button>
                <span class="badge bg-secondary" id="totalProductsCount"><?= count($products) ?> منتج</span>
            </div>
        </div>
        <div class="panel-body">
            <div class="search-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" class="search-input" placeholder="ابحث باسم المنتج أو الباركود..." onkeyup="filterProducts()">
            </div>
            <div id="productsListContainer">
                <?php foreach ($products as $p): ?>
                    <?php if (empty(trim((string)($p['barcode'] ?? '')))) continue; // المنتجات بدون باركود لا يمكن طباعتها ?>
                    <div class="product-item" data-id="<?= (int)$p['id'] ?>" data-name="<?= e($p['name']) ?>" data-barcode="<?= e($p['barcode']) ?>" onclick="addToQueue(this)">
                        <div class="product-info">
                            <h6><?= e($p['name']) ?></h6>
                            <span>🏷️ <?= e($p['barcode']) ?> | 💰 <?= money($p['sale_price']) ?></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold">➕ إضافة</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── لوحة قائمة انتظار الطباعة ── -->
    <div class="barcode-panel">
        <div class="panel-header">
            <h6 class="mb-0 fw-bold">📋 قائمة الملصقات المطلوب طباعتها</h6>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearQueue()">🗑️ تفريغ القائمة</button>
        </div>
        <div class="panel-body" style="padding:0;">
            <form id="printForm" method="post" action="<?= url('/barcode/print') ?>" target="_blank" style="height:100%; display:flex; flex-direction:column;">
                <?= csrf_field() ?>
                
                <div class="flex-grow-1 overflow-auto p-3" id="queueContent">
                    <div class="empty-queue" id="emptyQueueState">
                        <div class="empty-queue-icon">🏷️</div>
                        <h6>قائمة الانتظار فارغة</h6>
                        <p class="small text-muted text-center">اضغط على المنتجات في اللوحة الجانبية لإضافتها وتحديد عدد الملصقات</p>
                    </div>

                    <table class="queue-table d-none" id="queueTable">
                        <thead>
                            <tr>
                                <th style="text-align:right;">المنتج</th>
                                <th style="text-align:center; width:100px;">الباركود</th>
                                <th style="text-align:center; width:100px;">عدد النسخ</th>
                                <th style="text-align:center; width:50px;">حذف</th>
                            </tr>
                        </thead>
                        <tbody id="queueTbody">
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top bg-light" id="actionPanel" style="display:none;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="small text-muted fw-bold" id="totalLabelsText">إجمالي الملصقات: 0</span>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 fs-6">🖨️ عرض وطباعة الملصقات</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
// قائمة الملصقات النشطة
const queue = new Map();

function addAllToQueue() {
    const visibleItems = document.querySelectorAll('.product-item');
    let addedCount = 0;
    visibleItems.forEach(item => {
        if (item.style.display !== 'none') {
            const id = item.getAttribute('data-id');
            const name = item.getAttribute('data-name');
            const barcode = item.getAttribute('data-barcode');
            
            if (queue.has(id)) {
                queue.get(id).qty += 1;
            } else {
                queue.set(id, { name, barcode, qty: 1 });
            }
            addedCount++;
        }
    });
    if (addedCount > 0) {
        renderQueue();
        showToast(`✅ تم إضافة ${addedCount} صنف إلى قائمة الانتظار`, 'success');
    }
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3 z-3 shadow-sm`;
    toast.style.cssText = 'z-index: 9999; min-width: 250px; text-align: center;';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}

function filterProducts() {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    const items = document.querySelectorAll('.product-item');
    let visibleCount = 0;

    items.forEach(item => {
        const name = item.getAttribute('data-name').toLowerCase();
        const barcode = item.getAttribute('data-barcode').toLowerCase();
        
        if (name.includes(query) || barcode.includes(query)) {
            item.style.setProperty('display', 'flex', 'important');
            visibleCount++;
        } else {
            item.style.setProperty('display', 'none', 'important');
        }
    });

    document.getElementById('totalProductsCount').textContent = visibleCount + ' منتج';
}

function addToQueue(element) {
    const id = element.getAttribute('data-id');
    const name = element.getAttribute('data-name');
    const barcode = element.getAttribute('data-barcode');

    if (queue.has(id)) {
        // إذا كان موجوداً بالفعل، قم بزيادة العدد بمقدار 1
        const item = queue.get(id);
        updateItemQty(id, item.qty + 1);
    } else {
        queue.set(id, { name, barcode, qty: 1 });
        renderQueue();
    }
}

function updateItemQty(id, qty) {
    qty = Math.max(1, parseInt(qty) || 1);
    if (queue.has(id)) {
        queue.get(id).qty = qty;
        const input = document.getElementById(`qty-input-${id}`);
        if (input) input.value = qty;
        updateTotals();
    }
}

function removeFromQueue(id) {
    queue.delete(id);
    renderQueue();
}

function clearQueue() {
    queue.clear();
    renderQueue();
}

function updateTotals() {
    let total = 0;
    queue.forEach(item => {
        total += item.qty;
    });
    document.getElementById('totalLabelsText').textContent = `إجمالي الملصقات: ${total} ملصق`;
}

function renderQueue() {
    const tbody = document.getElementById('queueTbody');
    const emptyState = document.getElementById('emptyQueueState');
    const table = document.getElementById('queueTable');
    const actionPanel = document.getElementById('actionPanel');

    tbody.innerHTML = '';

    if (queue.size === 0) {
        emptyState.classList.remove('d-none');
        table.classList.add('d-none');
        actionPanel.style.display = 'none';
        return;
    }

    emptyState.classList.add('d-none');
    table.classList.remove('d-none');
    actionPanel.style.display = 'block';

    queue.forEach((item, id) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="fw-bold">${escHtml(item.name)}</div>
                <input type="hidden" name="product_ids[]" value="${id}">
            </td>
            <td class="text-center text-muted small font-monospace">${escHtml(item.barcode)}</td>
            <td class="text-center">
                <input type="number" id="qty-input-${id}" name="quantities[${id}]" class="qty-input" min="1" value="${item.qty}" onchange="updateItemQty('${id}', this.value)">
            </td>
            <td class="text-center">
                <button type="button" class="remove-btn" onclick="removeFromQueue('${id}')" title="حذف">🗑️</button>
            </td>
        `;
        tbody.appendChild(row);
    });

    updateTotals();
}

function escHtml(str) {
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
