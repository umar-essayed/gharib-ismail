<?php
$title = 'طباعة مرتجع بيع';
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
<h6 class="text-center mb-1"><?= e($companyName) ?></h6>
<div class="text-center fw-bold mb-2"><?= e($companyPhone) ?></div>
<h5 class="text-center mb-3">مرتجع بيع <?= e($row['return_no']) ?></h5>
<table class="table table-bordered"><thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody><?php foreach($row['items'] as $item): ?><?php $isScaleItem = (int)($item['is_scale_item'] ?? 0) === 1; ?><tr><td><?= e($item['product_name']) ?><?php if ($isScaleItem): ?><div style="font-size:11px;color:#0d6efd;"><?= number_format((float)$item['qty'], 3) ?> كجم × <?= money($item['unit_price']) ?> = <?= money($item['line_total']) ?></div><?php endif; ?></td><td><?= $isScaleItem ? number_format((float)$item['qty'], 3) . ' كجم' : money($item['qty']) ?></td><td><?= money($item['unit_price']) ?></td><td><?= money($item['line_total']) ?></td></tr><?php endforeach; ?></tbody></table>
<div class="text-end">الإجمالي: <?= money($row['grand_total']) ?></div>
<hr>
<div class="support-footer-wrap">
    <div class="support-text"><?= e($supportLine) ?></div>
    <img class="support-qr" src="<?= e($supportQrUrl) ?>" alt="QR support">
</div>
