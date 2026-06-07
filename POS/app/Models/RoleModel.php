<?php

namespace App\Models;

use App\Core\Model;

class RoleModel extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM roles ORDER BY id DESC')->fetchAll();
    }

    public function permissions(): array
    {
        return $this->db->query('SELECT * FROM permissions ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM roles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $role = $stmt->fetch();

        if (!$role) {
            return null;
        }

        $permStmt = $this->db->prepare(
            'SELECT p.code
             FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id'
        );
        $permStmt->execute(['role_id' => $id]);
        $role['permissions'] = array_column($permStmt->fetchAll(), 'code');

        return $role;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO roles (name, description, is_system, is_active) VALUES (:name, :description, 0, :is_active)');
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);
        $id = (int) $this->db->lastInsertId();
        $this->syncPermissions($id, $data['permissions'] ?? []);

        return $id;
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE roles SET name = :name, description = :description, is_active = :is_active WHERE id = :id');
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (int) ($data['is_active'] ?? 1),
            'id' => $id,
        ]);

        $this->syncPermissions($id, $data['permissions'] ?? []);
    }

    private function syncPermissions(int $roleId, array $codes): void
    {
        $this->db->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $roleId]);
        if (!$codes) {
            return;
        }

        $stmt = $this->db->prepare('SELECT id FROM permissions WHERE code = :code LIMIT 1');
        $ins = $this->db->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');

        foreach ($codes as $code) {
            $stmt->execute(['code' => $code]);
            $id = $stmt->fetchColumn();
            if ($id) {
                $ins->execute(['role_id' => $roleId, 'permission_id' => $id]);
            }
        }
    }
}
