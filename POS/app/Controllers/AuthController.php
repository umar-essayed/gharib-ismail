<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (AuthService::check()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/login', [], 'layouts/guest');
    }

    public function login(): void
    {
        validate_csrf_or_abort();

        $username = trim((string) input('username'));
        $password = (string) input('password');

        if ($username === '' || $password === '') {
            flash_error('يرجى إدخال اسم المستخدم وكلمة المرور');
            set_old($_POST);
            $this->redirect('/login');
        }

        if (!AuthService::attempt($username, $password)) {
            flash_error('بيانات الدخول غير صحيحة أو المستخدم معطّل');
            set_old($_POST);
            $this->redirect('/login');
        }

        flash_success('تم تسجيل الدخول بنجاح');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        validate_csrf_or_abort();
        AuthService::logout();
        $this->redirect('/login');
    }
}
