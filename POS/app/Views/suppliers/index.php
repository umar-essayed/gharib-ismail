<?php $title = 'الموردون'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h5 class="mb-0">الموردون</h5>
    <div class="d-flex gap-2">
        <form class="d-flex" method="get" action="<?= url('/suppliers') ?>">
            <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="بحث">
            <button class="btn btn-primary ms-2">بحث</button>
        </form>
        <a class="btn btn-success" href="<?= url('/suppliers/create') ?>">إضافة مورد</a>
    </div>
</div>

<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
    <thead><tr><th>#</th><th>الاسم</th><th>الهاتف</th><th>الرصيد</th><th>الحالة</th><th>إجراءات</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><?= (int) $row['id'] ?></td>
        <td><?= e($row['name']) ?></td>
        <td><?= e($row['phone']) ?></td>
        <td><?= money($row['current_balance']) ?></td>
        <td><?= $row['is_active'] ? 'نشط' : 'معطل' ?></td>
        <td>
            <a class="btn btn-sm btn-info" href="<?= url('/suppliers/' . (int) $row['id'] . '/statement') ?>">كشف حساب</a>
            <a class="btn btn-sm btn-success" href="<?= url('/suppliers/' . (int) $row['id'] . '/payment') ?>">سداد</a>
            <a class="btn btn-sm btn-warning" href="<?= url('/suppliers/' . (int) $row['id'] . '/edit') ?>">تعديل</a>
            <form method="post" action="<?= url('/suppliers/' . (int) $row['id'] . '/delete') ?>" class="d-inline" data-confirm="تأكيد حذف المورد؟">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-danger">حذف</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
