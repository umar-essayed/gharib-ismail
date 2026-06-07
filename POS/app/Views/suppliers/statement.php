<?php $title = 'كشف حساب مورد'; ?>
<div class="mb-3">
    <h5 class="mb-1">كشف حساب: <?= e($row['name']) ?></h5>
    <div class="text-muted">الرصيد الحالي: <?= money($row['current_balance']) ?></div>
    <div class="mt-2"><a class="btn btn-sm btn-success" href="<?= url('/suppliers/' . (int)$row['id'] . '/payment') ?>">إضافة سداد</a></div>
</div>
<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
    <thead><tr><th>التاريخ</th><th>النوع</th><th>مدين</th><th>دائن</th><th>الرصيد</th><th>ملاحظة</th></tr></thead>
    <tbody>
    <?php foreach ($statement as $s): ?>
        <tr>
            <td><?= e($s['created_at']) ?></td>
            <td><?= e($s['transaction_type']) ?></td>
            <td><?= money($s['debit']) ?></td>
            <td><?= money($s['credit']) ?></td>
            <td><?= money($s['balance_after']) ?></td>
            <td><?= e($s['note']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
