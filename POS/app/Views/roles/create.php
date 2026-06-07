<?php $title = 'إضافة دور'; ?>
<h5 class="mb-3">إضافة دور وصلاحيات</h5>
<form method="post" action="<?= url('/roles') ?>">
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label">اسم الدور</label><input class="form-control" name="name" required></div>
        <div class="col-md-4"><label class="form-label">الوصف</label><input class="form-control" name="description"></div>
        <div class="col-md-4"><label class="form-label">الحالة</label><select class="form-select" name="is_active"><option value="1">نشط</option><option value="0">معطل</option></select></div>
    </div>

    <div class="card p-3 mb-3">
        <h6 class="mb-3">الصلاحيات</h6>
        <div class="row g-2">
            <?php foreach($permissions as $p): ?>
                <div class="col-md-4"><label><input type="checkbox" name="permissions[]" value="<?= e($p['code']) ?>"> <?= e($p['name']) ?></label></div>
            <?php endforeach; ?>
        </div>
    </div>

    <button class="btn btn-success">حفظ الدور</button>
</form>
