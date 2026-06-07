<?php $title = 'عرض مرتجع بيع'; ?>
<div class="d-flex justify-content-between mb-3">
    <h5 class="mb-0">مرتجع بيع: <?= e($row['return_no']) ?></h5>
    <a class="btn btn-light" target="_blank" href="<?= url('/returns/sales/' . (int)$row['id'] . '/print') ?>">طباعة</a>
</div>
<div class="row g-2 mb-3">
    <div class="col-md-4"><div class="card p-2">الفاتورة الأصلية: <?= e($row['invoice_no']) ?></div></div>
    <div class="col-md-4"><div class="card p-2">العميل: <?= e($row['customer_name']) ?></div></div>
    <div class="col-md-4"><div class="card p-2">الإجمالي: <?= money($row['grand_total']) ?></div></div>
</div>
<div class="table-wrap table-responsive">
<table class="table table-striped"><thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody><?php foreach($row['items'] as $item): ?><?php $isScaleItem = (int)($item['is_scale_item'] ?? 0) === 1; ?><tr><td><?= e($item['product_name']) ?><?php if ($isScaleItem): ?><div class="small text-primary"><?= number_format((float)$item['qty'], 3) ?> كجم × <?= money($item['unit_price']) ?> = <?= money($item['line_total']) ?></div><?php endif; ?></td><td><?= $isScaleItem ? number_format((float)$item['qty'], 3) . ' كجم' : money($item['qty']) ?></td><td><?= money($item['unit_price']) ?></td><td><?= money($item['line_total']) ?></td></tr><?php endforeach; ?></tbody></table>
</div>
