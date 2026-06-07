<?php

namespace App\Models;

use App\Core\Model;

class SupplierModel extends Model
{
    public function all(string $q = ''): array
    {
        $sql = 'SELECT * FROM suppliers WHERE deleted_at IS NULL';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (name LIKE :q OR phone LIKE :q)';
            $params['q'] = "%{$q}%";
        }
        $sql .= ' ORDER BY id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM suppliers WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO suppliers
             (name, phone, email, address, opening_balance, current_balance, is_active)
             VALUES
             (:name, :phone, :email, :address, :opening_balance, :current_balance, :is_active)'
        );
        $opening = (float) ($data['opening_balance'] ?? 0);
        $stmt->execute([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE suppliers
             SET name = :name, phone = :phone, email = :email, address = :address, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => (int) ($data['is_active'] ?? 1),
            'id' => $id,
        ]);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE suppliers SET deleted_at = datetime(\'now\', \'localtime\') WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function statement(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM supplier_transactions WHERE supplier_id = :id ORDER BY id DESC LIMIT 500');
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }
}
