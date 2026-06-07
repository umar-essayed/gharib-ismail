<?php $title = 'تعديل منتج'; ?>
<h5 class="mb-3">تعديل المنتج: <?= e($row['name']) ?></h5>
<form method="post" action="<?= url('/products/' . (int)$row['id'] . '/update') ?>" enctype="multipart/form-data" class="row g-3" id="productForm">
    <?= csrf_field() ?>
    <div class="col-md-4"><label class="form-label">اسم المنتج</label><input class="form-control" name="name" value="<?= e($row['name']) ?>" required></div>
    <div class="col-md-4"><label class="form-label">SKU</label><input class="form-control" name="sku" value="<?= e($row['sku']) ?>"></div>
    <div class="col-md-4"><label class="form-label">الكود الداخلي</label><input class="form-control" name="internal_code" value="<?= e($row['internal_code']) ?>"></div>
    <div class="col-md-4"><label class="form-label">الباركود</label><input class="form-control" name="barcode" value="<?= e($row['barcode']) ?>"></div>
    <div class="col-md-4"><label class="form-label">التصنيف</label><select class="form-select" name="category_id"><option value="">-</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$row['category_id']===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">الوحدة</label><select class="form-select" name="unit_id"><option value="">-</option><?php foreach($units as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (int)$row['unit_id']===(int)$u['id']?'selected':'' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div>

    <div class="col-md-3"><label class="form-label">سعر الشراء</label><input class="form-control" type="number" step="0.001" name="purchase_price" value="<?= e($row['purchase_price']) ?>" required></div>
    <div class="col-md-3"><label class="form-label">سعر البيع</label><input class="form-control" type="number" step="0.001" name="sale_price" value="<?= e($row['sale_price']) ?>" required></div>
    <div class="col-md-3"><label class="form-label">سعر الجملة</label><input class="form-control" type="number" step="0.001" name="wholesale_price" value="<?= e($row['wholesale_price']) ?>"></div>
    <div class="col-md-3"><label class="form-label">حد أدنى</label><input class="form-control" type="number" step="0.001" name="min_stock" value="<?= e($row['min_stock']) ?>"></div>

    <div class="col-md-3"><label class="form-label">نوع البيع</label><select class="form-select" name="sell_type" id="sellType"><option value="piece" <?= $row['sell_type']==='piece'?'selected':'' ?>>بالقطعة</option><option value="weight" <?= $row['sell_type']==='weight'?'selected':'' ?>>بالوزن</option></select></div>
    <div class="col-md-3"><label class="form-label">نوع العبوة</label><select class="form-select" name="package_type" id="packageType"></select></div>
    <div class="col-md-3"><label class="form-label" id="packageSizeLabel">عدد القطع داخل العلبة</label><input class="form-control" type="number" step="0.001" min="1" name="package_size" id="packageSize" value="<?= e($row['package_size'] ?? 1) ?>"></div>
    <div class="col-md-3"><label class="form-label">تتبع المخزون</label><select class="form-select" name="track_stock"><option value="1" <?= $row['track_stock']?'selected':'' ?>>نعم</option><option value="0" <?= !$row['track_stock']?'selected':'' ?>>لا</option></select></div>
    <div class="col-md-3"><label class="form-label">تفعيل باركود الميزان</label><select class="form-select" name="allow_scale_barcode" id="allowScaleBarcode"><option value="0" <?= (int)($row['allow_scale_barcode'] ?? 0)===0?'selected':'' ?>>لا</option><option value="1" <?= (int)($row['allow_scale_barcode'] ?? 0)===1?'selected':'' ?>>نعم</option></select></div>
    <div class="col-md-3"><label class="form-label">كود الصنف داخل الميزان</label><input class="form-control" name="scale_code" id="scaleCode" value="<?= e($row['scale_code'] ?? '') ?>" placeholder="مثال: 11111"></div>
    <div class="col-md-3"><label class="form-label">وحدة الوزن</label><select class="form-select" name="weight_unit" id="weightUnit"><option value="kg" <?= ($row['weight_unit'] ?? 'kg')==='kg'?'selected':'' ?>>كجم</option><option value="g" <?= ($row['weight_unit'] ?? '')==='g'?'selected':'' ?>>جم</option></select></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-text m-0">ضع هنا نفس كود الصنف المسجل على الميزان وليس الباركود الكامل المتغير.</div></div>

    <div class="col-md-3"><label class="form-label">الحالة</label><select class="form-select" name="is_active"><option value="1" <?= $row['is_active']?'selected':'' ?>>نشط</option><option value="0" <?= !$row['is_active']?'selected':'' ?>>معطّل</option></select></div>
    <div class="col-md-9"><label class="form-label">صورة جديدة</label><input class="form-control" type="file" name="image" accept="image/*"></div>

    <div class="col-12"><button class="btn btn-success">حفظ</button> <a class="btn btn-light" href="<?= url('/products') ?>">إلغاء</a></div>
</form>

<script>
(function(){
    const sellType = document.getElementById('sellType');
    const packageType = document.getElementById('packageType');
    const packageSizeLabel = document.getElementById('packageSizeLabel');
    const packageSize = document.getElementById('packageSize');
    const oldPackageType = <?= json_encode($row['package_type'] ?? 'piece') ?>;

    function rebuildPackageOptions(){
        const isWeight = sellType.value === 'weight';
        if (isWeight) {
            document.getElementById('allowScaleBarcode').value = '1';
        }
        if (isWeight) {
            packageType.innerHTML = '<option value="kg">كيلو</option><option value="sack">شيكارة</option>';
            packageType.value = (oldPackageType === 'kg' || oldPackageType === 'sack') ? oldPackageType : 'kg';
        } else {
            packageType.innerHTML = '<option value="piece">قطعة</option><option value="box">علبة</option>';
            packageType.value = (oldPackageType === 'piece' || oldPackageType === 'box') ? oldPackageType : 'piece';
        }
        updatePackageLabel();
    }

    function updatePackageLabel(){
        const t = packageType.value;
        if (t === 'box') {
            packageSizeLabel.textContent = 'عدد القطع داخل العلبة';
            packageSize.readOnly = false;
        } else if (t === 'sack') {
            packageSizeLabel.textContent = 'عدد الكيلو داخل الشيكارة';
            packageSize.readOnly = false;
        } else {
            packageSizeLabel.textContent = 'معامل العبوة';
            packageSize.value = '1';
            packageSize.readOnly = true;
        }
    }

    sellType.addEventListener('change', rebuildPackageOptions);
    packageType.addEventListener('change', updatePackageLabel);
    rebuildPackageOptions();
})();
</script>
