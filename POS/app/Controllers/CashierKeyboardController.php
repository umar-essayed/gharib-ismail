<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CashierKeyboardModel;
use App\Models\ProductModel;
use App\Services\LogService;
use App\Services\ValidationService;

class CashierKeyboardController extends Controller
{
    public function index(): void
    {
        $userId = current_user()['id'] ?? null;
        if (!$userId) {
            $this->redirect('/login');
            return;
        }

        $model = new CashierKeyboardModel();
        $shortcuts = $model->allForUser($userId);
        
        $actionTypes = self::actionTypes();
        $products = [];
        
        $this->view('cashier-keyboard/index', compact('shortcuts', 'actionTypes', 'products'));
    }

    public function store(): void
    {
        validate_csrf_or_abort();
        $userId = current_user()['id'] ?? null;
        
        if (!$userId) {
            flash_error('يجب تسجيل الدخول أولاً');
            $this->redirectBackToKeyboard();
            return;
        }

        $data = $this->payload();
        $errors = ValidationService::validate($data, [
            'key_code' => 'required',
            'key_label' => 'required',
            'action_type' => 'required',
        ]);

        if ($errors) {
            flash_error('يرجى ملء جميع الحقول المطلوبة');
            $this->redirectBackToKeyboard();
            return;
        }

        // Check if key code already exists for this user
        $model = new CashierKeyboardModel();
        $existing = $model->findByKeyCode($userId, $data['key_code']);
        if ($existing) {
            flash_error('هذا المفتاح مستخدم بالفعل. يرجى اختيار مفتاح آخر');
            $this->redirectBackToKeyboard();
            return;
        }

        $data['user_id'] = $userId;
        $id = $model->create($data);

        LogService::audit('keyboard_shortcuts', $id, 'insert', null, [
            'key_code' => $data['key_code'],
            'action_type' => $data['action_type']
        ]);
        
        flash_success('تم إضافة اختصار لوحة المفاتيح');
        $this->redirectBackToKeyboard();
    }

    public function update(string $id): void
    {
        validate_csrf_or_abort();
        $userId = current_user()['id'] ?? null;
        
        if (!$userId) {
            flash_error('يجب تسجيل الدخول أولاً');
            $this->redirectBackToKeyboard();
            return;
        }

        $model = new CashierKeyboardModel();
        $shortcut = $model->find((int) $id);
        
        if (!$shortcut || $shortcut['user_id'] != $userId) {
            flash_error('الاختصار غير موجود أو غير مسموح لك بتعديله');
            $this->redirectBackToKeyboard();
            return;
        }

        $data = $this->payload();
        $errors = ValidationService::validate($data, [
            'key_code' => 'required',
            'key_label' => 'required',
            'action_type' => 'required',
        ]);

        if ($errors) {
            flash_error('يرجى ملء جميع الحقول المطلوبة');
            $this->redirectBackToKeyboard();
            return;
        }

        $old = $shortcut;

        // Check if key code is changing and if new one already exists
        if ($data['key_code'] !== $shortcut['key_code']) {
            $existing = $model->findByKeyCode($userId, $data['key_code']);
            if ($existing) {
                flash_error('هذا المفتاح مستخدم بالفعل. يرجى اختيار مفتاح آخر');
                $this->redirectBackToKeyboard();
                return;
            }
        }

        $model->update((int) $id, $data);

        LogService::audit('keyboard_shortcuts', (int) $id, 'update', $old, $data);
        flash_success('تم تحديث الاختصار');
        $this->redirectBackToKeyboard();
    }

    public function delete(string $id): void
    {
        validate_csrf_or_abort();
        $userId = current_user()['id'] ?? null;
        
        if (!$userId) {
            flash_error('يجب تسجيل الدخول أولاً');
            $this->redirectBackToKeyboard();
            return;
        }

        $model = new CashierKeyboardModel();
        $shortcut = $model->find((int) $id);
        
        if (!$shortcut || $shortcut['user_id'] != $userId) {
            flash_error('الاختصار غير موجود أو غير مسموح لك بحذفه');
            $this->redirectBackToKeyboard();
            return;
        }

        $model->delete((int) $id);
        LogService::audit('keyboard_shortcuts', (int) $id, 'delete', $shortcut, null);
        
        flash_success('تم حذف الاختصار');
        $this->redirectBackToKeyboard();
    }

    public function toggle(string $id): void
    {
        validate_csrf_or_abort();
        $userId = current_user()['id'] ?? null;
        
        if (!$userId) {
            flash_error('يجب تسجيل الدخول أولاً');
            $this->redirectBackToKeyboard();
            return;
        }

        $model = new CashierKeyboardModel();
        $shortcut = $model->find((int) $id);
        
        if (!$shortcut || $shortcut['user_id'] != $userId) {
            flash_error('الاختصار غير موجود');
            $this->redirectBackToKeyboard();
            return;
        }

        $model->toggleActive((int) $id);
        flash_success('تم تحديث حالة الاختصار');
        $this->redirectBackToKeyboard();
    }

    public static function actionTypes(): array
    {
        return [
            'add_product' => 'إضافة منتج',
            'open_invoice' => 'تركيز البحث عن صنف',
            'execute_payment' => 'تنفيذ الدفع',
            'apply_discount' => 'تطبيق خصم',
            'print_receipt' => 'طباعة الإيصال',
            'clear_cart' => 'مسح السلة',
            'suspend_invoice' => 'تعليق الفاتورة',
            'custom_function' => 'دالة مخصصة',
        ];
    }

    private function payload(): array
    {
        return [
            'key_code' => trim((string) input('key_code', '')),
            'key_label' => trim((string) input('key_label', '')),
            'action_type' => trim((string) input('action_type', '')),
            'reference_id' => input('reference_id', null) ? (int) input('reference_id') : null,
            'reference_name' => trim((string) input('reference_name', '')),
            'is_active' => input('is_active', '0') === '1' ? 1 : 0,
        ];
    }

    private function redirectBackToKeyboard(): void
    {
        $target = (string) input('_redirect', '');
        if ($target === 'settings') {
            $this->redirect('/settings#cashier-keyboard');
            return;
        }

        $this->redirect('/cashier-keyboard');
    }

    public function apiList(): void
    {
        $userId = current_user()['id'] ?? null;
        
        if (!$userId) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $model = new CashierKeyboardModel();
        $shortcuts = $model->allForUser($userId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'shortcuts' => $shortcuts,
            'count' => count($shortcuts)
        ], JSON_UNESCAPED_UNICODE);
    }
}
