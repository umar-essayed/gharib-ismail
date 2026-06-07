<?php $title = 'الطلبات الإلكترونية المقبولة'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">📋 الطلبات الإلكترونية المقبولة</h4>
    <a href="<?= url('/online-orders') ?>" class="btn btn-primary btn-sm">← العودة للطلبات الجديدة</a>
</div>

<div id="no-orders-alert" class="text-center py-5 text-muted <?= !empty($rows) ? 'd-none' : '' ?>">
    <div style="font-size:3rem">📭</div>
    <p class="mt-2">لا توجد طلبات إلكترونية مقبولة بعد</p>
</div>

<?php if (!empty($rows)): ?>
    <div class="table-responsive" id="orders-table-wrapper">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>العميل</th>
                    <th>معرف الطلب</th>
                    <th>الإجمالي</th>
                    <th>الحالة</th>
                    <th>وقت القبول</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody id="accepted-orders-tbody">
                <?php foreach ($rows as $row):
                    $sid    = $row['supabase_order_id'] ?? null;
                    $status = $row['online_order_status'] ?? 'preparing';
                    $statusBadge = match($status) {
                        'delivering' => '<span class="badge bg-warning text-dark">🚚 جاري التوصيل</span>',
                        'completed'  => '<span class="badge bg-success">✅ تم التسليم</span>',
                        default      => '<span class="badge bg-info text-dark">🔧 جاري التحضير</span>',
                    };
                ?>
                    <tr id="order-row-<?= e($sid ?? $row['id']) ?>">
                        <td class="fw-bold"><?= e($row['invoice_no']) ?></td>
                        <td><?= e($row['online_customer_name'] ?? '—') ?></td>
                        <td>
                            <span class="badge bg-secondary" style="font-size:11px">
                                <?= $sid ? '#' . strtoupper(substr($sid, 0, 8)) : '—' ?>
                            </span>
                        </td>
                        <td class="fw-bold text-success"><?= number_format($row['grand_total'], 2) ?> ج.م</td>
                        <td><?= $statusBadge ?></td>
                        <td>
                            <?php
                            $timeStr = date('d/m/Y h:i A', strtotime($row['invoice_date']));
                            echo str_replace(['AM', 'PM'], ['ص', 'م'], $timeStr);
                            ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap btn-actions-container">
                                <a href="<?= url('/sales/' . $row['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    📄 فاتورة
                                </a>
                                <?php if ($sid && $status === 'preparing'): ?>
                                    <form action="<?= url("/online-orders/{$sid}/ship") ?>" method="POST" class="d-inline"
                                          onsubmit="handleOrderAction('<?= e($sid) ?>', 'ship', event)">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-info fw-bold btn-ship">🚚 تم الشحن</button>
                                    </form>
                                    <form action="<?= url("/online-orders/{$sid}/complete") ?>" method="POST" class="d-inline"
                                          onsubmit="handleOrderAction('<?= e($sid) ?>', 'complete', event)">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-success fw-bold btn-complete">✅ تم التسليم</button>
                                    </form>
                                <?php elseif ($sid && $status === 'delivering'): ?>
                                    <form action="<?= url("/online-orders/{$sid}/complete") ?>" method="POST" class="d-inline"
                                          onsubmit="handleOrderAction('<?= e($sid) ?>', 'complete', event)">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-success fw-bold btn-complete">✅ تم التسليم</button>
                                    </form>
                                <?php elseif ($status === 'completed'): ?>
                                    <span class="text-success fw-bold small">✔ مكتمل</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
async function handleOrderAction(orderId, action, event) {
    event.preventDefault();

    const row = document.getElementById(`order-row-${orderId}`);
    if (!row) return;

    const btnShip = row.querySelector('.btn-ship');
    const btnComplete = row.querySelector('.btn-complete');
    
    if (btnShip) btnShip.disabled = true;
    if (btnComplete) btnComplete.disabled = true;
    
    const originalText = action === 'ship' ? '🚚 تم الشحن' : '✅ تم التسليم';
    const loadingText = action === 'ship' ? '⏳ جاري الشحن...' : '⏳ جاري التسليم...';
    
    if (action === 'ship' && btnShip) btnShip.textContent = loadingText;
    if (action === 'complete' && btnComplete) btnComplete.textContent = loadingText;

    try {
        // جلب csrf_token بأمان من أول form في الـ row
        const anyForm  = row.querySelector('form');
        const csrfInput = anyForm ? anyForm.querySelector('input[name="_token"]') : null;
        const csrfToken = csrfInput ? csrfInput.value : '';

        if (!csrfToken) {
            throw new Error('CSRF token not found – please reload the page');
        }

        // بناء الـ URL مباشرة
        const actionUrl = `<?= url('/online-orders') ?>/${orderId}/${action}?ajax=1`;

        const response = await fetch(actionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                '_token': csrfToken
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');

            if (action === 'ship') {
                // غيّر badge الحالة
                const statusCell = row.querySelector('td:nth-child(5)');
                if (statusCell) statusCell.innerHTML = '<span class="badge bg-warning text-dark">🚚 جاري التوصيل</span>';

                // شيل زرار الشحن، خلي زرار التسليم فقط
                const shipForm = btnShip ? btnShip.closest('form') : null;
                if (shipForm) shipForm.remove();
                if (btnComplete) {
                    btnComplete.disabled = false;
                    btnComplete.textContent = '✅ تم التسليم';
                }
            } else if (action === 'complete') {
                // غيّر badge الحالة
                const statusCell = row.querySelector('td:nth-child(5)');
                if (statusCell) statusCell.innerHTML = '<span class="badge bg-success">✅ تم التسليم</span>';

                row.style.transition = 'all 0.6s ease';
                row.style.opacity = '0';
                row.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    row.remove();
                    const tbody = document.getElementById('accepted-orders-tbody');
                    if (tbody && tbody.children.length === 0) {
                        const wrapper = document.getElementById('orders-table-wrapper');
                        if (wrapper) wrapper.remove();
                        document.getElementById('no-orders-alert').classList.remove('d-none');
                    }
                }, 600);
            }
        } else {
            showToast(data.message || 'حدث خطأ أثناء تنفيذ العملية', 'danger');
            resetButtonStates();
        }
    } catch (err) {
        showToast('فشل الاتصال بالخادم: ' + err.message, 'danger');
        resetButtonStates();
    }

    function resetButtonStates() {
        if (btnShip) {
            btnShip.disabled = false;
            btnShip.textContent = '🚚 تم الشحن';
        }
        if (btnComplete) {
            btnComplete.disabled = false;
            btnComplete.textContent = '✅ تم التسليم';
        }
    }
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container-custom') || (() => {
        const div = document.createElement('div');
        div.id = 'toast-container-custom';
        div.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px';
        document.body.appendChild(div);
        return div;
    })();

    const toast = document.createElement('div');
    const bg = type === 'success' ? '#22c55e' : '#ef4444';
    toast.style.cssText = `background:${bg};color:#fff;padding:12px 20px;border-radius:12px;font-weight:bold;font-size:13px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.15);opacity:0;transform:translateY(20px);transition:all 0.3s ease;direction:rtl`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 50);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
</script>
