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

class PurchaseModel extends Model
{
    public function list(array $filters = []): array
    {
        $sql = 'SELECT p.*, s.name AS supplier_name, u.full_name AS user_name
                FROM purchase_invoices p
                JOIN suppliers s ON s.id = p.supplier_id
                JOIN users u ON u.id = p.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(p.invoice_date) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(p.invoice_date) <= :to';
            $params['to'] = $filters['to'];
        }

        $sql .= ' ORDER BY p.id DESC LIMIT 500';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, s.name AS supplier_name, u.full_name AS user_name, pm.name AS payment_method_name
             FROM purchase_invoices p
             JOIN suppliers s ON s.id = p.supplier_id
             JOIN users u ON u.id = p.user_id
             LEFT JOIN payment_methods pm ON pm.id = p.payment_method_id
             WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            return null;
        }

        $itemsStmt = $this->db->prepare(
            'SELECT i.*, pr.name AS product_name, un.short_name AS unit_name
             FROM purchase_invoice_items i
             JOIN products pr ON pr.id = i.product_id
             LEFT JOIN units un ON un.id = i.unit_id
             WHERE i.purchase_invoice_id = :id'
        );
        $itemsStmt->execute(['id' => $id]);
        $invoice['items'] = $itemsStmt->fetchAll();

        return $invoice;
    }

    public function create(array $data): array
    {
        if (empty($data['items'])) {
            throw new RuntimeException('أضف صنفًا واحدًا على الأقل');
        }

        $this->db->beginTransaction();
        try {
            $invoiceNo = SequenceService::next('purchase_invoice');
            $totals = $this->calculateTotals($data['items']);
            $paid = min((float) ($data['paid_total'] ?? 0), $totals['grand_total']);
            $due = $totals['grand_total'] - $paid;
            $paymentStatus = $due <= 0.0001 ? 'paid' : ($paid > 0 ? 'partial' : 'due');
            $status = ($data['status'] ?? 'approved') === 'draft' ? 'draft' : 'approved';

            $ins = $this->db->prepare(
                'INSERT INTO purchase_invoices
                 (invoice_no, supplier_invoice_no, supplier_id, user_id, warehouse_id, invoice_date, status,
                  subtotal, discount_total, tax_total, grand_total, paid_total, due_total, payment_status,
                  payment_method_id, approved_at, approved_by, note)
                 VALUES
                 (:invoice_no, :supplier_invoice_no, :supplier_id, :user_id, :warehouse_id, datetime(\'now\', \'localtime\'), :status,
                  :subtotal, :discount_total, :tax_total, :grand_total, :paid_total, :due_total, :payment_status,
                  :payment_method_id, :approved_at, :approved_by, :note)'
            );
            $ins->execute([
                'invoice_no' => $invoiceNo,
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null,
                'supplier_id' => (int) $data['supplier_id'],
                'user_id' => AuthService::id(),
                'warehouse_id' => (int) $data['warehouse_id'],
                'status' => $status,
                'subtotal' => $totals['subtotal'],
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => $totals['grand_total'],
                'paid_total' => $paid,
                'due_total' => $due,
                'payment_status' => $paymentStatus,
                'payment_method_id' => !empty($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
                'approved_at' => $status === 'approved' ? date('Y-m-d H:i:s') : null,
                'approved_by' => $status === 'approved' ? AuthService::id() : null,
                'note' => $data['note'] ?? null,
            ]);

            $invoiceId = (int) $this->db->lastInsertId();

            $itemIns = $this->db->prepare(
                'INSERT INTO purchase_invoice_items
                 (purchase_invoice_id, product_id, unit_id, qty, purchase_unit, stock_qty, unit_price, discount_amount, tax_amount, line_total)
                 VALUES
                 (:purchase_invoice_id, :product_id, :unit_id, :qty, :purchase_unit, :stock_qty, :unit_price, :discount_amount, :tax_amount, :line_total)'
            );

            foreach ($data['items'] as $item) {
                $product = $this->product((int) $item['product_id']);
                if (!$product) {
                    throw new RuntimeException('صنف غير موجود');
                }

                $qty = (float) $item['qty'];
                $unitPrice = (float) $item['unit_price'];
                $purchaseUnit = $this->normalizeLineUnit($product, (string) ($item['purchase_unit'] ?? ''));
                $factor = $this->lineUnitFactor($product, $purchaseUnit);
                $stockQty = $qty * $factor;

                $itemIns->execute([
                    'purchase_invoice_id' => $invoiceId,
                    'product_id' => $product['id'],
                    'unit_id' => $product['unit_id'] ?: null,
                    'qty' => $qty,
                    'purchase_unit' => $purchaseUnit,
                    'stock_qty' => $stockQty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $qty * $unitPrice,
                ]);

                if ($status === 'approved' && $product['track_stock']) {
                    StockService::move(
                        (int) $data['warehouse_id'],
                        (int) $product['id'],
                        'purchase',
                        $stockQty,
                        0,
                        $unitPrice,
                        'purchase_invoices',
                        $invoiceId,
                        'اعتماد شراء ' . $invoiceNo,
                        AuthService::id()
                    );
                }
            }

            if ($status === 'approved') {
                if (!empty($data['payment_method_id']) && $paid > 0) {
                    $pay = $this->db->prepare('INSERT INTO purchase_invoice_payments (purchase_invoice_id, payment_method_id, amount) VALUES (:invoice_id,:method,:amount)');
                    $pay->execute([
                        'invoice_id' => $invoiceId,
                        'method' => (int) $data['payment_method_id'],
                        'amount' => $paid,
                    ]);
                }

                LedgerService::supplier((int) $data['supplier_id'], 'purchase_invoice', $totals['grand_total'], $paid, 'purchase_invoices', $invoiceId, 'فاتورة شراء');
                if ($paid > 0) {
                    $openShift = CashService::openShiftForUser((int) AuthService::id()) ?: CashService::openAnyShift();
                    CashService::movement(
                        $openShift ? (int) $openShift['id'] : null,
                        AuthService::id(),
                        'purchase_payment',
                        'out',
                        $paid,
                        'purchase_invoices',
                        $invoiceId,
                        'سداد فاتورة شراء'
                    );
                }
            }

            LogService::audit('purchase_invoices', $invoiceId, $status === 'approved' ? 'approve' : 'insert', null, ['invoice_no' => $invoiceNo, 'status' => $status]);
            LogService::activity(AuthService::id(), 'purchase.create', 'إنشاء فاتورة شراء ' . $invoiceNo);
            $this->db->commit();
            return ['id' => $invoiceId, 'invoice_no' => $invoiceNo];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateDraft(int $id, array $data): void
    {
        if (empty($data['items'])) {
            throw new RuntimeException('أضف صنفًا واحدًا على الأقل');
        }

        $invoice = $this->find($id);
        if (!$invoice) {
            throw new RuntimeException('الفاتورة غير موجودة');
        }
        if ($invoice['status'] !== 'draft') {
            throw new RuntimeException('لا يمكن تعديل فاتورة بعد الاعتماد');
        }

        $totals = $this->calculateTotals($data['items']);
        $paid = min((float) ($data['paid_total'] ?? 0), $totals['grand_total']);
        $due = $totals['grand_total'] - $paid;
        $paymentStatus = $due <= 0.0001 ? 'paid' : ($paid > 0 ? 'partial' : 'due');

        $this->db->beginTransaction();
        try {
            $upd = $this->db->prepare(
                'UPDATE purchase_invoices
                 SET supplier_invoice_no = :supplier_invoice_no,
                     supplier_id = :supplier_id,
                     warehouse_id = :warehouse_id,
                     subtotal = :subtotal,
                     discount_total = :discount_total,
                     tax_total = :tax_total,
                     grand_total = :grand_total,
                     paid_total = :paid_total,
                     due_total = :due_total,
                     payment_status = :payment_status,
                     payment_method_id = :payment_method_id,
                     note = :note
                 WHERE id = :id AND status = "draft"'
            );
            $upd->execute([
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null,
                'supplier_id' => (int) $data['supplier_id'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'subtotal' => $totals['subtotal'],
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => $totals['grand_total'],
                'paid_total' => $paid,
                'due_total' => $due,
                'payment_status' => $paymentStatus,
                'payment_method_id' => !empty($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
                'note' => $data['note'] ?? null,
                'id' => $id,
            ]);

            $this->db->prepare('DELETE FROM purchase_invoice_items WHERE purchase_invoice_id = :id')->execute(['id' => $id]);
            $this->db->prepare('DELETE FROM purchase_invoice_payments WHERE purchase_invoice_id = :id')->execute(['id' => $id]);

            $itemIns = $this->db->prepare(
                'INSERT INTO purchase_invoice_items
                 (purchase_invoice_id, product_id, unit_id, qty, purchase_unit, stock_qty, unit_price, discount_amount, tax_amount, line_total)
                 VALUES
                 (:purchase_invoice_id, :product_id, :unit_id, :qty, :purchase_unit, :stock_qty, :unit_price, :discount_amount, :tax_amount, :line_total)'
            );

            foreach ($data['items'] as $item) {
                $product = $this->product((int) $item['product_id']);
                if (!$product) {
                    throw new RuntimeException('صنف غير موجود');
                }

                $qty = (float) $item['qty'];
                $unitPrice = (float) $item['unit_price'];
                $purchaseUnit = $this->normalizeLineUnit($product, (string) ($item['purchase_unit'] ?? ''));
                $factor = $this->lineUnitFactor($product, $purchaseUnit);
                $stockQty = $qty * $factor;

                $itemIns->execute([
                    'purchase_invoice_id' => $id,
                    'product_id' => $product['id'],
                    'unit_id' => $product['unit_id'] ?: null,
                    'qty' => $qty,
                    'purchase_unit' => $purchaseUnit,
                    'stock_qty' => $stockQty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $qty * $unitPrice,
                ]);
            }

            LogService::audit('purchase_invoices', $id, 'update', ['status' => 'draft'], [
                'supplier_id' => (int) $data['supplier_id'],
                'grand_total' => $totals['grand_total'],
                'paid_total' => $paid,
            ]);
            LogService::activity(AuthService::id(), 'purchase.update_draft', 'تعديل فاتورة شراء مسودة ' . $invoice['invoice_no']);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function approve(int $id): void
    {
        $invoice = $this->find($id);
        if (!$invoice) {
            throw new RuntimeException('الفاتورة غير موجودة');
        }
        if ($invoice['status'] !== 'draft') {
            throw new RuntimeException('هذه الفاتورة ليست في حالة مسودة');
        }

        $this->db->beginTransaction();
        try {
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
                        'purchase',
                        $stockQty,
                        0,
                        (float) $item['unit_price'],
                        'purchase_invoices',
                        $id,
                        'اعتماد شراء ' . $invoice['invoice_no'],
                        AuthService::id()
                    );
                }
            }

            $upd = $this->db->prepare('UPDATE purchase_invoices SET status = "approved", approved_at = datetime(\'now\', \'localtime\'), approved_by = :user WHERE id = :id');
            $upd->execute(['user' => AuthService::id(), 'id' => $id]);

            if (!empty($invoice['payment_method_id']) && (float) $invoice['paid_total'] > 0) {
                $this->db->prepare('DELETE FROM purchase_invoice_payments WHERE purchase_invoice_id = :invoice_id')->execute(['invoice_id' => $id]);
                $pay = $this->db->prepare('INSERT INTO purchase_invoice_payments (purchase_invoice_id, payment_method_id, amount) VALUES (:invoice_id,:method,:amount)');
                $pay->execute([
                    'invoice_id' => $id,
                    'method' => (int) $invoice['payment_method_id'],
                    'amount' => (float) $invoice['paid_total'],
                ]);
            }

            LedgerService::supplier((int) $invoice['supplier_id'], 'purchase_invoice', (float) $invoice['grand_total'], (float) $invoice['paid_total'], 'purchase_invoices', $id, 'اعتماد فاتورة شراء');

            if ((float) $invoice['paid_total'] > 0) {
                $openShift = CashService::openShiftForUser((int) AuthService::id()) ?: CashService::openAnyShift();
                CashService::movement(
                    $openShift ? (int) $openShift['id'] : null,
                    AuthService::id(),
                    'purchase_payment',
                    'out',
                    (float) $invoice['paid_total'],
                    'purchase_invoices',
                    $id,
                    'سداد شراء بعد الاعتماد'
                );
            }

            LogService::audit('purchase_invoices', $id, 'approve', ['status' => 'draft'], ['status' => 'approved']);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function calculateTotals(array $items): array
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            if ($qty <= 0) {
                throw new RuntimeException('كمية غير صحيحة');
            }
            $subtotal += $qty * $price;
        }

        return [
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => $subtotal,
        ];
    }

    private function product(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
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
}
