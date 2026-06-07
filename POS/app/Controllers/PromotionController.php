<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PromotionModel;
use App\Services\AuthService;
use App\Services\LogService;
use App\Services\ValidationService;

class PromotionController extends Controller
{
    public function index(): void
    {
        $model = new PromotionModel();
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'product_id' => (int) ($_GET['product_id'] ?? 0),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ];

        $rows = $model->all($filters);
        $products = $model->productsForSelect();

        $this->view('promotions/index', compact('rows', 'products', 'filters'));
    }

    public function store(): void
    {
        validate_csrf_or_abort();

        $model = new PromotionModel();
        $data = $this->formData();
        $error = $this->validateBusiness($model, $data, null);
        if ($error !== null) {
            flash_error($error);
            $this->redirect('/promotions');
        }

        $data['created_by'] = AuthService::id();
        $id = $model->create($data);
        LogService::audit('promotions', $id, 'insert', null, $data);
        flash_success('تمت إضافة العرض بنجاح');

        $this->redirect('/promotions');
    }

    public function update(string $id): void
    {
        validate_csrf_or_abort();

        $model = new PromotionModel();
        $old = $model->find((int) $id);
        if (!$old) {
            flash_error('العرض غير موجود');
            $this->redirect('/promotions');
        }

        $data = $this->formData();
        $error = $this->validateBusiness($model, $data, (int) $id);
        if ($error !== null) {
            flash_error($error);
            $this->redirect('/promotions');
        }

        $data['updated_by'] = AuthService::id();
        $model->update((int) $id, $data);
        LogService::audit('promotions', (int) $id, 'update', $old, $data);
        flash_success('تم تحديث العرض');

        $this->redirect('/promotions');
    }

    public function delete(string $id): void
    {
        validate_csrf_or_abort();

        $model = new PromotionModel();
        $old = $model->find((int) $id);
        if (!$old) {
            flash_error('العرض غير موجود');
            $this->redirect('/promotions');
        }

        $model->deactivate((int) $id, AuthService::id());
        LogService::audit('promotions', (int) $id, 'update', $old, ['is_active' => 0]);
        flash_success('تم إيقاف العرض');

        $this->redirect('/promotions');
    }

    private function formData(): array
    {
        return [
            'product_id' => (int) input('product_id', 0),
            'name' => trim((string) input('name')),
            'discount_type' => trim((string) input('discount_type')),
            'discount_value' => (float) input('discount_value', 0),
            'start_date' => trim((string) input('start_date')),
            'end_date' => trim((string) input('end_date')),
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
            'note' => trim((string) input('note')),
        ];
    }

    private function validateBusiness(PromotionModel $model, array $data, ?int $excludeId): ?string
    {
        $errors = ValidationService::validate($data, [
            'product_id' => 'required|integer|min:1',
            'name' => 'required',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);
        if ($errors) {
            return 'يرجى استكمال جميع بيانات العرض بشكل صحيح';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['start_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['end_date'])) {
            return 'صيغة التاريخ غير صحيحة';
        }

        if ($data['start_date'] > $data['end_date']) {
            return 'تاريخ بداية العرض يجب أن يكون قبل تاريخ النهاية';
        }

        if (!in_array($data['discount_type'], ['percent', 'fixed', 'price'], true)) {
            return 'نوع الخصم غير صالح';
        }

        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            return 'نسبة الخصم يجب ألا تتجاوز 100%';
        }

        if (in_array($data['discount_type'], ['percent', 'fixed'], true) && $data['discount_value'] <= 0) {
            return 'قيمة الخصم يجب أن تكون أكبر من صفر';
        }

        if ($model->hasOverlap($data['product_id'], $data['start_date'], $data['end_date'], $excludeId)) {
            return 'يوجد عرض فعال متداخل لنفس المنتج في نفس الفترة';
        }

        return null;
    }
}
