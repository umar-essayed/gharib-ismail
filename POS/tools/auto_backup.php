<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$reason = 'periodic';
$keep = (int) ($_SERVER['POSG_AUTO_BACKUP_KEEP'] ?? getenv('POSG_AUTO_BACKUP_KEEP') ?: 144);

foreach ($argv ?? [] as $index => $arg) {
    if ($arg === '--reason' && isset($argv[$index + 1])) {
        $reason = (string) $argv[$index + 1];
        continue;
    }

    if (str_starts_with((string) $arg, '--reason=')) {
        $reason = substr((string) $arg, 9);
    }
}

$reason = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]+/', '_', $reason));
$reason = trim($reason, '_-') ?: 'periodic';
$keep = max(12, min(1000, $keep));

$logDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
$backupDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0775, true);
}

$logFile = $logDir . DIRECTORY_SEPARATOR . 'auto_backup.log';
$lockPath = $backupDir . DIRECTORY_SEPARATOR . 'auto_backup.lock';

$log = static function (string $message) use ($logFile): void {
    $line = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
};

$lock = fopen($lockPath, 'c');
if ($lock === false) {
    $log('[ERROR] cannot open lock file');
    exit(1);
}

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    $log('[INFO] backup skipped because another backup is running');
    fclose($lock);
    exit(0);
}

try {
    require $root . DIRECTORY_SEPARATOR . 'bootstrap.php';

    $path = \App\Services\DatabaseBackupService::createSafetyBackup('auto_' . $reason . '_');
    $log('[OK] backup created: ' . $path);

    // الرفع التلقائي إلى Supabase سحابياً لزيادة الأمان
    $log('[INFO] uploading backup to Supabase Storage...');
    $uploaded = \App\Services\SupabaseSyncService::uploadBackup($path);
    if ($uploaded) {
        $log('[OK] backup uploaded successfully to Supabase Storage!');
    } else {
        $log('[WARNING] failed to upload backup to Supabase (make sure "backups" storage bucket exists and is private)');
    }

    $files = glob($backupDir . DIRECTORY_SEPARATOR . 'auto_*.zip') ?: [];
    usort($files, static fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));

    foreach (array_slice($files, $keep) as $oldFile) {
        if (@unlink($oldFile)) {
            $log('[INFO] old backup removed: ' . $oldFile);
        }
    }
} catch (\Throwable $e) {
    $log('[ERROR] ' . $e->getMessage());
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    flock($lock, LOCK_UN);
    fclose($lock);
    exit(1);
}

flock($lock, LOCK_UN);
fclose($lock);
