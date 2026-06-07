<?php $title = 'سداد مورد'; ?>
<h5 class="mb-3">سداد مورد - <?= e($row['name']) ?></h5>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card p-3">
            <div class="mb-2">الرصيد المستحق للمورد: <strong><?= money($row['current_balance']) ?></strong></div>
            <form method="post" action="<?= url('/suppliers/' . (int)$row['id'] . '/payment') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label">المبلغ المسدد</label>
                    <input type="number" step="0.001" min="0.001" name="amount" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">طريقة الدفع</label>
                    <select class="form-select" name="payment_method_id">
                        <?php foreach($paymentMethods as $m): ?>
                            <option value="<?= (int)$m['id'] ?>" <?= $m['is_default'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">ملاحظة</label>
                    <input class="form-control" name="note" placeholder="ملاحظة اختيارية">
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success">حفظ السداد</button>
                    <a class="btn btn-light" href="<?= url('/suppliers/' . (int)$row['id'] . '/statement') ?>">رجوع</a>
                </div>
            </form>
        </div>
    </div>
</div>
