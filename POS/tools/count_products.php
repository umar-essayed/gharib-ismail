<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . DIRECTORY_SEPARATOR . 'bootstrap.php';

$db = \App\Core\Database::pdo();
$database = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$counts = $db->query(
    'SELECT
        COUNT(*) AS total_products,
        SUM(deleted_at IS NULL) AS visible_products,
        SUM(deleted_at IS NOT NULL) AS hidden_deleted_products,
        SUM(is_active = 1) AS active_products,
        MAX(id) AS last_product_id
     FROM products'
)->fetch();

$latest = $db->query(
    'SELECT id, name, barcode, deleted_at
     FROM products
     ORDER BY id DESC
     LIMIT 10'
)->fetchAll();

echo "Database: {$database}" . PHP_EOL;
echo "Total products: " . (int) ($counts['total_products'] ?? 0) . PHP_EOL;
echo "Visible products: " . (int) ($counts['visible_products'] ?? 0) . PHP_EOL;
echo "Hidden deleted products: " . (int) ($counts['hidden_deleted_products'] ?? 0) . PHP_EOL;
echo "Active products: " . (int) ($counts['active_products'] ?? 0) . PHP_EOL;
echo "Last product id: " . (int) ($counts['last_product_id'] ?? 0) . PHP_EOL;
echo PHP_EOL . "Latest 10 products:" . PHP_EOL;

foreach ($latest as $row) {
    $deleted = $row['deleted_at'] ? 'deleted=' . $row['deleted_at'] : 'visible';
    echo sprintf(
        "#%d | %s | %s | %s",
        (int) $row['id'],
        (string) $row['name'],
        (string) ($row['barcode'] ?? ''),
        $deleted
    ) . PHP_EOL;
}
