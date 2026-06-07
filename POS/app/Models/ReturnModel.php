<?php

namespace App\Models;

use App\Core\Model;
use App\Services\AuthService;
use App\Services\CashService;
use App\Services\LedgerService;
use App\Services\LogService;
use App\Services\SequenceService;
use App\Services\StockService;
use RuntimeException;

class ReturnModel extends Model
{
    public function salesReturns(): array
    {
        return $this->db->query(
            'SELECT r.*, s.invoice_no, c.name AS customer_name
             FROM sales_returns r
             JOIN sales_invoices s ON s.id = r.sales_invoice_id
             JOIN customers c ON c.id = r.customer_id
             ORDER BY r.id DESC LIMIT 300'
        )->fetchAll();
    }

    public function purchaseReturns(): array
    {
        return $this->db->query(
            'SELECT r.*, p.invoice_no, s.name AS supplier_name
             FROM purchase_returns r
             JOIN purchase_invoices p ON p.id = r.purchase_invoice_id
             JOIN suppliers s ON s.id = r.supplier_id
             ORDER BY r.id DESC LIMIT 300'
        )->fetchAll();
    }

    public function createSalesReturn(array $data): array
    {
        $invoiceId = (int) $data['sales_invoice_id'];
        $invoice = $this->salesInvoice($invoiceId);
        if (!$invoice) {
            throw new RuntimeException('فاتورة البيع غير موجودة');
        }

        if (empty($data['items'])) {
            throw new RuntimeException('لا توجد أصناف مرتجعة');
        }

        $this->db->beginTransaction();
        try {
            $returnNo = SequenceService::next('sales_return');
            $subtotal = 0.0;
            $itemsPayload = [];

            foreach ($data['items'] as $row) {
                $salesItemId = (int) $row['sales_invoice_item_id'];
                $qty = (float) $row['qty'];
                if ($qty <= 0) {
                    continue;
                }

                $source = $this->salesItem($salesItemId);
                if (!$source || (int) $source['sales_invoice_id'] !== $invoiceId) {
                    throw new RuntimeException('سطر فاتورة غير صالح');
                }

                $returnedQty = $this->returnedSalesQty($salesItemId);
                $available = (float) $source['qty'] - $returnedQty;
                if ($qty > $available + 0.0001) {
                    throw new RuntimeException('كمية المرتجع تتجاوز الكمية المسموحة للصنف: ' . $source['product_name']);
                }

                $lineTotal = $qty * (float) $source['unit_price'];
                $subtotal += $lineTotal;
                $itemsPayload[] = [
                    'source' => $source,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            if (!$itemsPayload) {
                throw new RuntimeException('لا توجد كميات مرتجعة صحيحة');
            }

            $refundTotal = min((float) ($data['refund_total'] ?? $subtotal), $subtotal);
            $shiftId = !empty($data['shift_id']) ? (int) $data['shift_id'] : null;

            $ins = $this->db->prepare(
                'INSERT INTO sales_returns
                 (return_no, sales_invoice_id, user_id, customer_id, shift_id, return_date, subtotal, discount_total, tax_total, grand_total, refund_total, payment_method_id, note)
                 VALUES
                 (:return_no, :sales_invoice_id, :user_id, :customer_id, :shift_id, datetime(\'now\', \'localtime\'), :subtotal, 0, 0, :grand_total, :refund_total, :payment_method_id, :note)'
            );
            $ins->execute([
                'return_no' => $returnNo,
                'sales_invoice_id' => $invoiceId,
                'user_id' => AuthService::id(),
                'customer_id' => $invoice['customer_id'],
                'shift_id' => $shiftId,
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
                'refund_total' => $refundTotal,
                'payment_method_id' => !empty($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
                'note' => $data['note'] ?? null,
            ]);
            $returnId = (int) $this->db->lastInsertId();

            $itemIns = $this->db->prepare(
                'INSERT INTO sales_return_items
                 (sales_return_id, sales_invoice_item_id, product_id, qty, stock_qty, unit_price, discount_amount, tax_amount, line_total)
                 VALUES
                 (:sales_return_id, :sales_invoice_item_id, :product_id, :qty, :stock_qty, :unit_price, 0, 0, :line_total)'
            );

            foreach ($itemsPayload as $row) {
                $src = $row['source'];
                $stockFactor = (float) ($src['stock_qty'] ?? 0) > 0 && (float) $src['qty'] > 0
                    ? ((float) $src['stock_qty'] / (float) $src['qty'])
                    : 1.0;
                $stockQty = (float) $row['qty'] * $stockFactor;
                $itemIns->execute([
                    'sales_return_id' => $returnId,
                    'sales_invoice_item_id' => $src['id'],
                    'product_id' => $src['product_id'],
                    'qty' => $row['qty'],
                    'stock_qty' => $stockQty,
                    'unit_price' => $src['unit_price'],
                    'line_total' => $row['line_total'],
                ]);

                StockService::move(
                    (int) $invoice['warehouse_id'],
                    (int) $src['product_id'],
                    'sales_return',
                    $stockQty,
                    0,
                    (float) $src['cost_price'],
                    'sales_returns',
                    $returnId,
                    'مرتجع بيع ' . $returnNo,
                    AuthService::id()
                );
            }

            LedgerService::customer((int) $invoice['customer_id'], 'sales_return', 0, $subtotal, 'sales_returns', $returnId, 'مرتجع بيع');

            if ($refundTotal > 0) {
                CashService::movement($shiftId, AuthService::id(), 'sales_return_refund', 'out', $refundTotal, 'sales_returns', $returnId, 'رد نقدية مرتجع بيع');
            }

            LogService::audit('sales_returns', $returnId, 'insert', null, ['return_no' => $returnNo]);
            $this->db->commit();
            return ['id' => $returnId, 'return_no' => $returnNo];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function createPurchaseReturn(array $data): array
    {
        $invoiceId = (int) $data['purchase_invoice_id'];
        $invoice = $this->purchaseInvoice($invoiceId);
        if (!$invoice) {
            throw new RuntimeException('فاتورة الشراء غير موجودة');
        }

        if (empty($data['items'])) {
            throw new RuntimeException('لا توجد أصناف مرتجعة');
        }

        $this->db->beginTransaction();
        try {
            $returnNo = SequenceService::next('purchase_return');
            $subtotal = 0.0;
            $itemsPayload = [];

            foreach ($data['items'] as $row) {
                $purchaseItemId = (int) $row['purchase_invoice_item_id'];
                $qty = (float) $row['qty'];
                if ($qty <= 0) {
                    continue;
                }

                $source = $this->purchaseItem($purchaseItemId);
                if (!$source || (int) $source['purchase_invoice_id'] !== $invoiceId) {
                    throw new RuntimeException('سطر فاتورة غير صالح');
                }

                $returnedQty = $this->returnedPurchaseQty($purchaseItemId);
                $available = (float) $source['qty'] - $returnedQty;
                if ($qty > $available + 0.0001) {
                    throw new RuntimeException('كمية المرتجع تتجاوز الكمية المسموحة للصنف: ' . $source['product_name']);
                }

                $lineTotal = $qty * (float) $source['unit_price'];
                $subtotal += $lineTotal;
                $itemsPayload[] = [
                    'source' => $source,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            if (!$itemsPayload) {
                throw new RuntimeException('لا توجد كميات مرتجعة صحيحة');
            }

            $refundTotal = min((float) ($data['refund_total'] ?? $subtotal), $subtotal);
            $ins = $this->db->prepare(
                'INSERT INTO purchase_returns
                 (return_no, purchase_invoice_id, user_id, supplier_id, return_date, subtotal, discount_total, tax_total, grand_total, refund_total, payment_method_id, note)
                 VALUES
                 (:return_no, :purchase_invoice_id, :user_id, :supplier_id, datetime(\'now\', \'localtime\'), :subtotal, 0, 0, :grand_total, :refund_total, :payment_method_id, :note)'
            );
            $ins->execute([
                'return_no' => $returnNo,
                'purchase_invoice_id' => $invoiceId,
                'user_id' => AuthService::id(),
                'supplier_id' => $invoice['supplier_id'],
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
                'refund_total' => $refundTotal,
                'payment_method_id' => !empty($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
                'note' => $data['note'] ?? null,
            ]);
            $returnId = (int) $this->db->lastInsertId();

            $itemIns = $this->db->prepare(
                'INSERT INTO purchase_return_items
                 (purchase_return_id, purchase_invoice_item_id, product_id, qty, stock_qty, unit_price, discount_amount, tax_amount, line_total)
                 VALUES
                 (:purchase_return_id, :purchase_invoice_item_id, :product_id, :qty, :stock_qty, :unit_price, 0, 0, :line_total)'
            );

            foreach ($itemsPayload as $row) {
                $src = $row['source'];
                $stockFactor = (float) ($src['stock_qty'] ?? 0) > 0 && (float) $src['qty'] > 0
                    ? ((float) $src['stock_qty'] / (float) $src['qty'])
                    : 1.0;
                $stockQty = (float) $row['qty'] * $stockFactor;
                $itemIns->execute([
                    'purchase_return_id' => $returnId,
                    'purchase_invoice_item_id' => $src['id'],
                    'product_id' => $src['product_id'],
                    'qty' => $row['qty'],
                    'stock_qty' => $stockQty,
                    'unit_price' => $src['unit_price'],
                    'line_total' => $row['line_total'],
                ]);

                StockService::move(
                    (int) $invoice['warehouse_id'],
                    (int) $src['product_id'],
                    'purchase_return',
                    0,
                    $stockQty,
                    (float) $src['unit_price'],
                    'purchase_returns',
                    $returnId,
                    'مرتجع شراء ' . $returnNo,
                    AuthService::id()
                );
            }

            LedgerService::supplier((int) $invoice['supplier_id'], 'purchase_return', 0, $subtotal, 'purchase_returns', $returnId, 'مرتجع شراء');

            if ($refundTotal > 0) {
                $openShift = CashService::openShiftForUser((int) AuthService::id()) ?: CashService::openAnyShift();
                CashService::movement(
                    $openShift ? (int) $openShift['id'] : null,
                    AuthService::id(),
                    'purchase_return_receipt',
                    'in',
                    $refundTotal,
                    'purchase_returns',
                    $returnId,
                    'تحصيل مرتجع شراء'
                );
            }

            LogService::audit('purchase_returns', $returnId, 'insert', null, ['return_no' => $returnNo]);
            $this->db->commit();
            return ['id' => $returnId, 'return_no' => $returnNo];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function salesReturnFind(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT r.*, s.invoice_no, c.name AS customer_name FROM sales_returns r JOIN sales_invoices s ON s.id=r.sales_invoice_id JOIN customers c ON c.id=r.customer_id WHERE r.id=:id');
        $stmt->execute(['id' => $id]);
        $return = $stmt->fetch();
        if (!$return) {
            return null;
        }

        $items = $this->db->prepare(
            'SELECT i.*, p.name AS product_name, si.sale_unit, si.is_scale_item, si.scale_weight, si.scanned_barcode
             FROM sales_return_items i
             JOIN products p ON p.id=i.product_id
             LEFT JOIN sales_invoice_items si ON si.id = i.sales_invoice_item_id
             WHERE sales_return_id=:id'
        );
        $items->execute(['id' => $id]);
        $return['items'] = $items->fetchAll();
        return $return;
    }

    public function salesInvoiceItemsWithAvailable(int $invoiceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, p.name AS product_name,
                    COALESCE((
                        SELECT SUM(r.qty)
                        FROM sales_return_items r
                        JOIN sales_returns sr ON sr.id = r.sales_return_id
                        WHERE r.sales_invoice_item_id = i.id
                    ), 0) AS returned_before
             FROM sales_invoice_items i
             JOIN products p ON p.id = i.product_id
             WHERE i.sales_invoice_id = :invoice_id'
        );
        $stmt->execute(['invoice_id' => $invoiceId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $soldQty = (float) ($row['qty'] ?? 0);
            $returned = (float) ($row['returned_before'] ?? 0);
            $available = max(0, $soldQty - $returned);
            $row['available_qty'] = $available;
        }
        unset($row);

        return $rows;
    }

    public function purchaseReturnFind(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT r.*, p.invoice_no, s.name AS supplier_name FROM purchase_returns r JOIN purchase_invoices p ON p.id=r.purchase_invoice_id JOIN suppliers s ON s.id=r.supplier_id WHERE r.id=:id');
        $stmt->execute(['id' => $id]);
        $return = $stmt->fetch();
        if (!$return) {
            return null;
        }

        $items = $this->db->prepare('SELECT i.*, p.name AS product_name FROM purchase_return_items i JOIN products p ON p.id=i.product_id WHERE purchase_return_id=:id');
        $items->execute(['id' => $id]);
        $return['items'] = $items->fetchAll();
        return $return;
    }

    private function salesInvoice(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sales_invoices WHERE id = :id AND status="posted"');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function purchaseInvoice(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM purchase_invoices WHERE id = :id AND status="approved"');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function salesItem(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT i.*, p.name AS product_name, p.sell_type AS product_sell_type FROM sales_invoice_items i JOIN products p ON p.id=i.product_id WHERE i.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function purchaseItem(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT i.*, p.name AS product_name FROM purchase_invoice_items i JOIN products p ON p.id=i.product_id WHERE i.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function returnedSalesQty(int $salesItemId): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(qty),0) FROM sales_return_items WHERE sales_invoice_item_id = :id');
        $stmt->execute(['id' => $salesItemId]);
        return (float) $stmt->fetchColumn();
    }

    private function returnedPurchaseQty(int $purchaseItemId): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(qty),0) FROM purchase_return_items WHERE purchase_invoice_item_id = :id');
        $stmt->execute(['id' => $purchaseItemId]);
        return (float) $stmt->fetchColumn();
    }
}
