<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use App\Services\AuthService;
use App\Services\CashService;
use App\Services\LedgerService;
use App\Services\LogService;
use App\Services\SequenceService;
use App\Services\SettingsService;
use App\Services\StockService;
use RuntimeException;

class SalesModel extends Model
{
    public function list(array $filters = []): array
    {
        $sql = 'SELECT s.*, c.name AS customer_name, u.full_name AS user_name
                FROM sales_invoices s
                JOIN customers c ON c.id = s.customer_id
                JOIN users u ON u.id = s.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(s.invoice_date) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(s.invoice_date) <= :to';
            $params['to'] = $filters['to'];
        }
        if (!empty($filters['customer_id'])) {
            $sql .= ' AND s.customer_id = :customer_id';
            $params['customer_id'] = (int) $filters['customer_id'];
        }

        $sql .= ' ORDER BY s.id DESC LIMIT 500';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, c.name AS customer_name, c.phone AS customer_phone, c.is_cash_customer,
                    u.full_name AS user_name, pm.name AS payment_method_name
             FROM sales_invoices s
             JOIN customers c ON c.id = s.customer_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN payment_methods pm ON pm.id = s.payment_method_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            return null;
        }

        $items = $this->db->prepare(
            'SELECT i.*, p.name AS product_name, p.sell_type AS product_sell_type, p.weight_unit AS product_weight_unit, u.short_name AS unit_name
             FROM sales_invoice_items i
             JOIN products p ON p.id = i.product_id
             LEFT JOIN units u ON u.id = i.unit_id
             WHERE i.sales_invoice_id = :id'
        );
        $items->execute(['id' => $id]);
        $invoice['items'] = $items->fetchAll();

        return $invoice;
    }

    public function createFromPos(array $data): array
    {
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new RuntimeException('لا توجد أصناف في الفاتورة');
        }

        $requireShift = SettingsService::get('require_shift_for_sale', '1') === '1';
        if ($requireShift && empty($data['shift_id'])) {
            throw new RuntimeException('يجب فتح شيفت قبل البيع');
        }

        $this->db->beginTransaction();
        try {
            $invoiceNo = SequenceService::next('sales_invoice');
            $userId = (int) AuthService::id();
            $warehouseId = $this->resolveWarehouseId((int) ($data['warehouse_id'] ?? 0));
            $branchId = $this->resolveBranchId();
            $customerId = (int) $data['customer_id'];
            $isCashCustomer = $this->isCashCustomer($customerId);
            $shiftId = !empty($data['shift_id']) ? (int) $data['shift_id'] : null;
            $paymentMethodId = !empty($data['payment_method_id']) ? (int) $data['payment_method_id'] : null;
            $paymentMethodCode = $paymentMethodId ? $this->paymentMethodCode($paymentMethodId) : null;
            if ($paymentMethodId && !$paymentMethodCode) {
                throw new RuntimeException('طريقة الدفع غير صالحة');
            }
            if ($isCashCustomer && $paymentMethodCode === 'credit') {
                throw new RuntimeException('الدفع الآجل متاح فقط للعملاء المسجلين');
            }

            $totals = $this->calculateTotals($data['items']);

            $requestedPaid = (float) ($data['paid_total'] ?? 0);
            $isCod = !empty($data['is_cod']); // طلب دفع عند الاستلام (COD)
            if ($requestedPaid <= 0 && !$isCod && $paymentMethodCode !== 'credit' && $paymentMethodCode !== 'mixed') {
                $requestedPaid = $totals['grand_total'];
            }
            $paymentBreakdown = [];
            $breakdownTotal = 0.0;
            if ($paymentMethodCode === 'mixed') {
                $paymentBreakdown = $this->normalizePaymentBreakdown($data['payment_breakdown'] ?? []);
                if (empty($paymentBreakdown)) {
                    throw new RuntimeException('أدخل تفاصيل الدفع المختلط');
                }
                if ($isCashCustomer) {
                    foreach ($paymentBreakdown as $payment) {
                        $code = $this->paymentMethodCode((int) $payment['payment_method_id']);
                        if ($code === 'credit') {
                            throw new RuntimeException('الدفع الآجل متاح فقط للعملاء المسجلين');
                        }
                    }
                }
                $breakdownTotal = array_sum(array_column($paymentBreakdown, 'amount'));
                if ($requestedPaid <= 0) {
                    $requestedPaid = $breakdownTotal;
                }
                if (abs($breakdownTotal - $requestedPaid) > 0.01) {
                    throw new RuntimeException('مجموع الدفع المختلط لا يساوي قيمة المدفوع');
                }
            }

            $paid = min($requestedPaid, $totals['grand_total']);
            if ($paymentMethodCode === 'mixed' && abs($breakdownTotal - $paid) > 0.01) {
                throw new RuntimeException('الدفع المختلط يجب أن يطابق المدفوع بالضبط');
            }
            $due = $totals['grand_total'] - $paid;
            if ($isCashCustomer && $due > 0.0001 && !$isCod) {
                throw new RuntimeException('البيع الآجل متاح فقط للعملاء المسجلين');
            }
            $paymentStatus = $due <= 0.0001 ? 'paid' : ($paid > 0 ? 'partial' : 'due');

            $ins = $this->db->prepare(
                'INSERT INTO sales_invoices
                 (invoice_no, branch_id, warehouse_id, shift_id, user_id, customer_id, invoice_date, status,
                  subtotal, discount_total, tax_total, grand_total, paid_total, due_total, payment_status,
                  payment_method_id, note)
                 VALUES
                 (:invoice_no, :branch_id, :warehouse_id, :shift_id, :user_id, :customer_id, datetime(\'now\', \'localtime\'), "posted",
                  :subtotal, :discount_total, :tax_total, :grand_total, :paid_total, :due_total, :payment_status,
                  :payment_method_id, :note)'
            );
            $ins->execute([
                'invoice_no' => $invoiceNo,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'shift_id' => $shiftId,
                'user_id' => $userId,
                'customer_id' => $customerId,
                'subtotal' => $totals['subtotal'],
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => $totals['grand_total'],
                'paid_total' => $paid,
                'due_total' => $due,
                'payment_status' => $paymentStatus,
                'payment_method_id' => $paymentMethodId,
                'note' => $data['note'] ?? null,
            ]);
            $invoiceId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                'INSERT INTO sales_invoice_items
                 (sales_invoice_id, product_id, unit_id, barcode, qty, sale_unit, stock_qty, unit_price, discount_amount, tax_amount, line_total, cost_price,
                  scanned_barcode, is_scale_item, scale_weight, scale_price)
                 VALUES
                 (:sales_invoice_id, :product_id, :unit_id, :barcode, :qty, :sale_unit, :stock_qty, :unit_price, :discount_amount, :tax_amount, :line_total, :cost_price,
                  :scanned_barcode, :is_scale_item, :scale_weight, :scale_price)'
            );

            foreach ($data['items'] as $item) {
                $product = $this->product((int) $item['product_id']);
                if (!$product) {
                    throw new RuntimeException('صنف غير موجود');
                }

                $qty = (float) $item['qty'];
                $unitPrice = (float) $item['unit_price'];
                $saleUnit = $this->normalizeLineUnit($product, (string) ($item['sale_unit'] ?? ''));
                $factor = $this->lineUnitFactor($product, $saleUnit);
                $stockQty = $qty * $factor;
                $lineTotal = $qty * $unitPrice;
                $isScaleItem = !empty($item['is_scale_item']) ? 1 : 0;
                $scaleWeight = isset($item['scale_weight']) ? (float) $item['scale_weight'] : null;
                if ($isScaleItem === 1 && $scaleWeight === null) {
                    $scaleWeight = $qty;
                }

                if ($product['track_stock'] && !StockService::canOut($warehouseId, (int) $product['id'], $stockQty)) {
                    throw new RuntimeException('مخزون غير كاف للصنف: ' . $product['name']);
                }

                $itemStmt->execute([
                    'sales_invoice_id' => $invoiceId,
                    'product_id' => (int) $product['id'],
                    'unit_id' => $product['unit_id'] ?: null,
                    'barcode' => $product['barcode'] ?: null,
                    'qty' => $qty,
                    'sale_unit' => $saleUnit,
                    'stock_qty' => $stockQty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                    'cost_price' => (float) $product['purchase_price'],
                    'scanned_barcode' => !empty($item['scanned_barcode']) ? (string) $item['scanned_barcode'] : null,
                    'is_scale_item' => $isScaleItem,
                    'scale_weight' => $scaleWeight,
                    'scale_price' => isset($item['scale_price']) ? (float) $item['scale_price'] : null,
                ]);

                if ($product['track_stock']) {
                    StockService::move(
                        $warehouseId,
                        (int) $product['id'],
                        'sale',
                        0,
                        $stockQty,
                        (float) $product['purchase_price'],
                        'sales_invoices',
                        $invoiceId,
                        'بيع فاتورة ' . $invoiceNo,
                        $userId
                    );
                }
            }

            if ($paid > 0) {
                if (!$paymentMethodId) {
                    throw new RuntimeException('حدد طريقة الدفع');
                }

                $pay = $this->db->prepare('INSERT INTO sales_invoice_payments (sales_invoice_id, payment_method_id, amount) VALUES (:invoice_id,:method,:amount)');
                if ($paymentMethodCode === 'mixed') {
                    foreach ($paymentBreakdown as $payment) {
                        $pay->execute([
                            'invoice_id' => $invoiceId,
                            'method' => (int) $payment['payment_method_id'],
                            'amount' => (float) $payment['amount'],
                        ]);
                    }
                } else {
                    $pay->execute([
                        'invoice_id' => $invoiceId,
                        'method' => $paymentMethodId,
                        'amount' => $paid,
                    ]);
                }
            }

            LedgerService::customer($customerId, 'sale_invoice', $totals['grand_total'], $paid, 'sales_invoices', $invoiceId, 'فاتورة بيع');

            if ($paid > 0) {
                CashService::movement($shiftId, $userId, 'sale', 'in', $paid, 'sales_invoices', $invoiceId, 'تحصيل فاتورة بيع');
            }

            LogService::audit('sales_invoices', $invoiceId, 'insert', null, ['invoice_no' => $invoiceNo]);
            LogService::activity($userId, 'sales.create', 'إنشاء فاتورة بيع ' . $invoiceNo);

            $this->db->commit();
            return ['id' => $invoiceId, 'invoice_no' => $invoiceNo];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function cancel(int $invoiceId): void
    {
        $invoice = $this->find($invoiceId);
        if (!$invoice) {
            throw new RuntimeException('الفاتورة غير موجودة');
        }
        if ($invoice['status'] === 'cancelled') {
            throw new RuntimeException('الفاتورة ملغاة بالفعل');
        }

        $this->db->beginTransaction();
        try {
            $upd = $this->db->prepare('UPDATE sales_invoices SET status = "cancelled", cancelled_at = datetime(\'now\', \'localtime\'), cancelled_by = :user WHERE id = :id');
            $upd->execute(['user' => AuthService::id(), 'id' => $invoiceId]);

            foreach ($invoice['items'] as $item) {
                $product = $this->product((int) $item['product_id']);
                if ($product && $product['track_stock']) {
                    $stockQty = (float) ($item['stock_qty'] ?? 0);
                    if ($stockQty <= 0) {
                        $stockQty = (float) $item['qty'];
                    }
                    StockService::move(
                        (int) $invoice['warehouse_id'],
                        (int) $item['product_id'],
                        'sales_return',
                        $stockQty,
                        0,
                        (float) $item['cost_price'],
                        'sales_invoices',
                        $invoiceId,
                        'إلغاء فاتورة ' . $invoice['invoice_no'],
                        AuthService::id()
                    );
                }
            }

            if ((float) $invoice['due_total'] > 0) {
                LedgerService::customer((int) $invoice['customer_id'], 'adjustment', 0, (float) $invoice['due_total'], 'sales_invoices', $invoiceId, 'إلغاء الفاتورة (تسوية آجل)');
            }

            if ((float) $invoice['paid_total'] > 0) {
                CashService::movement(
                    $invoice['shift_id'] ? (int) $invoice['shift_id'] : null,
                    AuthService::id(),
                    'sales_return_refund',
                    'out',
                    (float) $invoice['paid_total'],
                    'sales_invoices',
                    $invoiceId,
                    'رد مبلغ بعد إلغاء الفاتورة'
                );
            }

            LogService::audit('sales_invoices', $invoiceId, 'cancel', ['status' => 'posted'], ['status' => 'cancelled']);
            LogService::activity(AuthService::id(), 'sales.cancel', 'إلغاء فاتورة بيع ' . $invoice['invoice_no']);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function calculateTotals(array $items): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            if ($qty <= 0) {
                throw new RuntimeException('كمية غير صحيحة');
            }
            $subtotal += $qty * $price;
        }

        $grand = $subtotal;
        if ($grand < 0) {
            throw new RuntimeException('إجمالي الفاتورة غير صحيح');
        }

        return [
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => $grand,
        ];
    }

    private function product(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function paymentMethodCode(int $id): ?string
    {
        $stmt = $this->db->prepare('SELECT code FROM payment_methods WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $id]);
        $code = $stmt->fetchColumn();

        return is_string($code) ? $code : null;
    }

    private function normalizePaymentBreakdown(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            $methodId = isset($row['payment_method_id']) ? (int) $row['payment_method_id'] : 0;
            $amount = isset($row['amount']) ? (float) $row['amount'] : 0;
            if ($methodId <= 0 || $amount <= 0) {
                continue;
            }

            $code = $this->paymentMethodCode($methodId);
            if (!$code || $code === 'mixed') {
                continue;
            }

            if (!isset($normalized[$methodId])) {
                $normalized[$methodId] = 0.0;
            }
            $normalized[$methodId] += $amount;
        }

        $result = [];
        foreach ($normalized as $methodId => $amount) {
            $result[] = [
                'payment_method_id' => (int) $methodId,
                'amount' => (float) $amount,
            ];
        }

        return $result;
    }

    private function normalizeLineUnit(array $product, string $requestedUnit): string
    {
        $sellType = ($product['sell_type'] ?? 'piece') === 'weight' ? 'weight' : 'piece';
        $packageType = (string) ($product['package_type'] ?? ($sellType === 'weight' ? 'kg' : 'piece'));

        $allowed = $sellType === 'weight' ? ['kg', 'sack'] : ['piece', 'box'];
        if (in_array($requestedUnit, $allowed, true)) {
            return $requestedUnit;
        }
        if (in_array($packageType, $allowed, true)) {
            return $packageType;
        }

        return $allowed[0];
    }

    private function lineUnitFactor(array $product, string $unit): float
    {
        if (in_array($unit, ['box', 'sack'], true)) {
            $size = (float) ($product['package_size'] ?? 1);
            return $size > 0 ? $size : 1;
        }

        return 1.0;
    }

    private function resolveBranchId(): int
    {
        $configured = (int) SettingsService::get('default_branch_id', '0');
        if ($configured > 0) {
            $stmt = $this->db->prepare('SELECT id FROM branches WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $configured]);
            if ((int) $stmt->fetchColumn() > 0) {
                return $configured;
            }
        }

        $fallback = (int) $this->db->query('SELECT id FROM branches ORDER BY id ASC LIMIT 1')->fetchColumn();
        if ($fallback <= 0) {
            throw new RuntimeException('لا يوجد فرع مضاف في النظام');
        }

        SettingsService::set('default_branch_id', (string) $fallback);
        return $fallback;
    }

    private function resolveWarehouseId(int $requestedId): int
    {
        $candidate = $requestedId > 0 ? $requestedId : (int) SettingsService::get('default_warehouse_id', '0');
        if ($candidate > 0) {
            $stmt = $this->db->prepare('SELECT id FROM warehouses WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $candidate]);
            if ((int) $stmt->fetchColumn() > 0) {
                return $candidate;
            }
        }

        $fallback = (int) $this->db->query('SELECT id FROM warehouses ORDER BY is_default DESC, id ASC LIMIT 1')->fetchColumn();
        if ($fallback <= 0) {
            throw new RuntimeException('لا يوجد مخزن مضاف في النظام');
        }

        SettingsService::set('default_warehouse_id', (string) $fallback);
        return $fallback;
    }

    private function isCashCustomer(int $customerId): bool
    {
        $stmt = $this->db->prepare('SELECT is_cash_customer FROM customers WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $customerId]);
        return (int) $stmt->fetchColumn() === 1;
    }
}
