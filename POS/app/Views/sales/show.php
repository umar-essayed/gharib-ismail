<?php $title = 'تفاصيل فاتورة بيع'; ?>
<div class="d-flex justify-content-between mb-3">
    <h5 class="mb-0">فاتورة: <?= e($row['invoice_no']) ?></h5>
    <div>
        <a class="btn btn-light" target="_blank" href="<?= url('/sales/' . (int)$row['id'] . '/print') ?>">طباعة</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card p-2">العميل: <strong><?= e($row['customer_name']) ?></strong></div></div>
    <div class="col-md-3"><div class="card p-2">الكاشير: <strong><?= e($row['user_name']) ?></strong></div></div>
    <div class="col-md-3"><div class="card p-2">التاريخ: <strong><?= e($row['invoice_date']) ?></strong></div></div>
    <div class="col-md-3"><div class="card p-2">الحالة: <strong><?= e($row['status']) ?></strong></div></div>
</div>

<div class="table-wrap table-responsive mb-3">
<table class="table table-striped align-middle">
    <thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>إجمالي السطر</th></tr></thead>
    <tbody>
    <?php foreach ($row['items'] as $item): ?>
        <?php
        $unitKey = $item['sale_unit'] ?? (($item['unit_name'] ?? '') !== '' ? '' : 'piece');
        $unitLabel = $unitKey === 'box' ? 'علبة' : ($unitKey === 'sack' ? 'شيكارة' : ($unitKey === 'kg' ? 'كجم' : ($unitKey === 'piece' ? 'قطعة' : ($item['unit_name'] ?? ''))));
        $isScaleItem = (int) ($item['is_scale_item'] ?? 0) === 1;
        ?>
        <tr>
            <td>
                <?= e($item['product_name']) ?>
                <?php if ($isScaleItem): ?>
                    <div class="small text-primary"><?= number_format((float) $item['qty'], 3) ?> كجم × <?= money($item['unit_price']) ?> = <?= money($item['line_total']) ?></div>
                <?php endif; ?>
            </td>
            <td><?= $isScaleItem ? number_format((float) $item['qty'], 3) : money($item['qty']) ?> <?= e($isScaleItem ? 'كجم' : $unitLabel) ?></td>
            <td><?= money($item['unit_price']) ?></td>
            <td><?= money($item['line_total']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-3"><div class="card p-2">الإجمالي: <strong><?= money($row['subtotal']) ?></strong></div></div>
    <div class="col-md-3"><div class="card p-2">الصافي: <strong><?= money($row['grand_total']) ?></strong></div></div>
    <div class="col-md-3"><div class="card p-2">المتبقي: <strong><?= money($row['due_total']) ?></strong></div></div>
</div>

<?php if ($row['status'] !== 'cancelled' && can('sales.cancel')): ?>
<form method="post" action="<?= url('/sales/' . (int) $row['id'] . '/cancel') ?>" data-confirm="تأكيد إلغاء الفاتورة؟">
    <?= csrf_field() ?>
    <button class="btn btn-danger">إلغاء الفاتورة</button>
</form>
<?php endif; ?>
