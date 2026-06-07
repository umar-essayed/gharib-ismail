<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\RoleModel;
use App\Services\LogService;
use App\Services\ValidationService;

class RoleController extends Controller
{
    public function index(): void
    {
        $model = new RoleModel();
        $rows = $model->all();
        $permissions = $model->permissions();

        $this->view('roles/index', compact('rows', 'permissions'));
    }

    public function create(): void
    {
        $model = new RoleModel();
        $permissions = $model->permissions();
        $this->view('roles/create', compact('permissions'));
    }

    public function store(): void
    {
        validate_csrf_or_abort();
        $data = [
            'name' => trim((string) input('name')),
            'description' => trim((string) input('description')),
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
            'permissions' => $_POST['permissions'] ?? [],
        ];

        $errors = ValidationService::validate($data, ['name' => 'required']);
        if ($errors) {
            flash_error('اسم الدور مطلوب');
            $this->redirect('/roles/create');
        }

        $model = new RoleModel();
        $id = $model->create($data);
        LogService::audit('roles', $id, 'insert', null, ['name' => $data['name']]);
        flash_success('تم إضافة الدور');
        $this->redirect('/roles');
    }

    public function edit(string $id): void
    {
        $model = new RoleModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('الدور غير موجود');
            $this->redirect('/roles');
        }

        $permissions = $model->permissions();
        $this->view('roles/edit', compact('row', 'permissions'));
    }

    public function update(string $id): void
    {
        validate_csrf_or_abort();
        $model = new RoleModel();
        $old = $model->find((int) $id);
        if (!$old) {
            flash_error('الدور غير موجود');
            $this->redirect('/roles');
        }

        $data = [
            'name' => trim((string) input('name')),
            'description' => trim((string) input('description')),
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
            'permissions' => $_POST['permissions'] ?? [],
        ];

        $model->update((int) $id, $data);
        LogService::audit('roles', (int) $id, 'update', ['name' => $old['name']], ['name' => $data['name']]);
        flash_success('تم تحديث الدور');
        $this->redirect('/roles');
    }
}
