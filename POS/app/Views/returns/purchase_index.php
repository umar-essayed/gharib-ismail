<?php $title = 'مرتجعات الشراء'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">مرتجعات الشراء</h5>
    <a class="btn btn-success" href="<?= url('/returns/purchases/create') ?>">إنشاء مرتجع شراء</a>
</div>
<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
    <thead><tr><th>#</th><th>رقم المرتجع</th><th>الفاتورة الأصلية</th><th>المورد</th><th>التاريخ</th><th>إجمالي</th><th>إجراءات</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= e($row['return_no']) ?></td>
            <td><?= e($row['invoice_no']) ?></td>
            <td><?= e($row['supplier_name']) ?></td>
            <td><?= e($row['return_date']) ?></td>
            <td><?= money($row['grand_total']) ?></td>
            <td>
                <a class="btn btn-sm btn-primary" href="<?= url('/returns/purchases/' . (int)$row['id']) ?>">عرض</a>
                <a class="btn btn-sm btn-light" href="<?= url('/returns/purchases/' . (int)$row['id'] . '/print') ?>" target="_blank">طباعة</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
