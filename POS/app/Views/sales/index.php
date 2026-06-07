<?php $title = 'فواتير البيع'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">فواتير البيع</h5>
    <a class="btn btn-success" href="<?= url('/pos') ?>">فاتورة جديدة (POS)</a>
</div>

<form class="row g-2 mb-3" method="get" action="<?= url('/sales') ?>">
    <div class="col-md-3"><input type="date" name="from" class="form-control" value="<?= e($_GET['from'] ?? '') ?>"></div>
    <div class="col-md-3"><input type="date" name="to" class="form-control" value="<?= e($_GET['to'] ?? '') ?>"></div>
    <div class="col-md-2"><button class="btn btn-primary">تصفية</button></div>
</form>

<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
    <thead><tr><th>#</th><th>رقم الفاتورة</th><th>التاريخ</th><th>العميل</th><th>الكاشير</th><th>الإجمالي</th><th>المدفوع</th><th>المتبقي</th><th>الحالة</th><th>إجراءات</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= (int) $row['id'] ?></td>
            <td><?= e($row['invoice_no']) ?></td>
            <td><?= e($row['invoice_date']) ?></td>
            <td><?= e($row['customer_name']) ?></td>
            <td><?= e($row['user_name']) ?></td>
            <td><?= money($row['grand_total']) ?></td>
            <td><?= money($row['paid_total']) ?></td>
            <td><?= money($row['due_total']) ?></td>
            <td><?= e($row['status']) ?></td>
            <td>
                <a class="btn btn-sm btn-primary" href="<?= url('/sales/' . (int) $row['id']) ?>">عرض</a>
                <a class="btn btn-sm btn-light" href="<?= url('/sales/' . (int) $row['id'] . '/print') ?>" target="_blank">طباعة</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
