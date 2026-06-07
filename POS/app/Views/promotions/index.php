<?php $title = 'العروض والخصومات'; ?>
<?php
$status = $filters['status'] ?? '';
$q = $filters['q'] ?? '';
$productId = (int) ($filters['product_id'] ?? 0);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">العروض والخصومات</h5>
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createPromotionModal">إضافة عرض جديد</button>
</div>

<form method="get" action="<?= url('/promotions') ?>" class="card p-3 mb-3">
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label">بحث</label>
            <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="اسم المنتج / باركود / اسم العرض">
        </div>
        <div class="col-md-4">
            <label class="form-label">المنتج</label>
            <select class="form-select" name="product_id">
                <option value="0">كل المنتجات</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $productId === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">الحالة</label>
            <select class="form-select" name="status">
                <option value="">الكل</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>نشط الآن</option>
                <option value="upcoming" <?= $status === 'upcoming' ? 'selected' : '' ?>>قادم</option>
                <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>منتهي/متوقف</option>
            </select>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-light w-100">فلترة</button>
        </div>
    </div>
</form>

<div class="table-wrap table-responsive">
    <table class="table table-striped align-middle">
        <thead>
        <tr>
            <th>#</th>
            <th>المنتج</th>
            <th>العرض</th>
            <th>نوع الخصم</th>
            <th>القيمة</th>
            <th>الفترة</th>
            <th>الحالة</th>
            <th>إجراءات</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد عروض مسجلة</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <?php
            $typeLabel = 'خصم ثابت';
            if ($row['discount_type'] === 'percent') {
                $typeLabel = 'نسبة %';
            } elseif ($row['discount_type'] === 'price') {
                $typeLabel = 'سعر عرض';
            }

            $valueLabel = money($row['discount_value']);
            if ($row['discount_type'] === 'percent') {
                $valueLabel = rtrim(rtrim(number_format((float) $row['discount_value'], 2, '.', ''), '0'), '.') . '%';
            }

            $stateClass = 'bg-secondary';
            $stateLabel = 'متوقف';
            if ($row['run_state'] === 'active') {
                $stateClass = 'bg-success';
                $stateLabel = 'نشط';
            } elseif ($row['run_state'] === 'upcoming') {
                $stateClass = 'bg-primary';
                $stateLabel = 'قادم';
            } elseif ($row['run_state'] === 'expired') {
                $stateClass = 'bg-dark';
                $stateLabel = 'منتهي';
            }
            ?>
            <tr>
                <td><?= (int) $row['id'] ?></td>
                <td>
                    <div class="fw-bold"><?= e($row['product_name']) ?></div>
                    <div class="small text-muted"><?= e($row['product_barcode'] ?: '-') ?></div>
                </td>
                <td><?= e($row['name']) ?></td>
                <td><?= e($typeLabel) ?></td>
                <td><?= e($valueLabel) ?></td>
                <td><?= e($row['start_date']) ?> <span class="text-muted">حتى</span> <?= e($row['end_date']) ?></td>
                <td><span class="badge <?= e($stateClass) ?>"><?= e($stateLabel) ?></span></td>
                <td class="d-flex gap-1">
                    <button class="btn btn-sm btn-warning" type="button" data-bs-toggle="modal" data-bs-target="#editPromotion<?= (int) $row['id'] ?>">
                        تعديل
                    </button>
                    <form method="post" action="<?= url('/promotions/' . (int) $row['id'] . '/delete') ?>" data-confirm="تأكيد إيقاف العرض؟">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-danger">إيقاف</button>
                    </form>
                </td>
            </tr>

            <div class="modal fade" id="editPromotion<?= (int) $row['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post" action="<?= url('/promotions/' . (int) $row['id'] . '/update') ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h6 class="modal-title">تعديل العرض</h6>
                                <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
                            </div>
                            <div class="modal-body">
                                <?php \App\Core\View::partial('promotions/partials/form_fields', ['products' => $products, 'row' => $row]); ?>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-success">حفظ</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="createPromotionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= url('/promotions') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h6 class="modal-title">إضافة عرض وخصم</h6>
                    <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
                </div>
                <div class="modal-body">
                    <?php \App\Core\View::partial('promotions/partials/form_fields', ['products' => $products]); ?>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success">حفظ العرض</button>
                </div>
            </form>
        </div>
    </div>
</div>
