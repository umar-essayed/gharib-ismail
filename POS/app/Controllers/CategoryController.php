<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CategoryModel;
use App\Services\LogService;
use App\Services\SupabaseSyncService;
use App\Services\ValidationService;

class CategoryController extends Controller
{
    public function index(): void
    {
        $model = new CategoryModel();
        $rows = $model->all();
        $this->view('categories/index', compact('rows'));
    }

    public function store(): void
    {
        validate_csrf_or_abort();
        $data = [
            'name' => trim((string) input('name')),
            'description' => trim((string) input('description')),
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
        ];

        $errors = ValidationService::validate($data, ['name' => 'required']);
        if ($errors) {
            flash_error('يرجى إدخال اسم التصنيف');
            set_old($_POST);
            $this->redirect('/categories');
        }

        $model = new CategoryModel();
        $id = $model->create($data);
        LogService::audit('product_categories', $id, 'insert', null, $data);

        // ☁️ مزامنة القسم الجديد مع المتجر
        try { SupabaseSyncService::syncCategory($id, $data['name'], $data['description'] ?? '', $data['is_active']); }
        catch (\Throwable $e) { /* يُحفظ في الطابور */ }

        flash_success('تم إضافة التصنيف');
        $this->redirect('/categories');
    }

    public function update(string $id): void
    {
        validate_csrf_or_abort();
        $model = new CategoryModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('التصنيف غير موجود');
            $this->redirect('/categories');
        }

        $data = [
            'name' => trim((string) input('name')),
            'description' => trim((string) input('description')),
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
        ];

        $model->update((int) $id, $data);
        LogService::audit('product_categories', (int) $id, 'update', $row, $data);

        // ☁️ مزامنة تحديث القسم مع المتجر
        try { SupabaseSyncService::syncCategory((int)$id, $data['name'], $data['description'] ?? '', $data['is_active']); }
        catch (\Throwable $e) { /* يُحفظ في الطابور */ }

        flash_success('تم تحديث التصنيف');
        $this->redirect('/categories');
    }

    public function delete(string $id): void
    {
        validate_csrf_or_abort();
        $model = new CategoryModel();
        $row = $model->find((int) $id);
        if ($row) {
            $model->softDelete((int) $id);
            LogService::audit('product_categories', (int) $id, 'delete', $row, null);

            // ☁️ حذف القسم من المتجر
            try { SupabaseSyncService::deleteCategory((int) $id); }
            catch (\Throwable $e) { /* يُحفظ في الطابور */ }

            flash_success('تم حذف التصنيف');
        }
        $this->redirect('/categories');
    }
}
