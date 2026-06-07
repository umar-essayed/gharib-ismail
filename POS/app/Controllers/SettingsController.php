<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Controllers\CashierKeyboardController;
use App\Models\CashierKeyboardModel;
use App\Services\AuthService;
use App\Services\DatabaseBackupService;
use App\Services\DataResetService;
use App\Services\LogService;
use App\Services\SettingsService;

class SettingsController extends Controller
{
    public function index(): void
    {
        $settings = SettingsService::all();
        $keyboardShortcuts = [];
        $keyboardActionTypes = CashierKeyboardController::actionTypes();
        $userId = current_user()['id'] ?? null;

        if ($userId) {
            $keyboardShortcuts = (new CashierKeyboardModel())->allForUser((int) $userId);
        }

        $this->view('settings/index', compact('settings', 'keyboardShortcuts', 'keyboardActionTypes'));
    }

    public function save(): void
    {
        validate_csrf_or_abort();

        $companyName = trim((string) input('company_name', ''));
        if ($companyName === '') {
            flash_error('اسم المتجر مطلوب');
            $this->redirect('/settings');
        }

        $keys = [
            'company_name',
            'company_phone',
            'company_address',
            'tax_number',
            'currency',
            'receipt_print_mode',
            'default_branch_id',
            'default_warehouse_id',
            'require_shift_for_sale',
            'allow_negative_stock',
            'low_stock_alert_enabled',
            'scale_barcode_enabled',
            'scale_barcode_prefix',
            'scale_barcode_total_length',
            'scale_barcode_mode',
            'scale_item_code_start',
            'scale_item_code_length',
            'scale_weight_start',
            'scale_weight_length',
            'scale_weight_decimals',
            'scale_price_start',
            'scale_price_length',
            'scale_price_decimals',
            'scale_check_digit_enabled',
            'scale_max_weight_kg',
            'invoice_footer',
            'default_printer',
            'label_printer',
            'cloudflare_tunnel_token',
            'cloudflare_tunnel_domain',
        ];

        foreach ($keys as $key) {
            if ($key !== 'company_name' && !array_key_exists($key, $_POST)) {
                continue;
            }
            $value = $key === 'company_name'
                ? $companyName
                : trim((string) input($key, ''));
            SettingsService::set($key, $value);
        }

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                $name = 'logo_' . time() . '.' . $ext;
                $target = base_path('public/uploads/' . $name);
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
                    SettingsService::set('logo_path', 'uploads/' . $name);
                }
            }
        }

        flash_success('تم حفظ الإعدادات');
        $this->redirect('/settings');
    }

    public function dangerReset(): void
    {
        validate_csrf_or_abort();

        $scope = trim((string) input('reset_scope'));
        $password = (string) input('reset_password');
        $expectedHash = (string) (config('app')['danger_reset_password_hash'] ?? '');
        $legacyPlain = (string) (config('app')['danger_reset_password'] ?? '');

        if (!in_array($scope, ['entries', 'invoices', 'products', 'all'], true)) {
            flash_error('اختر نوع البيانات المطلوب مسحها');
            $this->redirect('/settings');
        }

        $isValidPassword = false;
        if ($expectedHash !== '') {
            $isValidPassword = password_verify($password, $expectedHash);
        } elseif ($legacyPlain !== '') {
            $isValidPassword = hash_equals($legacyPlain, $password);
        }

        if (!$isValidPassword) {
            $isValidPassword = AuthService::verifyCurrentUserPassword($password);
        }

        if (!$isValidPassword) {
            flash_error('كلمة المرور غير صحيحة');
            $this->redirect('/settings');
        }

        try {
            DataResetService::reset($scope, (int) AuthService::id());
            flash_success('تم تنفيذ عملية المسح بنجاح');
        } catch (\RuntimeException $e) {
            flash_error($e->getMessage());
        } catch (\Throwable $e) {
            flash_error('تعذر تنفيذ عملية المسح الآن');
        }

        $this->redirect('/settings');
    }

    public function backupDownload(): void
    {
        validate_csrf_or_abort();

        try {
            LogService::activity((int) AuthService::id(), 'settings.backup', 'تنزيل نسخة احتياطية');
            DatabaseBackupService::downloadPackage();
        } catch (\Throwable $e) {
            flash_error('تعذر إنشاء النسخة الاحتياطية الآن');
            $this->redirect('/settings');
        }
    }

    public function backupRestore(): void
    {
        validate_csrf_or_abort();

        try {
            if (!isset($_FILES['backup_file'])) {
                throw new \RuntimeException('يرجى اختيار ملف نسخة احتياطية أولًا');
            }

            $result = DatabaseBackupService::restoreFromUpload($_FILES['backup_file']);
            LogService::activity((int) AuthService::id(), 'settings.restore_backup', 'استعادة نسخة احتياطية');

            $message = 'تمت استعادة النسخة الاحتياطية بنجاح.';
            if (!empty($result['safety_backup'])) {
                $message .= ' تم إنشاء نسخة أمان تلقائية قبل الاستعادة.';
            }
            flash_success($message);
        } catch (\RuntimeException $e) {
            flash_error($e->getMessage());
        } catch (\Throwable $e) {
            Logger::error('backup restore failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            flash_error('تعذر استعادة النسخة الاحتياطية الآن');
        }

        $this->redirect('/settings');
    }
}
