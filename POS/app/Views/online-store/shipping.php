<?php $title = 'إدارة مصاريف الشحن والتسليم'; ?>

<h5 class="mb-3">🚚 إدارة مصاريف الشحن والتسليم للمتجر الإلكتروني</h5>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <p class="small text-muted mb-0">
            يمكنك من هنا تحديد رسوم الشحن الافتراضية للطلبات الواردة عبر المتجر الإلكتروني، بالإضافة إلى وضع حد الشحن المجاني التلقائي للعملاء عند وصول قيمة مشترياتهم لمبلغ معين.
            <br>
            <strong>ملاحظة:</strong> يتم تحديث ومزامنة هذه الإعدادات تلقائياً مع واجهة متجر العميل عند الحفظ.
        </p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="<?= url('/online-store/shipping/update') ?>" method="POST" class="row g-3">
                    <?= csrf_field() ?>
                    
                    <div class="col-12">
                        <label class="form-label fw-bold">🏍️ رسوم شحن الناصرية والعامرية</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" name="shipping_fee" class="form-control text-center fs-5 fw-bold" value="<?= (float)$shippingFee ?>" required>
                            <span class="input-group-text fw-bold">ج.م</span>
                        </div>
                        <div class="form-text">القيمة الافتراضية المضافة للطلبات التي لا تتخطى حد الشحن المجاني.</div>
                    </div>

                    <div class="col-12 mt-4">
                        <label class="form-label fw-bold">📦 الحد الأدنى للحصول على شحن مجاني</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" name="free_shipping_threshold" class="form-control text-center fs-5 fw-bold" value="<?= (float)$freeShippingThreshold ?>" required>
                            <span class="input-group-text fw-bold">ج.م</span>
                        </div>
                        <div class="form-text">عندما يتخطى إجمالي الأصناف هذا المبلغ، تصبح رسوم الشحن (توصيل مجاني 🚚).</div>
                    </div>

                    <div class="col-12 mt-4 pt-2">
                        <button type="submit" class="btn btn-success w-100 fw-bold">
                            💾 حفظ ومزامنة إعدادات الشحن
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-7">
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">💡 كيف تعمل آلية حساب الشحن؟</h6>
                <ul class="text-muted small ps-0" style="list-style-type: arabic-indic; line-height: 1.8;">
                    <li class="mb-2">يقوم المتجر الإلكتروني بقراءة ملف التهيئة <code>ecom_config.json</code> مباشرة عند دخول العميل لصفحة الدفع.</li>
                    <li class="mb-2">إذا كانت قيمة المشتريات (السلة) أقل من <strong>الحد الأدنى للشحن المجاني</strong>، يتم إضافة <strong>رسوم الشحن</strong> تلقائياً إلى المبلغ الإجمالي للطلب.</li>
                    <li class="mb-2">إذا كانت قيمة المشتريات مساوية أو أكبر من <strong>الحد الأدنى للشحن المجاني</strong>، تظهر رسالة <code>توصيل مجاني 🚚</code> وتصبح رسوم الشحن صفراً تلقائياً.</li>
                    <li class="mb-2">يظهر للعميل في واجهة المتجر شريط تقدم تفاعلي يخبره بالمبلغ المتبقي له للحصول على الشحن المجاني لتشجيعه على زيادة حجم مشترياته.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6 col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-bold mb-0">📍 مناطق الشحن الحالية وأسعارها</h6>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover align-middle table-sm">
                        <thead>
                            <tr>
                                <th class="py-2">المنطقة</th>
                                <th class="py-2 text-center">تكلفة الشحن</th>
                                <th class="py-2 text-center">الحالة</th>
                                <th class="py-2 text-end">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($zones)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4 small">
                                        لا توجد مناطق شحن مضافة بعد. أضف منطقة جديدة من النموذج المجاور.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($zones as $zone): ?>
                                    <tr>
                                        <td class="fw-semibold text-gray-800"><?= e($zone['name']) ?></td>
                                        <td class="text-center fw-bold text-success"><?= number_format($zone['price'], 2) ?> ج.م</td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">نشط</span>
                                        </td>
                                        <td class="text-end">
                                            <form action="<?= url('/online-store/shipping/zones/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه المنطقة؟');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= e($zone['id']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 border-0">
                                                    🗑️ حذف
                                                </button>
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

    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-bold mb-0">➕ إضافة أو تعديل منطقة شحن</h6>
            </div>
            <div class="card-body p-4">
                <form action="<?= url('/online-store/shipping/zones/add') ?>" method="POST" class="row g-3">
                    <?= csrf_field() ?>
                    
                    <div class="col-12">
                        <label class="form-label fw-bold">اسم المنطقة / الزون</label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: العامرية، الناصرية الجديدة، الكنج مريوط..." required>
                        <div class="form-text text-muted small">ملاحظة: إذا كانت المنطقة مضافة بالفعل، سيتم تحديث سعر شحنها فقط.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">تكلفة الشحن لهذه المنطقة</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" name="price" class="form-control text-center fw-bold fs-5" placeholder="0.00" required>
                            <span class="input-group-text fw-bold">ج.م</span>
                        </div>
                    </div>

                    <div class="col-12 pt-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            💾 حفظ ومزامنة المنطقة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
