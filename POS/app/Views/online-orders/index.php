<?php $title = 'الطلبات الإلكترونية'; ?>

<style>
/* ═══ Base ═══ */
.order-card {
    border: none;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    transition: transform .2s, box-shadow .2s;
    overflow: hidden;
}
.order-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.order-card .card-header-inner {
    background: linear-gradient(135deg,#16a34a,#10b981);
    color:#fff; padding:14px 18px;
}
.items-list li {
    list-style:none; padding:5px 0;
    border-bottom:1px dashed #e5e7eb; font-size:13.5px;
}
.items-list li:last-child { border-bottom:none }
.stat-card { border-radius:14px; padding:18px 22px; color:#fff; text-align:center; transition: transform .2s }
.stat-card:hover { transform: scale(1.02) }
.stat-card .stat-num { font-size:2.2rem; font-weight:800 }
.stat-card .stat-lbl { font-size:13px; opacity:.85 }
.sync-bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: rgba(15,23,42,.92); color:#fff;
    display: flex; align-items: center; justify-content: center;
    gap: 14px; font-size: 13px; padding: 8px 20px;
    z-index: 1050; backdrop-filter: blur(6px);
}
.pulse-dot {
    width:10px;height:10px;border-radius:50%;
    display:inline-block;flex-shrink:0;
}
@keyframes pulse-green {
    0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.5)}
    50%{box-shadow:0 0 0 8px rgba(34,197,94,0)}
}
.dot-online { background:#22c55e; animation: pulse-green 1.5s infinite }
.dot-offline { background:#ef4444 }
.order-container-card { animation: slideIn .35s ease }
@keyframes slideIn {
    from { opacity:0; transform: translateY(20px) scale(.97) }
    to   { opacity:1; transform: translateY(0) scale(1) }
}
.empty-state { text-align:center; padding:60px 20px }
.empty-state .icon { font-size:5rem; margin-bottom:16px }
#orders-grid { padding-bottom: 60px }

/* ═══ Notification Badge ═══ */
#new-orders-badge {
    display: none;
    position: fixed; top: 80px; left: 50%; transform: translateX(-50%);
    background: #16a34a; color: #fff;
    border-radius: 30px; padding: 10px 24px;
    font-weight: 700; font-size: 15px;
    box-shadow: 0 8px 24px rgba(22,163,74,.4);
    cursor: pointer; z-index: 1040;
    animation: bounceBadge .5s ease;
}
@keyframes bounceBadge {
    0%  { transform: translateX(-50%) scale(.7); opacity:0 }
    70% { transform: translateX(-50%) scale(1.08) }
    100%{ transform: translateX(-50%) scale(1);  opacity:1 }
}
</style>

<!-- زرار طلبات جديدة -->
<div id="new-orders-badge" onclick="applyNewOrders()">
    🔔 <span id="new-orders-count">0</span> طلب جديد — اضغط لعرضها
</div>

<div class="container-fluid py-3" id="orders-grid">

    <!-- ===== الرأس ===== -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-0 fw-bold">🛒 لوحة الطلبات الإلكترونية</h4>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span id="conn-badge" class="badge px-3 py-2" style="font-size:13px">
                جارٍ التحقق...
            </span>
            <button onclick="fetchOrders(true)" class="btn btn-outline-primary btn-sm" id="refresh-btn">
                🔄 تحديث
            </button>
            <form action="<?= url('/online-orders/full-sync') ?>" method="POST" class="d-inline" id="full-sync-form">
                <?= csrf_field() ?>
                <button class="btn btn-warning btn-sm fw-bold"
                        onclick="return confirm('سيتم مزامنة كل المنتجات والأقسام مع المتجر. قد تستغرق دقيقة. متابعة؟')">
                    ☁️ مزامنة كاملة
                </button>
            </form>
            <a href="<?= url('/online-orders/accepted') ?>" class="btn btn-secondary btn-sm">📋 الطلبات المقبولة</a>
        </div>
    </div>

    <!-- ===== إحصائيات ===== -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card" style="background:linear-gradient(135deg,#1e3a5f,#2563eb)">
                <div class="stat-num" id="stat-pending"><?= count($orders) ?></div>
                <div class="stat-lbl">طلبات جديدة معلقة</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card" style="background:linear-gradient(135deg,#065f46,#10b981)">
                <div class="stat-num" id="stat-today"><?= $totalOnlineToday ?></div>
                <div class="stat-lbl">تم قبولها اليوم</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card" style="background:linear-gradient(135deg,#7c3aed,#a78bfa)">
                <div class="stat-num" id="stat-all"><?= $totalOnlineAll ?></div>
                <div class="stat-lbl">إجمالي الطلبات الإلكترونية</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card" id="stat-queue-card"
                 style="background:linear-gradient(135deg,<?= $queueCount > 0 ? '#b45309,#f59e0b' : '#0f172a,#334155' ?>)">
                <div class="stat-num" id="stat-queue"><?= $queueCount ?></div>
                <div class="stat-lbl">عمليات في الطابور</div>
            </div>
        </div>
    </div>

    <!-- ===== منطقة الطلبات (SPA) ===== -->
    <div id="orders-area">
        <?php if (empty($orders)): ?>
            <div class="empty-state" id="empty-state">
                <div class="icon">🛍️</div>
                <h5 class="text-muted">لا توجد طلبات إلكترونية معلقة حالياً</h5>
                <p class="text-muted small">ستظهر الطلبات الجديدة هنا تلقائياً بدون تحديث ✨</p>
            </div>
        <?php else: ?>
            <div class="row g-3" id="orders-cards-row">
                <?php foreach ($orders as $order):
                    $orderShort = strtoupper(substr($order['id'], 0, 8));
                    $rawItems   = $order['items'];
                    $items      = is_string($rawItems) ? json_decode($rawItems, true) : $rawItems;
                    $timeStr = date('d/m h:i A', strtotime($order['created_at']));
                    $orderTime = str_replace(['AM', 'PM'], ['ص', 'م'], $timeStr);
                    $orderTotal = number_format((float)$order['total_price'], 2);
                ?>
                    <?= orderCardHtml($order['id'], $orderShort, $orderTime, $orderTotal, $order, $items) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ===== شريط الحالة السفلي ===== -->
<div class="sync-bar">
    <span class="pulse-dot" id="sync-dot"></span>
    <span id="sync-text">جارٍ التحقق من الاتصال...</span>
    <span style="opacity:.4">|</span>
    <span id="sync-time" style="opacity:.6"></span>
</div>

<!-- ===== Toast Container ===== -->
<div id="toast-container-custom"
     style="position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:9999;display:flex;flex-direction:column;gap:10px;align-items:center;pointer-events:none">
</div>

<?php
// Helper: generates a single order card HTML (used both server-side and via JS template)
function orderCardHtml($id, $short, $time, $total, $order, $items): string {
    $baseUrl = url('/online-orders');
    $payLabel = ($order['payment_method'] ?? '') === 'COD' ? '💵 دفع عند الاستلام' : e($order['payment_method'] ?? '');
    $phone   = e($order['delivery_phone'] ?? '');
    $addr    = e($order['delivery_address'] ?? '');
    $csrf    = csrf_field();

    $itemsHtml = '';
    foreach ((array)$items as $it) {
        $lineTotal = number_format((float)($it['price'] ?? 0) * (float)($it['qty'] ?? 1), 2);
        $itemsHtml .= "<li class='d-flex justify-content-between'>
            <span><span class='badge bg-primary bg-opacity-10 text-primary me-1' style='min-width:26px'>{$it['qty']}×</span>" . e($it['name'] ?? '—') . "</span>
            <span class='text-muted small'>{$lineTotal} ج.م</span>
        </li>";
    }

    return "
    <div id='order-card-{$id}' class='col-lg-6 col-xl-4 order-container-card'>
        <div class='order-card'>
            <div class='card-header-inner d-flex justify-content-between align-items-center'>
                <div>
                    <div class='fw-bold fs-6'>#{$short}</div>
                    <div style='font-size:12px;opacity:.8'>⏱ {$time}</div>
                </div>
                <div class='text-end'>
                    <div class='fw-bold fs-5'>{$total} <small>ج.م</small></div>
                    <span class='badge bg-light text-dark' style='font-size:11px'>{$payLabel}</span>
                </div>
            </div>
            <div class='p-3'>
                <div class='mb-3'>
                    <div class='d-flex align-items-start gap-2 mb-1'><span>📞</span><span class='fw-bold'>{$phone}</span></div>
                    <div class='d-flex align-items-start gap-2'><span>📍</span><span class='text-muted small'>{$addr}</span></div>
                </div>
                <div class='mb-3'>
                    <div class='fw-bold small mb-1 text-secondary'>الأصناف المطلوبة:</div>
                    <ul class='items-list ps-0 mb-0'>{$itemsHtml}</ul>
                </div>
                <div class='d-flex gap-2'>
                    <form action='{$baseUrl}/{$id}/accept' method='POST' class='flex-fill'
                          onsubmit=\"handleOrderAction('{$id}', 'accept', '{$short}', event)\">
                        {$csrf}
                        <button type='submit' class='btn btn-success w-100 fw-bold'>✅ قبول وطباعة</button>
                    </form>
                    <form action='{$baseUrl}/{$id}/cancel' method='POST'
                          onsubmit=\"handleOrderAction('{$id}', 'cancel', '{$short}', event)\">
                        {$csrf}
                        <button type='submit' class='btn btn-outline-danger'>❌</button>
                    </form>
                </div>
            </div>
        </div>
    </div>";
}
?>

<script>
// ══════════════════════════════════════════════════════════════
//  SPA Engine — Online Orders
// ══════════════════════════════════════════════════════════════

const BASE       = '<?= url('/online-orders') ?>';
const STATUS_URL = '<?= url('/api/online-orders/status') ?>';
const FETCH_URL  = '<?= url('/api/online-orders/pending') ?>';

// حالة الطلبات الحالية (IDs)
let knownOrderIds = new Set([<?= implode(',', array_map(fn($o) => '"'.$o['id'].'"', $orders)) ?>]);
let pendingNewOrders = []; // طلبات جديدة لم تُعرض بعد
let isFirstCheck    = true;

// ══════════════════════════════════════
//  🔔 تنبيه صوتي (Web Audio API)
// ══════════════════════════════════════
function playAlertSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();

        const playBeep = (freq, start, duration, volume = 0.7) => {
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, ctx.currentTime + start);
            gain.gain.setValueAtTime(0, ctx.currentTime + start);
            gain.gain.linearRampToValueAtTime(volume, ctx.currentTime + start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + duration);
            osc.start(ctx.currentTime + start);
            osc.stop(ctx.currentTime + start + duration + 0.05);
        };

        // نغمة تنبيه — 3 نبضات صاعدة
        playBeep(440, 0.0,  0.15, 0.8);
        playBeep(554, 0.18, 0.15, 0.8);
        playBeep(659, 0.36, 0.25, 0.9);
        playBeep(880, 0.64, 0.40, 1.0);

        setTimeout(() => ctx.close(), 2000);
    } catch(e) { /* المتصفح لا يدعم Web Audio */ }
}

// ══════════════════════════════════════
//  🌐 جلب الطلبات الجديدة (API)
// ══════════════════════════════════════
async function fetchOrders(manual = false) {
    const btn = document.getElementById('refresh-btn');
    if (manual && btn) { btn.innerHTML = '⏳'; btn.disabled = true; }

    try {
        const res  = await fetch(FETCH_URL, { credentials: 'same-origin' });
        const data = await res.json();

        const incoming = data.orders || [];
        const incomingIds = new Set(incoming.map(o => o.id));

        // طلبات جديدة لم تكن موجودة
        const newOnes = incoming.filter(o => !knownOrderIds.has(o.id));

        if (newOnes.length > 0 && !isFirstCheck) {
            // اعرضهم مباشرة على الشاشة بدون أزرار إضافية
            renderOrders(incoming, true);
            knownOrderIds = incomingIds;
            playAlertSound();
        } else if (isFirstCheck || manual) {
            // أول تحميل أو تحديث يدوي: أعرضهم مباشرة
            renderOrders(incoming, manual);
            knownOrderIds = incomingIds;
        } else {
            // polling عادي — حدّث knownOrderIds فقط (بدون إعادة رسم)
            knownOrderIds = incomingIds;
        }

        isFirstCheck = false;
        // ملاحظة: updateStats يتم فقط من checkStatus() لا من هنا

    } catch(e) {
        if (manual) showToast('تعذّر الاتصال بالخادم', 'danger');
    } finally {
        if (btn) { btn.innerHTML = '🔄 تحديث'; btn.disabled = false; }
    }
}

// ══════════════════════════════════════
//  🎴 رسم بطاقة طلب جديدة (JS Template)
// ══════════════════════════════════════
function buildOrderCard(o) {
    const short = o.id.substring(0,8).toUpperCase();
    const items = typeof o.items === 'string' ? JSON.parse(o.items) : (o.items || []);
    const total = parseFloat(o.total_price || 0).toFixed(2);
    const payLabel = o.payment_method === 'COD' ? '💵 دفع عند الاستلام' : (o.payment_method || '');
    const time  = new Date(o.created_at).toLocaleString('ar-EG', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit',hour12:true});

    const itemsHtml = items.map(it => {
        const lineTotal = ((parseFloat(it.price)||0) * (parseFloat(it.qty)||1)).toFixed(2);
        return `<li class="d-flex justify-content-between">
            <span><span class="badge bg-primary bg-opacity-10 text-primary me-1" style="min-width:26px">${it.qty}×</span>${escHtml(it.name||'—')}</span>
            <span class="text-muted small">${lineTotal} ج.م</span>
        </li>`;
    }).join('');

    const csrf = document.querySelector('#full-sync-form input[name="_token"]')?.value || '';

    return `
    <div id="order-card-${o.id}" class="col-lg-6 col-xl-4 order-container-card">
        <div class="order-card">
            <div class="card-header-inner d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold fs-6">#${short}</div>
                    <div style="font-size:12px;opacity:.8">⏱ ${time}</div>
                </div>
                <div class="text-end">
                    <div class="fw-bold fs-5">${total} <small>ج.م</small></div>
                    <span class="badge bg-light text-dark" style="font-size:11px">${payLabel}</span>
                </div>
            </div>
            <div class="p-3">
                <div class="mb-3">
                    <div class="d-flex align-items-start gap-2 mb-1"><span>📞</span><span class="fw-bold">${escHtml(o.delivery_phone||'')}</span></div>
                    <div class="d-flex align-items-start gap-2"><span>📍</span><span class="text-muted small">${escHtml(o.delivery_address||'')}</span></div>
                </div>
                <div class="mb-3">
                    <div class="fw-bold small mb-1 text-secondary">الأصناف المطلوبة:</div>
                    <ul class="items-list ps-0 mb-0">${itemsHtml}</ul>
                </div>
                <div class="d-flex gap-2">
                    <form action="${BASE}/${o.id}/accept" method="POST" class="flex-fill"
                          onsubmit="handleOrderAction('${o.id}', 'accept', '${short}', event)">
                        <input type="hidden" name="_token" value="${escHtml(csrf)}">
                        <button type="submit" class="btn btn-success w-100 fw-bold">✅ قبول وطباعة</button>
                    </form>
                    <form action="${BASE}/${o.id}/cancel" method="POST"
                          onsubmit="handleOrderAction('${o.id}', 'cancel', '${short}', event)">
                        <input type="hidden" name="_token" value="${escHtml(csrf)}">
                        <button type="submit" class="btn btn-outline-danger">❌</button>
                    </form>
                </div>
            </div>
        </div>
    </div>`;
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ══════════════════════════════════════
//  📌 عرض زرار "طلبات جديدة"
// ══════════════════════════════════════
function showNewOrdersBadge() {
    const badge = document.getElementById('new-orders-badge');
    document.getElementById('new-orders-count').textContent = pendingNewOrders.length;
    badge.style.display = 'block';
}

function applyNewOrders() {
    const badge = document.getElementById('new-orders-badge');
    badge.style.display = 'none';

    pendingNewOrders.forEach(o => {
        if (!document.getElementById(`order-card-${o.id}`)) {
            addOrderCard(o);
            knownOrderIds.add(o.id);
        }
    });
    pendingNewOrders = [];

    // أخفِ empty state لو كان ظاهر
    const empty = document.getElementById('empty-state');
    if (empty) empty.style.display = 'none';

    updatePendingCount();
}

// ══════════════════════════════════════
//  🃏 إضافة / حذف بطاقة
// ══════════════════════════════════════
function addOrderCard(o) {
    let row = document.getElementById('orders-cards-row');
    if (!row) {
        const area = document.getElementById('orders-area');
        area.innerHTML = '<div class="row g-3" id="orders-cards-row"></div>';
        row = document.getElementById('orders-cards-row');
    }
    row.insertAdjacentHTML('afterbegin', buildOrderCard(o));
}

function renderOrders(orders, animated = false) {
    const area = document.getElementById('orders-area');
    if (orders.length === 0) {
        area.innerHTML = `<div class="empty-state" id="empty-state">
            <div class="icon">🛍️</div>
            <h5 class="text-muted">لا توجد طلبات إلكترونية معلقة حالياً</h5>
            <p class="text-muted small">ستظهر الطلبات الجديدة هنا تلقائياً بدون تحديث ✨</p>
        </div>`;
    } else {
        area.innerHTML = '<div class="row g-3" id="orders-cards-row">' +
            orders.map(o => buildOrderCard(o)).join('') + '</div>';
    }
    updatePendingCount(orders.length);
}

function updatePendingCount(n) {
    const el = document.getElementById('stat-pending');
    if (el) el.textContent = (n !== undefined) ? n : document.querySelectorAll('.order-container-card').length;
}

function updateStats(data) {
    const dot  = document.getElementById('sync-dot');
    const text = document.getElementById('sync-text');
    const timeEl = document.getElementById('sync-time');
    const badge  = document.getElementById('conn-badge');

    const online = data.online ?? false;
    if (dot)  { dot.className = 'pulse-dot ' + (online ? 'dot-online' : 'dot-offline'); }
    if (text) text.textContent = online ? 'متصل بالمتجر ✓' : 'غير متصل ⚠️';
    if (timeEl) timeEl.textContent = data.timestamp || '';
    if (badge) {
        badge.className = 'badge px-3 py-2 ' + (online ? 'bg-success' : 'bg-danger');
        badge.innerHTML = `<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${online?'#fff':'#fca5a5'};margin-left:6px"></span>
            ${online ? 'متصل بالمتجر ✓' : 'غير متصل ⚠️'}`;
    }

    const q = data.queue_count ?? 0;
    const qEl = document.getElementById('stat-queue');
    if (qEl) qEl.textContent = q;
    const qCard = document.getElementById('stat-queue-card');
    if (qCard) {
        qCard.style.background = q > 0
            ? 'linear-gradient(135deg,#b45309,#f59e0b)'
            : 'linear-gradient(135deg,#0f172a,#334155)';
    }
}

// ══════════════════════════════════════
//  ✅ قبول / إلغاء (AJAX — لا redirect)
// ══════════════════════════════════════
async function handleOrderAction(orderId, action, orderShort, event) {
    event.preventDefault();

    const card     = document.getElementById(`order-card-${orderId}`);
    const btnOk    = card?.querySelector('.btn-success');
    const btnX     = card?.querySelector('.btn-outline-danger');

    if (btnOk) { btnOk.disabled = true; btnOk.innerHTML = '⏳ جاري...'; }
    if (btnX)  { btnX.disabled  = true; }

    try {
        const csrfToken = card?.querySelector('input[name="_token"]')?.value || '';
        const res  = await fetch(`${BASE}/${orderId}/${action}?ajax=1`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ '_token': csrfToken })
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message || '✅ تمت العملية', 'success');

            // 🖨️ طباعة الفاتورة صامتاً إذا تم قبول الطلب بنجاح
            if (action === 'accept' && data.invoice_id) {
                const printUrl = `<?= url('/sales/') ?>` + data.invoice_id + '/print?autoprint=1&self_close=1';
                const popupName = `pos-print-online-${data.invoice_id}`;
                const popupFeatures = [
                    'popup=yes',
                    'width=460',
                    'height=760',
                    'resizable=yes',
                    'scrollbars=yes',
                    'toolbar=no',
                    'location=no',
                    'menubar=no',
                    'status=no'
                ].join(',');
                window.open(printUrl, popupName, popupFeatures);
            }

            // ✨ حذف الكارت بأنيميشن ناعم
            if (card) {
                card.style.transition = 'all .4s ease';
                card.style.opacity    = '0';
                card.style.transform  = 'scale(.9)';
                setTimeout(() => {
                    card.remove();
                    knownOrderIds.delete(orderId);
                    updatePendingCount();

                    // لو كانت الشاشة فاضية
                    const remaining = document.querySelectorAll('.order-container-card');
                    if (remaining.length === 0) {
                        document.getElementById('orders-area').innerHTML = `
                        <div class="empty-state" id="empty-state">
                            <div class="icon">🛍️</div>
                            <h5 class="text-muted">لا توجد طلبات معلقة حالياً</h5>
                            <p class="text-muted small">ستظهر الطلبات الجديدة هنا تلقائياً ✨</p>
                        </div>`;
                        document.getElementById('stat-pending').textContent = 0;
                    }
                }, 420);
            }

            // حدّث إحصائية "قُبلت اليوم"
            if (action === 'accept') {
                const todayEl = document.getElementById('stat-today');
                if (todayEl) todayEl.textContent = parseInt(todayEl.textContent||0) + 1;
                const allEl = document.getElementById('stat-all');
                if (allEl) allEl.textContent = parseInt(allEl.textContent||0) + 1;
            }
        } else {
            showToast(data.message || 'حدث خطأ غير متوقع', 'danger');
            if (btnOk) { btnOk.disabled = false; btnOk.innerHTML = '✅ قبول وطباعة'; }
            if (btnX)  { btnX.disabled  = false; }
        }
    } catch(err) {
        showToast('فشل الاتصال: ' + err.message, 'danger');
        if (btnOk) { btnOk.disabled = false; btnOk.innerHTML = '✅ قبول وطباعة'; }
        if (btnX)  { btnX.disabled  = false; }
    }
}

// ══════════════════════════════════════
//  🍞 Toast
// ══════════════════════════════════════
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container-custom');
    const toast = document.createElement('div');
    const bg    = type === 'success' ? '#22c55e' : '#ef4444';
    toast.style.cssText = `pointer-events:auto;background:${bg};color:#fff;padding:13px 24px;border-radius:14px;font-weight:700;font-size:14px;box-shadow:0 12px 28px rgba(0,0,0,.2);opacity:0;transform:translateY(-16px);transition:all .3s ease;direction:rtl;white-space:nowrap`;
    toast.textContent = message;
    container.appendChild(toast);
    requestAnimationFrame(() => { toast.style.opacity='1'; toast.style.transform='translateY(0)'; });
    setTimeout(() => {
        toast.style.opacity='0'; toast.style.transform='translateY(-16px)';
        setTimeout(() => toast.remove(), 350);
    }, 4500);
}

// ══════════════════════════════════════
//  ⏱ Polling — كل 20 ثانية
// ══════════════════════════════════════
// فحص الحالة (connection + queue)
async function checkStatus() {
    try {
        const res  = await fetch(STATUS_URL, { credentials: 'same-origin' });
        const data = await res.json();
        updateStats(data);
    } catch(e) {
        const dot = document.getElementById('sync-dot');
        if (dot) dot.className = 'pulse-dot dot-offline';
    }
}

// أول تحميل
checkStatus();
fetchOrders(false);

// polling دوري كاحتياط
setInterval(() => fetchOrders(false), 20000); // فحص طلبات جديدة كل 20 ثانية
setInterval(checkStatus, 30000);               // فحص الاتصال كل 30 ثانية

// الاستماع لحدث طلب جديد لتحديث الصفحة فورياً
if (window.electronAPI && typeof window.electronAPI.onNewOrderReceived === 'function') {
    window.electronAPI.onNewOrderReceived(() => {
        console.log('Real-time order refresh triggered by webhook!');
        fetchOrders(true); // جلب فوري للطلبات الجديدة
        checkStatus();     // فحص الحالة
    });
}
</script>
