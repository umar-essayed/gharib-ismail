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
    .labels-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 8px !important;
        padding: 0 !important;
    }
    .label-card {
        border: 1px solid #000 !important;
        margin-bottom: 2px;
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
                    width: 1.6,
                    height: 48,
                    displayValue: true,
                    fontSize: 11,
                    textMargin: 3,
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
