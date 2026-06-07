<?php

namespace App\Models;

use App\Core\Model;

class CashierKeyboardModel extends Model
{
    public function allForUser(int $userId): array
    {
        $this->ensureShortcutsForUser($userId);

        $stmt = $this->db->prepare(
            'SELECT id, user_id, key_code, key_label, action_type, reference_id, reference_name, is_active, created_at, updated_at
             FROM keyboard_shortcuts
             WHERE user_id = :user_id
             ORDER BY key_code ASC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, key_code, key_label, action_type, reference_id, reference_name, is_active, created_at, updated_at
             FROM keyboard_shortcuts
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByKeyCode(int $userId, string $keyCode): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, key_code, key_label, action_type, reference_id, reference_name, is_active
             FROM keyboard_shortcuts
             WHERE user_id = :user_id AND key_code = :key_code'
        );
        $stmt->execute(['user_id' => $userId, 'key_code' => $keyCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO keyboard_shortcuts (user_id, key_code, key_label, action_type, reference_id, reference_name, is_active)
             VALUES (:user_id, :key_code, :key_label, :action_type, :reference_id, :reference_name, :is_active)'
        );
        $stmt->execute([
            'user_id' => (int) $data['user_id'],
            'key_code' => $data['key_code'],
            'key_label' => $data['key_label'],
            'action_type' => $data['action_type'],
            'reference_id' => $data['reference_id'] ?? null,
            'reference_name' => $data['reference_name'] ?? null,
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE keyboard_shortcuts
             SET key_code = :key_code, key_label = :key_label, action_type = :action_type, reference_id = :reference_id, 
                 reference_name = :reference_name, is_active = :is_active, updated_at = datetime(\'now\', \'localtime\')
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $id,
            'key_code' => $data['key_code'],
            'key_label' => $data['key_label'],
            'action_type' => $data['action_type'],
            'reference_id' => $data['reference_id'] ?? null,
            'reference_name' => $data['reference_name'] ?? null,
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM keyboard_shortcuts WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE keyboard_shortcuts SET is_active = NOT is_active, updated_at = datetime(\'now\', \'localtime\') WHERE id = :id'
        );
        return $stmt->execute(['id' => $id]);
    }

    public function deleteForUser(int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM keyboard_shortcuts WHERE user_id = :user_id');
        return $stmt->execute(['user_id' => $userId]);
    }

    private function ensureShortcutsForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM keyboard_shortcuts WHERE user_id = :user_id');
        $countStmt->execute(['user_id' => $userId]);
        if ((int) $countStmt->fetchColumn() > 0) {
            return;
        }

        $sourceUserId = (int) $this->db->query(
            'SELECT user_id
             FROM keyboard_shortcuts
             WHERE user_id <> ' . (int) $userId . '
             GROUP BY user_id
             ORDER BY COUNT(*) DESC, user_id ASC
             LIMIT 1'
        )->fetchColumn();

        if ($sourceUserId <= 0) {
            return;
        }

        $sourceStmt = $this->db->prepare(
            'SELECT key_code, key_label, action_type, reference_id, reference_name, is_active
             FROM keyboard_shortcuts
             WHERE user_id = :source_user_id
             ORDER BY key_code ASC'
        );
        $sourceStmt->execute(['source_user_id' => $sourceUserId]);
        $shortcuts = $sourceStmt->fetchAll();
        if (!$shortcuts) {
            return;
        }

        $insert = $this->db->prepare(
            'INSERT INTO keyboard_shortcuts (user_id, key_code, key_label, action_type, reference_id, reference_name, is_active)
             VALUES (:user_id, :key_code, :key_label, :action_type, :reference_id, :reference_name, :is_active)
             ON DUPLICATE KEY UPDATE
                key_label = VALUES(key_label),
                action_type = VALUES(action_type),
                reference_id = VALUES(reference_id),
                reference_name = VALUES(reference_name),
                is_active = VALUES(is_active),
                updated_at = datetime(\'now\', \'localtime\')'
        );

        foreach ($shortcuts as $shortcut) {
            $insert->execute([
                'user_id' => $userId,
                'key_code' => $shortcut['key_code'],
                'key_label' => $shortcut['key_label'],
                'action_type' => $shortcut['action_type'],
                'reference_id' => $shortcut['reference_id'],
                'reference_name' => $shortcut['reference_name'],
                'is_active' => (int) $shortcut['is_active'],
            ]);
        }
    }
}
