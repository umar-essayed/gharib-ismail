<?php $title = 'تعديل مورد'; ?>
<h5 class="mb-3">تعديل مورد: <?= e($row['name']) ?></h5>
<form method="post" action="<?= url('/suppliers/' . (int)$row['id'] . '/update') ?>" class="row g-3">
    <?= csrf_field() ?>
    <div class="col-md-4"><label class="form-label">الاسم</label><input class="form-control" name="name" value="<?= e($row['name']) ?>" required></div>
    <div class="col-md-4"><label class="form-label">الهاتف</label><input class="form-control" name="phone" value="<?= e($row['phone']) ?>"></div>
    <div class="col-md-4"><label class="form-label">البريد</label><input class="form-control" name="email" value="<?= e($row['email']) ?>"></div>
    <div class="col-md-6"><label class="form-label">العنوان</label><input class="form-control" name="address" value="<?= e($row['address']) ?>"></div>
    <div class="col-md-3"><label class="form-label">الحالة</label><select class="form-select" name="is_active"><option value="1" <?= $row['is_active'] ? 'selected' : '' ?>>نشط</option><option value="0" <?= !$row['is_active'] ? 'selected' : '' ?>>معطل</option></select></div>
    <div class="col-12"><button class="btn btn-success">حفظ</button> <a class="btn btn-light" href="<?= url('/suppliers') ?>">إلغاء</a></div>
</form>
