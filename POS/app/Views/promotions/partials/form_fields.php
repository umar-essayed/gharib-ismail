<?php
$editRow = $row ?? null;
$selectedProductId = (int) ($editRow['product_id'] ?? 0);
$selectedType = (string) ($editRow['discount_type'] ?? 'percent');
?>

<div class="mb-2">
    <label class="form-label">المنتج</label>
    <select class="form-select" name="product_id" required>
        <option value="">اختر المنتج</option>
        <?php foreach ($products as $product): ?>
            <option value="<?= (int) $product['id'] ?>" <?= $selectedProductId === (int) $product['id'] ? 'selected' : '' ?>>
                <?= e($product['name']) ?> (<?= e($product['barcode'] ?: '-') ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-2">
    <label class="form-label">اسم العرض</label>
    <input class="form-control" name="name" value="<?= e($editRow['name'] ?? '') ?>" required placeholder="مثال: خصم نهاية الأسبوع">
</div>

<div class="row g-2 mb-2">
    <div class="col-md-5">
        <label class="form-label">نوع الخصم</label>
        <select class="form-select" name="discount_type" required>
            <option value="percent" <?= $selectedType === 'percent' ? 'selected' : '' ?>>نسبة مئوية %</option>
            <option value="fixed" <?= $selectedType === 'fixed' ? 'selected' : '' ?>>قيمة خصم ثابتة</option>
            <option value="price" <?= $selectedType === 'price' ? 'selected' : '' ?>>سعر عرض نهائي</option>
        </select>
    </div>
    <div class="col-md-7">
        <label class="form-label">القيمة</label>
        <input type="number" min="0" step="0.001" class="form-control" name="discount_value" value="<?= e($editRow['discount_value'] ?? '0') ?>" required>
    </div>
</div>

<div class="row g-2 mb-2">
    <div class="col-md-6">
        <label class="form-label">من تاريخ</label>
        <input type="date" class="form-control" name="start_date" value="<?= e($editRow['start_date'] ?? date('Y-m-d')) ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">إلى تاريخ</label>
        <input type="date" class="form-control" name="end_date" value="<?= e($editRow['end_date'] ?? date('Y-m-d')) ?>" required>
    </div>
</div>

<div class="mb-2">
    <label class="form-label">ملاحظات</label>
    <input class="form-control" name="note" value="<?= e($editRow['note'] ?? '') ?>">
</div>

<div>
    <label class="form-label">الحالة</label>
    <select class="form-select" name="is_active">
        <option value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>نشط</option>
        <option value="0" <?= (int) ($editRow['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>متوقف</option>
    </select>
</div>
