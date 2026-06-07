<?php $title = 'إدارة المستخدمين'; ?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card p-3">
            <h6 class="mb-3">إضافة مستخدم</h6>
            <form method="post" action="<?= url('/users') ?>">
                <?= csrf_field() ?>
                <div class="mb-2"><label class="form-label">الاسم الكامل</label><input class="form-control" name="full_name" required></div>
                <div class="mb-2"><label class="form-label">اسم المستخدم</label><input class="form-control" name="username" required></div>
                <div class="mb-2"><label class="form-label">كلمة المرور</label><input type="password" class="form-control" name="password" required></div>
                <div class="mb-2"><label class="form-label">الدور</label><select class="form-select" name="role_id"><?php foreach($roles as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
                <div class="mb-2"><label class="form-label">الهاتف</label><input class="form-control" name="phone"></div>
                <div class="mb-2"><label class="form-label">البريد</label><input class="form-control" name="email"></div>
                <div class="mb-3"><label class="form-label">الحالة</label><select class="form-select" name="is_active"><option value="1">نشط</option><option value="0">معطل</option></select></div>
                <button class="btn btn-success">حفظ</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="table-wrap table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>#</th><th>الاسم</th><th>المستخدم</th><th>الدور</th><th>آخر دخول</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                <tbody>
                <?php foreach($rows as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e($row['username']) ?></td>
                        <td><?= e($row['role_name']) ?></td>
                        <td><?= e($row['last_login_at']) ?></td>
                        <td><?= $row['is_active'] ? 'نشط' : 'معطل' ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUser<?= (int)$row['id'] ?>">تعديل</button>
                            <form method="post" action="<?= url('/users/' . (int)$row['id'] . '/delete') ?>" class="d-inline" data-confirm="تأكيد حذف المستخدم؟"><?= csrf_field() ?><button class="btn btn-sm btn-danger">حذف</button></form>
                        </td>
                    </tr>

                    <div class="modal fade" id="editUser<?= (int)$row['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
                        <form method="post" action="<?= url('/users/' . (int)$row['id'] . '/update') ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header"><h6 class="modal-title">تعديل مستخدم</h6><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
                            <div class="modal-body">
                                <div class="mb-2"><label class="form-label">الاسم الكامل</label><input class="form-control" name="full_name" value="<?= e($row['full_name']) ?>" required></div>
                                <div class="mb-2"><label class="form-label">اسم المستخدم</label><input class="form-control" name="username" value="<?= e($row['username']) ?>" required></div>
                                <div class="mb-2"><label class="form-label">كلمة المرور الجديدة (اختياري)</label><input type="password" class="form-control" name="password"></div>
                                <div class="mb-2"><label class="form-label">الدور</label><select class="form-select" name="role_id"><?php foreach($roles as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (int)$r['id']===(int)$row['role_id']?'selected':'' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
                                <div class="mb-2"><label class="form-label">الهاتف</label><input class="form-control" name="phone" value="<?= e($row['phone']) ?>"></div>
                                <div class="mb-2"><label class="form-label">البريد</label><input class="form-control" name="email" value="<?= e($row['email']) ?>"></div>
                                <div><label class="form-label">الحالة</label><select class="form-select" name="is_active"><option value="1" <?= $row['is_active']?'selected':'' ?>>نشط</option><option value="0" <?= !$row['is_active']?'selected':'' ?>>معطل</option></select></div>
                            </div>
                            <div class="modal-footer"><button class="btn btn-success">حفظ</button></div>
                        </form>
                    </div></div></div>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
