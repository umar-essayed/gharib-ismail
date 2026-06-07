<?php $title = 'حركات المخزون'; ?>
<h5 class="mb-3">حركات المخزون</h5>
<form method="get" class="row g-2 mb-3" action="<?= url('/inventory/movements') ?>">
    <div class="col-md-4"><select class="form-select" name="product_id"><option value="">كل الأصناف</option><?php foreach($products as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (int)($_GET['product_id'] ?? 0)===(int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><input type="date" class="form-control" name="from" value="<?= e($_GET['from'] ?? '') ?>"></div>
    <div class="col-md-3"><input type="date" class="form-control" name="to" value="<?= e($_GET['to'] ?? '') ?>"></div>
    <div class="col-md-2"><button class="btn btn-primary">تصفية</button></div>
</form>

<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
<thead><tr><th>التاريخ</th><th>الصنف</th><th>المخزن</th><th>نوع الحركة</th><th>وارد</th><th>صادر</th><th>الرصيد</th><th>مستند</th><th>المستخدم</th></tr></thead>
<tbody>
<?php foreach($rows as $row): ?>
<tr>
    <td><?= e($row['created_at']) ?></td>
    <td><?= e($row['product_name']) ?></td>
    <td><?= e($row['warehouse_name']) ?></td>
    <td><?= e($row['movement_type']) ?></td>
    <td><?= money($row['qty_in']) ?></td>
    <td><?= money($row['qty_out']) ?></td>
    <td><?= money($row['balance_after']) ?></td>
    <td><?= e($row['reference_table']) ?> #<?= e($row['reference_id']) ?></td>
    <td><?= e($row['user_name']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
