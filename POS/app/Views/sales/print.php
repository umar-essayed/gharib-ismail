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
// للطلبات الإلكترونية: استخدم اسم العميل الإلكتروني المخزن عند القبول
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

    html, body.print-page {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
    }

    .print-container {
        padding: 0 !important;
        margin: 0 !important;
    }

    .receipt {
        width: 72mm !important;
        margin-left: 4mm !important;
        margin-right: auto !important;
        padding: 1.2mm 0;
        box-sizing: border-box;
        color: #000;
        font-family: "Cairo", "Tajawal", "Segoe UI", Tahoma, sans-serif;
        font-size: 13.5px;
        line-height: 1.5;
        font-weight: 700;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        text-rendering: geometricPrecision;
    }

    .receipt-head {
        border: 1px solid #000;
        border-radius: 10px;
        padding: 8px;
        margin-bottom: 8px;
    }

    .receipt-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    .invoice-no {
        font-size: 21px;
        font-weight: 800;
        letter-spacing: .4px;
    }

    .invoice-date {
        font-size: 12px;
        font-weight: 800;
        color: #000;
        text-align: left;
    }

    .receipt-company {
        text-align: center;
        font-size: 15px;
        font-weight: 800;
    }

    .receipt-company-phone {
        text-align: center;
        margin-top: 2px;
        font-size: 13px;
        font-weight: 700;
        color: #000;
    }

    .receipt-meta {
        margin-top: 6px;
        border-top: 1px solid #000;
        padding-top: 6px;
        display: grid;
        gap: 2px;
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
        border: 1px solid #000;
        border-radius: 10px;
        overflow: hidden;
    }

    .receipt-table th,
    .receipt-table td {
        border-bottom: 1px solid #000;
        padding: 5px 6px;
        vertical-align: top;
        color: #000;
    }

    .receipt-table thead th {
        background: #fff;
        font-size: 11.5px;
        font-weight: 800;
    }

    .receipt-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .text-center { text-align: center; }
    .text-end { text-align: left; }
    .product-cell { font-size: 12.5px; font-weight: 700; }
    .unit-label { font-size: 11px; }
    .unit-piece { color: #000; font-weight: 800; font-size: 11.5px; }

    .summary {
        border: 1px solid #000;
        border-radius: 10px;
        padding: 6px 8px;
        margin-bottom: 8px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 1px 0;
        font-size: 13px;
    }

    .summary-row.total {
        border-top: 1px solid #000;
        margin-top: 3px;
        padding-top: 4px;
        font-size: 14px;
        font-weight: 800;
    }

    .payments {
        border: 1px solid #000;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .payments table {
        width: 100%;
        border-collapse: collapse;
    }

    .payments th,
    .payments td {
        border-left: 1px solid #000;
        padding: 5px 6px;
        text-align: center;
        font-size: 12px;
        color: #000;
    }

    .payments th:last-child,
    .payments td:last-child {
        border-left: 0;
    }

    .receipt-foot {
        border-top: 1px solid #000;
        padding-top: 7px;
        text-align: center;
    }

    .support-footer-wrap {
        margin-top: 5px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 5px;
        width: 100%;
    }

    .invoice-footer {
        width: 100%;
        box-sizing: border-box;
        text-align: center;
        padding: 4px 6px;
        border: 1px solid #000;
        border-radius: 10px;
        font-size: 11.5px;
        font-weight: 800;
        line-height: 1.3;
    }

    .support-qr {
        width: 42px;
        height: 42px;
        border: 1px solid #000;
        border-radius: 4px;
        padding: 1px;
        background: #fff;
        object-fit: contain;
        image-rendering: crisp-edges;
    }

    .muted {
        color: #000;
        font-size: 11px;
        font-weight: 700;
    }

    .receipt-datetime {
        color: #000;
        font-size: 11.5px;
        font-weight: 800;
    }

    @media print {
        html, body, .print-page, .print-container {
            margin: 0 !important;
            padding: 0 !important;
        }
        .receipt {
            width: 72mm !important;
            margin-left: 4mm !important;
            margin-right: auto !important;
        }
        .receipt, .receipt * {
            color: #000 !important;
            -webkit-text-fill-color: #000 !important;
            text-shadow: none !important;
        }
    }
</style>

<div class="receipt">
    <div class="receipt-head">
        <div class="receipt-top" style="align-items: flex-start; gap: 4px; margin-bottom: 2px;">
            <div>
                <div class="invoice-no" style="line-height: 1.1; margin-bottom: 2px;"><?= e($row['invoice_no']) ?></div>
                <div class="receipt-company" style="text-align: right; font-size: 14.5px;"><?= e($companyName) ?></div>
                <div class="receipt-company-phone" style="text-align: right; font-size: 12px; margin-top: 0;"><?= e($companyPhone) ?></div>
            </div>
            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 3px; min-width: 25mm;">
                <div class="invoice-date" style="text-align: left; font-size: 11.5px; line-height: 1.3;">
                    <div><?= e($dayLabel) ?></div>
                    <div><?= e(date('d-m-Y', $invoiceDate)) ?></div>
                    <div><?= e(date('H:i', $invoiceDate)) ?></div>
                </div>
                <img class="support-qr" src="<?= e($supportQrUrl) ?>" alt="QR support">
            </div>
        </div>
        <div class="receipt-meta" style="margin-top: 4px; padding-top: 4px;">
            <div>العميل: <?= e($customerName) ?></div>
            <div>
                <?php if ($invoiceNote !== ''): ?>
                    ملاحظة: <?= e($invoiceNote) ?>
                <?php else: ?>
                    عدد الأصناف: <?= e((string) $itemsCount) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>الصنف</th>
                <th class="text-center">ك</th>
                <th class="text-center">سعر</th>
                <th class="text-center">قيمة</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($row['items'] as $item): ?>
            <?php
            $unitKey = $item['sale_unit'] ?? 'piece';
            $unitLabel = $unitKey === 'box' ? 'علبة' : ($unitKey === 'sack' ? 'شيكارة' : ($unitKey === 'kg' ? 'كجم' : 'قطعة'));
            $unitClass = $unitLabel === 'قطعة' ? 'unit-label unit-piece' : 'muted unit-label';
            $isScaleItem = (int) ($item['is_scale_item'] ?? 0) === 1;
            ?>
            <tr>
                <td class="product-cell">
                    <?= e($item['product_name']) ?><br>
                    <?php if ($isScaleItem): ?>
                        <span class="muted unit-label"><?= number_format((float) $item['qty'], 3) ?> كجم × <?= money($item['unit_price']) ?> = <?= money($item['line_total']) ?></span>
                    <?php else: ?>
                        <span class="<?= e($unitClass) ?>"><?= e($unitLabel) ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-center"><?= $isScaleItem ? number_format((float) $item['qty'], 3) : money($item['qty']) ?></td>
                <td class="text-center"><?= money($item['unit_price']) ?></td>
                <td class="text-center"><?= money($item['line_total']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row total"><span>الإجمالي</span><strong><?= money($row['grand_total']) ?></strong></div>
    </div>

    <?php
    $isOnlineOrder = (int)($row['is_online_order'] ?? 0) === 1;
    $isCodPending  = $isOnlineOrder && ($row['payment_status'] ?? '') !== 'paid';
    ?>
    <div class="payments">
        <table>
            <tr>
                <th><?= $isCodPending ? 'وسيلة الدفع' : 'مدفوع' ?></th>
                <?php if (!$isRetailInvoice && !$isCodPending): ?><th>آجل</th><?php endif; ?>
                <th>وسيلة</th>
            </tr>
            <tr>
                <?php if ($isCodPending): ?>
                    <td style="font-weight:900;color:#000">دفع عند الاستلام</td>
                <?php else: ?>
                    <td><?= money($row['paid_total']) ?></td>
                    <?php if (!$isRetailInvoice): ?><td><?= money($row['due_total']) ?></td><?php endif; ?>
                <?php endif; ?>
                <td><?= e($paymentMethodLabel) ?></td>
            </tr>
        </table>
    </div>

    <div class="receipt-foot">
        <div class="receipt-datetime"><?= e(date('d-m-Y H:i', $invoiceDate)) ?></div>
        <div class="support-footer-wrap" style="margin-top: 3px; width: 100%;">
            <div class="invoice-footer" style="width: 100%; text-align: center; box-sizing: border-box; padding: 4px 6px; font-size: 11px;"><?= e($supportLine) ?></div>
        </div>
    </div>
</div>
