<?php $title = 'إدارة عملاء المتجر'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">👥 إدارة عملاء المتجر</h5>
    <?php if (!$online): ?>
        <span class="badge bg-danger">🔴 غير متصل بالمتجر</span>
    <?php else: ?>
        <span class="badge bg-success">🟢 متصل بالمتجر</span>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <p class="small text-muted mb-0">
            شاشة التحكم في عملاء المتجر الإلكتروني المسجلين. يمكنك مراجعة عناوينهم وتعديل أرصدة النقاط الذهبية (الولاء) الممنوحة لهم أو حذف الحسابات غير المرغوب فيها.
        </p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>العميل</th>
                        <th>الهاتف</th>
                        <th>العنوان</th>
                        <th class="text-center">النقاط الذهبية</th>
                        <th>تاريخ التسجيل</th>
                        <th class="text-center">العمليات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <?php if (!$online): ?>
                                    تعذر جلب العملاء بسبب انقطاع الاتصال بـ Supabase
                                <?php else: ?>
                                    لا يوجد عملاء مسجلين بالمتجر حالياً.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold d-flex align-items-center gap-2 flex-wrap">
                                        <?= e($user['full_name'] ?: 'عميل غير معروف') ?>
                                        <?php if (isset($user['provider']) && $user['provider'] === 'google'): ?>
                                            <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1" style="font-size:10px;" title="حساب مرتبط بجوجل">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="margin-top:-1px">
                                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                                </svg>
                                                جوجل
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border d-inline-flex align-items-center" style="font-size:10px;" title="حساب مرتبط برقم الهاتف">
                                                📱 هاتف
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($user['email'])): ?>
                                        <div class="small text-muted font-monospace" style="font-size: 11px;"><?= e($user['email']) ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted text-monospace" style="font-size: 10px;"><?= e($user['id']) ?></small>
                                </td>
                                <td class="text-monospace"><?= e($user['phone'] ?: 'غير متوفر') ?></td>
                                <td>
                                    <span class="small" title="<?= e($user['address']) ?>">
                                        <?= e($user['address'] ? substr($user['address'], 0, 50) . (strlen($user['address']) > 50 ? '...' : '') : 'لا يوجد عنوان مسجل') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark px-3 py-2 fw-black">
                                        🪙 <?= (int)$user['points'] ?> نقطة
                                    </span>
                                </td>
                                <td class="small">
                                    <?= date('Y-m-d H:i', strtotime($user['created_at'])) ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            onclick="showPointsModal('<?= e($user['id']) ?>', '<?= e($user['full_name']) ?>', <?= (int)$user['points'] ?>)"
                                            title="تحديث النقاط">
                                        🪙 تعديل النقاط
                                    </button>
                                    <form action="<?= url('/online-store/users/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('⚠️ هل أنت متأكد تماماً من حذف هذا العميل من المتجر؟ لا يمكن التراجع عن هذا الإجراء!')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= e($user['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف العميل">
                                            🗑️ حذف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal تعديل النقاط -->
<div class="modal fade" id="pointsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= url('/online-store/users/update-points') ?>" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" id="modalUserId">
            <div class="modal-header">
                <h6 class="modal-title">🪙 تعديل نقاط العميل: <span id="modalUserName" class="text-primary fw-bold"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">رصيد النقاط الذهبية الحالي</label>
                    <input type="number" name="points" id="modalUserPoints" class="form-control text-center fs-4 fw-black" min="0" required>
                    <div class="form-text mt-2 text-center text-muted">
                        النقاط تمكن العميل من الحصول على كوبونات خصم خاصة ومميزات شراء من المتجر.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary btn-sm">حفظ وتحديث النقاط</button>
            </div>
        </form>
    </div>
</div>

<script>
function showPointsModal(id, name, points) {
    document.getElementById('modalUserId').value = id;
    document.getElementById('modalUserName').textContent = name;
    document.getElementById('modalUserPoints').value = points;
    
    const modal = new bootstrap.Modal(document.getElementById('pointsModal'));
    modal.show();
}
</script>
