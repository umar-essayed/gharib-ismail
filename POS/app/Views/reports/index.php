<?php $title = 'التقارير'; ?>
<h5 class="mb-3">التقارير</h5>
<form method="get" class="row g-2 mb-3" action="<?= url('/reports') ?>">
    <div class="col-md-2"><input type="date" class="form-control" name="from" value="<?= e($_GET['from'] ?? '') ?>"></div>
    <div class="col-md-2"><input type="date" class="form-control" name="to" value="<?= e($_GET['to'] ?? '') ?>"></div>
    <div class="col-md-2"><select class="form-select" name="user_id"><option value="">كل المستخدمين</option><?php foreach($lookups['users'] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (int)($_GET['user_id'] ?? 0)===(int)$u['id']?'selected':'' ?>><?= e($u['full_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><select class="form-select" name="customer_id"><option value="">كل العملاء</option><?php foreach($lookups['customers'] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (int)($_GET['customer_id'] ?? 0)===(int)$u['id']?'selected':'' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><select class="form-select" name="supplier_id"><option value="">كل الموردين</option><?php foreach($lookups['suppliers'] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (int)($_GET['supplier_id'] ?? 0)===(int)$u['id']?'selected':'' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><button class="btn btn-primary">تطبيق</button></div>
</form>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rSales" type="button">المبيعات</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rPurchases" type="button">المشتريات</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rInventory" type="button">المخزون</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rDebts" type="button">المديونيات</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rCash" type="button">الصندوق</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rActivity" type="button">النشاط</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="rSales">
        <div class="table-wrap table-responsive"><table class="table table-sm"><thead><tr><th>رقم</th><th>تاريخ</th><th>عميل</th><th>كاشير</th><th>إجمالي</th><th>مدفوع</th><th>متبقي</th></tr></thead><tbody><?php foreach($sales as $r): ?><tr><td><?= e($r['invoice_no']) ?></td><td><?= e($r['invoice_date']) ?></td><td><?= e($r['customer_name']) ?></td><td><?= e($r['cashier_name']) ?></td><td><?= money($r['grand_total']) ?></td><td><?= money($r['paid_total']) ?></td><td><?= money($r['due_total']) ?></td></tr><?php endforeach; ?></tbody></table></div>
    </div>
    <div class="tab-pane fade" id="rPurchases">
        <div class="table-wrap table-responsive"><table class="table table-sm"><thead><tr><th>رقم</th><th>تاريخ</th><th>مورد</th><th>مستخدم</th><th>إجمالي</th><th>مدفوع</th><th>متبقي</th><th>حالة</th></tr></thead><tbody><?php foreach($purchases as $r): ?><tr><td><?= e($r['invoice_no']) ?></td><td><?= e($r['invoice_date']) ?></td><td><?= e($r['supplier_name']) ?></td><td><?= e($r['user_name']) ?></td><td><?= money($r['grand_total']) ?></td><td><?= money($r['paid_total']) ?></td><td><?= money($r['due_total']) ?></td><td><?= e($r['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
    </div>
    <div class="tab-pane fade" id="rInventory">
        <div class="row g-3"><div class="col-md-6"><h6>أصناف تحت الحد الأدنى</h6><div class="table-wrap table-responsive"><table class="table table-sm"><thead><tr><th>صنف</th><th>متاح</th><th>حد أدنى</th></tr></thead><tbody><?php foreach($lowStock as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= money($r['stock']) ?></td><td><?= money($r['min_stock']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    </div>
    <div class="tab-pane fade" id="rDebts">
        <div class="row g-3">
            <div class="col-md-6"><h6>مديونيات العملاء</h6><div class="table-wrap table-responsive"><table class="table table-sm"><thead><tr><th>العميل</th><th>هاتف</th><th>الرصيد</th><th>الحد</th></tr></thead><tbody><?php foreach($customerDebts as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['phone']) ?></td><td><?= money($r['current_balance']) ?></td><td><?= money($r['credit_limit']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
            <div class="col-md-6"><h6>أرصدة الموردين</h6><div class="table-wrap table-responsive"><table class="table table-sm"><thead><tr><th>المورد</th><th>هاتف</th><th>الرصيد</th></tr></thead><tbody><?php foreach($supplierBalances as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['phone']) ?></td><td><?= money($r['current_balance']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
        </div>
    </div>
    <div class="tab-pane fade" id="rCash">
        <div class="table-wrap table-responsive"><table class="table table-sm"><thead><tr><th>التاريخ</th><th>النوع</th><th>اتجاه</th><th>مبلغ</th><th>شيفت</th><th>مستخدم</th><th>ملاحظة</th></tr></thead><tbody><?php foreach($cash as $r): ?><tr><td><?= e($r['created_at']) ?></td><td><?= e($r['movement_type']) ?></td><td><?= e($r['direction']) ?></td><td><?= money($r['amount']) ?></td><td><?= e($r['shift_no']) ?></td><td><?= e($r['user_name']) ?></td><td><?= e($r['note']) ?></td></tr><?php endforeach; ?></tbody></table></div>
    </div>
    <div class="tab-pane fade" id="rActivity">
        <div class="table-wrap table-responsive"><table class="table table-sm"><thead><tr><th>تاريخ</th><th>مستخدم</th><th>إجراء</th><th>وصف</th><th>IP</th></tr></thead><tbody><?php foreach($activity as $r): ?><tr><td><?= e($r['created_at']) ?></td><td><?= e($r['user_name']) ?></td><td><?= e($r['action']) ?></td><td><?= e($r['description']) ?></td><td><?= e($r['ip_address']) ?></td></tr><?php endforeach; ?></tbody></table></div>
    </div>
</div>
