<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'طباعة') ?></title>
    <link rel="stylesheet" href="<?= url('/assets/vendor/bootstrap/bootstrap.rtl.min.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <style>
        html, body.print-page {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            width: 100%;
        }

        .print-container {
            width: 100%;
            max-width: 100%;
            padding: 0; /* تم تصفير الهوامش الافتراضية لضمان سلامة الملصقات الحرارية */
            margin: 0;
            box-sizing: border-box;
        }

        /* إذا كنت تريد مسافة جمالية للفواتير الكبيرة على الشاشة فقط دون التأثير على الملصقات */
        @media screen {
            .print-container {
                padding: 12px;
            }
        }

        @media print {
            .print-container {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body class="print-page">
<div class="print-container">
    <?= $content ?>
</div>
<script>
(() => {
    const params = new URLSearchParams(window.location.search);
    const autoPrint = params.get('autoprint');
    const returnTo = params.get('return_to');
    const embedded = params.get('embedded') === '1';
    const selfClose = params.get('self_close') === '1';
    const invoiceNo = params.get('invoice_no') || '';
    const jobId = params.get('job_id') || '';
    const shouldPrint = autoPrint === null || autoPrint === '1';
    let completed = false;

    const notifyParent = (type, message = '') => {
        if (!embedded || window.parent === window) {
            return;
        }

        try {
            window.parent.postMessage({ type, invoiceNo, message, jobId }, window.location.origin);
        } catch (e) {}
    };

    const finish = (type = 'pos-print-complete', message = '') => {
        if (completed) {
            return;
        }
        completed = true;

        if (embedded) {
            notifyParent(type, message);
            setTimeout(() => {
                window.location.replace('about:blank');
            }, 20);
            return;
        }

        if (selfClose) {
            try {
                window.open('', '_self');
                window.close();
            } catch (e) {}
            return;
        }

        if (returnTo) {
            window.location.replace(returnTo);
        }
    };

    window.addEventListener('load', () => {
        if (shouldPrint) {
            setTimeout(() => {
                try {
                    if (window.electronAPI) {
                        let printerName = '';
                        // فحص ذكي: إذا كان الرابط يحتوي على باركود، يتم سحب اسم طابعة الباركود المخصصة
                        if (window.location.pathname.includes('/barcode/print')) {
                            printerName = <?= json_encode(\App\Services\SettingsService::get('label_printer', '')) ?> || <?= json_encode(\App\Services\SettingsService::get('default_printer', '')) ?>;
                        } else {
                            printerName = <?= json_encode(\App\Services\SettingsService::get('default_printer', '')) ?>;
                        }
                        window.electronAPI.printSilent(printerName);
                    } else {
                        window.print();
                    }
                } catch (e) {
                    finish('pos-print-error', 'تعذر بدء الطباعة');
                }
            }, 400);

            if (window.electronAPI) {
                if (typeof window.electronAPI.onPrintFinished === 'function') {
                    window.electronAPI.onPrintFinished((data) => {
                        if (data.success) {
                            finish('pos-print-complete');
                        } else {
                            finish('pos-print-error', 'فشلت الطباعة: ' + data.error);
                        }
                    });
                } else {
                    setTimeout(() => {
                        finish('pos-print-complete');
                    }, 1500);
                }
                return;
            }

            if (embedded) {
                setTimeout(() => {
                    finish('pos-print-complete');
                }, 2500);
                return;
            }

            if (returnTo) {
                setTimeout(() => {
                    finish('pos-print-complete');
                }, 900);
                return;
            }

            if (selfClose) {
                setTimeout(() => {
                    finish('pos-print-complete');
                }, 900);
            }
            return;
        }
        finish('pos-print-complete');
    });

    window.addEventListener('afterprint', () => {
        finish('pos-print-complete');
    });
})();
</script>
</body>
</html>
