<?php $title = 'لوحة التحكم'; ?>
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="card-stat"><div class="label">مبيعات اليوم</div><div class="value"><?= money($cards['today_sales']) ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card-stat"><div class="label">مبيعات الشهر</div><div class="value"><?= money($cards['month_sales']) ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card-stat"><div class="label">إجمالي المبيعات</div><div class="value"><?= money($cards['all_sales']) ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card-stat"><div class="label">فواتير اليوم</div><div class="value"><?= (int) $cards['today_invoices'] ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card-stat"><div class="label">عدد المنتجات</div><div class="value"><?= (int) $cards['products_count'] ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card-stat"><div class="label">عدد العملاء</div><div class="value"><?= (int) $cards['customers_count'] ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card-stat"><div class="label">إجمالي المرتجعات</div><div class="value"><?= money($cards['returns_total']) ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card-stat"><div class="label">إجمالي الآجل</div><div class="value"><?= money($cards['due_total']) ?></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="table-wrap p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">مخطط مبيعات اليوم (بالساعة)</h6>
            </div>
            <?php
            $labels = array_map(fn($r) => $r['d'], $salesByDay);
            $values = array_map(fn($r) => (float) $r['total'], $salesByDay);
            ?>
            <canvas id="salesChart" data-chart='<?= e(json_encode(['labels' => $labels, 'values' => $values], JSON_UNESCAPED_UNICODE)) ?>' height="120"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="table-wrap p-3 h-100">
            <h6 class="mb-3">ملخص الشيفت الحالي</h6>
            <?php if ($openShift): ?>
                <div>رقم الشيفت: <strong><?= e($openShift['shift_no']) ?></strong></div>
                <div>افتتح: <?= e($openShift['opened_at']) ?></div>
                <div>الرصيد الافتتاحي: <?= money($openShift['opening_balance']) ?></div>
                <div>الرصيد المتوقع: <?= money($openShift['expected_balance']) ?></div>
            <?php else: ?>
                <div class="text-muted">لا يوجد شيفت مفتوح.</div>
                <?php if (can('shifts.manage')): ?><a class="btn btn-sm btn-primary mt-2" href="<?= url('/shifts') ?>">فتح شيفت</a><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="table-wrap">
            <div class="p-3 border-bottom"><h6 class="mb-0">الأصناف الأكثر مبيعًا</h6></div>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>الصنف</th><th>الكمية</th></tr></thead>
                    <tbody>
                    <?php foreach ($topProducts as $row): ?>
                        <tr><td><?= e($row['name']) ?></td><td><?= money($row['total_qty']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="table-wrap">
            <div class="p-3 border-bottom"><h6 class="mb-0">تنبيهات المخزون المنخفض</h6></div>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>الصنف</th><th>المتاح</th><th>الحد الأدنى</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowStock as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td><span class="badge badge-stock-low"><?= money($row['current_stock']) ?></span></td>
                            <td><?= money($row['min_stock']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<h6 class="mt-4 mb-3 fw-bold text-primary border-bottom pb-2">📊 تحليلات المتجر الإلكتروني</h6>
<div class="row g-3 mb-4" id="ecomStatsContainer">
    <div class="col-12 text-center py-4" id="ecomStatsLoading">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
        <span class="ms-2 text-muted small">جاري تحميل إحصائيات المتجر...</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    function formatMoney(val) {
        return parseFloat(val || 0).toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ج.م';
    }

    fetch('<?= url('/api/online-store/stats') ?>')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('ecomStatsContainer');
            const loading = document.getElementById('ecomStatsLoading');
            if (loading) loading.remove();
            
            if (data.success) {
                container.innerHTML = `
                    <div class="col-6 col-xl-3">
                        <div class="card-stat bg-light border-start border-4 border-info">
                            <div class="label text-dark">مبيعات المتجر الإلكتروني</div>
                            <div class="value text-info font-monospace">${formatMoney(data.total_sales)}</div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card-stat bg-light border-start border-4 border-warning">
                            <div class="label text-dark">طلبات معلقة</div>
                            <div class="value text-warning font-monospace">${data.pending_orders}</div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card-stat bg-light border-start border-4 border-primary">
                            <div class="label text-dark">طلبات قيد التحضير</div>
                            <div class="value text-primary font-monospace">${data.preparing_orders}</div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card-stat bg-light border-start border-4 border-success">
                            <div class="label text-dark">طلبات قيد التوصيل</div>
                            <div class="value text-success font-monospace">${data.delivering_orders}</div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card-stat bg-light border-start border-4 border-secondary">
                            <div class="label text-dark">طلبات مكتملة</div>
                            <div class="value text-secondary font-monospace">${data.completed_orders}</div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card-stat bg-light border-start border-4 border-dark">
                            <div class="label text-dark">إجمالي الطلبات</div>
                            <div class="value font-monospace">${data.total_orders}</div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card-stat bg-light border-start border-4 border-danger">
                            <div class="label text-dark">عملاء المتجر</div>
                            <div class="value text-danger font-monospace">${data.customers_count}</div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card-stat bg-light border-start border-4 border-muted">
                            <div class="label text-dark">الكوبونات / البنرات</div>
                            <div class="value text-muted font-monospace">${data.coupons_count} / ${data.banners_count}</div>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-warning text-center small py-2 mb-0">
                            ⚠️ تعذر جلب إحصائيات المتجر الإلكتروني: ${data.error || 'خطأ غير معروف'}
                        </div>
                    </div>
                `;
            }
        })
        .catch(err => {
            const container = document.getElementById('ecomStatsContainer');
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger text-center small py-2 mb-0">
                        ❌ فشل الاتصال بخادم المزامنة السحابي.
                    </div>
                </div>
            `;
        });
});
</script>

