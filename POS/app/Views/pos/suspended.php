<?php $title = 'الفواتير المعلقة'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">الفواتير المعلقة</h5>
    <a class="btn btn-primary" href="<?= url('/pos') ?>">العودة لـ POS</a>
</div>

<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
    <thead><tr><th>#</th><th>رقم التعليق</th><th>العميل</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= (int) $row['id'] ?></td>
            <td><?= e($row['hold_no']) ?></td>
            <td><?= e($row['customer_name']) ?></td>
            <td><?= e($row['created_at']) ?></td>
            <td>
                <a class="btn btn-sm btn-success" href="<?= url('/pos/suspended/' . (int) $row['id'] . '/resume') ?>">استدعاء</a>
                <form method="post" action="<?= url('/pos/suspended/' . (int) $row['id'] . '/delete') ?>" class="d-inline" data-confirm="حذف الفاتورة المعلقة؟">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-danger">حذف</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
