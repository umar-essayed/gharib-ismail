<?php $title = 'العملاء'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h5 class="mb-0">العملاء</h5>
    <div class="d-flex gap-2">
        <form class="d-flex" method="get" action="<?= url('/customers') ?>">
            <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="بحث">
            <button class="btn btn-primary ms-2">بحث</button>
        </form>
        <a class="btn btn-success" href="<?= url('/customers/create') ?>">إضافة عميل</a>
    </div>
</div>

<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
    <thead><tr><th>#</th><th>الاسم</th><th>الهاتف</th><th>الرصيد الحالي</th><th>الحد الائتماني</th><th>الحالة</th><th>إجراءات</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><?= (int) $row['id'] ?></td>
        <td><?= e($row['name']) ?> <?= $row['is_cash_customer'] ? '<span class="badge bg-secondary">نقدي</span>' : '' ?></td>
        <td><?= e($row['phone']) ?></td>
        <td><?= money($row['current_balance']) ?></td>
        <td><?= money($row['credit_limit']) ?></td>
        <td><?= $row['is_active'] ? 'نشط' : 'معطل' ?></td>
        <td>
            <a class="btn btn-sm btn-info" href="<?= url('/customers/' . (int) $row['id'] . '/statement') ?>">كشف حساب</a>
            <a class="btn btn-sm btn-success" href="<?= url('/customers/' . (int) $row['id'] . '/receipt') ?>">سند قبض</a>
            <a class="btn btn-sm btn-warning" href="<?= url('/customers/' . (int) $row['id'] . '/edit') ?>">تعديل</a>
            <form method="post" action="<?= url('/customers/' . (int) $row['id'] . '/delete') ?>" class="d-inline" data-confirm="تأكيد حذف العميل؟">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-danger">حذف</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
