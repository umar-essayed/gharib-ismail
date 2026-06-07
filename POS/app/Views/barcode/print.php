<?php $title = 'طباعة ملصقات الباركود'; ?>

<style>
/* ═══════════════════════════════════════════════════════════════
   تنسيق ملصقات الباركود للطباعة
══════════════════════════════════════════════════════════════════ */
body.print-page {
    background: #fff;
    padding: 0;
    margin: 0;
}

.labels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(6cm, 1fr));
    gap: 12px;
    padding: 10px;
}

.label-card {
    border: 1px dashed #bbb;
    border-radius: 6px;
    padding: 12px 8px;
    text-align: center;
    background: #fff;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    box-sizing: border-box;
    page-break-inside: avoid;
}

.product-name {
    font-size: 12px;
    font-weight: 700;
    color: #333;
    margin-bottom: 4px;
    line-height: 1.3;
    max-height: 2.6em;
    overflow: hidden;
    width: 100%;
}

.barcode-container {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 4px 0;
}

.barcode-svg {
    max-width: 100%;
    height: auto;
}

.fallback-barcode {
    font-family: monospace;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 2px;
    border: 1px solid #333;
    padding: 4px 8px;
    margin: 6px 0;
    background: #f8fafc;
}

.product-price {
    font-size: 13px;
    font-weight: 800;
    color: #000;
    border-top: 1px solid #eee;
    width: 100%;
    padding-top: 4px;
    margin-top: 4px;
}

@media print {
    @page {
        size: 50mm 30mm;
        margin: 0;
    }
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        -webkit-print-color-adjust: exact;
    }
    .labels-grid {
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        gap: 0 !important;
    }
    .label-card {
        width: 50mm !important;
        height: 30mm !important;
        border: none !important;
        margin: 0 !important;
        padding: 1.5mm 2mm !important;
        page-break-after: always !important;
        page-break-inside: avoid !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        align-items: center !important;
        box-sizing: border-box !important;
    }
    .product-name {
        font-size: 10px !important;
        font-weight: bold !important;
        margin-bottom: 1px !important;
        line-height: 1.1 !important;
        white-space: nowrap !important;
        text-overflow: ellipsis !important;
        overflow: hidden !important;
    }
    .barcode-container {
        margin: 1px 0 !important;
    }
    .barcode-svg {
        height: 30px !important;
    }
    .product-price {
        font-size: 10px !important;
        font-weight: bold !important;
        border-top: 1px solid #000 !important;
        padding-top: 1px !important;
        margin-top: 1px !important;
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
                        <div class="text-danger small">بدون باركود</div>
                    <?php endif; ?>
                </div>
                <div class="product-price">السعر: <?= money($row['sale_price']) ?></div>
            </div>
        <?php endfor; ?>
    <?php endforeach; ?>
</div>

<!-- مكتبة JsBarcode لإنشاء الباركود حقيقي وقابل للمسح -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
    if (typeof JsBarcode === 'undefined') {
        console.warn('تعذر تحميل مكتبة JsBarcode من السيرفر السحابي - سيتم عرض الباركود كنص.');
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
                    width: 1.2,
                    height: 32,
                    displayValue: true,
                    fontSize: 8,
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
