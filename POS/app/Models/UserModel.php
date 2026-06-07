<?php

namespace App\Models;

use App\Core\Model;

class UserModel extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.deleted_at IS NULL
             ORDER BY u.id DESC'
        )->fetchAll();
    }

    public function roles(): array
    {
        return $this->db->query('SELECT id, name FROM roles WHERE is_active = 1 ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (role_id, username, full_name, password_hash, email, phone, is_active)
             VALUES (:role_id, :username, :full_name, :password_hash, :email, :phone, :is_active)'
        );
        $stmt->execute([
            'role_id' => (int) $data['role_id'],
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE users SET role_id = :role_id, username = :username, full_name = :full_name, email = :email, phone = :phone, is_active = :is_active';
        $params = [
            'role_id' => (int) $data['role_id'],
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => (int) ($data['is_active'] ?? 1),
            'id' => $id,
        ];

        if (!empty($data['password'])) {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET deleted_at = datetime(\'now\', \'localtime\') WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
