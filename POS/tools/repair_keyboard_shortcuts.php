<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . DIRECTORY_SEPARATOR . 'bootstrap.php';

$db = \App\Core\Database::pdo();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'sqlite') {
    $tableExists = (bool) $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='keyboard_shortcuts'")->fetchColumn();
} else {
    $tableExists = (bool) $db->query("SHOW TABLES LIKE 'keyboard_shortcuts'")->fetchColumn();
}

if (!$tableExists) {
    if ($driver === 'sqlite') {
        $db->exec(
            "CREATE TABLE keyboard_shortcuts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                key_code VARCHAR(50) NOT NULL,
                key_label VARCHAR(100) NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                reference_id INTEGER NULL,
                reference_name VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_keyboard_shortcuts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE (user_id, key_code)
            )"
        );
        $db->exec("CREATE INDEX idx_user_id ON keyboard_shortcuts (user_id)");
        $db->exec("CREATE INDEX idx_action_type ON keyboard_shortcuts (action_type)");
    } else {
        $db->exec(
            "CREATE TABLE keyboard_shortcuts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                key_code VARCHAR(50) NOT NULL,
                key_label VARCHAR(100) NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                reference_id BIGINT UNSIGNED NULL,
                reference_name VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_key (user_id, key_code),
                KEY idx_user_id (user_id),
                KEY idx_action_type (action_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}

$users = $db->query(
    'SELECT id, username
     FROM users
     WHERE deleted_at IS NULL AND is_active = 1
     ORDER BY id'
)->fetchAll();

if (!$users) {
    echo "No active users found." . PHP_EOL;
    exit(1);
}

$product = $db->query(
    "SELECT id, name
     FROM products
     WHERE deleted_at IS NULL
     ORDER BY
        CASE
            WHEN name LIKE '%زبادي%' THEN 0
            ELSE 1
        END,
        id DESC
     LIMIT 1"
)->fetch();

if (!$product) {
    echo "No visible products found. Cannot create add_product shortcut." . PHP_EOL;
    exit(1);
}

$sourceShortcut = $db->query(
    "SELECT key_code, key_label, action_type, reference_id, reference_name, is_active
     FROM keyboard_shortcuts
     WHERE action_type = 'add_product'
     ORDER BY id ASC
     LIMIT 1"
)->fetch();

if (!$sourceShortcut) {
    $sourceShortcut = [
        'key_code' => 'Q',
        'key_label' => 'زر Q',
        'action_type' => 'add_product',
        'reference_id' => (int) $product['id'],
        'reference_name' => (string) $product['name'],
        'is_active' => 1,
    ];
}

if (empty($sourceShortcut['reference_id'])) {
    $sourceShortcut['reference_id'] = (int) $product['id'];
}
if (trim((string) ($sourceShortcut['reference_name'] ?? '')) === '') {
    $sourceShortcut['reference_name'] = (string) $product['name'];
}

if ($driver === 'sqlite') {
    $insert = $db->prepare(
        'INSERT INTO keyboard_shortcuts (user_id, key_code, key_label, action_type, reference_id, reference_name, is_active)
         VALUES (:user_id, :key_code, :key_label, :action_type, :reference_id, :reference_name, :is_active)
         ON CONFLICT(user_id, key_code) DO UPDATE SET
            key_label = excluded.key_label,
            action_type = excluded.action_type,
            reference_id = excluded.reference_id,
            reference_name = excluded.reference_name,
            is_active = excluded.is_active,
            updated_at = datetime(\'now\')'
    );
} else {
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
}

$db->beginTransaction();
try {
    foreach ($users as $user) {
        $insert->execute([
            'user_id' => (int) $user['id'],
            'key_code' => (string) $sourceShortcut['key_code'],
            'key_label' => (string) $sourceShortcut['key_label'],
            'action_type' => (string) $sourceShortcut['action_type'],
            'reference_id' => $sourceShortcut['reference_id'] !== null ? (int) $sourceShortcut['reference_id'] : null,
            'reference_name' => (string) ($sourceShortcut['reference_name'] ?? ''),
            'is_active' => (int) ($sourceShortcut['is_active'] ?? 1),
        ]);
    }
    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    throw $e;
}

echo "Keyboard shortcut repaired for " . count($users) . " users." . PHP_EOL;
echo "Shortcut: " . $sourceShortcut['key_code'] . " => " . $sourceShortcut['action_type'] . " / " . $sourceShortcut['reference_name'] . PHP_EOL;

$rows = $db->query(
    'SELECT u.id AS user_id, u.username, COUNT(k.id) AS shortcuts_count
     FROM users u
     LEFT JOIN keyboard_shortcuts k ON k.user_id = u.id
     WHERE u.deleted_at IS NULL
     GROUP BY u.id, u.username
     ORDER BY u.id'
)->fetchAll();

foreach ($rows as $row) {
    echo sprintf(
        "user #%d %s shortcuts=%d",
        (int) $row['user_id'],
        (string) $row['username'],
        (int) $row['shortcuts_count']
    ) . PHP_EOL;
}
