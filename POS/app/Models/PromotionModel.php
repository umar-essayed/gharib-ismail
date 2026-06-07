<?php

namespace App\Models;

use App\Core\Model;

class PromotionModel extends Model
{
    public function all(array $filters = []): array
    {
        $sql = 'SELECT pr.*, p.name AS product_name, p.barcode AS product_barcode,
                       CASE
                           WHEN pr.is_active = 0 THEN "disabled"
                           WHEN date(\'now\') < pr.start_date THEN "upcoming"
                           WHEN date(\'now\') > pr.end_date THEN "expired"
                           ELSE "active"
                       END AS run_state
                FROM promotions pr
                JOIN products p ON p.id = pr.product_id
                WHERE p.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= ' AND (p.name LIKE :q_name OR p.barcode LIKE :q_barcode OR pr.name LIKE :q_offer)';
            $like = '%' . trim((string) $filters['q']) . '%';
            $params['q_name'] = $like;
            $params['q_barcode'] = $like;
            $params['q_offer'] = $like;
        }

        if (!empty($filters['product_id'])) {
            $sql .= ' AND pr.product_id = :product_id';
            $params['product_id'] = (int) $filters['product_id'];
        }

        if (!empty($filters['status'])) {
            $status = (string) $filters['status'];
            if ($status === 'active') {
                $sql .= ' AND pr.is_active = 1 AND date(\'now\') BETWEEN pr.start_date AND pr.end_date';
            } elseif ($status === 'upcoming') {
                $sql .= ' AND pr.is_active = 1 AND date(\'now\') < pr.start_date';
            } elseif ($status === 'expired') {
                $sql .= ' AND (pr.is_active = 0 OR date(\'now\') > pr.end_date)';
            }
        }

        $sql .= ' ORDER BY pr.id DESC LIMIT 500';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM promotions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function productsForSelect(): array
    {
        return $this->db->query(
            'SELECT id, name, barcode, sale_price
             FROM products
             WHERE deleted_at IS NULL AND is_active = 1
             ORDER BY name'
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO promotions
             (product_id, name, discount_type, discount_value, start_date, end_date, is_active, note, created_by, updated_by)
             VALUES
             (:product_id, :name, :discount_type, :discount_value, :start_date, :end_date, :is_active, :note, :created_by, :created_by)'
        );
        $stmt->execute([
            'product_id' => (int) $data['product_id'],
            'name' => $data['name'],
            'discount_type' => $data['discount_type'],
            'discount_value' => (float) $data['discount_value'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => (int) ($data['is_active'] ?? 1),
            'note' => $data['note'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE promotions
             SET product_id = :product_id,
                 name = :name,
                 discount_type = :discount_type,
                 discount_value = :discount_value,
                 start_date = :start_date,
                 end_date = :end_date,
                 is_active = :is_active,
                 note = :note,
                 updated_by = :updated_by
             WHERE id = :id'
        );
        $stmt->execute([
            'product_id' => (int) $data['product_id'],
            'name' => $data['name'],
            'discount_type' => $data['discount_type'],
            'discount_value' => (float) $data['discount_value'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => (int) ($data['is_active'] ?? 1),
            'note' => $data['note'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
            'id' => $id,
        ]);
    }

    public function deactivate(int $id, ?int $updatedBy = null): void
    {
        $stmt = $this->db->prepare('UPDATE promotions SET is_active = 0, updated_by = :updated_by WHERE id = :id');
        $stmt->execute([
            'updated_by' => $updatedBy,
            'id' => $id,
        ]);
    }

    public function hasOverlap(int $productId, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id
                FROM promotions
                WHERE product_id = :product_id
                  AND is_active = 1
                  AND :start_date <= end_date
                  AND :end_date >= start_date';
        $params = [
            'product_id' => $productId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($excludeId) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function activeMap(string $onDate): array
    {
        $stmt = $this->db->prepare(
            'SELECT pr.*
             FROM promotions pr
             INNER JOIN (
                SELECT product_id, MAX(id) AS max_id
                FROM promotions
                WHERE is_active = 1 AND :on_date BETWEEN start_date AND end_date
                GROUP BY product_id
             ) idx ON idx.max_id = pr.id'
        );
        $stmt->execute(['on_date' => $onDate]);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['product_id']] = $row;
        }

        return $map;
    }
}
