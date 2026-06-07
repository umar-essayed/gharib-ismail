<?php $title = 'وحدات القياس'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">وحدات القياس</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUnit">إضافة وحدة</button>
</div>

<div class="table-wrap table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr><th>#</th><th>الاسم</th><th>الاختصار</th><th>بالوزن</th><th>الحالة</th><th>إجراءات</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= (int) $row['id'] ?></td>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['short_name']) ?></td>
                <td><?= $row['is_weight'] ? 'نعم' : 'لا' ?></td>
                <td><?= $row['is_active'] ? 'نشط' : 'معطّل' ?></td>
                <td><button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editU<?= (int) $row['id'] ?>">تعديل</button></td>
            </tr>

            <div class="modal fade" id="editU<?= (int) $row['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
                <form method="post" action="<?= url('/units/' . (int) $row['id'] . '/update') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header"><h6 class="modal-title">تعديل وحدة</h6><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
                    <div class="modal-body">
                        <div class="mb-2"><label class="form-label">الاسم</label><input class="form-control" name="name" value="<?= e($row['name']) ?>" required></div>
                        <div class="mb-2"><label class="form-label">الاختصار</label><input class="form-control" name="short_name" value="<?= e($row['short_name']) ?>" required></div>
                        <div class="mb-2"><label class="form-label">بالوزن</label><select class="form-select" name="is_weight"><option value="0" <?= !$row['is_weight'] ? 'selected' : '' ?>>لا</option><option value="1" <?= $row['is_weight'] ? 'selected' : '' ?>>نعم</option></select></div>
                        <div><label class="form-label">الحالة</label><select class="form-select" name="is_active"><option value="1" <?= $row['is_active'] ? 'selected' : '' ?>>نشط</option><option value="0" <?= !$row['is_active'] ? 'selected' : '' ?>>معطّل</option></select></div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-success">حفظ</button></div>
                </form>
            </div></div></div>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="createUnit" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= url('/units') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h6 class="modal-title">إضافة وحدة</h6><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
        <div class="modal-body">
            <div class="mb-2"><label class="form-label">الاسم</label><input class="form-control" name="name" required></div>
            <div class="mb-2"><label class="form-label">الاختصار</label><input class="form-control" name="short_name" required></div>
            <div class="mb-2"><label class="form-label">بالوزن</label><select class="form-select" name="is_weight"><option value="0">لا</option><option value="1">نعم</option></select></div>
            <div><label class="form-label">الحالة</label><select class="form-select" name="is_active"><option value="1">نشط</option><option value="0">معطّل</option></select></div>
        </div>
        <div class="modal-footer"><button class="btn btn-success">حفظ</button></div>
    </form>
</div></div></div>
