<?php

return [
    'name' => 'نظام نقاط بيع POSG',
    'base_url' => (isset($_SERVER['SCRIPT_NAME']) && str_contains($_SERVER['SCRIPT_NAME'], 'public')) ? '/POSG/public' : '',
    'timezone' => 'Africa/Cairo',
    'locale' => 'ar',
    'debug' => true,
    'session_name' => 'posg_session',
    'csrf_key' => '_csrf_token',
    'fixed_invoice_footer' => 'الدعم الفني Glory Tech م/احمد ابو المجد',
    'receipt_orders_phone' => '٠١٠١٩٧٨٦٠٣٤ - ٠١٢٧٧٩٧٨٢١٠',
    'support_tech_line' => 'الدعم الفني Glory Tech م/احمد ابو المجد',
    'glory_support_qr_payload' => 'Glory Tech | المهندس احمد ابو المجد | 01032162163',
    'danger_reset_password_hash' => '$2y$10$.aVLmP9d1wd7IwzAzITAyueJDjUyE9MufncEjuxpxxuqIp1ie1p.S',
    'webhook_token' => 'nasriya_pos_webhook_secret_key_2026',
    'upload_dir' => __DIR__ . '/../public/uploads',
    'log_file' => __DIR__ . '/../storage/logs/app.log',
    'error_log_file' => __DIR__ . '/../storage/logs/error.log',
];
