<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CustomerModel;
use App\Models\ProductModel;
use App\Models\SalesModel;
use App\Services\ScaleBarcodeParser;
use App\Services\SettingsService;

class PosController extends Controller
{
    public function index(): void
    {
        $productModel = new ProductModel();
        $customerModel = new CustomerModel();

        $products = $productModel->all('');
        $customerModel->ensureDefaultCashCustomer();
        $customers = $customerModel->all('');
        $defaultCustomerId = $customerModel->cashCustomerId();
        $resumePayload = ['customer_id' => null, 'payload_json' => null];

        $paymentMethods = \App\Core\Database::pdo()->query('SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id')->fetchAll();
        $openShift = \App\Services\CashService::openShiftForUser((int) current_user()['id']);

        $this->view('pos/index', compact('products', 'customers', 'paymentMethods', 'openShift', 'resumePayload', 'defaultCustomerId'));
    }

    public function search(): void
    {
        $term = trim((string) ($_GET['q'] ?? ''));
        $model = new ProductModel();
        $rows = [];
        $normalRows = $term !== '' ? $model->findByBarcodeOrName($term) : [];

        $parser = new ScaleBarcodeParser();
        if ($parser->isScaleBarcode($term)) {
            try {
                $parsed = $parser->parse($term);
                $product = $parser->resolveProduct($parsed);
                if (!$product) {
                    $parsedItemCode = (string) ($parsed['item_code_raw'] ?? '');
                    $notFoundMessage = $parsedItemCode !== ''
                        ? 'كود الميزان ' . $parsedItemCode . ' غير مربوط بأي صنف. اربطه في حقل "كود الصنف داخل الميزان" بالصنف الصحيح'
                        : 'كود الميزان غير مربوط بأي صنف';
                    if ($this->hasExactScanMatch($normalRows, $term)) {
                        $this->json(['data' => $normalRows]);
                        return;
                    }
                    $parser->logParseAttempt([
                        'barcode' => $term,
                        'parsed_item_code' => $parsed['item_code_raw'] ?? null,
                        'parsed_weight' => $parsed['weight'] ?? null,
                        'parsed_price' => $parsed['price'] ?? null,
                        'status' => 'not_found',
                        'error_message' => $notFoundMessage,
                    ]);
                    $this->json(['error' => $notFoundMessage], 422);
                    return;
                }

                if (!$parser->shouldAllowScaleSale($product)) {
                    if ($this->hasExactScanMatch($normalRows, $term)) {
                        $this->json(['data' => $normalRows]);
                        return;
                    }
                    $parser->logParseAttempt([
                        'barcode' => $term,
                        'product_id' => (int) $product['id'],
                        'parsed_item_code' => $parsed['item_code_raw'] ?? null,
                        'parsed_weight' => $parsed['weight'] ?? null,
                        'parsed_price' => $parsed['price'] ?? null,
                        'status' => 'product_not_weight',
                        'error_message' => 'هذا الصنف غير مفعل للبيع بالوزن',
                    ]);
                    $this->json(['error' => 'هذا الصنف غير مفعل للبيع بالوزن'], 422);
                    return;
                }

                $line = $parser->calculateLine($product, $parsed);

                $lookupStmt = \App\Core\Database::pdo()->prepare(
                    'SELECT p.id, p.name, p.barcode, p.sale_price, p.wholesale_price, p.purchase_price,
                            p.package_type, p.package_size, p.scale_code, p.allow_scale_barcode, p.weight_unit,
                            p.sell_type, p.track_stock, p.unit_id, u.short_name,
                            COALESCE(sm.balance_after, 0) AS current_stock
                     FROM products p
                     LEFT JOIN units u ON u.id = p.unit_id
                     LEFT JOIN (
                        SELECT s1.product_id, s1.balance_after
                        FROM stock_movements s1
                        INNER JOIN (
                            SELECT product_id, MAX(id) AS max_id FROM stock_movements GROUP BY product_id
                        ) s2 ON s1.id = s2.max_id
                     ) sm ON sm.product_id = p.id
                     WHERE p.id = :id
                     LIMIT 1'
                );
                $lookupStmt->execute(['id' => (int) $product['id']]);
                $row = $lookupStmt->fetch();
                if (!$row) {
                    throw new \RuntimeException('الصنف المرتبط بباركود الميزان غير متاح الآن');
                }
                $rows = [$row];

                $parser->logParseAttempt([
                    'barcode' => $term,
                    'product_id' => (int) $product['id'],
                    'parsed_item_code' => $parsed['item_code_raw'] ?? null,
                    'parsed_weight' => $line['scale_weight'] ?? null,
                    'parsed_price' => $line['scale_price'] ?? null,
                    'status' => 'ok',
                    'error_message' => null,
                ]);

                $this->json([
                    'data' => $rows,
                    'scale' => [
                        'is_scale' => true,
                        'barcode' => $term,
                        'product_id' => (int) $product['id'],
                        'parsed_item_code' => $parsed['item_code_raw'] ?? '',
                        'qty' => (float) $line['qty'],
                        'unit_price' => (float) $line['unit_price'],
                        'line_total' => (float) $line['line_total'],
                        'is_scale_item' => 1,
                        'scale_weight' => (float) $line['scale_weight'],
                        'scale_price' => $line['scale_price'] !== null ? (float) $line['scale_price'] : null,
                    ],
                ]);
                return;
            } catch (\RuntimeException $e) {
                if ($this->hasExactScanMatch($normalRows, $term)) {
                    $this->json(['data' => $normalRows]);
                    return;
                }
                $parser->logParseAttempt([
                    'barcode' => $term,
                    'parsed_item_code' => null,
                    'parsed_weight' => null,
                    'parsed_price' => null,
                    'status' => 'parse_error',
                    'error_message' => $e->getMessage(),
                ]);
                $this->json(['error' => $e->getMessage()], 422);
                return;
            }
        }

        $looksLikeScaleBarcode = preg_match('/^\d{13}$/', $term) === 1
            && (str_starts_with($term, '20') || str_starts_with($term, '28'));
        $scaleBarcodeEnabled = SettingsService::get('scale_barcode_enabled', '0') === '1';
        if (
            $term !== ''
            && !$scaleBarcodeEnabled
            && !$this->hasExactScanMatch($normalRows, $term)
            && $looksLikeScaleBarcode
        ) {
            $this->json(['error' => 'باركود الميزان ظاهر لكن الميزة غير مفعلة من الإعدادات'], 422);
            return;
        }

        $rows = $normalRows;

        $this->json(['data' => $rows]);
    }

    public function sell(): void
    {
        validate_csrf_or_abort();
        $itemsJson = input('items_json', '[]');
        $items = json_decode((string) $itemsJson, true) ?: [];
        $paymentBreakdown = json_decode((string) input('payment_breakdown_json', '[]'), true) ?: [];
        $quickAction = trim((string) input('quick_action', ''));
        $printTransport = trim((string) input('print_transport', ''));
        $printJobId = trim((string) input('print_job_id', ''));
        $isIframePrint = $quickAction === 'print' && $printTransport === 'iframe';
        $isQzPrint = $quickAction === 'print' && $printTransport === 'qz';
        $isPopupPrint = $quickAction === 'print' && $printTransport === 'popup';
        $paidTotal = (float) input('paid_total', 0);
        $paymentMethodId = input('payment_method_id') ? (int) input('payment_method_id') : null;
        $paymentMethodCode = '';
        if ($paymentMethodId) {
            $pm = \App\Core\Database::pdo()->query('SELECT code FROM payment_methods WHERE id = ' . (int)$paymentMethodId)->fetch();
            $paymentMethodCode = $pm['code'] ?? '';
        }

        if ($paidTotal <= 0 && $paymentMethodCode !== 'credit' && $paymentMethodCode !== 'mixed') {
            $autoPaid = 0.0;
            foreach ($items as $item) {
                $autoPaid += (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0);
            }
            $paidTotal = max(0, $autoPaid);
        }

        try {
            $model = new SalesModel();
            $result = $model->createFromPos([
                'warehouse_id' => (int) \App\Services\SettingsService::get('default_warehouse_id', '1'),
                'customer_id' => (int) input('customer_id', 1),
                'shift_id' => input('shift_id') ? (int) input('shift_id') : null,
                'payment_method_id' => input('payment_method_id') ? (int) input('payment_method_id') : null,
                'paid_total' => $paidTotal,
                'payment_breakdown' => $paymentBreakdown,
                'note' => trim((string) input('note')),
                'items' => $items,
            ]);

            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                      || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'تم حفظ ودفع الفاتورة رقم ' . $result['invoice_no'],
                    'invoice_id' => $result['id'],
                    'invoice_no' => $result['invoice_no'],
                ]);
                return;
            }

            if (!$isIframePrint && !$isQzPrint && !$isPopupPrint) {
                flash_success('تم حفظ الفاتورة رقم ' . $result['invoice_no']);
            }
            if ($quickAction === 'save') {
                $this->redirect('/pos');
            }

            if ($quickAction === 'print') {
                if ($isQzPrint) {
                    $printPath = url('/sales/' . $result['id'] . '/print?autoprint=0');
                    $printUrl = $this->absoluteUrl($printPath);
                    $this->emitIframePrintMessage('pos-qz-print', '', $printJobId, [
                        'invoiceId' => (int) $result['id'],
                        'invoiceNo' => (string) $result['invoice_no'],
                        'printUrl' => $printUrl,
                    ]);
                    return;
                }
                if ($isIframePrint) {
                    $query = http_build_query([
                        'autoprint' => 1,
                        'embedded' => 1,
                        'invoice_no' => (string) $result['invoice_no'],
                        'job_id' => $printJobId,
                    ]);
                    $this->redirect('/sales/' . $result['id'] . '/print?' . $query);
                    return;
                }
                if ($isPopupPrint) {
                    $printPath = url('/sales/' . $result['id'] . '/print?autoprint=1');
                    $printUrl = $this->absoluteUrl($printPath);
                    $this->emitIframePrintMessage('pos-browser-print', '', $printJobId, [
                        'invoiceId' => (int) $result['id'],
                        'invoiceNo' => (string) $result['invoice_no'],
                        'printUrl' => $printUrl,
                    ]);
                    return;
                }
                // Fallback to embedded browser print without opening invoice preview page.
                $query = http_build_query([
                    'autoprint' => 1,
                    'embedded' => 1,
                    'invoice_no' => (string) $result['invoice_no'],
                    'job_id' => $printJobId,
                ]);
                $this->redirect('/sales/' . $result['id'] . '/print?' . $query);
                return;
            }

            $this->redirect('/sales/' . $result['id']);
        } catch (\RuntimeException $e) {
            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                      || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
            if ($isAjax) {
                $this->json(['success' => false, 'error' => $e->getMessage()], 422);
                return;
            }
            if ($isIframePrint || $isQzPrint || $isPopupPrint) {
                $this->emitIframePrintMessage('pos-print-error', $e->getMessage(), $printJobId);
            }
            flash_error($e->getMessage());
            $this->redirect('/pos');
        } catch (\Throwable $e) {
            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                      || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
            if ($isAjax) {
                $this->json(['success' => false, 'error' => 'تعذر حفظ الفاتورة الآن، راجع إعدادات الفرع والمخزن.'], 500);
                return;
            }
            if ($isIframePrint || $isQzPrint || $isPopupPrint) {
                $this->emitIframePrintMessage('pos-print-error', 'تعذر حفظ الفاتورة الآن، راجع إعدادات الفرع والمخزن.', $printJobId);
            }
            flash_error('تعذر حفظ الفاتورة الآن، راجع إعدادات الفرع والمخزن.');
            $this->redirect('/pos');
        }
    }

    public function hold(): void
    {
        validate_csrf_or_abort();
        $payload = input('items_json', '[]');
        $holdNo = null;

        $db = \App\Core\Database::pdo();
        $db->beginTransaction();
        try {
            $holdNo = \App\Services\SequenceService::next('sale_hold');
            $stmt = $db->prepare('INSERT INTO sale_suspensions (hold_no, user_id, customer_id, payload_json) VALUES (:hold_no,:user_id,:customer_id,:payload_json)');
            $stmt->execute([
                'hold_no' => $holdNo,
                'user_id' => current_user()['id'],
                'customer_id' => input('customer_id') ? (int) input('customer_id') : null,
                'payload_json' => (string) $payload,
            ]);
            $db->commit();
            
            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                      || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'تم تعليق الفاتورة: ' . $holdNo,
                    'hold_no' => $holdNo,
                ]);
                return;
            }

            flash_success('تم تعليق الفاتورة: ' . $holdNo);
        } catch (\Throwable $e) {
            $db->rollBack();
            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                      || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
            if ($isAjax) {
                $this->json(['success' => false, 'error' => $e->getMessage()], 500);
                return;
            }
            throw $e;
        }

        $this->redirect('/pos');
    }

    public function suspended(): void
    {
        $rows = \App\Core\Database::pdo()->query(
            'SELECT s.*, c.name AS customer_name
             FROM sale_suspensions s
             LEFT JOIN customers c ON c.id = s.customer_id
             ORDER BY s.id DESC LIMIT 200'
        )->fetchAll();

        $this->view('pos/suspended', compact('rows'));
    }

    public function resume(string $id): void
    {
        $stmt = \App\Core\Database::pdo()->prepare('SELECT * FROM sale_suspensions WHERE id = :id');
        $stmt->execute(['id' => (int) $id]);
        $row = $stmt->fetch();
        if (!$row) {
            flash_error('الفاتورة المعلقة غير موجودة');
            $this->redirect('/pos/suspended');
        }

        $customerModel = new CustomerModel();
        $products = (new ProductModel())->all('');
        $customerModel->ensureDefaultCashCustomer();
        $customers = $customerModel->all('');
        $defaultCustomerId = $customerModel->cashCustomerId();
        $paymentMethods = \App\Core\Database::pdo()->query('SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id')->fetchAll();
        $openShift = \App\Services\CashService::openShiftForUser((int) current_user()['id']);
        $resumePayload = $row;

        $this->view('pos/index', compact('products', 'customers', 'paymentMethods', 'openShift', 'resumePayload', 'defaultCustomerId'));
    }

    public function removeSuspended(string $id): void
    {
        validate_csrf_or_abort();
        $stmt = \App\Core\Database::pdo()->prepare('DELETE FROM sale_suspensions WHERE id = :id');
        $stmt->execute(['id' => (int) $id]);

        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                  || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
        if ($isAjax) {
            $this->json([
                'success' => true,
                'message' => 'تم حذف الفاتورة المعلقة'
            ]);
            return;
        }

        flash_success('تم حذف الفاتورة المعلقة');
        $this->redirect('/pos/suspended');
    }

    private function emitIframePrintMessage(string $type, string $message = '', string $jobId = '', array $extra = []): void
    {
        $payload = [
            'type' => $type,
            'message' => $message,
            'jobId' => $jobId,
        ];
        if (!empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"></head><body>';
        echo '<script>(function(){';
        echo 'var payload=' . $json . ';';
        echo 'var delivered=false;';
        echo 'try { if (window.parent && window.parent !== window) { window.parent.postMessage(payload, window.location.origin); delivered=true; } } catch (e) {}';
        echo 'if (!delivered) { try { if (window.opener && !window.opener.closed) { window.opener.postMessage(payload, window.location.origin); delivered=true; } } catch (e) {} }';
        echo 'if (window.parent && window.parent !== window) { window.location.replace("about:blank"); return; }';
        echo 'if (window.opener && !window.opener.closed) { try { window.close(); } catch (e) {} }';
        echo '})();</script>';
        echo '</body></html>';
        exit;
    }

    private function absoluteUrl(string $path): string
    {
        $scheme = 'http';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';

        return $scheme . '://' . $host . $path;
    }

    private function hasExactScanMatch(array $rows, string $term): bool
    {
        foreach ($rows as $row) {
            if (
                (string) ($row['barcode'] ?? '') === $term
                || (string) ($row['sku'] ?? '') === $term
                || (string) ($row['internal_code'] ?? '') === $term
                || (string) ($row['scale_code'] ?? '') === $term
            ) {
                return true;
            }
        }

        return false;
    }
}
