<?php $title = 'تحليلات المتجر الإلكتروني'; ?>

<style>
/* ═══════════════════════════════════════════════════════════════
   صفحة تحليلات المتجر الإلكتروني – ستايل كامل
══════════════════════════════════════════════════════════════════ */
.analytics-page { padding: 0; }

/* ─── شريط الرأس ─────────────────────────────────────────── */
.analytics-hero {
    background: linear-gradient(135deg, #0f172a 0%, #16a34a 50%, #0f172a 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    position: relative;
    overflow: hidden;
}
.analytics-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 80% 80% at 20% 50%, rgba(22,163,74,.18) 0%, transparent 70%);
    pointer-events: none;
}
.analytics-hero-text h2 {
    font-size: 1.6rem; font-weight: 800; color: #fff;
    margin: 0 0 4px; display: flex; align-items: center; gap: 10px;
}
.analytics-hero-text p { color: #94a3b8; margin: 0; font-size: .95rem; }
.analytics-refresh-btn {
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18);
    color: #fff; border-radius: 10px; padding: 10px 20px;
    font-size: .92rem; font-weight: 600; cursor: pointer;
    transition: all .2s; display: flex; align-items: center; gap: 8px;
    white-space: nowrap; backdrop-filter: blur(6px);
}
.analytics-refresh-btn:hover { background: rgba(255,255,255,.18); }
.analytics-refresh-btn.loading svg { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ─── بطاقات الأرقام السريعة ──────────────────────────────── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.kpi-card {
    background: #fff;
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    border: 1px solid #e2e8f0;
    display: flex; flex-direction: column; gap: 8px;
    transition: transform .2s, box-shadow .2s;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }
.kpi-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
}
.kpi-value {
    font-size: 1.65rem; font-weight: 800; color: #0f172a; line-height: 1;
}
.kpi-label { font-size: .78rem; color: #64748b; font-weight: 600; }
.kpi-sub { font-size: .74rem; color: #94a3b8; }

/* ─── ألوان البطاقات ─────────────────────────────────────── */
.kpi-blue   .kpi-icon { background: #dbeafe; }
.kpi-green  .kpi-icon { background: #dcfce7; }
.kpi-amber  .kpi-icon { background: #fef3c7; }
.kpi-purple .kpi-icon { background: #ede9fe; }
.kpi-rose   .kpi-icon { background: #ffe4e6; }
.kpi-cyan   .kpi-icon { background: #cffafe; }
.kpi-indigo .kpi-icon { background: #e0e7ff; }
.kpi-teal   .kpi-icon { background: #ccfbf1; }

/* ─── قسم الرسوم البيانية ────────────────────────────────── */
.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
.charts-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
@media (max-width: 1100px) {
    .charts-grid { grid-template-columns: 1fr; }
    .charts-grid-3 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .charts-grid-3 { grid-template-columns: 1fr; }
}

.chart-card {
    background: #fff; border-radius: 14px; padding: 22px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #e2e8f0;
}
.chart-card-title {
    font-size: .95rem; font-weight: 700; color: #1e293b;
    margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
}
.chart-card canvas { max-height: 260px; }
.chart-card.tall canvas { max-height: 320px; }

/* ─── جدول المنتجات الأكثر مبيعاً ──────────────────────── */
.products-table { width: 100%; border-collapse: collapse; }
.products-table th {
    font-size: .75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; color: #94a3b8; padding: 8px 12px;
    border-bottom: 2px solid #f1f5f9; text-align: right;
}
.products-table td {
    padding: 10px 12px; border-bottom: 1px solid #f8fafc;
    font-size: .88rem; color: #334155;
}
.products-table tr:last-child td { border-bottom: none; }
.products-table tr:hover td { background: #f8fafc; }
.rank-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 50%;
    font-size: .78rem; font-weight: 800; color: #fff;
}
.rank-1 { background: #f59e0b; }
.rank-2 { background: #94a3b8; }
.rank-3 { background: #b45309; }
.rank-other { background: #e2e8f0; color: #64748b; }

/* ─── جدول العملاء ──────────────────────────────────────── */
.customers-table { width: 100%; border-collapse: collapse; }
.customers-table td {
    padding: 10px 12px; border-bottom: 1px solid #f8fafc;
    font-size: .88rem; color: #334155;
}
.customers-table tr:last-child td { border-bottom: none; }
.customer-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: .85rem;
}

/* ─── حالة التحميل ──────────────────────────────────────── */
.loading-overlay {
    display: flex; align-items: center; justify-content: center;
    min-height: 220px; color: #94a3b8; flex-direction: column; gap: 12px;
}
.loading-spinner {
    width: 36px; height: 36px; border: 3px solid #e2e8f0;
    border-top-color: #2563eb; border-radius: 50%;
    animation: spin 1s linear infinite;
}
.error-box {
    background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px;
    padding: 20px; text-align: center; color: #dc2626;
}
.progress-mini {
    height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;
    margin-top: 4px;
}
.progress-mini-fill {
    height: 100%; border-radius: 3px; transition: width .5s;
}
</style>

<div class="analytics-page">

    <!-- ── Hero ──────────────────────────────────────── -->
    <div class="analytics-hero">
        <div class="analytics-hero-text">
            <h2>📊 تحليلات المتجر الإلكتروني</h2>
            <p>لوحة بيانات كاملة ومحدّثة من Supabase Cloud</p>
        </div>
        <button class="analytics-refresh-btn" id="refreshBtn" onclick="loadAnalytics()">
            <svg id="refreshIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            تحديث البيانات
        </button>
    </div>

    <!-- ── KPI Cards ──────────────────────────────── -->
    <div class="kpi-grid" id="kpiGrid">
        <div class="loading-overlay" style="grid-column: 1/-1; min-height: 100px;">
            <div class="loading-spinner"></div>
            <span style="font-size:.85rem">جاري تحميل الإحصائيات…</span>
        </div>
    </div>

    <!-- ── Charts Row 1 ───────────────────────────── -->
    <div class="charts-grid" id="chartsRow1" style="display:none;">
        <div class="chart-card tall">
            <div class="chart-card-title">📈 المبيعات اليومية (آخر 30 يوم)</div>
            <canvas id="salesChart"></canvas>
        </div>
        <div class="chart-card">
            <div class="chart-card-title">🍩 توزيع الطلبات حسب الحالة</div>
            <canvas id="statusChart"></canvas>
        </div>
    </div>

    <!-- ── Charts Row 2 ───────────────────────────── -->
    <div class="charts-grid-3" id="chartsRow2" style="display:none;">
        <div class="chart-card">
            <div class="chart-card-title">💳 طرق الدفع</div>
            <canvas id="paymentChart"></canvas>
        </div>
        <div class="chart-card" style="grid-column: span 2; overflow-x:auto;">
            <div class="chart-card-title">🏆 أكثر المنتجات مبيعاً</div>
            <table class="products-table" id="productsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم المنتج</th>
                        <th>الكمية المباعة</th>
                        <th>إجمالي الإيرادات</th>
                        <th>نسبة المبيعات</th>
                    </tr>
                </thead>
                <tbody id="productsBody">
                    <tr><td colspan="5" class="text-center text-muted py-3">لا يوجد بيانات</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Charts Row 3 ───────────────────────────── -->
    <div class="charts-grid" id="chartsRow3" style="display:none;">
        <div class="chart-card">
            <div class="chart-card-title">👥 أحدث العملاء المسجلين</div>
            <table class="customers-table" id="customersTable">
                <tbody id="customersBody"></tbody>
            </table>
        </div>
        <div class="chart-card">
            <div class="chart-card-title">📋 ملخص سريع</div>
            <div id="summaryContent"></div>
        </div>
    </div>

    <!-- error -->
    <div id="errorBox" style="display:none;" class="error-box">
        <div style="font-size:2rem;margin-bottom:8px">⚠️</div>
        <div id="errorMsg">فشل تحميل البيانات</div>
        <button onclick="loadAnalytics()" class="btn btn-sm btn-danger mt-3">إعادة المحاولة</button>
    </div>

</div>

<script>
/* ═══════════════════════════════════════════════════
   Analytics Page Script
══════════════════════════════════════════════════════ */
let salesChart, statusChart, paymentChart;

const COLORS = {
    pending:    '#f59e0b',
    preparing:  '#3b82f6',
    delivering: '#8b5cf6',
    completed:  '#22c55e',
    cancelled:  '#ef4444',
};

const STATUS_LABELS = {
    pending: '⏳ معلق',
    preparing: '🔨 قيد التحضير',
    delivering: '🚚 قيد التوصيل',
    completed: '✅ مكتمل',
    cancelled: '❌ ملغي',
};

const PAYMENT_LABELS = {
    COD: '💵 الدفع عند الاستلام',
    online: '💳 دفع إلكتروني',
    cash: '💵 كاش',
    card: '💳 بطاقة',
};

function fmt(n, dec = 2) {
    return Number(n || 0).toLocaleString('ar-EG', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}
function fmtInt(n) {
    return Number(n || 0).toLocaleString('ar-EG');
}

async function loadAnalytics() {
    const btn = document.getElementById('refreshBtn');
    const icon = document.getElementById('refreshIcon');
    btn.disabled = true;
    btn.classList.add('loading');

    document.getElementById('kpiGrid').innerHTML = `
        <div class="loading-overlay" style="grid-column:1/-1;min-height:100px;">
            <div class="loading-spinner"></div>
            <span style="font-size:.85rem">جاري تحميل الإحصائيات…</span>
        </div>`;
    document.getElementById('chartsRow1').style.display = 'none';
    document.getElementById('chartsRow2').style.display = 'none';
    document.getElementById('chartsRow3').style.display = 'none';
    document.getElementById('errorBox').style.display = 'none';

    try {
        const res = await fetch('<?= url('/api/online-store/analytics') ?>', { credentials: 'same-origin' });
        const data = await res.json();

        if (!data.success) throw new Error(data.error || 'فشل الاتصال');

        renderKPI(data);
        renderSalesChart(data.sales_by_day || {});
        renderStatusChart(data.by_status || {});
        renderPaymentChart(data.by_payment || {});
        renderProducts(data.top_products || {}, data.total_orders || 1);
        renderCustomers(data.top_customers || []);
        renderSummary(data);

        document.getElementById('chartsRow1').style.display = '';
        document.getElementById('chartsRow2').style.display = '';
        document.getElementById('chartsRow3').style.display = '';
    } catch (e) {
        document.getElementById('kpiGrid').innerHTML = '';
        document.getElementById('errorBox').style.display = '';
        document.getElementById('errorMsg').textContent = e.message;
    } finally {
        btn.disabled = false;
        btn.classList.remove('loading');
    }
}

/* ─── KPI Cards ────────────────────────────────────── */
function renderKPI(d) {
    const total = Number(d.total_orders) || 0;
    const completed = Number(d.completed_orders) || 0;
    const completionRate = total > 0 ? Math.round((completed / total) * 100) : 0;

    const cards = [
        { icon: '💰', label: 'إجمالي مبيعات المتجر', value: fmt(d.total_sales) + ' ج.م', sub: 'كل الطلبات', cls: 'kpi-green' },
        { icon: '✅', label: 'مبيعات مكتملة', value: fmt(d.completed_sales) + ' ج.م', sub: `${completionRate}% معدل إتمام`, cls: 'kpi-teal' },
        { icon: '📦', label: 'إجمالي الطلبات', value: fmtInt(d.total_orders), sub: 'كل الوقت', cls: 'kpi-blue' },
        { icon: '⏳', label: 'طلبات معلقة', value: fmtInt(d.pending_orders), sub: 'تحتاج موافقة', cls: 'kpi-amber' },
        { icon: '🔨', label: 'قيد التحضير', value: fmtInt(d.preparing_orders), sub: 'يجري تجهيزها', cls: 'kpi-purple' },
        { icon: '🚚', label: 'قيد التوصيل', value: fmtInt(d.delivering_orders), sub: 'على الطريق', cls: 'kpi-indigo' },
        { icon: '👥', label: 'عملاء المتجر', value: fmtInt(d.total_customers), sub: `${d.new_customers_7d} جديد هذا الأسبوع`, cls: 'kpi-rose' },
        { icon: '🎟️', label: 'كوبونات نشطة', value: fmtInt(d.coupons_count), sub: 'عروض وخصومات', cls: 'kpi-cyan' },
    ];

    document.getElementById('kpiGrid').innerHTML = cards.map(c => `
        <div class="kpi-card ${c.cls}">
            <div class="kpi-icon">${c.icon}</div>
            <div class="kpi-value">${c.value}</div>
            <div class="kpi-label">${c.label}</div>
            <div class="kpi-sub">${c.sub}</div>
        </div>`).join('');
}

/* ─── رسم مبيعات اليوم ─────────────────────────────── */
function renderSalesChart(byDay) {
    const labels = Object.keys(byDay);
    const values = Object.values(byDay);
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;
    const existing = Chart.getChart(ctx);
    if (existing) existing.destroy();

    salesChart = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'المبيعات (ج.م)',
                data: values,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,.1)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointRadius: labels.length < 15 ? 4 : 2,
                pointBackgroundColor: '#2563eb',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: {
                callbacks: { label: ctx => fmt(ctx.parsed.y) + ' ج.م' }
            }},
            scales: {
                x: { ticks: { font: { size: 11 }, maxTicksLimit: 10 } },
                y: { ticks: { callback: v => fmt(v, 0) } }
            }
        }
    });
}

/* ─── رسم حالات الطلبات ────────────────────────────── */
function renderStatusChart(byStatus) {
    const entries = Object.entries(byStatus).filter(([, v]) => v > 0);
    const ctx = document.getElementById('statusChart');
    if (!ctx) return;
    const existing = Chart.getChart(ctx);
    if (existing) existing.destroy();

    statusChart = new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: entries.map(([k]) => STATUS_LABELS[k] || k),
            datasets: [{
                data: entries.map(([, v]) => v),
                backgroundColor: entries.map(([k]) => COLORS[k] || '#94a3b8'),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 12 }, padding: 12 } },
                tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} طلب` } }
            },
            cutout: '62%',
        }
    });
}

/* ─── رسم طرق الدفع ────────────────────────────────── */
function renderPaymentChart(byPayment) {
    const entries = Object.entries(byPayment).filter(([, v]) => v > 0);
    const ctx = document.getElementById('paymentChart');
    if (!ctx) return;
    const existing = Chart.getChart(ctx);
    if (existing) existing.destroy();

    const bgColors = ['#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6', '#ec4899'];
    paymentChart = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: entries.map(([k]) => PAYMENT_LABELS[k] || k),
            datasets: [{
                label: 'عدد الطلبات',
                data: entries.map(([, v]) => v),
                backgroundColor: bgColors,
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
}

/* ─── جدول المنتجات ────────────────────────────────── */
function renderProducts(products, totalOrders) {
    const entries = Object.entries(products);
    if (!entries.length) return;

    const maxQty = Math.max(...entries.map(([, v]) => v.qty));

    document.getElementById('productsBody').innerHTML = entries.map(([name, stats], i) => {
        const pct = maxQty > 0 ? Math.round((stats.qty / maxQty) * 100) : 0;
        const rankCls = i === 0 ? 'rank-1' : i === 1 ? 'rank-2' : i === 2 ? 'rank-3' : 'rank-other';
        const barColor = i < 3 ? '#2563eb' : '#94a3b8';
        return `<tr>
            <td><span class="rank-badge ${rankCls}">${i + 1}</span></td>
            <td><strong>${name}</strong></td>
            <td>${fmtInt(stats.qty)} وحدة</td>
            <td>${fmt(stats.revenue)} ج.م</td>
            <td style="min-width:100px">
                <div style="font-size:.75rem;color:#64748b;margin-bottom:3px">${pct}%</div>
                <div class="progress-mini"><div class="progress-mini-fill" style="width:${pct}%;background:${barColor}"></div></div>
            </td>
        </tr>`;
    }).join('');
}

/* ─── جدول العملاء ────────────────────────────────── */
function renderCustomers(customers) {
    document.getElementById('customersBody').innerHTML = customers.map(c => {
        const name = c.full_name || 'عميل';
        const initials = name.charAt(0);
        const joined = c.created_at ? new Date(c.created_at).toLocaleDateString('ar-EG') : '—';
        return `<tr>
            <td><div style="display:flex;align-items:center;gap:10px">
                <div class="customer-avatar">${initials}</div>
                <div>
                    <div style="font-weight:600">${name}</div>
                    <div style="font-size:.75rem;color:#94a3b8">${c.phone || '—'}</div>
                </div>
            </div></td>
            <td><span style="background:#fef3c7;color:#92400e;border-radius:20px;padding:3px 10px;font-size:.78rem;font-weight:700">🪙 ${fmtInt(c.points || 0)} نقطة</span></td>
            <td style="font-size:.78rem;color:#94a3b8">${joined}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="3" class="text-center text-muted">لا يوجد عملاء بعد</td></tr>';
}

/* ─── الملخص السريع ────────────────────────────────── */
function renderSummary(d) {
    const total = Number(d.total_orders) || 0;
    const completed = Number(d.completed_orders) || 0;
    const completedSales = Number(d.completed_sales) || 0;
    const convRate = total > 0 ? ((completed / total) * 100).toFixed(1) : '0.0';
    const avgOrder = completed > 0 ? fmt(completedSales / completed) : '0.00';

    const items = [
        ['📦', 'إجمالي الطلبات', fmtInt(d.total_orders) + ' طلب'],
        ['✅', 'معدل إتمام الطلبات', convRate + '%'],
        ['💵', 'متوسط قيمة الطلب', avgOrder + ' ج.م'],
        ['👥', 'إجمالي العملاء', fmtInt(d.total_customers) + ' عميل'],
        ['🆕', 'عملاء جدد (7 أيام)', fmtInt(d.new_customers_7d) + ' عميل'],
        ['🎟️', 'كوبونات الخصم', fmtInt(d.coupons_count) + ' كوبون'],
    ];

    document.getElementById('summaryContent').innerHTML = items.map(([icon, label, val]) => `
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;">
            <div style="display:flex;align-items:center;gap:8px;font-size:.88rem;color:#475569">
                <span>${icon}</span><span>${label}</span>
            </div>
            <strong style="font-size:.9rem;color:#0f172a">${val}</strong>
        </div>`).join('');
}

// تحميل البيانات عند فتح الصفحة
document.addEventListener('DOMContentLoaded', loadAnalytics);
</script>
