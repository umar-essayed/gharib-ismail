<?php $title = 'الملف الشخصي'; ?>
<div class="card p-4">
    <h5 class="mb-3">الملف الشخصي</h5>
    <div class="row g-3">
        <div class="col-md-4"><strong>الاسم:</strong> <?= e($user['full_name']) ?></div>
        <div class="col-md-4"><strong>اسم المستخدم:</strong> <?= e($user['username']) ?></div>
        <div class="col-md-4"><strong>الدور:</strong> <?= e($user['role_name']) ?></div>
    </div>
</div>
