<?php $title = 'الإعدادات'; ?>
<h5 class="mb-3">إعدادات النظام</h5>
<form method="post" action="<?= url('/settings') ?>" enctype="multipart/form-data" class="row g-3">
    <?= csrf_field() ?>
    <div class="col-md-4">
        <label class="form-label">اسم المتجر</label>
        <input class="form-control" name="company_name" value="<?= e($settings['company_name'] ?? '') ?>" required placeholder="مثال: سوبر ماركت النور">
        <div class="form-text">يمكنك كتابة أي اسم وسيظهر في الهيدر والطباعة.</div>
    </div>
    <div class="col-md-4"><label class="form-label">الهاتف</label><input class="form-control" name="company_phone" value="<?= e($settings['company_phone'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">العملة</label><input class="form-control" name="currency" value="<?= e($settings['currency'] ?? 'ج.م') ?>"></div>
    <div class="col-md-6"><label class="form-label">العنوان</label><input class="form-control" name="company_address" value="<?= e($settings['company_address'] ?? '') ?>"></div>
    <div class="col-md-3"><label class="form-label">الرقم الضريبي</label><input class="form-control" name="tax_number" value="<?= e($settings['tax_number'] ?? '') ?>"></div>
    <div class="col-md-3"><label class="form-label">ملف الشعار</label><input class="form-control" type="file" name="logo"></div>
    <div class="col-md-4"><label class="form-label">نمط الطباعة</label><select class="form-select" name="receipt_print_mode"><option value="thermal" <?= ($settings['receipt_print_mode'] ?? '')==='thermal'?'selected':'' ?>>حراري</option><option value="a4" <?= ($settings['receipt_print_mode'] ?? '')==='a4'?'selected':'' ?>>A4</option></select></div>
    <div class="col-md-4"><label class="form-label">يتطلب شيفت قبل البيع</label><select class="form-select" name="require_shift_for_sale"><option value="1" <?= ($settings['require_shift_for_sale'] ?? '1')==='1'?'selected':'' ?>>نعم</option><option value="0" <?= ($settings['require_shift_for_sale'] ?? '')==='0'?'selected':'' ?>>لا</option></select></div>
    <div class="col-md-4"><label class="form-label">السماح بمخزون سالب</label><select class="form-select" name="allow_negative_stock"><option value="0" <?= ($settings['allow_negative_stock'] ?? '0')==='0'?'selected':'' ?>>لا</option><option value="1" <?= ($settings['allow_negative_stock'] ?? '')==='1'?'selected':'' ?>>نعم</option></select></div>
    <div class="col-12"><hr class="my-1"></div>
    <div class="col-12"><h6 class="mb-1">إعدادات ميزان الباركود</h6><div class="form-text">الإعدادات الافتراضية المقترحة: Prefix = 20,28 (أو 28)، الطول = 13، وضع = وزن.</div></div>
    <div class="col-md-3"><label class="form-label">تفعيل ميزان الباركود</label><select class="form-select" name="scale_barcode_enabled"><option value="1" <?= ($settings['scale_barcode_enabled'] ?? '0')==='1'?'selected':'' ?>>مفعل</option><option value="0" <?= ($settings['scale_barcode_enabled'] ?? '0')==='0'?'selected':'' ?>>غير مفعل</option></select></div>
    <div class="col-md-3"><label class="form-label">Prefix (comma separated)</label><input class="form-control" name="scale_barcode_prefix" value="<?= e($settings['scale_barcode_prefix'] ?? '20,28') ?>"></div>
    <div class="col-md-3"><label class="form-label">الطول الكلي</label><input class="form-control" type="number" min="8" name="scale_barcode_total_length" value="<?= e($settings['scale_barcode_total_length'] ?? '13') ?>"></div>
    <div class="col-md-3"><label class="form-label">الوضع</label><select class="form-select" name="scale_barcode_mode"><option value="weight" <?= ($settings['scale_barcode_mode'] ?? 'weight')==='weight'?'selected':'' ?>>Weight</option><option value="price" <?= ($settings['scale_barcode_mode'] ?? '')==='price'?'selected':'' ?>>Price</option></select></div>

    <div class="col-md-3"><label class="form-label">item code start</label><input class="form-control" type="number" min="1" name="scale_item_code_start" value="<?= e($settings['scale_item_code_start'] ?? '3') ?>"></div>
    <div class="col-md-3"><label class="form-label">item code length</label><input class="form-control" type="number" min="1" name="scale_item_code_length" value="<?= e($settings['scale_item_code_length'] ?? '5') ?>"></div>
    <div class="col-md-3"><label class="form-label">weight start</label><input class="form-control" type="number" min="1" name="scale_weight_start" value="<?= e($settings['scale_weight_start'] ?? '8') ?>"></div>
    <div class="col-md-3"><label class="form-label">weight length</label><input class="form-control" type="number" min="1" name="scale_weight_length" value="<?= e($settings['scale_weight_length'] ?? '5') ?>"></div>

    <div class="col-md-3"><label class="form-label">weight decimals</label><input class="form-control" type="number" min="0" max="6" name="scale_weight_decimals" value="<?= e($settings['scale_weight_decimals'] ?? '3') ?>"></div>
    <div class="col-md-3"><label class="form-label">price start</label><input class="form-control" type="number" min="1" name="scale_price_start" value="<?= e($settings['scale_price_start'] ?? '8') ?>"></div>
    <div class="col-md-3"><label class="form-label">price length</label><input class="form-control" type="number" min="1" name="scale_price_length" value="<?= e($settings['scale_price_length'] ?? '5') ?>"></div>
    <div class="col-md-3"><label class="form-label">price decimals</label><input class="form-control" type="number" min="0" max="6" name="scale_price_decimals" value="<?= e($settings['scale_price_decimals'] ?? '2') ?>"></div>

    <div class="col-md-3"><label class="form-label">تحقق رقم المراجعة</label><select class="form-select" name="scale_check_digit_enabled"><option value="1" <?= ($settings['scale_check_digit_enabled'] ?? '0')==='1'?'selected':'' ?>>نعم</option><option value="0" <?= ($settings['scale_check_digit_enabled'] ?? '0')==='0'?'selected':'' ?>>لا</option></select></div>
    <div class="col-md-3"><label class="form-label">أقصى وزن (كجم)</label><input class="form-control" type="number" min="0.001" step="0.001" name="scale_max_weight_kg" value="<?= e($settings['scale_max_weight_kg'] ?? '50') ?>"></div>
    <div class="col-md-12">
        <label class="form-label">تذييل الفاتورة</label>
        <input class="form-control" name="invoice_footer" value="<?= e($settings['invoice_footer'] ?? 'صل ع النبي') ?>">
    </div>

    <div class="col-12"><hr class="my-2"></div>
    <div class="col-12">
        <h6 class="mb-1 text-success fw-bold"><i class="bi bi-cpu"></i> إعدادات تطبيق الديسك توب والاتصال السحابي (Cloudflare & Printing)</h6>
        <div class="form-text text-muted">هذه الإعدادات مخصصة لإدارة طابعة الفواتير والتحكم في الخدمة السحابية.</div>
    </div>

    <!-- قسم طابعة الفواتير الافتراضية -->
    <div class="col-md-4" id="desktop-printer-section" style="display:none;">
        <label class="form-label fw-semibold">طابعة الفواتير الافتراضية (Thermal Printer)</label>
        <select class="form-select border-success" name="default_printer" id="default_printer">
            <option value="">طابعة النظام الافتراضية</option>
        </select>
        <div class="form-text text-muted">سيتم توجيه الفواتير صامتاً إلى هذه الطابعة.</div>
    </div>

    <!-- قسم الـ Cloudflare Tunnel Token -->
    <div class="col-md-4" id="desktop-tunnel-section" style="display:none;">
        <label class="form-label fw-semibold">رمز نفق كلاود فلير (Tunnel Token)</label>
        <input class="form-control border-success" name="cloudflare_tunnel_token" id="cloudflare_tunnel_token" value="<?= e($settings['cloudflare_tunnel_token'] ?? '') ?>" placeholder="أدخل الـ Token المشفر هنا">
        <div class="form-text text-muted">مطلوب لتشغيل النفق بالخلفية للاتصال بالمتجر.</div>
    </div>

    <!-- قسم الـ Cloudflare Tunnel Domain -->
    <div class="col-md-4" id="desktop-domain-section" style="display:none;">
        <label class="form-label fw-semibold">رابط/نطاق النفق (Tunnel Domain)</label>
        <input class="form-control border-success" name="cloudflare_tunnel_domain" id="cloudflare_tunnel_domain" value="<?= e($settings['cloudflare_tunnel_domain'] ?? '') ?>" placeholder="مثال: pos.nexus-os.site">
        <div class="form-text text-muted">الدومين العام للوصول للكاشير من الخارج.</div>
    </div>

    <!-- حالة الخدمة السحابية -->
    <div class="col-12" id="desktop-tunnel-status-container" style="display:none;">
        <div class="card bg-light border-0 shadow-sm mb-3">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span id="tunnel-status-badge" class="badge bg-secondary p-2 fs-6">غير متصل</span>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">حالة خدمة Cloudflare Tunnel</h6>
                        <span id="tunnel-status-text" class="small text-muted">البرنامج يتأكد من حالة الخدمة...</span>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-success btn-sm me-2" id="btn-check-tunnel">
                        <i class="bi bi-arrow-repeat"></i> فحص الاتصال الآن
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="btn-restart-tunnel">
                        <i class="bi bi-play-fill"></i> تشغيل وتفعيل النفق
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12"><button class="btn btn-success">حفظ الإعدادات</button></div>
</form>

<div class="card border-info mt-4">
    <div class="card-body">
        <?php \App\Core\View::partial('cashier-keyboard/partials/manager', [
            'keyboardShortcuts' => $keyboardShortcuts ?? [],
            'keyboardActionTypes' => $keyboardActionTypes ?? [],
            'keyboardRedirect' => 'settings',
            'keyboardHeading' => 'إعدادات كيبورد CNR Cashier',
            'keyboardSubheading' => 'اربط زر الكيبورد بوظيفة داخل شاشة البيع مثل الطباعة، الدفع، الخصم، تعليق الفاتورة، أو إضافة صنف سريع.',
        ]); ?>
    </div>
</div>

<div class="card border-primary mt-4">
    <div class="card-body">
        <h6 class="text-primary fw-bold mb-2">نسخة احتياطية</h6>
        <p class="small text-muted mb-3">
            تنزيل نسخة احتياطية كاملة (ZIP) تشمل قاعدة البيانات + ملفات الرفع `uploads` + إعدادات التهيئة الأساسية.
            لا يتم تعديل أو حذف أي بيانات أثناء التنزيل.
        </p>
        <form method="post" action="<?= url('/settings/backup') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-primary" type="submit">تنزيل نسخة احتياطية كاملة</button>
        </form>
    </div>
</div>

<div class="card border-warning mt-4">
    <div class="card-body">
        <h6 class="text-warning-emphasis fw-bold mb-2">رفع واستعادة نسخة احتياطية</h6>
        <p class="small text-muted mb-3">
            ارفع ملف `ZIP` أو `SQL` لاستعادة البيانات. قبل التنفيذ يتم إنشاء نسخة أمان تلقائية من حالتك الحالية.
        </p>
        <form method="post" action="<?= url('/settings/backup/restore') ?>" enctype="multipart/form-data" data-confirm="سيتم استبدال بيانات البرنامج الحالية من الملف المرفوع. هل تريد المتابعة؟" class="row g-2">
            <?= csrf_field() ?>
            <div class="col-md-8">
                <input class="form-control" type="file" name="backup_file" accept=".zip,.sql" required>
            </div>
            <div class="col-md-4">
                <button class="btn btn-warning w-100" type="submit">رفع واستعادة النسخة</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-danger mt-4">
    <div class="card-body">
        <h6 class="text-danger fw-bold mb-2">منطقة خطرة - مسح البيانات</h6>
        <p class="small text-muted mb-3">
            يمكنك مسح الإدخالات أو الفواتير أو الأصناف. هذه العملية لا يمكن التراجع عنها.
        </p>
        <form method="post" action="<?= url('/settings/danger-reset') ?>" class="row g-2" data-confirm="تحذير: سيتم مسح البيانات المحددة نهائيًا. هل تريد المتابعة؟">
            <?= csrf_field() ?>
            <div class="col-md-4">
                <label class="form-label">نوع المسح</label>
                <select class="form-select" name="reset_scope" required>
                    <option value="">اختر</option>
                    <option value="entries">مسح الإدخالات (Logs + محاولات + تعليقات)</option>
                    <option value="invoices">مسح الفواتير والمعاملات</option>
                    <option value="products">مسح الأصناف</option>
                    <option value="all">مسح الكل</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">كلمة مرور التأكيد</label>
                <input class="form-control" type="password" name="reset_password" required placeholder="أدخل كلمة المرور">
                <div class="form-text">استخدم كلمة مرور حسابك الحالي أو كلمة المرور الخاصة بالمسح إن كانت مهيأة.</div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-danger w-100" type="submit">تنفيذ المسح</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // التحقق من أننا داخل تطبيق الديسكتوب (إلكترون)
    if (window.electronAPI) {
        // إظهار أقسام الديسكتوب
        document.getElementById('desktop-printer-section').style.display = 'block';
        document.getElementById('desktop-tunnel-section').style.display = 'block';
        document.getElementById('desktop-domain-section').style.display = 'block';
        document.getElementById('desktop-tunnel-status-container').style.display = 'block';

        // تعريف دالة نسخ الرابط للكلية
        window.copyDomainToClipboard = (text, event, isWebhook = false) => {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            navigator.clipboard.writeText(text).then(() => {
                const msg = isWebhook ? 'تم نسخ رابط الويب هوك للمتجر بنجاح ✓' : 'تم نسخ الرابط السحابي بنجاح ✓';
                if (typeof showGlobalDesktopToast === 'function') {
                    showGlobalDesktopToast(msg, 'success');
                } else {
                    alert(msg);
                }
            }).catch(err => {
                console.error('Failed to copy domain: ', err);
            });
        };

        // جلب الطابعات من النظام وإضافتها للـ Select
        window.electronAPI.getPrinters().then(printers => {
            const select = document.getElementById('default_printer');
            const savedPrinter = <?= json_encode($settings['default_printer'] ?? '') ?>;
            
            printers.forEach(printer => {
                const opt = document.createElement('option');
                opt.value = printer.name;
                opt.textContent = printer.name + (printer.isDefault ? ' (الافتراضية)' : '');
                if (printer.name === savedPrinter) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
        }).catch(err => {
            console.error('Error fetching printers:', err);
        });

        // دالة فحص حالة النفق
        const checkTunnelStatus = () => {
            const statusBadge = document.getElementById('tunnel-status-badge');
            const statusText = document.getElementById('tunnel-status-text');
            const btnRestart = document.getElementById('btn-restart-tunnel');

            statusBadge.className = 'badge bg-warning text-dark p-2 fs-6';
            statusBadge.textContent = 'جاري الفحص...';
            statusText.textContent = 'يتم الاستعلام من منفذ مقاييس كلاود فلير...';

            window.electronAPI.checkTunnelStatus().then(res => {
                if (res.online) {
                    statusBadge.className = 'badge bg-success p-2 fs-6';
                    statusBadge.textContent = 'متصل بنجاح ✓';
                    
                    const domain = document.getElementById('cloudflare_tunnel_domain').value.trim();
                    let domainHtml = '';
                    if (domain) {
                        const cleanDomain = domain.replace(/^(https?:\/\/)?/, 'https://');
                        const webhookUrl = `${cleanDomain.replace(/\/$/, '')}/api/webhook/new-order`;
                        domainHtml = ` | الرابط السحابي: <a href="${cleanDomain}" target="_blank" class="fw-bold text-success text-decoration-underline" title="اضغط لفتح الرابط، أو لنسخه" onclick="copyDomainToClipboard('${cleanDomain}', event, false)">${domain}</a> <i class="bi bi-clipboard text-muted fs-6 ms-1" style="cursor:pointer" onclick="copyDomainToClipboard('${cleanDomain}', event, false)"></i>`;
                        domainHtml += `<br><span class="mt-1 d-inline-block text-secondary small">رابط الـ Webhook للمتجر: <code class="text-primary fw-bold">${webhookUrl}</code> <i class="bi bi-clipboard text-muted fs-6 ms-1" style="cursor:pointer" title="نسخ رابط الويب هوك" onclick="copyDomainToClipboard('${webhookUrl}', event, true)"></i></span>`;
                    }
                    
                    statusText.innerHTML = `الخدمة تعمل وتستقبل الاتصالات. البورت المحلي: <code>${res.metricsPort}</code>${domainHtml}`;
                    btnRestart.innerHTML = '<i class="bi bi-arrow-repeat"></i> إعادة تشغيل النفق';
                } else {
                    statusBadge.className = 'badge bg-danger p-2 fs-6';
                    statusBadge.textContent = 'غير متصل ⚠️';
                    statusText.textContent = 'الخدمة غير متصلة أو متوقفة بالخلفية. تأكد من إدخال رمز Token صحيح والنقر على حفظ ثم إعادة التشغيل.';
                    btnRestart.innerHTML = '<i class="bi bi-play-fill"></i> تشغيل النفق';
                }
            }).catch(err => {
                statusBadge.className = 'badge bg-danger p-2 fs-6';
                statusBadge.textContent = 'خطأ في الاتصال';
                statusText.textContent = 'فشل الاستعلام من تطبيق الديسك توب: ' + err.message;
            });
        };

        // فحص تلقائي عند التحميل
        setTimeout(checkTunnelStatus, 1000);

        // أزرار الفحص والتحكم
        document.getElementById('btn-check-tunnel').addEventListener('click', checkTunnelStatus);
        
        document.getElementById('btn-restart-tunnel').addEventListener('click', () => {
            const token = document.getElementById('cloudflare_tunnel_token').value.trim();
            const domain = document.getElementById('cloudflare_tunnel_domain').value.trim();
            const printer = document.getElementById('default_printer').value;
            const csrfToken = document.querySelector('input[name="_token"]').value;

            const statusBadge = document.getElementById('tunnel-status-badge');
            const statusText = document.getElementById('tunnel-status-text');

            statusBadge.className = 'badge bg-warning text-dark p-2 fs-6';
            statusBadge.textContent = 'جاري الحفظ...';
            statusText.textContent = 'يتم حفظ الرمز والنطاق في قاعدة البيانات أولاً...';

            // 1. حفظ الإعدادات عبر الـ AJAX
            fetch('<?= url("/api/settings/save-tunnel") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    '_token': csrfToken,
                    'token': token,
                    'domain': domain,
                    'printer': printer
                })
            })
            .then(res => res.json())
            .then(saveRes => {
                if (saveRes.success) {
                    statusBadge.textContent = 'جاري التشغيل...';
                    statusText.textContent = 'تم حفظ الإعدادات بنجاح. جاري تشغيل/إعادة تشغيل النفق السحابي...';
                    
                    // 2. التحكم في النفق عبر الإلكترون
                    return window.electronAPI.controlTunnel('restart', token);
                } else {
                    throw new Error('فشل حفظ التعديلات بقاعدة البيانات المحلية.');
                }
            })
            .then(() => {
                setTimeout(checkTunnelStatus, 2500); // الانتظار للربط ثم الفحص
            })
            .catch(err => {
                alert('فشل تفعيل النفق: ' + err.message);
                checkTunnelStatus();
            });
        });
    }
});
</script>
