<?php

namespace App\Models;

use App\Core\Model;
use App\Services\AuthService;
use App\Services\LogService;
use App\Services\SequenceService;
use App\Services\StockService;

class InventoryModel extends Model
{
    public function stockSummary(): array
    {
        return $this->db->query(
            'SELECT p.id, p.name, p.barcode, p.min_stock, p.sell_type,
                    COALESCE(sm.balance_after, 0) AS current_stock,
                    c.name AS category_name, u.short_name AS unit_name
             FROM products p
             LEFT JOIN product_categories c ON c.id = p.category_id
             LEFT JOIN units u ON u.id = p.unit_id
             LEFT JOIN (
                SELECT s1.product_id, s1.balance_after
                FROM stock_movements s1
                INNER JOIN (SELECT product_id, MAX(id) AS max_id FROM stock_movements GROUP BY product_id) s2 ON s1.id = s2.max_id
             ) sm ON sm.product_id = p.id
             WHERE p.deleted_at IS NULL
             ORDER BY p.name ASC'
        )->fetchAll();
    }

    public function movements(array $filters = []): array
    {
        $sql = 'SELECT m.*, p.name AS product_name, w.name AS warehouse_name, u.full_name AS user_name
                FROM stock_movements m
                JOIN products p ON p.id = m.product_id
                JOIN warehouses w ON w.id = m.warehouse_id
                LEFT JOIN users u ON u.id = m.created_by
                WHERE 1=1';
        $params = [];

        if (!empty($filters['product_id'])) {
            $sql .= ' AND m.product_id = :product_id';
            $params['product_id'] = (int) $filters['product_id'];
        }
        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(m.created_at) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(m.created_at) <= :to';
            $params['to'] = $filters['to'];
        }

        $sql .= ' ORDER BY m.id DESC LIMIT 1000';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function adjust(array $data): array
    {
        $this->db->beginTransaction();
        try {
            $adjustNo = SequenceService::next('inventory_adjustment');

            $ins = $this->db->prepare('INSERT INTO inventory_adjustments (adjust_no, warehouse_id, user_id, adjust_date, note) VALUES (:adjust_no, :warehouse_id, :user_id, datetime(\'now\', \'localtime\'), :note)');
            $ins->execute([
                'adjust_no' => $adjustNo,
                'warehouse_id' => (int) $data['warehouse_id'],
                'user_id' => AuthService::id(),
                'note' => $data['note'] ?? null,
            ]);
            $adjId = (int) $this->db->lastInsertId();

            $itemIns = $this->db->prepare(
                'INSERT INTO inventory_adjustment_items (inventory_adjustment_id, product_id, old_qty, new_qty, diff_qty)
                 VALUES (:inventory_adjustment_id, :product_id, :old_qty, :new_qty, :diff_qty)'
            );

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $newQty = (float) $item['new_qty'];
                $oldQty = StockService::currentBalance((int) $data['warehouse_id'], $productId);
                $diff = $newQty - $oldQty;

                $itemIns->execute([
                    'inventory_adjustment_id' => $adjId,
                    'product_id' => $productId,
                    'old_qty' => $oldQty,
                    'new_qty' => $newQty,
                    'diff_qty' => $diff,
                ]);

                if (abs($diff) < 0.0001) {
                    continue;
                }

                if ($diff > 0) {
                    StockService::move((int) $data['warehouse_id'], $productId, 'adjustment_in', $diff, 0, 0, 'inventory_adjustments', $adjId, 'تسوية مخزون زيادة', AuthService::id());
                } else {
                    StockService::move((int) $data['warehouse_id'], $productId, 'adjustment_out', 0, abs($diff), 0, 'inventory_adjustments', $adjId, 'تسوية مخزون عجز', AuthService::id());
                }
            }

            LogService::audit('inventory_adjustments', $adjId, 'insert', null, ['adjust_no' => $adjustNo]);
            $this->db->commit();
            return ['id' => $adjId, 'adjust_no' => $adjustNo];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function warehouses(): array
    {
        return $this->db->query('SELECT * FROM warehouses ORDER BY id')->fetchAll();
    }
}
