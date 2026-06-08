<?php $title = 'طباعة ملصقات الباركود'; ?>

<style>
/* ═══════════════════════════════════════════════════════════════
   تنسيق ملصقات الباركود النهائي والمعدل هندسياً لمنع القص والترحيل
══════════════════════════════════════════════════════════════════ */
html, body {
    background: #fff;
    padding: 0;
    margin: 0;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    direction: rtl;
}

/* التنسيق أثناء العرض على الشاشة للمعاينة */
.labels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(55mm, 1fr));
    gap: 15px;
    padding: 15px;
    background: #f4f4f4;
}

.label-card {
    background: #fff;
    border: 1px dashed #999;
    border-radius: 4px;
    width: 50mm;
    height: 30mm;
    padding: 1.5mm 2mm;
    box-sizing: border-box;
    margin: 0 auto;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.product-name {
    font-size: 10.5pt;
    font-weight: bold;
    color: #000;
    line-height: 1.2;
    height: 2.4em;
    overflow: hidden;
    margin-bottom: 1mm;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.barcode-container {
    width: 100%;
    height: 11mm;
    margin: 0 auto;
    text-align: center;
}

.barcode-svg {
    width: 100% !important;
    height: 100% !important;
    display: block;
    margin: 0 auto;
}

.fallback-barcode {
    font-family: monospace;
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 1px;
    border: 1px dashed #000;
    padding: 2px;
    background: #fff;
}

.product-price {
    font-size: 11pt;
    font-weight: 900;
    color: #000;
    border-top: 1px dashed #000;
    padding-top: 1mm;
    margin-top: 1mm;
    position: absolute;
    bottom: 1.5mm;
    left: 2mm;
    right: 2mm;
}

/* ═══════════════════════════════════════════════════════════════
   التنسيق الصارم لمعالجة تضارب المحاور وقت الطباعة الفعلية
══════════════════════════════════════════════════════════════════ */
@media print {
    @page {
        size: 50mm 30mm;
        margin: 0 !important;
    }

    html, body {
        width: 50mm !important;
        height: 30mm !important;
        overflow: hidden !important;
        background: #fff !important;
    }

    .labels-grid {
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #fff !important;
    }

    .label-card {
        width: 46mm !important;
        height: 27mm !important;
        margin: 1.5mm auto 0 auto !important;
        /* إزاحة قسرية لليسار لتجاوز هامش الأجهزة */
        transform: translateX(-4mm) !important;
        position: relative !important;
        padding: 0 !important;
        box-sizing: border-box !important;
        page-break-after: always !important;
        page-break-inside: avoid !important;
        overflow: hidden !important;
        background: #fff !important;
    }

    .product-name {
        font-size: 8.5pt !important;
        font-weight: bold !important;
        text-align: center !important;
        margin: 0 !important;
        line-height: 1.1 !important;
        max-height: 2.2em !important;
        width: 100% !important;
        overflow: hidden !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important;
        -webkit-box-orient: vertical !important;
    }

    .barcode-container {
        width: 100% !important;
        text-align: center !important;
        margin: 0 auto !important;
        transform: scale(0.85);
        transform-origin: center center;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        flex-grow: 1 !important;
    }

    .product-price {
        position: absolute !important;
        top: 14.5mm !important;           /* تثبيت من الأعلى */
        bottom: auto !important;
        left: 5% !important;
        width: 90% !important;
        text-align: center !important;
        font-size: 9.5pt !important;
        font-weight: bold !important;
        direction: rtl !important;
        border-top: 1px dashed #000 !important;
        padding-top: 1.5mm !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
}
</style>

<div class="labels-grid">
    <?php foreach ($rows as $row): ?>
        <?php 
        $qty = $row['print_qty'] ?? 1;
        $barcodeVal = trim($row['barcode'] ?? '');
        ?>
        <?php for ($i = 0; $i < $qty; $i++): ?>
            <div class="label-card">
                <div class="product-name"><?= e($row['name']) ?></div>
                <div class="barcode-container">
                    <?php if (!empty($barcodeVal)): ?>
                        <svg class="barcode-svg" data-value="<?= e($barcodeVal) ?>"></svg>
                    <?php else: ?>
                        <div style="font-size: 10px; color: red; font-weight: bold; padding-top: 2px;">بدون باركود</div>
                    <?php endif; ?>
                </div>
                <div class="product-price">السعر: <?= money($row['sale_price']) ?></div>
            </div>
        <?php endfor; ?>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
    if (typeof JsBarcode === 'undefined') {
        document.querySelectorAll('.barcode-svg').forEach(svg => {
            const val = svg.getAttribute('data-value');
            const fallback = document.createElement('div');
            fallback.className = 'fallback-barcode';
            fallback.textContent = '*' + val + '*';
            svg.replaceWith(fallback);
        });
        return;
    }

    document.querySelectorAll('.barcode-svg').forEach(svg => {
        const val = svg.getAttribute('data-value');
        if (val) {
            try {
                JsBarcode(svg, val, {
                    format: "CODE128",
                    width: 1.15,      /* خطوط أرفع لتجنب الخروج عن الحافة اليمنى */
                    height: 18,
                    displayValue: true,
                    fontSize: 9,
                    textMargin: 1,
                    fontOptions: "bold",
                    margin: 0
                });
            } catch (err) {
                console.error('فشل إنشاء باركود للقيمة: ' + val, err);
                const fallback = document.createElement('div');
                fallback.className = 'fallback-barcode';
                fallback.textContent = '*' + val + '*';
                svg.replaceWith(fallback);
            }
        }
    });
});
</script>
