<?php $title = 'تقرير الشيفت'; ?>
<h5 class="mb-3">تقرير شيفت: <?= e($row['shift_no']) ?></h5>
<div class="row g-2 mb-3">
    <div class="col-md-3"><div class="card p-2">المستخدم: <?= e($row['user_name']) ?></div></div>
    <div class="col-md-3"><div class="card p-2">افتتاحي: <?= money($row['opening_balance']) ?></div></div>
    <div class="col-md-3"><div class="card p-2">متوقع: <?= money($row['expected_balance']) ?></div></div>
    <div class="col-md-3"><div class="card p-2">فعلي: <?= money($row['actual_balance']) ?></div></div>
</div>

<h6>حركات الصندوق</h6>
<div class="table-wrap table-responsive mb-3"><table class="table table-sm"><thead><tr><th>التاريخ</th><th>نوع الحركة</th><th>اتجاه</th><th>المبلغ</th><th>ملاحظة</th></tr></thead><tbody><?php foreach($row['movements'] as $m): ?><tr><td><?= e($m['created_at']) ?></td><td><?= e($m['movement_type']) ?></td><td><?= e($m['direction']) ?></td><td><?= money($m['amount']) ?></td><td><?= e($m['note']) ?></td></tr><?php endforeach; ?></tbody></table></div>

<h6>فواتير البيع داخل الشيفت</h6>
<div class="table-wrap table-responsive"><table class="table table-sm"><thead><tr><th>رقم</th><th>إجمالي</th><th>مدفوع</th></tr></thead><tbody><?php foreach($row['sales'] as $s): ?><tr><td><?= e($s['invoice_no']) ?></td><td><?= money($s['grand_total']) ?></td><td><?= money($s['paid_total']) ?></td></tr><?php endforeach; ?></tbody></table></div>
