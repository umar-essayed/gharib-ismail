<?php

namespace App\Models;

use App\Core\Model;

class CategoryModel extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM product_categories WHERE deleted_at IS NULL ORDER BY id DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_categories WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO product_categories (name, description, is_active) VALUES (:name, :description, :is_active)');
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE product_categories SET name = :name, description = :description, is_active = :is_active WHERE id = :id');
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (int) ($data['is_active'] ?? 1),
            'id' => $id,
        ]);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE product_categories SET deleted_at = datetime(\'now\', \'localtime\') WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
