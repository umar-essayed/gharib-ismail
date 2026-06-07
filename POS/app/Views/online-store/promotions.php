<?php $title = 'إدارة العروض والخصومات'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">✨ إدارة العروض والكوبونات والبنرات</h5>
    <?php if (!$online): ?>
        <span class="badge bg-danger">🔴 غير متصل بالمتجر</span>
    <?php else: ?>
        <span class="badge bg-success">🟢 متصل بالمتجر</span>
    <?php endif; ?>
</div>

<ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="pills-coupons-tab" data-bs-toggle="pill" data-bs-target="#pills-coupons" type="button" role="tab" aria-selected="true">🎟️ كوبونات الخصم</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="pills-banners-tab" data-bs-toggle="pill" data-bs-target="#pills-banners" type="button" role="tab" aria-selected="false">🖼️ بنرات العروض الرئيسية</button>
    </li>
</ul>

<div class="tab-content" id="pills-tabContent">
    <!-- تبويب الكوبونات -->
    <div class="tab-pane fade show active" id="pills-coupons" role="tabpanel" aria-labelledby="pills-coupons-tab">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                <h6 class="mb-0 fw-bold">🎟️ الكوبونات النشطة وقسائم الشراء في المتجر</h6>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createCouponModal" <?= !$online ? 'disabled' : '' ?>>➕ إنشاء كوبون جديد</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الكود</th>
                                <th>الوصف</th>
                                <th>تفاصيل الخصم</th>
                                <th class="text-center">أدنى طلبية</th>
                                <th class="text-center">تكلفة النقاط</th>
                                <th class="text-center">مرات الاستخدام</th>
                                <th class="text-center">الحالة</th>
                                <th class="text-center">العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        لا توجد كوبونات خصم نشطة حالياً.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary text-white font-monospace fs-6 px-3 py-1.5 border">
                                                <?= e($coupon['code']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small fw-bold text-gray-700"><?= e($coupon['description'] ?: 'لا يوجد وصف') ?></div>
                                            <small class="text-muted">أُنشئ في: <?= date('Y-m-d', strtotime($coupon['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                                <span class="badge text-bg-success">خصم نسبى <?= (float)$coupon['discount_value'] ?>%</span>
                                            <?php elseif ($coupon['discount_type'] === 'fixed'): ?>
                                                <span class="badge text-bg-info">خصم ثابت <?= (float)$coupon['discount_value'] ?> ج.م</span>
                                            <?php elseif ($coupon['discount_type'] === 'points'): ?>
                                                <span class="badge text-bg-warning text-dark">شراء بالنقاط <?= (float)$coupon['discount_value'] ?> ج.م</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold"><?= (float)$coupon['min_order_amount'] ?> ج.م</td>
                                        <td class="text-center">
                                            <?php if ($coupon['points_cost'] > 0): ?>
                                                <span class="badge bg-warning text-dark">🪙 <?= (int)$coupon['points_cost'] ?> نقطة</span>
                                            <?php else: ?>
                                                <span class="text-muted small">بدون نقاط</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold text-primary font-monospace">
                                            <?= (int)($coupon['usage_count'] ?? 0) ?>
                                        </td>
                                        <td class="text-center">
                                            <form action="<?= url('/online-store/coupons/toggle') ?>" method="POST">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="code" value="<?= e($coupon['code']) ?>">
                                                <input type="hidden" name="is_active" value="<?= $coupon['is_active'] ? '0' : '1' ?>">
                                                <button type="submit" class="btn btn-sm <?= $coupon['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>">
                                                    <?= $coupon['is_active'] ? '🟢 نشط' : '🔴 معطل' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <form action="<?= url('/online-store/coupons/delete') ?>" method="POST" onsubmit="return confirm('⚠️ هل أنت متأكد من حذف هذا الكوبون نهائياً؟')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="code" value="<?= e($coupon['code']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">🗑️ حذف</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- تبويب البنرات -->
    <div class="tab-pane fade" id="pills-banners" role="tabpanel" aria-labelledby="pills-banners-tab">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                <h6 class="mb-0 fw-bold">🖼️ بنرات العروض والإعلانات المنزلقة في الواجهة الرئيسية للمتجر</h6>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createBannerModal" <?= !$online ? 'disabled' : '' ?>>➕ إضافة بنر عرض جديد</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>البنر (معاينة)</th>
                                <th>العنوان</th>
                                <th>الرابط الموجه إليه</th>
                                <th>تاريخ الإضافة</th>
                                <th class="text-center">العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($banners)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        لا توجد بنرات عروض مضافة حالياً.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($banners as $banner): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= e($banner['image_url']) ?>" alt="Banner Image" class="rounded border" style="height: 60px; width: 120px; object-fit: cover;">
                                        </td>
                                        <td class="fw-bold"><?= e($banner['title']) ?></td>
                                        <td class="text-monospace small"><?= e($banner['link_url'] ?: '/') ?></td>
                                        <td class="small"><?= date('Y-m-d', strtotime($banner['created_at'])) ?></td>
                                        <td class="text-center">
                                            <form action="<?= url('/online-store/banners/delete') ?>" method="POST" onsubmit="return confirm('هل تريد حذف هذا البنر نهائياً من شاشة عروض المتجر؟')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= e($banner['id']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">🗑️ حذف</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal إنشاء كوبون -->
<div class="modal fade" id="createCouponModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= url('/online-store/coupons/create') ?>" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">🎟️ إنشاء كوبون خصم جديد</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">كود الخصم (رمز الكوبون)</label>
                    <input type="text" name="code" class="form-control text-monospace fw-bold text-uppercase" placeholder="مثال: OFFER20" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">نوع الخصم</label>
                    <select name="discount_type" class="form-select">
                        <option value="percentage">نسبة مئوية (%)</option>
                        <option value="fixed">خصم نقدي ثابت (ج.م)</option>
                        <option value="points">شراء بمكافأة النقاط الذهبية (ج.م)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">قيمة الخصم</label>
                    <input type="number" step="0.01" name="discount_value" class="form-control fw-bold" min="0" placeholder="0.00" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">أدنى قيمة للطلب لتفعيل الكوبون</label>
                    <input type="number" step="0.01" name="min_order_amount" class="form-control" min="0" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">تكلفة النقاط الذهبية للاسترداد</label>
                    <input type="number" name="points_cost" class="form-control" min="0" value="0">
                    <div class="form-text">تستخدم لو كان الكوبون يستلزم استبداله بنقاط العميل.</div>
                </div>
                <div class="col-md-6 d-flex align-items-end mb-3">
                    <div class="form-check form-switch w-100">
                        <input class="form-check-input" type="checkbox" name="is_active" id="couponActiveSwitch" checked>
                        <label class="form-check-label fw-bold" for="couponActiveSwitch">تفعيل الكوبون فوراً</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">الوصف (يظهر للعميل)</label>
                    <input type="text" name="description" class="form-control" placeholder="مثال: خصم 20% لعملاء العامرية والناصرية" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary btn-sm">حفظ الكوبون</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal إنشاء بنر عرض -->
<div class="modal fade" id="createBannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= url('/online-store/banners/create') ?>" method="POST" enctype="multipart/form-data" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">🖼️ إضافة بنر عرض إعلاني جديد</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">عنوان العرض الإعلاني</label>
                    <input type="text" name="title" class="form-control" placeholder="أدخل اسماً أو عنواناً مختصراً للعرض" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">ملف الصورة (تحميل من الجهاز)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <div class="form-text">أو اكتب رابطاً مباشراً للصورة بالأسفل إذا كانت مرفوعة مسبقاً.</div>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">رابط الصورة المباشر (اختياري)</label>
                    <input type="url" name="image_url" class="form-control text-monospace" placeholder="https://example.com/image.jpg">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">رابط التوجيه عند النقر (Link URL)</label>
                    <input type="text" name="link_url" class="form-control text-monospace" value="/" placeholder="مثال: /products أو /category/1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary btn-sm">إضافة البنر</button>
            </div>
        </form>
    </div>
</div>
