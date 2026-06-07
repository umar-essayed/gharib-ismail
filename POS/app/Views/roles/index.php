<?php $title = 'الأدوار والصلاحيات'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">الأدوار</h5>
    <a class="btn btn-success" href="<?= url('/roles/create') ?>">إضافة دور</a>
</div>

<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
    <thead><tr><th>#</th><th>الاسم</th><th>الوصف</th><th>نشط</th><th>إجراءات</th></tr></thead>
    <tbody>
    <?php foreach($rows as $row): ?>
        <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= e($row['name']) ?></td>
            <td><?= e($row['description']) ?></td>
            <td><?= $row['is_active'] ? 'نعم' : 'لا' ?></td>
            <td><a class="btn btn-sm btn-warning" href="<?= url('/roles/' . (int)$row['id'] . '/edit') ?>">تعديل</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
