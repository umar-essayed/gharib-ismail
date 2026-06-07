<?php

namespace App\Services;

use App\Core\Database;
use RuntimeException;

class DataResetService
{
    public static function reset(string $scope, int $actorUserId): void
    {
        $db = Database::pdo();
        $db->beginTransaction();

        try {
            if ($scope === 'entries') {
                self::clearEntries($db);
            } elseif ($scope === 'invoices') {
                self::clearInvoices($db);
            } elseif ($scope === 'products') {
                self::clearProducts($db);
            } elseif ($scope === 'all') {
                self::clearInvoices($db);
                self::clearProducts($db);
                self::clearEntries($db);
            } else {
                throw new RuntimeException('نطاق المسح غير صالح');
            }

            LogService::audit('system', 'danger_reset', 'delete', null, [
                'scope' => $scope,
                'actor_user_id' => $actorUserId,
            ]);
            LogService::activity($actorUserId, 'settings.danger_reset', 'تنفيذ مسح بيانات: ' . $scope);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private static function clearEntries(\PDO $db): void
    {
        $db->exec('DELETE FROM failed_logins');
        $db->exec('DELETE FROM activity_logs');
        $db->exec('DELETE FROM audit_logs');
        $db->exec('DELETE FROM sale_suspensions');
    }

    private static function clearInvoices(\PDO $db): void
    {
        $db->exec('DELETE FROM sales_return_items');
        $db->exec('DELETE FROM sales_returns');
        $db->exec('DELETE FROM purchase_return_items');
        $db->exec('DELETE FROM purchase_returns');

        $db->exec('DELETE FROM sales_invoice_payments');
        $db->exec('DELETE FROM sales_invoice_items');
        $db->exec('DELETE FROM sales_invoices');

        $db->exec('DELETE FROM purchase_invoice_payments');
        $db->exec('DELETE FROM purchase_invoice_items');
        $db->exec('DELETE FROM purchase_invoices');

        $db->exec('DELETE FROM inventory_adjustment_items');
        $db->exec('DELETE FROM inventory_adjustments');

        $db->exec('DELETE FROM cash_movements');
        $db->exec('DELETE FROM cash_shifts');

        $db->exec('DELETE FROM customer_transactions');
        $db->exec('DELETE FROM supplier_transactions');
        $db->exec('DELETE FROM sale_suspensions');
        $db->exec('DELETE FROM stock_movements');

        // إعادة تسفير عدادات الفواتير والمرتجعات والحركات.
        $stmt = $db->prepare(
            'UPDATE number_sequences
             SET current_number = 0
             WHERE seq_key IN ("sales_invoice","purchase_invoice","sales_return","purchase_return","cash_shift","inventory_adjustment","sale_hold")'
        );
        $stmt->execute();

        // إعادة أرصدة العملاء/الموردين للقيم الافتتاحية.
        $db->exec('UPDATE customers SET current_balance = opening_balance');
        $db->exec('UPDATE suppliers SET current_balance = opening_balance');

        self::rebuildOpeningStock($db);
    }

    private static function clearProducts(\PDO $db): void
    {
        $hasReferences = (int) $db->query(
            'SELECT
                (SELECT COUNT(*) FROM sales_invoice_items) +
                (SELECT COUNT(*) FROM purchase_invoice_items) +
                (SELECT COUNT(*) FROM sales_return_items) +
                (SELECT COUNT(*) FROM purchase_return_items)'
        )->fetchColumn();

        if ($hasReferences > 0) {
            throw new RuntimeException('لا يمكن مسح الأصناف مع وجود فواتير. امسح الفواتير أولًا ثم أعد المحاولة.');
        }

        $db->exec('DELETE FROM promotions');
        $db->exec('DELETE FROM product_barcodes');
        $db->exec('DELETE FROM stock_movements');
        $db->exec('DELETE FROM inventory_adjustment_items');
        $db->exec('DELETE FROM inventory_adjustments');
        $db->exec('DELETE FROM products');
    }

    private static function rebuildOpeningStock(\PDO $db): void
    {
        $defaultWarehouseId = (int) SettingsService::get('default_warehouse_id', '1');
        if ($defaultWarehouseId <= 0) {
            $defaultWarehouseId = 1;
        }

        $stmt = $db->query(
            'SELECT id, opening_stock, purchase_price, track_stock
             FROM products
             WHERE deleted_at IS NULL AND is_active = 1
             ORDER BY id'
        );
        $products = $stmt->fetchAll();

        foreach ($products as $product) {
            if ((int) $product['track_stock'] !== 1) {
                continue;
            }

            $openingQty = (float) $product['opening_stock'];
            if ($openingQty <= 0) {
                continue;
            }

            StockService::move(
                $defaultWarehouseId,
                (int) $product['id'],
                'initial',
                $openingQty,
                0,
                (float) $product['purchase_price'],
                'products',
                (int) $product['id'],
                'إعادة إنشاء رصيد افتتاحي بعد مسح الفواتير',
                null
            );
        }
    }
}
