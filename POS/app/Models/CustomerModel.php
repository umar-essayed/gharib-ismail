<?php

namespace App\Models;

use App\Core\Model;

class CustomerModel extends Model
{
    public function ensureDefaultCashCustomer(): int
    {
        $stmt = $this->db->query(
            'SELECT id
             FROM customers
             WHERE is_cash_customer = 1
               AND deleted_at IS NULL
             ORDER BY id ASC
             LIMIT 1'
        );
        $existingId = (int) ($stmt->fetchColumn() ?: 0);
        if ($existingId > 0) {
            return $existingId;
        }

        $stmt = $this->db->query(
            'SELECT id
             FROM customers
             WHERE is_cash_customer = 1
               AND deleted_at IS NOT NULL
             ORDER BY id ASC
             LIMIT 1'
        );
        $deletedId = (int) ($stmt->fetchColumn() ?: 0);
        if ($deletedId > 0) {
            $restore = $this->db->prepare(
                'UPDATE customers
                 SET deleted_at = NULL,
                     is_active = 1
                 WHERE id = :id'
            );
            $restore->execute(['id' => $deletedId]);
            return $deletedId;
        }

        $create = $this->db->prepare(
            'INSERT INTO customers
             (name, phone, email, address, opening_balance, credit_limit, current_balance, is_cash_customer, is_active)
             VALUES
             (:name, NULL, NULL, NULL, 0, 0, 0, 1, 1)'
        );
        $create->execute(['name' => 'عميل نقدي']);

        return (int) $this->db->lastInsertId();
    }

    public function all(string $q = ''): array
    {
        $sql = 'SELECT * FROM customers WHERE deleted_at IS NULL';
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
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO customers
             (name, phone, email, address, opening_balance, credit_limit, current_balance, is_cash_customer, is_active)
             VALUES
             (:name, :phone, :email, :address, :opening_balance, :credit_limit, :current_balance, :is_cash_customer, :is_active)'
        );
        $opening = (float) ($data['opening_balance'] ?? 0);
        $stmt->execute([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'opening_balance' => $opening,
            'credit_limit' => (float) ($data['credit_limit'] ?? 0),
            'current_balance' => $opening,
            'is_cash_customer' => (int) ($data['is_cash_customer'] ?? 0),
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE customers
             SET name = :name, phone = :phone, email = :email, address = :address,
                 credit_limit = :credit_limit, is_cash_customer = :is_cash_customer, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'credit_limit' => (float) ($data['credit_limit'] ?? 0),
            'is_cash_customer' => (int) ($data['is_cash_customer'] ?? 0),
            'is_active' => (int) ($data['is_active'] ?? 1),
            'id' => $id,
        ]);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE customers SET deleted_at = datetime(\'now\', \'localtime\') WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function statement(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM customer_transactions WHERE customer_id = :id ORDER BY id DESC LIMIT 500');
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function cashCustomerId(): int
    {
        return $this->ensureDefaultCashCustomer();
    }

    public function activeCashCustomersCount(): int
    {
        $stmt = $this->db->query(
            'SELECT COUNT(*)
             FROM customers
             WHERE is_cash_customer = 1
               AND deleted_at IS NULL'
        );
        return (int) ($stmt->fetchColumn() ?: 0);
    }
}
