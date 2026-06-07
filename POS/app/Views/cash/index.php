<?php $title = 'حركات الصندوق'; ?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card p-3">
            <h6 class="mb-3">إضافة حركة صندوق</h6>
            <form method="post" action="<?= url('/cash') ?>">
                <?= csrf_field() ?>
                <div class="mb-2"><label class="form-label">النوع</label><select class="form-select" name="movement_type"><option value="deposit">إيداع</option><option value="withdraw">سحب</option><option value="expense">مصروف</option><option value="adjustment">تسوية</option></select></div>
                <div class="mb-2"><label class="form-label">المبلغ</label><input type="number" step="0.001" class="form-control" name="amount" required></div>
                <div class="mb-2"><label class="form-label">ملاحظة</label><input class="form-control" name="note"></div>
                <button class="btn btn-success">حفظ الحركة</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="table-wrap table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>التاريخ</th><th>النوع</th><th>الاتجاه</th><th>المبلغ</th><th>الشيفت</th><th>المستخدم</th><th>ملاحظة</th></tr></thead>
                <tbody>
                <?php foreach($rows as $r): ?>
                    <tr>
                        <td><?= e($r['created_at']) ?></td>
                        <td><?= e($r['movement_type']) ?></td>
                        <td><?= e($r['direction']) ?></td>
                        <td><?= money($r['amount']) ?></td>
                        <td><?= e($r['shift_no']) ?></td>
                        <td><?= e($r['user_name']) ?></td>
                        <td><?= e($r['note']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
