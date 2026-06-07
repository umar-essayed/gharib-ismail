<?php $title = 'تعديل دور'; ?>
<h5 class="mb-3">تعديل دور: <?= e($row['name']) ?></h5>
<form method="post" action="<?= url('/roles/' . (int)$row['id'] . '/update') ?>">
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label">اسم الدور</label><input class="form-control" name="name" value="<?= e($row['name']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">الوصف</label><input class="form-control" name="description" value="<?= e($row['description']) ?>"></div>
        <div class="col-md-4"><label class="form-label">الحالة</label><select class="form-select" name="is_active"><option value="1" <?= $row['is_active']?'selected':'' ?>>نشط</option><option value="0" <?= !$row['is_active']?'selected':'' ?>>معطل</option></select></div>
    </div>

    <div class="card p-3 mb-3">
        <h6 class="mb-3">الصلاحيات</h6>
        <div class="row g-2">
            <?php foreach($permissions as $p): ?>
                <div class="col-md-4"><label><input type="checkbox" name="permissions[]" value="<?= e($p['code']) ?>" <?= in_array($p['code'], $row['permissions'], true)?'checked':'' ?>> <?= e($p['name']) ?></label></div>
            <?php endforeach; ?>
        </div>
    </div>

    <button class="btn btn-success">حفظ التعديلات</button>
</form>
