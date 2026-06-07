<div class="text-center py-5">
    <h1 class="mb-3">403</h1>
    <p>ليس لديك صلاحية للوصول إلى هذه الصفحة.</p>
    <?php if (!empty($permission)): ?><p class="text-muted">الصلاحية المطلوبة: <?= e($permission) ?></p><?php endif; ?>
    <a class="btn btn-primary" href="<?= url('/dashboard') ?>">العودة</a>
</div>
