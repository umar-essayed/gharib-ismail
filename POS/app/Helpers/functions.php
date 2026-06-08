<?php

use App\Core\Session;

function base_path(string $path = ''): string
{
    $base = dirname(__DIR__, 2);
    return $path ? $base . '/' . ltrim($path, '/') : $base;
}

function config(string $key): array
{
    static $loaded = [];
    if (!isset($loaded[$key])) {
        $path = base_path('config/' . $key . '.php');
        $loaded[$key] = require $path;
    }

    return $loaded[$key];
}

function url(string $path = ''): string
{
    $base = rtrim(config('app')['base_url'], '/');
    return $base . '/' . ltrim($path, '/');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function old(string $key, mixed $default = ''): mixed
{
    return Session::getFlash('old_' . $key, $default);
}

function set_old(array $data): void
{
    foreach ($data as $key => $value) {
        if (!is_array($value)) {
            Session::flash('old_' . $key, $value);
        }
    }
}

function flash_success(string $message): void
{
    Session::flash('success', $message);
}

function flash_error(string $message): void
{
    Session::flash('error', $message);
}

function current_user(): ?array
{
    \App\Services\AuthService::syncSessionUser();
    return Session::get('user');
}

function can(string $permission): bool
{
    return \App\Services\AuthService::can($permission);
}

function csrf_field(): string
{
    $cfg = config('app');
    $token = \App\Core\Csrf::token($cfg['csrf_key']);
    return '<input type="hidden" name="_token" value="' . e($token) . '">';
}

function validate_csrf_or_abort(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && ($_POST['_method'] ?? '') === '') {
        return;
    }

    $cfg = config('app');
    $token = $_POST['_token'] ?? null;

    if (!\App\Core\Csrf::validate($cfg['csrf_key'], $token)) {
        http_response_code(419);
        throw new RuntimeException('انتهت صلاحية الجلسة، أعد المحاولة.');
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function money(float|int|string|null $value): string
{
    return number_format((float) $value, 2);
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
}

function redirect_back(string $default = '/dashboard'): void
{
    $target = $_SERVER['HTTP_REFERER'] ?? url($default);
    header('Location: ' . $target);
    exit;
}

function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function fixed_invoice_footer(): string
{
    $value = \App\Services\SettingsService::get('invoice_footer');
    if ($value !== null && trim((string)$value) !== '') {
        return trim((string)$value);
    }
    return 'صل ع النبي';
}

function receipt_orders_phone(): string
{
    $value = trim((string) (config('app')['receipt_orders_phone'] ?? ''));
    return $value !== '' ? $value : '01286868676';
}

function support_tech_line(): string
{
    return fixed_invoice_footer();
}

function support_qr_payload(): string
{
    return 'https://nassryaa-gomla.markets';
}

function support_qr_image_url(int $size = 82): string
{
    $safeSize = max(48, min(240, $size));
    // Always generate a QR code with the store's custom payload dynamically
    return 'https://quickchart.io/qr?size=' . $safeSize . '&text=' . rawurlencode(support_qr_payload());
}
