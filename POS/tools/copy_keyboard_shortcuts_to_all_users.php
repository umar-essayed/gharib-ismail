<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . DIRECTORY_SEPARATOR . 'bootstrap.php';

$db = \App\Core\Database::pdo();
$sourceUserId = isset($argv[1]) ? (int) $argv[1] : 0;

if ($sourceUserId <= 0) {
    $sourceUserId = (int) $db->query(
        'SELECT user_id
         FROM keyboard_shortcuts
         GROUP BY user_id
         ORDER BY COUNT(*) DESC, user_id ASC
         LIMIT 1'
    )->fetchColumn();
}

if ($sourceUserId <= 0) {
    echo "No keyboard shortcuts found." . PHP_EOL;
    exit(0);
}

$sourceStmt = $db->prepare(
    'SELECT key_code, key_label, action_type, reference_id, reference_name, is_active
     FROM keyboard_shortcuts
     WHERE user_id = :user_id
     ORDER BY key_code ASC'
);
$sourceStmt->execute(['user_id' => $sourceUserId]);
$shortcuts = $sourceStmt->fetchAll();

if (!$shortcuts) {
    echo "Source user has no keyboard shortcuts." . PHP_EOL;
    exit(0);
}

$users = $db->query('SELECT id, username FROM users WHERE deleted_at IS NULL AND is_active = 1 ORDER BY id')->fetchAll();
$insert = $db->prepare(
    'INSERT INTO keyboard_shortcuts (user_id, key_code, key_label, action_type, reference_id, reference_name, is_active)
     VALUES (:user_id, :key_code, :key_label, :action_type, :reference_id, :reference_name, :is_active)
     ON DUPLICATE KEY UPDATE
        key_label = VALUES(key_label),
        action_type = VALUES(action_type),
        reference_id = VALUES(reference_id),
        reference_name = VALUES(reference_name),
        is_active = VALUES(is_active),
        updated_at = NOW()'
);

$copied = 0;
$db->beginTransaction();
try {
    foreach ($users as $user) {
        foreach ($shortcuts as $shortcut) {
            $insert->execute([
                'user_id' => (int) $user['id'],
                'key_code' => $shortcut['key_code'],
                'key_label' => $shortcut['key_label'],
                'action_type' => $shortcut['action_type'],
                'reference_id' => $shortcut['reference_id'],
                'reference_name' => $shortcut['reference_name'],
                'is_active' => (int) $shortcut['is_active'],
            ]);
            $copied++;
        }
    }
    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    throw $e;
}

echo "Source user: {$sourceUserId}" . PHP_EOL;
echo "Users updated: " . count($users) . PHP_EOL;
echo "Shortcut writes: {$copied}" . PHP_EOL;
