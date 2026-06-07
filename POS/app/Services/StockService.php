<?php

namespace App\Services;

use App\Core\Database;
use RuntimeException;

class StockService
{
    public static function currentBalance(int $warehouseId, int $productId): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT balance_after
             FROM stock_movements
             WHERE warehouse_id = :warehouse_id AND product_id = :product_id
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
        ]);
        $balance = $stmt->fetchColumn();

        return $balance === false ? 0.0 : (float) $balance;
    }

    public static function canOut(int $warehouseId, int $productId, float $qty): bool
    {
        $allowNegative = SettingsService::get('allow_negative_stock', '0') === '1';
        if ($allowNegative) {
            return true;
        }

        return self::currentBalance($warehouseId, $productId) >= $qty;
    }

    public static function move(
        int $warehouseId,
        int $productId,
        string $type,
        float $qtyIn,
        float $qtyOut,
        float $unitCost,
        ?string $refTable,
        ?int $refId,
        ?string $note,
        ?int $userId
    ): void {
        $before = self::currentBalance($warehouseId, $productId);
        $after = $before + $qtyIn - $qtyOut;

        if ($after < 0 && SettingsService::get('allow_negative_stock', '0') !== '1') {
            throw new RuntimeException('الكمية غير كافية في المخزون للصنف #' . $productId);
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO stock_movements
             (warehouse_id, product_id, movement_type, qty_in, qty_out, balance_after, unit_cost, reference_table, reference_id, note, created_by)
             VALUES
             (:warehouse_id, :product_id, :movement_type, :qty_in, :qty_out, :balance_after, :unit_cost, :reference_table, :reference_id, :note, :created_by)'
        );
        $stmt->execute([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'movement_type' => $type,
            'qty_in' => $qtyIn,
            'qty_out' => $qtyOut,
            'balance_after' => $after,
            'unit_cost' => $unitCost,
            'reference_table' => $refTable,
            'reference_id' => $refId,
            'note' => $note,
            'created_by' => $userId,
        ]);

        // ☁️ مزامنة رصيد المخزون فوراً مع المتجر الإلكتروني
        try {
            \App\Services\SupabaseSyncService::syncStock($productId, $after);
        } catch (\Throwable $e) {
            // الخطأ يُحفظ في طابور المزامنة تلقائياً داخل syncStock
        }
    }
}
