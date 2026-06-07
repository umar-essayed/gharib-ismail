<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UnitModel;
use App\Services\LogService;
use App\Services\ValidationService;

class UnitController extends Controller
{
    public function index(): void
    {
        $model = new UnitModel();
        $rows = $model->all();
        $this->view('units/index', compact('rows'));
    }

    public function store(): void
    {
        validate_csrf_or_abort();
        $data = [
            'name' => trim((string) input('name')),
            'short_name' => trim((string) input('short_name')),
            'is_weight' => input('is_weight', '0') === '1' ? 1 : 0,
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
        ];

        $errors = ValidationService::validate($data, ['name' => 'required', 'short_name' => 'required']);
        if ($errors) {
            flash_error('يرجى استكمال بيانات الوحدة');
            $this->redirect('/units');
        }

        $model = new UnitModel();
        $id = $model->create($data);
        LogService::audit('units', $id, 'insert', null, $data);

        flash_success('تم إضافة الوحدة');
        $this->redirect('/units');
    }

    public function update(string $id): void
    {
        validate_csrf_or_abort();
        $model = new UnitModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('الوحدة غير موجودة');
            $this->redirect('/units');
        }

        $data = [
            'name' => trim((string) input('name')),
            'short_name' => trim((string) input('short_name')),
            'is_weight' => input('is_weight', '0') === '1' ? 1 : 0,
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
        ];

        $model->update((int) $id, $data);
        LogService::audit('units', (int) $id, 'update', $row, $data);
        flash_success('تم تحديث الوحدة');
        $this->redirect('/units');
    }
}
