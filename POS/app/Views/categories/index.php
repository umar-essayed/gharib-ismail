<?php $title = 'التصنيفات'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">إدارة التصنيفات</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">إضافة تصنيف</button>
</div>

<div class="table-wrap table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr><th>#</th><th>الاسم</th><th>الوصف</th><th>الحالة</th><th>إجراءات</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= (int) $row['id'] ?></td>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['description']) ?></td>
                <td><?= $row['is_active'] ? 'نشط' : 'معطّل' ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#edit<?= (int) $row['id'] ?>">تعديل</button>
                    <form method="post" action="<?= url('/categories/' . (int) $row['id'] . '/delete') ?>" class="d-inline" data-confirm="تأكيد حذف التصنيف؟">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-danger">حذف</button>
                    </form>
                </td>
            </tr>

            <div class="modal fade" id="edit<?= (int) $row['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
                <form method="post" action="<?= url('/categories/' . (int) $row['id'] . '/update') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header"><h6 class="modal-title">تعديل التصنيف</h6><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
                    <div class="modal-body">
                        <div class="mb-2"><label class="form-label">الاسم</label><input class="form-control" name="name" value="<?= e($row['name']) ?>" required></div>
                        <div class="mb-2"><label class="form-label">الوصف</label><input class="form-control" name="description" value="<?= e($row['description']) ?>"></div>
                        <div><label class="form-label">الحالة</label>
                            <select name="is_active" class="form-select"><option value="1" <?= $row['is_active'] ? 'selected' : '' ?>>نشط</option><option value="0" <?= !$row['is_active'] ? 'selected' : '' ?>>معطّل</option></select>
                        </div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-success">حفظ</button></div>
                </form>
            </div></div></div>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= url('/categories') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h6 class="modal-title">إضافة تصنيف</h6><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
        <div class="modal-body">
            <div class="mb-2"><label class="form-label">الاسم</label><input class="form-control" name="name" required></div>
            <div class="mb-2"><label class="form-label">الوصف</label><input class="form-control" name="description"></div>
            <div><label class="form-label">الحالة</label>
                <select name="is_active" class="form-select"><option value="1">نشط</option><option value="0">معطّل</option></select>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-success">حفظ</button></div>
    </form>
</div></div></div>
