<?php $title = 'طباعة ملصقات الباركود'; ?>

<style>
/* ═══════════════════════════════════════════════════════════════
   تنسيق ملصقات الباركود المتوافق تماماً مع Xprinter XP-323B
══════════════════════════════════════════════════════════════════ */
html, body {
    background: #fff;
    padding: 0;
    margin: 0;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    direction: rtl;
}

/* التنسيق أثناء العرض على الشاشة */
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
    height: 2.4em; /* أقصى حد سطرين لاسم المنتج */
    overflow: hidden;
    margin-bottom: 1mm;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.barcode-container {
    width: 100%;
    height: 11mm; /* مساحة ثابتة ومحددة للباركود */
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
   التنسيق الصارم والخاص بالطباعة الحرارية الفعلية (50mm x 30mm)
══════════════════════════════════════════════════════════════════ */
@media print {
    @page {
        size: 50mm 30mm;
        margin: 0 !important;
    }
    
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .labels-grid {
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #fff !important;
    }

    .label-card {
        width: 50mm !important;
        height: 30mm !important;
        border: none !important; /* إزالة الحدود تماماً لكي لا تظهر على الملصق المطبوع */
        margin: 0 !important;
        padding: 1.5mm 2mm !important;
        page-break-after: always !important; /* إجبار الطابعة على الانتقال للملصق التالي بدقة */
        page-break-inside: avoid !important;
        position: relative !important;
        box-sizing: border-box !important;
        display: block !important; /* تحويله لكتلة ثابتة بدلاً من flex */
    }

    .product-name {
        font-size: 10pt !important;
        font-weight: bold !important;
        margin-bottom: 1mm !important;
        height: 2.4em !important;
        overflow: hidden !important;
    }

    .barcode-container {
        height: 11mm !important;
        margin: 0 auto !important;
    }

    .product-price {
        font-size: 11pt !important;
        font-weight: bold !important;
        border-top: 1px dashed #000 !important;
        padding-top: 1mm !important;
        position: absolute !important;
        bottom: 1.5mm !important;
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
                        <div style="font-size: 11px; color: red; font-weight: bold; padding-top: 5px;">بدون باركود</div>
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
                    width: 1.4,       /* تم زيادة العرض قليلاً لتسهيل القراءة بالماسح */
                    height: 28,       /* ارتفاع متناسق وممتاز جداً مع مقاس الـ 30 مم للملصق */
                    displayValue: true,
                    fontSize: 9,      /* حجم خط رقم الباركود أسفل الخطوط ليكون واضحاً */
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
