<?php $title = 'الشيفتات'; ?>
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card p-3">
            <h6 class="mb-3">فتح شيفت</h6>
            <?php if ($openShift): ?>
                <div class="alert alert-info">لديك شيفت مفتوح: <strong><?= e($openShift['shift_no']) ?></strong></div>
                <form method="post" action="<?= url('/shifts/' . (int)$openShift['id'] . '/close') ?>" data-confirm="تأكيد إقفال الشيفت؟">
                    <?= csrf_field() ?>
                    <div class="mb-2"><label class="form-label">الرصيد الفعلي عند الإقفال</label><input type="number" step="0.001" class="form-control" name="actual_balance" required></div>
                    <div class="mb-2"><label class="form-label">ملاحظة</label><input class="form-control" name="note"></div>
                    <button class="btn btn-danger">إقفال الشيفت</button>
                    <a class="btn btn-light" href="<?= url('/shifts/' . (int)$openShift['id'] . '/report') ?>">تقرير الشيفت</a>
                </form>
            <?php else: ?>
                <form method="post" action="<?= url('/shifts/open') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2"><label class="form-label">رصيد افتتاحي</label><input type="number" step="0.001" class="form-control" name="opening_balance" value="0" required></div>
                    <div class="mb-2"><label class="form-label">ملاحظة</label><input class="form-control" name="note"></div>
                    <button class="btn btn-success">فتح شيفت</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="table-wrap table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>#</th><th>رقم الشيفت</th><th>المستخدم</th><th>مفتوح</th><th>مغلق</th><th>متوقع</th><th>فعلي</th><th>فرق</th><th>حالة</th><th>تقرير</th></tr></thead>
                <tbody>
                <?php foreach($rows as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= e($row['shift_no']) ?></td>
                        <td><?= e($row['user_name']) ?></td>
                        <td><?= e($row['opened_at']) ?></td>
                        <td><?= e($row['closed_at']) ?></td>
                        <td><?= money($row['expected_balance']) ?></td>
                        <td><?= money($row['actual_balance']) ?></td>
                        <td><?= money($row['difference']) ?></td>
                        <td><?= e($row['status']) ?></td>
                        <td><a class="btn btn-sm btn-light" href="<?= url('/shifts/' . (int)$row['id'] . '/report') ?>">عرض</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
