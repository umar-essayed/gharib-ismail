<?php
$title = 'طباعة فاتورة شراء';
$settings = \App\Services\SettingsService::all();
$companyName = $settings['company_name'] ?? config('app')['name'];
$companyPhone = receipt_orders_phone();
$supportLine = support_tech_line();
$supportQrUrl = support_qr_image_url(180);
?>
<style>
.support-footer-wrap{margin-top:8px;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:5px;width:100%}
.support-qr{width:76px;height:76px;border:1px solid #d1d5db;border-radius:8px;padding:3px;background:#fff;object-fit:contain}
.support-text{width:100%;box-sizing:border-box;text-align:center;padding:4px 6px;border:1px solid #d1d5db;border-radius:10px;font-size:11px;font-weight:700;line-height:1.3}
</style>
<div class="text-center mb-3">
    <h5 class="mb-1"><?= e($companyName) ?></h5>
    <div class="fw-bold"><?= e($companyPhone) ?></div>
    <div class="mt-1">فاتورة شراء</div>
    <div>رقم: <?= e($row['invoice_no']) ?></div>
    <div>التاريخ: <?= e($row['invoice_date']) ?></div>
</div>
<table class="table table-bordered">
    <thead><tr><th>الصنف</th><th>كمية</th><th>سعر</th><th>إجمالي</th></tr></thead>
    <tbody>
    <?php foreach ($row['items'] as $item): ?>
    <?php
    $unitKey = $item['purchase_unit'] ?? 'piece';
    $unitLabel = $unitKey === 'box' ? 'علبة' : ($unitKey === 'sack' ? 'شيكارة' : ($unitKey === 'kg' ? 'كجم' : 'قطعة'));
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
<div class="text-end">
    <div>الإجمالي: <?= money($row['grand_total']) ?></div>
    <div>المدفوع: <?= money($row['paid_total']) ?></div>
    <div>المتبقي: <?= money($row['due_total']) ?></div>
</div>
<hr>
<div class="support-footer-wrap">
    <div class="support-text"><?= e($supportLine) ?></div>
    <img class="support-qr" src="<?= e($supportQrUrl) ?>" alt="QR support">
</div>
