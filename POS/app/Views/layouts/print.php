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
            margin: 0;
            box-sizing: border-box;
        }

        @media screen {
            .print-container { padding: 12px; }
        }

        /* Dynamic Print Rules */
        @media print {
            /* Default (for Barcode) - Strict Zero Margins */
            body.is-barcode .print-container {
                padding: 0 !important;
                margin: 0 !important;
            }
            /* Specific Fix for Normal Receipts - moderate left shift to prevent edge cutoff */
            body.is-receipt .print-container {
                margin: 0 !important;
                padding: 0 37px 0 0 !important;
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

    // Dynamically add class based on print type
    const isLabel = window.location.pathname.includes('/barcode/print');
    document.body.classList.add(isLabel ? 'is-barcode' : 'is-receipt');

    const notifyParent = (type, message = '') => {
        if (!embedded || window.parent === window) return;
        try {
            window.parent.postMessage({ type, invoiceNo, message, jobId }, window.location.origin);
        } catch (e) {}
    };

    const log = (msg) => {
        if (window.electronAPI && typeof window.electronAPI.logPrint === 'function') {
            window.electronAPI.logPrint(msg);
        }
    };

    const finish = (type = 'pos-print-complete', message = '') => {
        log('finish() called with type: "' + type + '", message: "' + message + '"');
        if (completed) return;
        completed = true;

        if (embedded) {
            notifyParent(type, message);
            setTimeout(() => { window.location.replace('about:blank'); }, 20);
            return;
        }

        if (selfClose) {
            try { window.open('', '_self'); window.close(); } catch (e) {}
            return;
        }

        if (returnTo) { window.location.replace(returnTo); }
    };

    window.addEventListener('load', () => {
        log('Load event fired. pathname: ' + window.location.pathname + ', search: ' + window.location.search);
        if (shouldPrint) {
            log('Initiating automatic silent printing...');
            setTimeout(() => {
                try {
                    if (window.electronAPI) {
                        let printerName = isLabel
                            ? (<?= json_encode(\App\Services\SettingsService::get('label_printer', '')) ?> || <?= json_encode(\App\Services\SettingsService::get('default_printer', '')) ?>)
                            : <?= json_encode(\App\Services\SettingsService::get('default_printer', '')) ?>;

                        log('Electron environment detected. Printer: "' + (printerName || 'default') + '", isLabel: ' + isLabel);
                        // Pass isLabel as second parameter so Electron uses the correct page size
                        window.electronAPI.printSilent(printerName, isLabel);

                        // In Electron: onPrintFinished handles everything.
                        // Register the callback here (inside the setTimeout) so it fires
                        // after the print job is confirmed finished by the print engine.
                        if (typeof window.electronAPI.onPrintFinished === 'function') {
                            log('Registering onPrintFinished callback listener...');
                            window.electronAPI.onPrintFinished((data) => {
                                log('onPrintFinished triggered. success: ' + data.success + ', error: ' + (data.error || 'none'));
                                if (!completed) {
                                    if (data.success) {
                                        finish('pos-print-complete');
                                    } else {
                                        finish('pos-print-error', 'فشلت الطباعة: ' + data.error);
                                    }
                                    // Close this popup window after Electron finishes printing
                                    if (selfClose || embedded) {
                                        try { window.open('', '_self'); window.close(); } catch (e) {}
                                    }
                                }
                            });
                        } else {
                            log('No onPrintFinished callback found in electronAPI. Assuming success in 2000ms.');
                            // No callback available — assume success after a safe delay
                            setTimeout(() => { finish('pos-print-complete'); }, 2000);
                        }

                    } else {
                        log('Non-Electron environment. Using browser window.print()');
                        // Non-Electron browser fallback
                        window.print();

                        if (embedded) { setTimeout(() => { finish('pos-print-complete'); }, 2500); return; }
                        if (returnTo)  { setTimeout(() => { finish('pos-print-complete'); }, 900);  return; }
                        if (selfClose) { setTimeout(() => { finish('pos-print-complete'); }, 900);  }
                    }
                } catch (e) {
                    log('Exception caught in print loop: ' + e.message);
                    finish('pos-print-error', 'تعذر بدء الطباعة: ' + e.message);
                }
            }, 400);

            return; // All paths handled above
        }
        log('shouldPrint is false. Finalizing pos-print-complete.');
        finish('pos-print-complete');
    });

    window.addEventListener('afterprint', () => {
        log('afterprint event fired');
        if (!window.electronAPI) {
            finish('pos-print-complete');
        } else {
            log('afterprint event ignored inside Electron to prevent premature window closure (waiting for onPrintFinished)');
        }
    });
})();
</script>
</body>
</html>
