<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Services\LogService;
use App\Services\ValidationService;

class UserController extends Controller
{
    public function index(): void
    {
        $model = new UserModel();
        $rows = $model->all();
        $roles = $model->roles();

        $this->view('users/index', compact('rows', 'roles'));
    }

    public function store(): void
    {
        validate_csrf_or_abort();
        $data = $this->payload();

        $errors = ValidationService::validate($data, [
            'role_id' => 'required|integer|min:1',
            'username' => 'required',
            'full_name' => 'required',
            'password' => 'required',
        ]);
        if ($errors) {
            flash_error('يرجى استكمال بيانات المستخدم');
            $this->redirect('/users');
        }

        $model = new UserModel();
        $id = $model->create($data);

        LogService::audit('users', $id, 'insert', null, ['username' => $data['username']]);
        flash_success('تم إضافة المستخدم');
        $this->redirect('/users');
    }

    public function update(string $id): void
    {
        validate_csrf_or_abort();
        $model = new UserModel();
        $old = $model->find((int) $id);
        if (!$old) {
            flash_error('المستخدم غير موجود');
            $this->redirect('/users');
        }

        $data = $this->payload();
        $model->update((int) $id, $data);

        LogService::audit('users', (int) $id, 'update', $old, ['username' => $data['username']]);
        flash_success('تم تحديث المستخدم');
        $this->redirect('/users');
    }

    public function delete(string $id): void
    {
        validate_csrf_or_abort();
        $model = new UserModel();
        $old = $model->find((int) $id);
        if ($old) {
            $model->softDelete((int) $id);
            LogService::audit('users', (int) $id, 'delete', $old, null);
            flash_success('تم حذف المستخدم');
        }
        $this->redirect('/users');
    }

    private function payload(): array
    {
        return [
            'role_id' => (int) input('role_id', 0),
            'username' => trim((string) input('username')),
            'full_name' => trim((string) input('full_name')),
            'password' => (string) input('password'),
            'email' => trim((string) input('email')),
            'phone' => trim((string) input('phone')),
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
        ];
    }
}
