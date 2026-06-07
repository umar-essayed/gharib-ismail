<?php $title = 'إضافة عميل'; ?>
<h5 class="mb-3">إضافة عميل</h5>
<form method="post" action="<?= url('/customers') ?>" class="row g-3">
    <?= csrf_field() ?>
    <div class="col-md-4"><label class="form-label">الاسم</label><input class="form-control" name="name" required></div>
    <div class="col-md-4"><label class="form-label">الهاتف</label><input class="form-control" name="phone"></div>
    <div class="col-md-4"><label class="form-label">البريد</label><input class="form-control" name="email"></div>
    <div class="col-md-6"><label class="form-label">العنوان</label><input class="form-control" name="address"></div>
    <div class="col-md-3"><label class="form-label">رصيد افتتاحي</label><input class="form-control" type="number" step="0.001" name="opening_balance" value="0"></div>
    <div class="col-md-3"><label class="form-label">حد ائتماني</label><input class="form-control" type="number" step="0.001" name="credit_limit" value="0"></div>
    <div class="col-md-3"><label class="form-label">عميل نقدي؟</label><select class="form-select" name="is_cash_customer"><option value="0">لا</option><option value="1">نعم</option></select></div>
    <div class="col-md-3"><label class="form-label">الحالة</label><select class="form-select" name="is_active"><option value="1">نشط</option><option value="0">معطل</option></select></div>
    <div class="col-12"><button class="btn btn-success">حفظ</button> <a class="btn btn-light" href="<?= url('/customers') ?>">إلغاء</a></div>
</form>
