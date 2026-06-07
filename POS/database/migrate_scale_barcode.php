<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Services\DatabaseBackupService;

$migrationPath = __DIR__ . '/migrations/2026_04_29_scale_barcode_support.sql';
if (!is_file($migrationPath)) {
    fwrite(STDERR, "Migration file not found: {$migrationPath}\n");
    exit(1);
}

try {
    $backupPath = DatabaseBackupService::createSafetyBackup('pre_scale_migration_');
    echo "Backup created: {$backupPath}\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Failed to create backup: {$e->getMessage()}\n");
    exit(1);
}

$sql = file_get_contents($migrationPath);
if ($sql === false) {
    fwrite(STDERR, "Unable to read migration SQL.\n");
    exit(1);
}

$pdo = Database::pdo();
$statements = [];
$buffer = '';

foreach (preg_split("/\r\n|\n|\r/", $sql) as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || str_starts_with($trimmed, '--')) {
        continue;
    }
    $buffer .= $line . "\n";
    if (str_ends_with(rtrim($line), ';')) {
        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = rtrim($statement, "; \t\n\r\0\x0B");
        }
        $buffer = '';
    }
}

if (trim($buffer) !== '') {
    $statements[] = trim($buffer);
}

try {
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
    echo "Scale barcode migration applied successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: {$e->getMessage()}\n");
    exit(1);
}
