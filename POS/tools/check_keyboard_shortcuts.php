<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . DIRECTORY_SEPARATOR . 'bootstrap.php';

$db = \App\Core\Database::pdo();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'sqlite') {
    $database = basename(config('database')['database'] ?? 'posg.sqlite');
    $tableExists = (bool) $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='keyboard_shortcuts'")->fetchColumn();
} else {
    $database = (string) $db->query('SELECT DATABASE()')->fetchColumn();
    $tableExists = (bool) $db->query("SHOW TABLES LIKE 'keyboard_shortcuts'")->fetchColumn();
}
echo "Database: {$database}" . PHP_EOL;
echo 'keyboard_shortcuts table: ' . ($tableExists ? 'yes' : 'no') . PHP_EOL;

if (!$tableExists) {
    exit(0);
}

$total = (int) $db->query('SELECT COUNT(*) FROM keyboard_shortcuts')->fetchColumn();
echo "Total keyboard shortcuts: {$total}" . PHP_EOL . PHP_EOL;

$rows = $db->query(
    'SELECT u.id AS user_id, u.username, u.full_name, COUNT(k.id) AS shortcuts_count
     FROM users u
     LEFT JOIN keyboard_shortcuts k ON k.user_id = u.id
     WHERE u.deleted_at IS NULL
     GROUP BY u.id, u.username, u.full_name
     ORDER BY u.id'
)->fetchAll();

echo "Shortcuts by user:" . PHP_EOL;
foreach ($rows as $row) {
    echo sprintf(
        "#%d | %s | %s | shortcuts=%d",
        (int) $row['user_id'],
        (string) $row['username'],
        (string) $row['full_name'],
        (int) $row['shortcuts_count']
    ) . PHP_EOL;
}

echo PHP_EOL . "Shortcut rows:" . PHP_EOL;
$shortcuts = $db->query(
    'SELECT id, user_id, key_code, key_label, action_type, reference_id, reference_name, is_active
     FROM keyboard_shortcuts
     ORDER BY user_id, key_code'
)->fetchAll();

foreach ($shortcuts as $shortcut) {
    echo sprintf(
        "#%d | user=%d | key=%s | label=%s | action=%s | ref=%s | active=%d",
        (int) $shortcut['id'],
        (int) $shortcut['user_id'],
        (string) $shortcut['key_code'],
        (string) $shortcut['key_label'],
        (string) $shortcut['action_type'],
        (string) ($shortcut['reference_name'] ?? $shortcut['reference_id'] ?? ''),
        (int) $shortcut['is_active']
    ) . PHP_EOL;
}
