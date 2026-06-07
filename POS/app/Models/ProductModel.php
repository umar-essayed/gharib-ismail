<?php

namespace App\Models;

use App\Core\Model;

class ProductModel extends Model
{
    public function all(string $q = ''): array
    {
        $sql = 'SELECT p.*, c.name AS category_name, u.name AS unit_name,
                       promo.id AS promotion_id,
                       promo.name AS promotion_name,
                       promo.discount_type AS promotion_discount_type,
                       promo.discount_value AS promotion_discount_value,
                       promo.start_date AS promotion_start_date,
                       promo.end_date AS promotion_end_date,
                       COALESCE(sm.balance_after, 0) AS current_stock
                FROM products p
                LEFT JOIN product_categories c ON c.id = p.category_id
                LEFT JOIN units u ON u.id = p.unit_id
                LEFT JOIN (
                    SELECT pr1.product_id, pr1.id, pr1.name, pr1.discount_type, pr1.discount_value, pr1.start_date, pr1.end_date
                    FROM promotions pr1
                    INNER JOIN (
                        SELECT product_id, MAX(id) AS max_id
                        FROM promotions
                        WHERE is_active = 1 AND date(\'now\') BETWEEN start_date AND end_date
                        GROUP BY product_id
                    ) pr2 ON pr1.id = pr2.max_id
                ) promo ON promo.product_id = p.id
                LEFT JOIN (
                    SELECT s1.product_id, s1.balance_after
                    FROM stock_movements s1
                    INNER JOIN (
                        SELECT product_id, MAX(id) AS max_id
                        FROM stock_movements
                        GROUP BY product_id
                    ) s2 ON s1.id = s2.max_id
                ) sm ON sm.product_id = p.id
                WHERE p.deleted_at IS NULL';

        $params = [];
        if ($q !== '') {
            $sql .= ' AND (p.name LIKE :q_name OR p.barcode LIKE :q_barcode OR p.sku LIKE :q_sku OR p.internal_code LIKE :q_internal)';
            $like = "%{$q}%";
            $params['q_name'] = $like;
            $params['q_barcode'] = $like;
            $params['q_sku'] = $like;
            $params['q_internal'] = $like;
        }

        $sql .= ' ORDER BY p.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByBarcodeOrName(string $term): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.name, p.barcode, p.sale_price, p.wholesale_price, p.purchase_price,
                    p.package_type, p.package_size, p.scale_code, p.allow_scale_barcode, p.weight_unit,
                    p.sell_type, p.track_stock, p.unit_id, u.short_name,
                    promo.id AS promotion_id,
                    promo.name AS promotion_name,
                    promo.discount_type AS promotion_discount_type,
                    promo.discount_value AS promotion_discount_value,
                    promo.start_date AS promotion_start_date,
                    promo.end_date AS promotion_end_date,
                    COALESCE(sm.balance_after, 0) AS current_stock
             FROM products p
             LEFT JOIN units u ON u.id = p.unit_id
             LEFT JOIN (
                SELECT pr1.product_id, pr1.id, pr1.name, pr1.discount_type, pr1.discount_value, pr1.start_date, pr1.end_date
                FROM promotions pr1
                INNER JOIN (
                    SELECT product_id, MAX(id) AS max_id
                    FROM promotions
                    WHERE is_active = 1 AND date(\'now\') BETWEEN start_date AND end_date
                    GROUP BY product_id
                ) pr2 ON pr1.id = pr2.max_id
             ) promo ON promo.product_id = p.id
             LEFT JOIN (
                SELECT s1.product_id, s1.balance_after
                FROM stock_movements s1
                INNER JOIN (
                    SELECT product_id, MAX(id) AS max_id FROM stock_movements GROUP BY product_id
                ) s2 ON s1.id = s2.max_id
             ) sm ON sm.product_id = p.id
             WHERE p.deleted_at IS NULL AND p.is_active = 1
             AND (
                p.barcode = :exact_barcode
                OR p.scale_code = :exact_scale
                OR p.name LIKE :like_name
                OR p.sku = :exact_sku
                OR p.internal_code = :exact_internal
                OR EXISTS (
                    SELECT 1
                    FROM product_barcodes pb
                    WHERE pb.product_id = p.id AND pb.barcode = :exact_product_barcode
                )
             )
             ORDER BY p.name ASC
             LIMIT 30'
        );
        $stmt->execute([
            'exact_barcode' => $term,
            'exact_scale' => $term,
            'like_name' => "%{$term}%",
            'exact_sku' => $term,
            'exact_internal' => $term,
            'exact_product_barcode' => $term,
        ]);

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products
             (category_id, unit_id, name, sku, internal_code, barcode, scale_code, allow_scale_barcode, weight_unit,
              purchase_price, sale_price, wholesale_price, min_stock, opening_stock, sell_type, package_type, package_size,
              track_stock, is_active, created_by)
             VALUES
             (:category_id, :unit_id, :name, :sku, :internal_code, :barcode, :scale_code, :allow_scale_barcode, :weight_unit,
              :purchase_price, :sale_price, :wholesale_price, :min_stock, :opening_stock, :sell_type, :package_type, :package_size,
              :track_stock, :is_active, :created_by)'
        );

        $sellType = ($data['sell_type'] ?? 'piece') === 'weight' ? 'weight' : 'piece';
        $packageType = $this->normalizePackageType($sellType, (string) ($data['package_type'] ?? 'piece'));
        $packageSize = max(1, (float) ($data['package_size'] ?? 1));

        $stmt->execute([
            'category_id' => $data['category_id'] ?: null,
            'unit_id' => $data['unit_id'] ?: null,
            'name' => $data['name'],
            'sku' => $data['sku'] ?: null,
            'internal_code' => $data['internal_code'] ?: null,
            'barcode' => $data['barcode'] ?: null,
            'scale_code' => $data['scale_code'] ?: null,
            'allow_scale_barcode' => (int) ($data['allow_scale_barcode'] ?? 0),
            'weight_unit' => ($data['weight_unit'] ?? 'kg') === 'g' ? 'g' : 'kg',
            'purchase_price' => (float) ($data['purchase_price'] ?? 0),
            'sale_price' => (float) ($data['sale_price'] ?? 0),
            'wholesale_price' => $data['wholesale_price'] !== '' ? (float) $data['wholesale_price'] : null,
            'min_stock' => (float) ($data['min_stock'] ?? 0),
            'opening_stock' => (float) ($data['opening_stock'] ?? 0),
            'sell_type' => $sellType,
            'package_type' => $packageType,
            'package_size' => $packageSize,
            'track_stock' => (int) ($data['track_stock'] ?? 1),
            'is_active' => (int) ($data['is_active'] ?? 1),
            'created_by' => $data['created_by'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE products
             SET category_id = :category_id,
                 unit_id = :unit_id,
                 name = :name,
                 sku = :sku,
                 internal_code = :internal_code,
                 barcode = :barcode,
                 scale_code = :scale_code,
                 allow_scale_barcode = :allow_scale_barcode,
                 weight_unit = :weight_unit,
                 purchase_price = :purchase_price,
                 sale_price = :sale_price,
                 wholesale_price = :wholesale_price,
                 min_stock = :min_stock,
                 sell_type = :sell_type,
                 package_type = :package_type,
                 package_size = :package_size,
                 track_stock = :track_stock,
                 is_active = :is_active,
                 updated_by = :updated_by
             WHERE id = :id'
        );

        $sellType = ($data['sell_type'] ?? 'piece') === 'weight' ? 'weight' : 'piece';
        $packageType = $this->normalizePackageType($sellType, (string) ($data['package_type'] ?? 'piece'));
        $packageSize = max(1, (float) ($data['package_size'] ?? 1));

        $stmt->execute([
            'category_id' => $data['category_id'] ?: null,
            'unit_id' => $data['unit_id'] ?: null,
            'name' => $data['name'],
            'sku' => $data['sku'] ?: null,
            'internal_code' => $data['internal_code'] ?: null,
            'barcode' => $data['barcode'] ?: null,
            'scale_code' => $data['scale_code'] ?: null,
            'allow_scale_barcode' => (int) ($data['allow_scale_barcode'] ?? 0),
            'weight_unit' => ($data['weight_unit'] ?? 'kg') === 'g' ? 'g' : 'kg',
            'purchase_price' => (float) ($data['purchase_price'] ?? 0),
            'sale_price' => (float) ($data['sale_price'] ?? 0),
            'wholesale_price' => $data['wholesale_price'] !== '' ? (float) $data['wholesale_price'] : null,
            'min_stock' => (float) ($data['min_stock'] ?? 0),
            'sell_type' => $sellType,
            'package_type' => $packageType,
            'package_size' => $packageSize,
            'track_stock' => (int) ($data['track_stock'] ?? 1),
            'is_active' => (int) ($data['is_active'] ?? 1),
            'updated_by' => $data['updated_by'] ?? null,
            'id' => $id,
        ]);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE products SET deleted_at = datetime(\'now\') WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function categories(): array
    {
        return $this->db->query('SELECT id, name FROM product_categories WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name')->fetchAll();
    }

    public function units(): array
    {
        return $this->db->query('SELECT id, name, short_name FROM units WHERE is_active = 1 ORDER BY name')->fetchAll();
    }

    private function normalizePackageType(string $sellType, string $packageType): string
    {
        if ($sellType === 'weight') {
            return in_array($packageType, ['kg', 'sack'], true) ? $packageType : 'kg';
        }

        return in_array($packageType, ['piece', 'box'], true) ? $packageType : 'piece';
    }
}
