<?php $title = 'فواتير الشراء'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">فواتير الشراء</h5>
    <a class="btn btn-success" href="<?= url('/purchases/create') ?>">فاتورة شراء جديدة</a>
</div>

<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
    <thead><tr><th>#</th><th>رقم الفاتورة</th><th>التاريخ</th><th>المورد</th><th>المستخدم</th><th>الإجمالي</th><th>المدفوع</th><th>المتبقي</th><th>الحالة</th><th>إجراءات</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= (int) $row['id'] ?></td>
            <td><?= e($row['invoice_no']) ?></td>
            <td><?= e($row['invoice_date']) ?></td>
            <td><?= e($row['supplier_name']) ?></td>
            <td><?= e($row['user_name']) ?></td>
            <td><?= money($row['grand_total']) ?></td>
            <td><?= money($row['paid_total']) ?></td>
            <td><?= money($row['due_total']) ?></td>
            <td><?= e($row['status']) ?></td>
            <td>
                <a class="btn btn-sm btn-primary" href="<?= url('/purchases/' . (int) $row['id']) ?>">عرض</a>
                <?php if ($row['status'] === 'draft'): ?>
                    <a class="btn btn-sm btn-warning" href="<?= url('/purchases/' . (int) $row['id'] . '/edit') ?>">تعديل</a>
                <?php endif; ?>
                <a class="btn btn-sm btn-light" target="_blank" href="<?= url('/purchases/' . (int) $row['id'] . '/print') ?>">طباعة</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
