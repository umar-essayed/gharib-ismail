<?php

namespace App\Models;

use App\Core\Model;

class UnitModel extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM units ORDER BY id DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM units WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO units (name, short_name, is_weight, is_active) VALUES (:name, :short_name, :is_weight, :is_active)');
        $stmt->execute([
            'name' => $data['name'],
            'short_name' => $data['short_name'],
            'is_weight' => (int) ($data['is_weight'] ?? 0),
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE units SET name = :name, short_name = :short_name, is_weight = :is_weight, is_active = :is_active WHERE id = :id');
        $stmt->execute([
            'name' => $data['name'],
            'short_name' => $data['short_name'],
            'is_weight' => (int) ($data['is_weight'] ?? 0),
            'is_active' => (int) ($data['is_active'] ?? 1),
            'id' => $id,
        ]);
    }
}
