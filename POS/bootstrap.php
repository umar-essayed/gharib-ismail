<?php

declare(strict_types=1);

require __DIR__ . '/app/Helpers/functions.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/app/' . $relative . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$appConfig = config('app');
date_default_timezone_set($appConfig['timezone']);

App\Core\Session::start($appConfig['session_name']);

set_exception_handler(function (Throwable $e): void {
    App\Core\Logger::error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    http_response_code(500);
    App\Core\View::render('errors/500', ['message' => $e->getMessage()], 'layouts/guest');
});

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

App\Core\Database::connect(config('database'));

// تأمين النظام: منع تصفح واجهة الكاشير عبر نفق كلاود فلير العام والسماح فقط بمسار الـ Webhook
$isCloudflare = isset($_SERVER['HTTP_CF_RAY']) || isset($_SERVER['HTTP_CF_CONNECTING_IP']) || isset($_SERVER['HTTP_CDN_LOOP']);

if ($isCloudflare) {
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    // السماح فقط بمسار الـ Webhook الخاص باستقبال طلبات المتجر الجديد
    if ($requestUri !== '/api/webhook/new-order') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'عذراً، غير مسموح بالوصول إلى لوحة التحكم عبر هذا النفق السحابي. فقط مسارات الـ Webhook هي المتاحة.']);
        exit;
    }
}
