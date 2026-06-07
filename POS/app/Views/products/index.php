<?php $title = 'المنتجات'; ?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h5 class="mb-0">قائمة المنتجات</h5>
    <div class="d-flex gap-2">
        <form class="d-flex" method="get" action="<?= url('/products') ?>">
            <input class="form-control" name="q" placeholder="بحث بالاسم/الباركود" value="<?= e($q) ?>">
            <button class="btn btn-primary ms-2">بحث</button>
        </form>
        <a class="btn btn-success" href="<?= url('/products/create') ?>">إضافة منتج</a>
    </div>
</div>

<div class="table-wrap table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr><th>#</th><th>الاسم</th><th>الباركود</th><th>التصنيف</th><th>الوحدة</th><th>العبوة</th><th>سعر البيع</th><th>المخزون</th><th>الحالة</th><th>إجراءات</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= (int) $row['id'] ?></td>
                <td>
                    <div class="fw-bold"><?= e($row['name']) ?></div>
                    <small class="text-muted">SKU: <?= e($row['sku']) ?></small>
                </td>
                <td><?= e($row['barcode']) ?></td>
                <td><?= e($row['category_name']) ?></td>
                <td><?= e($row['unit_name']) ?></td>
                <td>
                    <?php
                    $pkg = $row['package_type'] ?? 'piece';
                    $pkgLabel = $pkg === 'box' ? 'علبة' : ($pkg === 'sack' ? 'شيكارة' : ($pkg === 'kg' ? 'كيلو' : 'قطعة'));
                    ?>
                    <?= e($pkgLabel) ?>
                    <?php if (in_array($pkg, ['box', 'sack'], true)): ?>
                        <small class="text-muted d-block">× <?= money($row['package_size'] ?? 1) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= money($row['sale_price']) ?></td>
                <td><?= money($row['current_stock']) ?></td>
                <td><?= $row['is_active'] ? 'نشط' : 'معطّل' ?></td>
                <td>
                    <a class="btn btn-sm btn-warning" href="<?= url('/products/' . (int) $row['id'] . '/edit') ?>">تعديل</a>
                    <form method="post" action="<?= url('/products/' . (int) $row['id'] . '/delete') ?>" class="d-inline" data-confirm="تأكيد حذف المنتج؟">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-danger">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
