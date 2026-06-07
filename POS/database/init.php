<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/database.php';
$dbPath = $config['database'] ?? __DIR__ . '/posg.sqlite';

if (file_exists($dbPath)) {
    unlink($dbPath);
}

$dir = dirname($dbPath);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec('PRAGMA foreign_keys = OFF;');

$sql = file_get_contents(__DIR__ . '/full_install.sql');
if ($sql === false) {
    throw new RuntimeException('Unable to read full_install.sql');
}

try {
    $pdo->exec($sql);
    $pdo->exec('PRAGMA foreign_keys = ON;');
    echo "Database initialized successfully at {$dbPath}.\n";
} catch (Throwable $e) {
    echo "Initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}
