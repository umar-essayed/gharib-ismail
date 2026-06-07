<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class DatabaseBackupService
{
    public static function createSafetyBackup(string $prefix = 'pre_migration_'): string
    {
        set_time_limit(0);
        self::ensureZipSupport();

        $dir = base_path('storage/backups');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('تعذر إنشاء مجلد النسخ الاحتياطي');
        }

        $path = $dir . DIRECTORY_SEPARATOR . $prefix . date('Ymd_His') . '.zip';
        self::buildBackupArchive($path);
        return $path;
    }

    public static function downloadPackage(): void
    {
        set_time_limit(0);
        self::ensureZipSupport();

        $databaseName = self::currentDatabaseName(Database::pdo());
        if ($databaseName === '') {
            throw new RuntimeException('تعذر تحديد قاعدة البيانات الحالية');
        }

        $filename = sprintf('%s_full_backup_%s.zip', $databaseName, date('Ymd_His'));
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('posg_backup_', true) . '.zip';

        self::buildBackupArchive($zipPath);
        self::sendHeaders($filename, 'application/zip');

        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }

    public static function restoreFromUpload(array $file): array
    {
        set_time_limit(0);

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('تعذر رفع الملف، تأكد من اختيار نسخة احتياطية صحيحة');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('ملف النسخة الاحتياطية غير صالح');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['zip', 'sql'], true)) {
            throw new RuntimeException('نوع الملف غير مدعوم. ارفع ملف ZIP أو SQL');
        }

        $safetyPath = self::createSafetySnapshot();

        if ($extension === 'sql') {
            self::restoreDatabaseFromSqlFile($tmpPath);
            return ['safety_backup' => $safetyPath, 'restored_uploads' => false];
        }

        self::ensureZipSupport();
        $extractDir = self::makeTempDir('posg_restore_');
        try {
            $zip = new ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                throw new RuntimeException('تعذر فتح ملف النسخة الاحتياطية المضغوط');
            }
            if (!$zip->extractTo($extractDir)) {
                $zip->close();
                throw new RuntimeException('تعذر فك ضغط النسخة الاحتياطية');
            }
            $zip->close();

            $sqlFile = self::findSqlFile($extractDir);
            if ($sqlFile === null) {
                throw new RuntimeException('ملف SQL غير موجود داخل النسخة الاحتياطية');
            }

            self::restoreDatabaseFromSqlFile($sqlFile);

            $uploadsDir = self::findDirectoryByName($extractDir, 'uploads');
            $uploadsRestored = false;
            if ($uploadsDir !== null) {
                self::replaceUploads($uploadsDir);
                $uploadsRestored = true;
            }

            return ['safety_backup' => $safetyPath, 'restored_uploads' => $uploadsRestored];
        } finally {
            self::deleteDirectory($extractDir);
        }
    }

    private static function buildBackupArchive(string $targetZipPath): void
    {
        $db = Database::pdo();
        $databaseName = self::currentDatabaseName($db);
        if ($databaseName === '') {
            throw new RuntimeException('تعذر تحديد قاعدة البيانات الحالية');
        }

        $tmpDir = self::makeTempDir('posg_backup_build_');
        try {
            $sqlPath = $tmpDir . DIRECTORY_SEPARATOR . 'database.sql';
            self::writeSqlDumpToFile($sqlPath, $db, $databaseName);

            $manifest = [
                'generated_at' => date('c'),
                'database' => $databaseName,
                'app_name' => (string) (config('app')['name'] ?? 'POSG'),
                'includes' => [
                    'database.sql',
                    'uploads/',
                    'config/app.php',
                    'config/database.php',
                ],
                'version' => 1,
            ];

            $zip = new ZipArchive();
            if ($zip->open($targetZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('تعذر إنشاء ملف النسخة الاحتياطية');
            }

            $zip->addFile($sqlPath, 'database.sql');
            $zip->addFromString('backup_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            self::addFileIfExists($zip, base_path('config/app.php'), 'config/app.php');
            self::addFileIfExists($zip, base_path('config/database.php'), 'config/database.php');

            $uploadsDir = base_path('public/uploads');
            if (is_dir($uploadsDir)) {
                self::addDirectoryToZip($zip, $uploadsDir, 'uploads');
            }

            $zip->close();
        } finally {
            self::deleteDirectory($tmpDir);
        }
    }

    private static function writeSqlDumpToFile(string $targetPath, PDO $db, string $databaseName): void
    {
        $handle = fopen($targetPath, 'wb');
        if (!$handle) {
            throw new RuntimeException('تعذر إنشاء ملف SQL للنسخة الاحتياطية');
        }

        try {
            self::writeLine($handle, '-- POSG SQL Backup');
            self::writeLine($handle, '-- Generated at: ' . date('Y-m-d H:i:s'));
            self::writeLine($handle, '-- Database: `' . $databaseName . '`');
            self::writeLine($handle, '');
            self::writeLine($handle, 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";');
            self::writeLine($handle, 'START TRANSACTION;');
            self::writeLine($handle, 'SET time_zone = "+00:00";');
            self::writeLine($handle, 'SET FOREIGN_KEY_CHECKS = 0;');
            self::writeLine($handle, '/*!40101 SET NAMES utf8mb4 */;');
            self::writeLine($handle, '');

            $tables = self::listTables($db);
            foreach ($tables as $table) {
                self::dumpTable($db, $table, $handle);
            }

            self::writeLine($handle, 'SET FOREIGN_KEY_CHECKS = 1;');
            self::writeLine($handle, 'COMMIT;');
            self::writeLine($handle, '');
        } finally {
            fclose($handle);
        }
    }

    private static function dumpTable(PDO $db, string $table, $handle): void
    {
        $safeTable = self::escapeIdentifier($table);
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $db->prepare('SELECT sql FROM sqlite_master WHERE type IN ("table", "view") AND name = :name');
            $stmt->execute(['name' => $table]);
            $createStmt = $stmt->fetch(PDO::FETCH_NUM);
        } else {
            $createStmt = $db->query('SHOW CREATE TABLE `' . $safeTable . '`')->fetch(PDO::FETCH_NUM);
        }
        
        if (!$createStmt || empty($createStmt[0])) {
            return;
        }

        self::writeLine($handle, '-- --------------------------------------------------------');
        self::writeLine($handle, '-- Table structure for table `' . $table . '`');
        self::writeLine($handle, '-- --------------------------------------------------------');
        self::writeLine($handle, 'DROP TABLE IF EXISTS `' . $safeTable . '`;');
        self::writeLine($handle, (string) ($createStmt[1] ?? $createStmt[0]) . ';');
        self::writeLine($handle, '');

        $rowsStmt = $db->query('SELECT * FROM `' . $safeTable . '`');
        $batch = [];
        $columns = [];

        while ($row = $rowsStmt->fetch(PDO::FETCH_ASSOC)) {
            if (empty($columns)) {
                $columns = array_keys($row);
            }

            $values = [];
            foreach ($columns as $column) {
                $values[] = self::toSqlValue($db, $row[$column] ?? null);
            }
            $batch[] = '(' . implode(', ', $values) . ')';

            if (count($batch) >= 200) {
                self::writeInsertBatch($handle, $table, $columns, $batch);
                $batch = [];
            }
        }

        if (!empty($batch) && !empty($columns)) {
            self::writeInsertBatch($handle, $table, $columns, $batch);
        }

        self::writeLine($handle, '');
    }

    private static function writeInsertBatch($handle, string $table, array $columns, array $batch): void
    {
        $safeColumns = array_map(
            static fn(string $column): string => '`' . self::escapeIdentifier($column) . '`',
            $columns
        );
        $safeTable = self::escapeIdentifier($table);

        self::writeLine($handle, 'INSERT INTO `' . $safeTable . '` (' . implode(', ', $safeColumns) . ') VALUES');
        self::writeLine($handle, implode(",\n", $batch) . ';');
    }

    private static function restoreDatabaseFromSqlFile(string $sqlPath): void
    {
        $dbConfig = config('database');
        $db = Database::pdo();
        self::clearCurrentDatabase($db);

        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = file_get_contents($sqlPath);
            if ($sql === false) {
                throw new RuntimeException('تعذر قراءة ملف SQL');
            }
            $db->exec('PRAGMA foreign_keys = OFF;');
            $db->exec($sql);
            $db->exec('PRAGMA foreign_keys = ON;');
            return;
        }

        $mysqlFailureReason = null;
        if (self::restoreViaMysqlClient($sqlPath, $dbConfig, $mysqlFailureReason)) {
            return;
        }

        $sql = file_get_contents($sqlPath);
        if ($sql === false) {
            throw new RuntimeException('تعذر قراءة ملف SQL');
        }

        // mysql import قد يفشل بعد تنفيذ جزء من الملف؛ نعيد تنظيف القاعدة قبل fallback.
        self::clearCurrentDatabase($db);

        try {
            self::executeSqlScript($db, $sql);
        } catch (\Throwable $e) {
            $message = 'فشل استعادة النسخة: ' . self::singleLine($e->getMessage());
            if (is_string($mysqlFailureReason) && $mysqlFailureReason !== '') {
                $message .= ' (mysql: ' . self::singleLine($mysqlFailureReason) . ')';
            }

            throw new RuntimeException($message, 0, $e);
        }
    }

    private static function restoreViaMysqlClient(string $sqlPath, array $dbConfig, ?string &$failureReason = null): bool
    {
        $mysqlBin = self::detectMysqlBinary();
        if ($mysqlBin === null) {
            $failureReason = 'mysql client not found';
            return false;
        }

        $database = (string) ($dbConfig['database'] ?? '');
        if ($database === '') {
            throw new RuntimeException('تعذر تحديد قاعدة البيانات الحالية');
        }

        $host = (string) ($dbConfig['host'] ?? '127.0.0.1');
        $port = (int) ($dbConfig['port'] ?? 3306);
        $username = (string) ($dbConfig['username'] ?? 'root');
        $password = (string) ($dbConfig['password'] ?? '');

        $command = escapeshellarg($mysqlBin)
            . ' --protocol=tcp'
            . ' -h' . escapeshellarg($host)
            . ' -P' . $port
            . ' -u' . escapeshellarg($username)
            . ' ' . escapeshellarg($database);

        $descriptorSpec = [
            0 => ['file', $sqlPath, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = null;
        if ($password !== '') {
            $env = $_ENV;
            $env['MYSQL_PWD'] = $password;
        }

        if (!function_exists('proc_open')) {
            $failureReason = 'proc_open disabled';
            return false;
        }

        $process = proc_open($command, $descriptorSpec, $pipes, null, $env);
        if (!is_resource($process)) {
            $failureReason = 'cannot start mysql process';
            return false;
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            $error = trim((string) $stderr);
            if ($error === '') {
                $error = trim((string) $stdout);
            }
            if ($error === '') {
                $error = 'mysql import failed with exit code ' . $exitCode;
            }
            $failureReason = self::singleLine($error);
            return false;
        }

        return true;
    }

    private static function clearCurrentDatabase(PDO $db): void
    {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $db->exec('PRAGMA foreign_keys = OFF;');
            $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $db->exec('DROP TABLE IF EXISTS "' . $table . '"');
            }
            $views = $db->query("SELECT name FROM sqlite_master WHERE type='view'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($views as $view) {
                $db->exec('DROP VIEW IF EXISTS "' . $view . '"');
            }
            $db->exec('PRAGMA foreign_keys = ON;');
            return;
        }

        $views = [];
        $tables = [];

        $stmt = $db->query('SHOW FULL TABLES');
        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
            if (empty($row[0])) {
                continue;
            }

            $name = (string) $row[0];
            $type = strtoupper((string) ($row[1] ?? 'BASE TABLE'));

            if ($type === 'VIEW') {
                $views[] = $name;
            } else {
                $tables[] = $name;
            }
        }

        $db->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($views as $view) {
                $db->exec('DROP VIEW IF EXISTS `' . self::escapeIdentifier($view) . '`');
            }

            foreach ($tables as $table) {
                $db->exec('DROP TABLE IF EXISTS `' . self::escapeIdentifier($table) . '`');
            }
        } finally {
            $db->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    private static function detectMysqlBinary(): ?string
    {
        $candidates = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $candidates[] = 'C:\\xampp\\mysql\\bin\\mysql.exe';
            $candidates[] = 'C:\\xampp\\bin\\mysql\\mysql8.0.30\\bin\\mysql.exe';
        } else {
            $candidates[] = '/Applications/XAMPP/xamppfiles/bin/mysql';
            $candidates[] = '/opt/lampp/bin/mysql';
            $candidates[] = '/usr/local/bin/mysql';
            $candidates[] = '/usr/bin/mysql';
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        $lookup = PHP_OS_FAMILY === 'Windows'
            ? @shell_exec('where mysql 2>nul')
            : @shell_exec('command -v mysql 2>/dev/null');
        $lookup = trim((string) $lookup);

        if ($lookup !== '') {
            $first = trim((string) strtok($lookup, PHP_EOL));
            if ($first !== '' && is_file($first) && is_executable($first)) {
                return $first;
            }
        }

        return null;
    }

    private static function singleLine(string $message): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($message));
        if ($normalized === null || $normalized === '') {
            return 'Unknown error';
        }

        return mb_substr($normalized, 0, 220);
    }

    private static function executeSqlScript(PDO $db, string $sql): void
    {
        $buffer = '';
        $length = strlen($sql);
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                }
                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                if ($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) {
                    $inLineComment = true;
                    $i++;
                    continue;
                }

                if ($char === '#') {
                    $inLineComment = true;
                    continue;
                }

                if ($char === '/' && $next === '*') {
                    if ($i + 2 < $length && $sql[$i + 2] === '!') {
                        $endPos = strpos($sql, '*/', $i + 3);
                        if ($endPos === false) {
                            break;
                        }
                        $inner = substr($sql, $i + 3, $endPos - ($i + 3));
                        $inner = preg_replace('/^\d+\s*/', '', $inner);
                        $buffer .= $inner;
                        $i = $endPos + 1;
                        continue;
                    }

                    $inBlockComment = true;
                    $i++;
                    continue;
                }

                if ($char === ';') {
                    $statement = trim($buffer);
                    if ($statement !== '') {
                        $db->exec($statement);
                    }
                    $buffer = '';
                    continue;
                }
            }

            if (!$inDoubleQuote && !$inBacktick && $char === "'" && !self::isEscaped($sql, $i)) {
                $inSingleQuote = !$inSingleQuote;
            } elseif (!$inSingleQuote && !$inBacktick && $char === '"' && !self::isEscaped($sql, $i)) {
                $inDoubleQuote = !$inDoubleQuote;
            } elseif (!$inSingleQuote && !$inDoubleQuote && $char === '`') {
                $inBacktick = !$inBacktick;
            }

            $buffer .= $char;
        }

        $statement = trim($buffer);
        if ($statement !== '') {
            $db->exec($statement);
        }
    }

    private static function isEscaped(string $text, int $position): bool
    {
        $slashes = 0;
        for ($i = $position - 1; $i >= 0; $i--) {
            if ($text[$i] !== '\\') {
                break;
            }
            $slashes++;
        }

        return ($slashes % 2) === 1;
    }

    private static function replaceUploads(string $sourceUploadsDir): void
    {
        $targetUploadsDir = base_path('public/uploads');
        if (!is_dir($targetUploadsDir) && !mkdir($targetUploadsDir, 0775, true) && !is_dir($targetUploadsDir)) {
            throw new RuntimeException('تعذر إنشاء مجلد uploads');
        }

        self::clearDirectory($targetUploadsDir);
        self::copyDirectory($sourceUploadsDir, $targetUploadsDir);
    }

    private static function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
            throw new RuntimeException('تعذر إنشاء مجلد: ' . $destination);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relative = substr($sourcePath, strlen($source) + 1);
            $targetPath = $destination . DIRECTORY_SEPARATOR . $relative;

            if ($item->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
                    throw new RuntimeException('تعذر إنشاء مجلد: ' . $targetPath);
                }
                continue;
            }

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw new RuntimeException('تعذر إنشاء مجلد: ' . $targetDir);
            }

            if (!copy($sourcePath, $targetPath)) {
                throw new RuntimeException('تعذر نسخ ملف: ' . $relative);
            }
        }
    }

    private static function clearDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }

    private static function createSafetySnapshot(): string
    {
        $dir = base_path('storage/backups');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('تعذر إنشاء مجلد النسخ الاحتياطي الداخلي');
        }

        $path = $dir . DIRECTORY_SEPARATOR . 'pre_restore_' . date('Ymd_His') . '.zip';
        self::buildBackupArchive($path);

        return $path;
    }

    private static function findSqlFile(string $root): ?string
    {
        $direct = $root . DIRECTORY_SEPARATOR . 'database.sql';
        if (is_file($direct)) {
            return $direct;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $name = strtolower($item->getFilename());
            if ($name === 'database.sql') {
                return $item->getPathname();
            }
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && strtolower($item->getExtension()) === 'sql') {
                return $item->getPathname();
            }
        }

        return null;
    }

    private static function findDirectoryByName(string $root, string $targetName): ?string
    {
        $direct = $root . DIRECTORY_SEPARATOR . $targetName;
        if (is_dir($direct)) {
            return $direct;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir() && strtolower($item->getFilename()) === strtolower($targetName)) {
                return $item->getPathname();
            }
        }

        return null;
    }

    private static function addDirectoryToZip(ZipArchive $zip, string $sourceDir, string $zipPrefix): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relative = substr($sourcePath, strlen($sourceDir) + 1);
            $zipPath = $zipPrefix . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relative);

            if ($item->isDir()) {
                $zip->addEmptyDir($zipPath);
                continue;
            }

            $zip->addFile($sourcePath, $zipPath);
        }
    }

    private static function addFileIfExists(ZipArchive $zip, string $sourcePath, string $zipPath): void
    {
        if (is_file($sourcePath)) {
            $zip->addFile($sourcePath, $zipPath);
        }
    }

    private static function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }

    private static function makeTempDir(string $prefix): string
    {
        $baseCandidates = array_values(array_unique(array_filter([
            rtrim((string) sys_get_temp_dir(), DIRECTORY_SEPARATOR),
            rtrim(base_path('storage/tmp'), DIRECTORY_SEPARATOR),
            rtrim(base_path('storage/backups/tmp'), DIRECTORY_SEPARATOR),
        ])));

        foreach ($baseCandidates as $base) {
            if ($base === '') {
                continue;
            }

            if (!is_dir($base) && !@mkdir($base, 0775, true) && !is_dir($base)) {
                continue;
            }

            if (!is_writable($base)) {
                continue;
            }

            $path = $base . DIRECTORY_SEPARATOR . $prefix . uniqid('', true);
            if (@mkdir($path, 0775, true) || is_dir($path)) {
                return $path;
            }
        }

        throw new RuntimeException('تعذر إنشاء مجلد مؤقت، تحقق من صلاحيات الكتابة على مجلد storage');
    }

    private static function ensureZipSupport(): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('إضافة ZIP غير متاحة على الخادم');
        }
    }

    private static function currentDatabaseName(PDO $db): string
    {
        $configured = (string) (config('database')['database'] ?? '');
        if ($configured !== '') {
            return $configured;
        }

        $name = $db->query('SELECT DATABASE()')->fetchColumn();
        return is_string($name) ? $name : '';
    }

    private static function listTables(PDO $db): array
    {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = [];
        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
            if (!empty($row[0])) {
                $tables[] = (string) $row[0];
            }
        }

        return $tables;
    }

    private static function toSqlValue(PDO $db, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $db->quote($value);
        }

        return $db->quote((string) $value);
    }

    private static function sendHeaders(string $filename, string $contentType): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private static function writeLine($handle, string $line): void
    {
        fwrite($handle, $line . PHP_EOL);
    }

    private static function escapeIdentifier(string $value): string
    {
        return str_replace('`', '``', $value);
    }
}
