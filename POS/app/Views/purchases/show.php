<?php $title = 'تفاصيل فاتورة شراء'; ?>
<div class="d-flex justify-content-between mb-3">
    <h5 class="mb-0">فاتورة شراء: <?= e($row['invoice_no']) ?></h5>
    <div>
        <a class="btn btn-light" href="<?= url('/purchases/' . (int)$row['id'] . '/print') ?>" target="_blank">طباعة</a>
        <?php if ($row['status'] === 'draft' && can('purchases.approve')): ?>
            <a class="btn btn-warning" href="<?= url('/purchases/' . (int)$row['id'] . '/edit') ?>">تعديل المسودة</a>
            <form method="post" action="<?= url('/purchases/' . (int)$row['id'] . '/approve') ?>" class="d-inline" data-confirm="اعتماد الفاتورة الآن؟"><?= csrf_field() ?><button class="btn btn-success">اعتماد</button></form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-3"><div class="card p-2">المورد: <strong><?= e($row['supplier_name']) ?></strong></div></div>
    <div class="col-md-3"><div class="card p-2">الحالة: <strong><?= e($row['status']) ?></strong></div></div>
    <div class="col-md-3"><div class="card p-2">الإجمالي: <strong><?= money($row['grand_total']) ?></strong></div></div>
    <div class="col-md-3"><div class="card p-2">المتبقي: <strong><?= money($row['due_total']) ?></strong></div></div>
</div>

<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
    <thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead>
    <tbody>
    <?php foreach ($row['items'] as $item): ?>
        <?php
        $unitKey = $item['purchase_unit'] ?? (($item['unit_name'] ?? '') !== '' ? '' : 'piece');
        $unitLabel = $unitKey === 'box' ? 'علبة' : ($unitKey === 'sack' ? 'شيكارة' : ($unitKey === 'kg' ? 'كجم' : ($unitKey === 'piece' ? 'قطعة' : ($item['unit_name'] ?? ''))));
        ?>
        <tr>
            <td><?= e($item['product_name']) ?></td>
            <td><?= money($item['qty']) ?> <?= e($unitLabel) ?></td>
            <td><?= money($item['unit_price']) ?></td>
            <td><?= money($item['line_total']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
