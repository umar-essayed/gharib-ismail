<?php $title = 'تسجيل الدخول'; ?>
<?php $settings = \App\Services\SettingsService::all(); ?>
<div class="login-shell">
    <div class="login-bg-image"></div>
    <div class="login-bg-overlay"></div>

    <div class="login-card">
        <div class="login-logo-container">
            <img src="<?= url('/assets/icons/logo.jpeg') ?>" alt="Logo" class="login-logo">
        </div>
        <h2 class="login-title"><?= e($settings['company_name'] ?? 'الناصرية جملة ماركت') ?></h2>
        <p class="login-subtitle">تسجيل دخول نظام نقاط البيع (الكاشير)</p>

        <?php if ($err = \App\Core\Session::getFlash('error')): ?>
            <div class="alert alert-danger text-center small py-2 rounded-3 mb-3"><?= e($err) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= url('/login') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">اسم المستخدم</label>
                <input type="text" class="form-control" name="username" value="<?= e(old('username')) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">كلمة المرور</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button class="btn btn-primary w-100">دخول</button>
        </form>

    </div>
</div>
