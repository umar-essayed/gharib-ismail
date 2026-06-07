<?php

namespace App\Services;

use App\Core\Database;

class LogService
{
    public static function activity(?int $userId, string $action, ?string $description = null): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            'INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
             VALUES (:user_id, :action, :description, :ip, :agent)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    public static function audit(string $table, string|int $recordId, string $operation, ?array $oldValues, ?array $newValues): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            'INSERT INTO audit_logs (user_id, table_name, record_id, operation, old_values, new_values, ip_address)
             VALUES (:user_id, :table_name, :record_id, :operation, :old_values, :new_values, :ip)'
        );
        $stmt->execute([
            'user_id' => AuthService::id(),
            'table_name' => $table,
            'record_id' => (string) $recordId,
            'operation' => $operation,
            'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
