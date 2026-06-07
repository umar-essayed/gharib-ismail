#!/usr/bin/env php
<?php
/**
 * sync_worker.php
 * يُشغَّل كـ Cron Job كل 5 دقائق لمعالجة طابور المزامنة المحلي
 *
 * Windows Task Scheduler:
 *   php C:\xampp\htdocs\POSG\tools\sync_worker.php >> C:\xampp\htdocs\POSG\storage\sync.log 2>&1
 *
 * Linux Cron:
 *   * /5 * * * * php /var/www/html/POSG/tools/sync_worker.php >> /var/www/html/POSG/storage/sync.log 2>&1
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/bootstrap.php';

use App\Services\SupabaseSyncService;
use App\Core\Database;

$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] بدء معالجة الطابور...\n";

try {
    $result = SupabaseSyncService::processQueue();
    echo "[{$timestamp}] تمت المعالجة: {$result['processed']} نجح | {$result['failed']} فشل | {$result['total']} إجمالي\n";
} catch (\Throwable $e) {
    echo "[{$timestamp}] خطأ: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[{$timestamp}] انتهى.\n";
exit(0);
