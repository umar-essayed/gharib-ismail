<?php $title = 'عرض مرتجع شراء'; ?>
<div class="d-flex justify-content-between mb-3">
    <h5 class="mb-0">مرتجع شراء: <?= e($row['return_no']) ?></h5>
    <a class="btn btn-light" target="_blank" href="<?= url('/returns/purchases/' . (int)$row['id'] . '/print') ?>">طباعة</a>
</div>
<div class="row g-2 mb-3">
    <div class="col-md-4"><div class="card p-2">الفاتورة الأصلية: <?= e($row['invoice_no']) ?></div></div>
    <div class="col-md-4"><div class="card p-2">المورد: <?= e($row['supplier_name']) ?></div></div>
    <div class="col-md-4"><div class="card p-2">الإجمالي: <?= money($row['grand_total']) ?></div></div>
</div>
<div class="table-wrap table-responsive">
<table class="table table-striped"><thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody><?php foreach($row['items'] as $item): ?><tr><td><?= e($item['product_name']) ?></td><td><?= money($item['qty']) ?></td><td><?= money($item['unit_price']) ?></td><td><?= money($item['line_total']) ?></td></tr><?php endforeach; ?></tbody></table>
</div>
