<?php $title = 'المخزون الحالي'; ?>
<h5 class="mb-3">المخزون الحالي</h5>
<div class="table-wrap table-responsive">
<table class="table table-striped align-middle">
<thead><tr><th>الصنف</th><th>الباركود</th><th>التصنيف</th><th>الوحدة</th><th>المتاح</th><th>الحد الأدنى</th><th>تنبيه</th></tr></thead>
<tbody>
<?php foreach($rows as $row): ?>
<tr>
    <td><?= e($row['name']) ?></td>
    <td><?= e($row['barcode']) ?></td>
    <td><?= e($row['category_name']) ?></td>
    <td><?= e($row['unit_name']) ?></td>
    <td><?= money($row['current_stock']) ?></td>
    <td><?= money($row['min_stock']) ?></td>
    <td><?= (float)$row['current_stock'] <= (float)$row['min_stock'] ? '<span class="badge bg-danger">منخفض</span>' : '<span class="badge bg-success">جيد</span>' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
