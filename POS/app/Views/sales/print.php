<?php
$title = 'طباعة فاتورة بيع';
$settings = \App\Services\SettingsService::all();
$companyName = $settings['company_name'] ?? config('app')['name'];
$companyPhone = receipt_orders_phone();
$supportLine = support_tech_line();
$supportQrUrl = support_qr_image_url(180);
$invoiceDate = strtotime((string) ($row['invoice_date'] ?? 'now'));
$dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$dayLabel = $dayNames[(int) date('w', $invoiceDate)] ?? '';
$itemsCount = count($row['items'] ?? []);
$invoiceNote = trim((string) ($row['note'] ?? ''));
$isRetailCustomer = (int) ($row['is_cash_customer'] ?? 0) === 1;
$paymentMethodLabel = (string) ($row['payment_method_name'] ?? '-');
$customerName = (string) ($row['customer_name'] ?? 'عميل نقدي');

if (!empty($row['online_customer_name']) && (int)($row['is_online_order'] ?? 0) === 1) {
    $customerName = (string) $row['online_customer_name'];
}
$isRetailByName = preg_match('/عميل\s*(?:تجز(?:ئة|ئه)|نقدي)/u', $customerName) === 1;
$isRetailInvoice = $isRetailCustomer || $isRetailByName;

if ($isRetailInvoice && in_array($paymentMethodLabel, ['آجل', 'اجل', 'credit', 'Credit'], true)) {
    $paymentMethodLabel = 'نقدي';
}
if ($isRetailInvoice) {
    $customerName = trim((string) preg_replace('/\s*(?:آجل|اجل)\s*/u', ' ', $customerName));
    if ($customerName === '') {
        $customerName = 'عميل تجزئة';
    }
}
?>
<style>
    @page {
        size: 80mm auto;
        margin: 0;
    }

    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .receipt {
        width: 72mm; /* adjusted to fit standard 80mm rolls perfectly */
        margin: 0 auto;
        padding: 4mm 2mm;
        box-sizing: border-box;
        color: #000;
        font-family: "Cairo", "Tajawal", "Segoe UI", Tahoma, sans-serif;
        font-size: 13px;
        line-height: 1.4;
        direction: rtl; /* ضمان الاتجاه الصحيح للغة العربية */
    }

    /* إزالة الحواف الدائرية واستبدالها بنظام حواف مستقيمة نظيفة */
    .receipt-head {
        border-bottom: 2px dashed #000;
        padding-bottom: 8px;
        margin-bottom: 8px;
    }

    /* تحسين الجدول ليكون متوافق 100% مع الطابعات الحرارية */
    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    .receipt-table th {
        border-bottom: 2px solid #000;
        border-top: 2px solid #000;
        padding: 6px 3px;
        font-weight: 800;
        font-size: 12px;
    }

    .receipt-table td {
        border-bottom: 1px dashed #000;
        padding: 6px 3px;
        vertical-align: middle;
    }

    .summary {
        border-top: 2px solid #000;
        padding-top: 6px;
        margin-bottom: 8px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        padding: 2px 0;
    }

    .summary-row.total {
        font-size: 16px;
        font-weight: 800;
        border-bottom: 2px double #000;
        padding-bottom: 4px;
    }

    .payments-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
        border: 1px solid #000;
    }

    .payments-table th, .payments-table td {
        border: 1px solid #000;
        padding: 4px;
        text-align: center;
        font-size: 12px;
    }

    .text-center { text-align: center; }
    .text-end { text-align: left; } /* بما أن الاتجاه RTL فالـ End تعني اليسار */
    
    .support-qr {
        width: 50px;
        height: 50px;
        display: block;
    }

    @media print {
        body { width: 80mm; }
    }
</style>

<div class="receipt">
    <div class="receipt-head">
        <table style="width: 100%; border: 0;">
            <tr>
                <td style="border: 0; padding: 0; width: 65%; vertical-align: top;">
                    <div style="font-size: 22px; font-weight: 800;"><?= e($row['invoice_no']) ?></div>
                    <div style="font-size: 16px; font-weight: 800; margin-top: 4px;"><?= e($companyName) ?></div>
                    <div style="font-size: 13px;"><?= e($companyPhone) ?></div>
                </td>
                <td style="border: 0; padding: 0; width: 35%; text-align: left; vertical-align: top;">
                    <div style="font-size: 12px; font-weight: 800; margin-bottom: 4px;">
                        <div><?= e($dayLabel) ?></div>
                        <div><?= e(date('d-m-Y', $invoiceDate)) ?></div>
                        <div><?= e(date('H:i', $invoiceDate)) ?></div>
                    </div>
                    <img class="support-qr" src="<?= e($supportQrUrl) ?>" alt="QR" style="margin-left: 0; margin-right: auto;">
                </td>
            </tr>
        </table>

        <div style="margin-top: 8px; font-size: 13px; line-height: 1.5;">
            <div><strong>العميل:</strong> <?= e($customerName) ?></div>
            <div>
                <?php if ($invoiceNote !== ''): ?>
                    <strong>ملاحظة:</strong> <?= e($invoiceNote) ?>
                <?php else: ?>
                    <strong>عدد الأصناف:</strong> <?= e((string) $itemsCount) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th style="text-align: right;">الصنف</th>
                <th class="text-center">ك</th>
                <th class="text-center">سعر</th>
                <th class="text-center">إجمالي</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($row['items'] as $item): ?>
            <?php
            $unitKey = $item['sale_unit'] ?? 'piece';
            $unitLabel = $unitKey === 'box' ? 'علبة' : ($unitKey === 'sack' ? 'شيكارة' : ($unitKey === 'kg' ? 'كجم' : 'قطعة'));
            $isScaleItem = (int) ($item['is_scale_item'] ?? 0) === 1;
            ?>
            <tr>
                <td style="text-align: right; font-weight: 700;">
                    <?= e($item['product_name']) ?><br>
                    <span style="font-size: 11px; font-weight: normal; color: #333;">
                        (<?= e($unitLabel) ?>)
                        <?php if ($isScaleItem): ?>
                            <?= number_format((float) $item['qty'], 3) ?> كجم × <?= money($item['unit_price']) ?>
                        <?php endif; ?>
                    </span>
                </td>
                <td class="text-center" style="font-weight: 800;"><?= $isScaleItem ? number_format((float) $item['qty'], 3) : money($item['qty']) ?></td>
                <td class="text-center"><?= money($item['unit_price']) ?></td>
                <td class="text-center" style="font-weight: 800;"><?= money($item['line_total']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row total">
            <span>الإجمالي النهائي</span>
            <strong><?= money($row['grand_total']) ?></strong>
        </div>
    </div>

    <?php
    $isOnlineOrder = (int)($row['is_online_order'] ?? 0) === 1;
    $isCodPending  = $isOnlineOrder && ($row['payment_status'] ?? '') !== 'paid';
    ?>
    <table class="payments-table">
        <thead>
            <tr>
                <th><?= $isCodPending ? 'وسيلة الدفع' : 'مدفوع' ?></th>
                <?php if (!$isRetailInvoice && !$isCodPending): ?><th>آجل</th><?php endif; ?>
                <th>الوسيلة</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php if ($isCodPending): ?>
                    <td style="font-weight: 800;">الدفع عند الاستلام</td>
                <?php else: ?>
                    <td style="font-weight: 800;"><?= money($row['paid_total']) ?></td>
                    <?php if (!$isRetailInvoice): ?><td style="font-weight: 800;"><?= money($row['due_total']) ?></td><?php endif; ?>
                <?php endif; ?>
                <td><?= e($paymentMethodLabel) ?></td>
            </tr>
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 15px; border-top: 1px dashed #000; padding-top: 8px; font-size: 12px;">
        <div style="font-weight: 800; margin-bottom: 4px;"><?= e($supportLine) ?></div>
        <div style="font-size: 11px;"><?= e(date('d-m-Y H:i', $invoiceDate)) ?></div>
    </div>
</div>
