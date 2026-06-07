<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SupabaseSyncService;
use App\Models\SalesModel;
use App\Models\ProductModel;
use App\Models\CustomerModel;
use App\Services\AuthService;
use App\Services\SettingsService;
use App\Core\Database;
use Exception;

class OnlineOrdersController extends Controller
{
    // ============================================================
    //  لوحة الطلبات الإلكترونية
    // ============================================================
    public function index(): void
    {
        $orders = [];
        $syncError = null;
        $online = false;

        try {
            $online = SupabaseSyncService::isOnline();
            if ($online) {
                $orders = SupabaseSyncService::pullOrders();
            }
        } catch (Exception $e) {
            $syncError = 'فشل الاتصال بالمتجر: ' . $e->getMessage();
        }

        // إحصائيات سريعة
        $db = Database::pdo();
        $totalOnlineToday = $db->query(
            "SELECT COUNT(*) FROM sales_invoices WHERE is_online_order = 1 AND DATE(created_at) = DATE('now')"
        )->fetchColumn();
        $totalOnlineAll   = $db->query(
            "SELECT COUNT(*) FROM sales_invoices WHERE is_online_order = 1"
        )->fetchColumn();
        $queueCount       = $db->query(
            "SELECT COUNT(*) FROM supabase_sync_queue"
        )->fetchColumn();

        $this->view('online-orders/index', compact(
            'orders', 'syncError', 'online',
            'totalOnlineToday', 'totalOnlineAll', 'queueCount'
        ));
    }

    // ============================================================
    //  قبول طلب إلكتروني وتحويله لفاتورة
    // ============================================================
    public function accept(string $id): void
    {
        validate_csrf_or_abort();

        try {
            $db = Database::pdo();

            // 1. جلب الطلب من Supabase
            $order = SupabaseSyncService::getOrder($id);
            if (!$order) {
                throw new Exception('الطلب غير موجود أو تم حذفه من المتجر.');
            }
            if ($order['status'] !== 'pending') {
                throw new Exception('الطلب بالفعل تمت معالجته (حالته: ' . $order['status'] . ').');
            }

            // 2. التحقق من وجود شيفت مفتوح — أولاً من الـ Session ثم من قاعدة البيانات
            $shiftId      = $_SESSION['shift_id'] ?? null;
            $requireShift = \App\Services\SettingsService::get('require_shift_for_sale', '1') === '1';

            if (!$shiftId) {
                // ابحث عن شيفت مفتوح للمستخدم الحالي في DB
                $userId   = (int) (\App\Services\AuthService::id() ?? 0);
                $shiftStmt = $db->prepare(
                    'SELECT id FROM cash_shifts WHERE user_id = :uid AND status = "open" ORDER BY id DESC LIMIT 1'
                );
                $shiftStmt->execute(['uid' => $userId]);
                $shiftId = $shiftStmt->fetchColumn() ?: null;
            }

            if (!$shiftId) {
                // جرب أي شيفت مفتوح في النظام
                $anyShift = $db->query('SELECT id FROM cash_shifts WHERE status = "open" ORDER BY id DESC LIMIT 1')->fetchColumn();
                $shiftId  = $anyShift ?: null;
            }

            if ($requireShift && !$shiftId) {
                throw new Exception('يجب فتح شيفت أولاً من صفحة الشيفتات ثم العودة لقبول الطلبات الإلكترونية.');
            }

            // 3. تحليل الأصناف ومطابقتها بالمنتجات المحلية
            $rawItems   = $order['items'];
            $items      = is_string($rawItems) ? json_decode($rawItems, true) : $rawItems;
            $localItems = [];

            foreach ((array) $items as $item) {
                // بحث أولاً بـ supabase_id ثم باسم المنتج
                $prodSid  = $item['product_id'] ?? null;
                $localRow = null;

                if ($prodSid) {
                    $s = $db->prepare('SELECT id, sale_price, name FROM products WHERE supabase_id = :sid AND deleted_at IS NULL');
                    $s->execute(['sid' => $prodSid]);
                    $localRow = $s->fetch() ?: null;
                }

                if (!$localRow) {
                    $s2 = $db->prepare('SELECT id, sale_price, name FROM products WHERE name = :nm AND deleted_at IS NULL LIMIT 1');
                    $s2->execute(['nm' => $item['name'] ?? '']);
                    $localRow = $s2->fetch() ?: null;
                }

                if (!$localRow) {
                    throw new Exception("الصنف «" . ($item['name'] ?? '—') . "» غير موجود محلياً في الكاشير. أضفه أولاً من صفحة المنتجات.");
                }

                $localItems[] = [
                    'product_id'  => (int)   $localRow['id'],
                    'qty'         => (float)  ($item['qty'] ?? 1),
                    'unit_price'  => (float)  ($item['price'] ?? $localRow['sale_price']),
                    'sale_unit'   => 'piece',
                ];
            }

            // 4. إنشاء الفاتورة المحلية
            $warehouseId     = (int) SettingsService::get('default_warehouse_id', '1');
            $cashCustomerId  = (int) SettingsService::get('default_customer_id', '1');

            // محاولة إيجاد عميل بهاتف الطلب أو استخدام العميل الافتراضي
            $deliveryPhone = $order['delivery_phone'] ?? '';
            if ($deliveryPhone) {
                $sc = $db->prepare('SELECT id FROM customers WHERE phone = :ph AND deleted_at IS NULL LIMIT 1');
                $sc->execute(['ph' => $deliveryPhone]);
                $found = $sc->fetchColumn();
                if ($found) {
                    $cashCustomerId = (int) $found;
                }
            }

            // جلب اسم العميل من Supabase profiles
            $onlineCustomerName = null;
            $orderUserId = $order['user_id'] ?? null;
            if ($orderUserId) {
                try {
                    $profileRes = SupabaseSyncService::getProfile($orderUserId);
                    if (!empty($profileRes['full_name'])) {
                        $onlineCustomerName = $profileRes['full_name'];
                    }
                } catch (\Throwable $pe) {
                    // تجاهل خطأ جلب الاسم
                }
            }
            // إذا لم نجد الاسم، نستخدم رقم الهاتف
            if (!$onlineCustomerName && $deliveryPhone) {
                $onlineCustomerName = $deliveryPhone;
            }

            // إيجاد طريقة الدفع النقدي
            $pmStmt = $db->prepare("SELECT id FROM payment_methods WHERE code = 'cash' AND is_active = 1 LIMIT 1");
            $pmStmt->execute();
            $cashPaymentMethodId = $pmStmt->fetchColumn() ?: 1;

            $salesModel  = new SalesModel();
            $invoiceData = [
                'warehouse_id'     => $warehouseId,
                'customer_id'      => $cashCustomerId,
                'shift_id'         => $shiftId,
                'payment_method_id'=> (int) $cashPaymentMethodId,
                'paid_total'       => 0,
                'is_cod'           => true, // دفع عند الاستلام — لا تعبئ تلقائياً
                'note'             => 'طلب متجر إلكتروني #' . strtoupper(substr($id, 0, 8))
                                    . ' | 📍 ' . ($order['delivery_address'] ?? '')
                                    . ' | 📞 ' . $deliveryPhone,
                'items'            => $localItems,
            ];

            $result = $salesModel->createFromPos($invoiceData);

            // 5. ربط الفاتورة بـ UUID السحابي + حفظ اسم العميل الإلكتروني
            $upd = $db->prepare(
                'UPDATE sales_invoices SET supabase_order_id = :sid, is_online_order = 1, online_customer_name = :cname, online_order_status = \'preparing\' WHERE id = :id'
            );
            $upd->execute(['sid' => $id, 'cname' => $onlineCustomerName, 'id' => $result['id']]);

            // 6. تحديث حالة الطلب في Supabase → preparing
            SupabaseSyncService::updateOrderStatus($id, 'preparing', $result['invoice_no']);

            $isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
            if ($isAjax) {
                $this->json([
                    'success'    => true,
                    'message'    => '✅ تم قبول الطلب بنجاح! رقم الفاتورة: ' . $result['invoice_no'],
                    'invoice_id' => $result['id'],
                    'invoice_no' => $result['invoice_no'],
                ]);
                return;
            }

            flash_success('✅ تم قبول الطلب بنجاح! رقم الفاتورة: ' . $result['invoice_no']);
            $this->redirect('/online-orders');

        } catch (Exception $e) {
            $isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => '❌ فشل قبول الطلب: ' . $e->getMessage()
                ], 400);
                return;
            }

            flash_error('❌ فشل قبول الطلب: ' . $e->getMessage());
            $this->redirect('/online-orders');
        }
    }

    // ============================================================
    //  إلغاء طلب إلكتروني
    // ============================================================
    public function cancel(string $id): void
    {
        validate_csrf_or_abort();
        $isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
        try {
            // نحدث الحالة في Supabase (نضعها completed لأن لا يوجد status=cancelled حالياً)
            // أو يمكن إضافة status=cancelled في المخطط لاحقاً
            SupabaseSyncService::updateOrderStatus($id, 'completed');
            
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'تم إلغاء الطلب الإلكتروني بنجاح.'
                ]);
                return;
            }
            flash_success('تم إلغاء الطلب الإلكتروني.');
        } catch (Exception $e) {
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'فشل إلغاء الطلب: ' . $e->getMessage()
                ], 400);
                return;
            }
            flash_error('فشل إلغاء الطلب: ' . $e->getMessage());
        }
        $this->redirect('/online-orders');
    }

    // ============================================================
    //  تحديث الحالة → Delivering
    // ============================================================
    public function ship(string $id): void
    {
        validate_csrf_or_abort();
        $isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
        try {
            SupabaseSyncService::updateOrderStatus($id, 'delivering');

            // حفظ الحالة محلياً
            Database::pdo()->prepare(
                'UPDATE sales_invoices SET online_order_status = "delivering" WHERE supabase_order_id = :sid AND is_online_order = 1'
            )->execute(['sid' => $id]);

            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'تم تحديث حالة الطلب إلى "جاري التوصيل" 🚚'
                ]);
                return;
            }
            flash_success('تم تحديث حالة الطلب إلى "جاري التوصيل" 🚚');
        } catch (Exception $e) {
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'فشل التحديث: ' . $e->getMessage()
                ], 400);
                return;
            }
            flash_error('فشل التحديث: ' . $e->getMessage());
        }
        $this->redirect('/online-orders/accepted');
    }

    // ============================================================
    //  تحديث الحالة → Completed
    // ============================================================
    public function complete(string $id): void
    {
        validate_csrf_or_abort();
        $isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
        try {
            $db = Database::pdo();

            // 1. تحديث حالة الطلب في Supabase → completed
            SupabaseSyncService::updateOrderStatus($id, 'completed');

            // 2. تحديث الفاتورة المحلية → مدفوعة (COD تم الاستلام)
            $invStmt = $db->prepare(
                'SELECT id, grand_total, shift_id, user_id, payment_method_id
                 FROM sales_invoices
                 WHERE supabase_order_id = :sid AND is_online_order = 1
                 LIMIT 1'
            );
            $invStmt->execute(['sid' => $id]);
            $invoice = $invStmt->fetch();

            if ($invoice) {
                $invoiceId  = (int) $invoice['id'];
                $grandTotal = (float) $invoice['grand_total'];

                // تحديث الفاتورة → مدفوعة
                $db->prepare(
                    'UPDATE sales_invoices
                     SET payment_status = "paid", paid_total = :total, due_total = 0,
                         online_order_status = "completed"
                     WHERE id = :id'
                )->execute(['total' => $grandTotal, 'id' => $invoiceId]);

                // تسجيل حركة الصندوق
                if ($invoice['shift_id']) {
                    \App\Services\CashService::movement(
                        (int) $invoice['shift_id'],
                        (int) $invoice['user_id'],
                        'sale', 'in',
                        $grandTotal,
                        'sales_invoices', $invoiceId,
                        'تحصيل COD - طلب إلكتروني'
                    );
                }

                // تسجيل دفعة في جدول invoice_payments
                $pmId = (int) ($invoice['payment_method_id'] ?? 1);
                $db->prepare(
                    'INSERT OR IGNORE INTO sales_invoice_payments
                     (sales_invoice_id, payment_method_id, amount, created_at)
                     VALUES (:inv, :pm, :amt, datetime(\'now\', \'localtime\'))'
                )->execute(['inv' => $invoiceId, 'pm' => $pmId, 'amt' => $grandTotal]);

                // تحديث دفتر الأستاذ (تحصيل نقدي)
                $custId = (int) $db->query('SELECT customer_id FROM sales_invoices WHERE id = ' . $invoiceId)->fetchColumn();
                \App\Services\LedgerService::customer(
                    $custId,
                    'payment_in', 0, $grandTotal,
                    'sales_invoices', $invoiceId, 'تحصيل COD - طلب إلكتروني'
                );
            }

            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'تم تسليم وإغلاق الطلب ✅'
                ]);
                return;
            }
            flash_success('تم تسليم وإغلاق الطلب ✅');
        } catch (Exception $e) {
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'فشل إغلاق الطلب: ' . $e->getMessage()
                ], 400);
                return;
            }
            flash_error('فشل إغلاق الطلب: ' . $e->getMessage());
        }
        $this->redirect('/online-orders/accepted');
    }

    // ============================================================
    //  الطلبات المقبولة (في الكاشير فواتير)
    // ============================================================
    public function accepted(): void
    {
        $db   = Database::pdo();
        $rows = $db->query(
            "SELECT si.*, si.supabase_order_id
             FROM sales_invoices si
             WHERE si.is_online_order = 1
             ORDER BY si.id DESC
             LIMIT 100"
        )->fetchAll();

        $this->view('online-orders/accepted', compact('rows'));
    }

    // ============================================================
    //  مزامنة يدوية كاملة (Full Sync)
    // ============================================================
    public function fullSync(): void
    {
        validate_csrf_or_abort();
        try {
            $result = SupabaseSyncService::fullSync();
            $msg = "✅ اكتملت المزامنة: {$result['categories']} قسم، {$result['products']} منتج.";
            if (!empty($result['errors'])) {
                $msg .= ' | أخطاء: ' . count($result['errors']);
            }
            flash_success($msg);
        } catch (Exception $e) {
            flash_error('فشل المزامنة الكاملة: ' . $e->getMessage());
        }
        $this->redirect('/online-orders');
    }

    // ============================================================
    //  معالجة الطابور المحلي
    // ============================================================
    public function processQueue(): void
    {
        validate_csrf_or_abort();
        try {
            $result = SupabaseSyncService::processQueue();
            flash_success("تمت معالجة الطابور: {$result['processed']} نجح، {$result['failed']} فشل من أصل {$result['total']}.");
        } catch (Exception $e) {
            flash_error('فشل معالجة الطابور: ' . $e->getMessage());
        }
        $this->redirect('/online-orders');
    }

    // ============================================================
    //  API: الطلبات المعلقة JSON (للـ SPA polling)
    // ============================================================
    public function pendingOrders(): void
    {
        $cacheFile = sys_get_temp_dir() . '/pos_pending_orders.json';
        $cacheTtl  = 15; // ثانية — أقل من فترة الـ polling (20 ث)

        // إرجاع من الـ cache لو صالح
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached !== null) {
                $this->json($cached);
                return;
            }
        }

        $online = false;
        $orders = [];
        try {
            $orders = SupabaseSyncService::pullOrders();
            $online = true;
        } catch (\Throwable $e) {
            $orders = [];
            $online = false;
        }

        $result = ['orders' => $orders, 'count' => count($orders), 'online' => $online];
        file_put_contents($cacheFile, json_encode($result));
        $this->json($result);
    }

    // ============================================================
    //  API: حالة الاتصال (JSON)
    // ============================================================
    public function syncStatus(): void
    {
        $db         = Database::pdo();
        $queueCount = (int) $db->query('SELECT COUNT(*) FROM supabase_sync_queue')->fetchColumn();

        // ━━━ Cache لمدة 30 ثانية لتجنب blocking السيرفر ━━━
        $cacheFile = sys_get_temp_dir() . '/pos_sync_status.json';
        $cacheTtl  = 30; // ثانية

        $cached = null;
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
            $cached = json_decode(file_get_contents($cacheFile), true);
        }

        if ($cached !== null) {
            // أرجع من الـ cache فوراً بدون أي request لـ Supabase
            $this->json([
                'online'        => $cached['online'] ?? false,
                'queue_count'   => $queueCount,
                'pending_count' => $cached['pending_count'] ?? 0,
                'timestamp'     => date('H:i:s'),
                'cached'        => true,
            ]);
            return;
        }

        // ━━━ بعد انتهاء الـ cache: اعمل request حقيقي ━━━
        $online       = false;
        $pendingCount = 0;

        try {
            $online = SupabaseSyncService::isOnline();
        } catch (\Throwable $e) {
            $online = false;
        }

        if ($online) {
            // معالجة الطابور إذا فيه عناصر
            if ($queueCount > 0) {
                try {
                    SupabaseSyncService::processQueue();
                    $queueCount = (int) $db->query('SELECT COUNT(*) FROM supabase_sync_queue')->fetchColumn();
                } catch (\Throwable $e) { /* تجاهل */ }
            }

            // جلب الطلبات المعلقة
            try {
                $orders       = SupabaseSyncService::pullOrders();
                $pendingCount = count($orders);
            } catch (\Throwable $e) {
                $pendingCount = 0;
            }
        }

        // حفظ في الـ cache
        file_put_contents($cacheFile, json_encode([
            'online'        => $online,
            'pending_count' => $pendingCount,
        ]));

        $this->json([
            'online'        => $online,
            'queue_count'   => $queueCount,
            'pending_count' => $pendingCount,
            'timestamp'     => date('H:i:s'),
            'cached'        => false,
        ]);
    }

    // ============================================================
    //  Webhook: استقبال إشعار طلب ويب جديد من سوبابيس (بدون Auth)
    // ============================================================
    public function handleNewOrderWebhook(): void
    {
        // 1. تحقق من التوكن الأمني
        $receivedToken = $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? '';
        $expectedToken = config('app')['webhook_token'] ?? '';

        if (empty($receivedToken) || $receivedToken !== $expectedToken) {
            http_response_code(401);
            $this->json(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        // 2. إتلاف ملفات الكاش الخاصة بالطلبات المعلقة لإجبار الواجهة على جلب الجديد فوراً
        $cacheFilePending = sys_get_temp_dir() . '/pos_pending_orders.json';
        $cacheFileStatus = sys_get_temp_dir() . '/pos_sync_status.json';

        if (file_exists($cacheFilePending)) {
            @unlink($cacheFilePending);
        }
        if (file_exists($cacheFileStatus)) {
            @unlink($cacheFileStatus);
        }

        // 3. كتابة ملف الإشارة لإعلام تطبيق الديسك توب فورياً بورود طلب جديد
        $flagFile = base_path('storage/logs/new_order.flag');
        @file_put_contents($flagFile, (string) time());

        $this->json([
            'success' => true,
            'message' => 'New order webhook received. Cache invalidated.'
        ]);
    }

}
