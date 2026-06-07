<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SupabaseSyncService;
use App\Services\SettingsService;
use App\Core\Database;

class OnlineStoreController extends Controller
{
    // ============================================================
    //  API: إحصائيات المتجر (JSON للـ Dashboard)
    // ============================================================
    public function apiStats(): void
    {
        $cacheFile = sys_get_temp_dir() . '/pos_ecom_stats.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) {
                $this->json($cached);
                return;
            }
        }

        try {
            $online = SupabaseSyncService::isOnline();
            if (!$online) {
                $this->json(['success' => false, 'error' => 'سيرفر المتجر غير متصل']);
                return;
            }

            // 1. جلب الطلبات
            $orders = SupabaseSyncService::sendRequest('/orders?select=total_price,status', 'GET') ?: [];
            $totalOrders = count($orders);
            $pending = 0;
            $preparing = 0;
            $delivering = 0;
            $completed = 0;
            $totalSales = 0;

            foreach ($orders as $o) {
                $status = $o['status'] ?? 'pending';
                if ($status === 'pending') $pending++;
                elseif ($status === 'preparing') $preparing++;
                elseif ($status === 'delivering') $delivering++;
                elseif ($status === 'completed') $completed++;
                $totalSales += (float)($o['total_price'] ?? 0);
            }

            // 2. جلب المستخدمين
            $profiles = SupabaseSyncService::sendRequest('/profiles?select=id', 'GET') ?: [];
            $customersCount = count($profiles);

            // 3. جلب الكوبونات والبنرات
            $coupons = SupabaseSyncService::sendRequest('/coupons?select=code', 'GET') ?: [];
            $couponsCount = count($coupons);
            $banners = SupabaseSyncService::sendRequest('/banners?select=id', 'GET') ?: [];
            $bannersCount = count($banners);

            $data = [
                'success' => true,
                'total_orders' => $totalOrders,
                'pending_orders' => $pending,
                'preparing_orders' => $preparing,
                'delivering_orders' => $delivering,
                'completed_orders' => $completed,
                'total_sales' => $totalSales,
                'customers_count' => $customersCount,
                'coupons_count' => $couponsCount,
                'banners_count' => $bannersCount,
            ];

            file_put_contents($cacheFile, json_encode($data));
            $this->json($data);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ============================================================
    //  API: تحليلات مفصلة للمتجر (JSON)
    // ============================================================
    public function apiAnalytics(): void
    {
        try {
            $online = SupabaseSyncService::isOnline();
            if (!$online) {
                $this->json(['success' => false, 'error' => 'غير متصل']);
                return;
            }

            // 1. كل الطلبات كاملة
            $orders = SupabaseSyncService::sendRequest(
                '/orders?select=id,total_price,status,payment_method,created_at,items&order=created_at.asc',
                'GET'
            ) ?: [];

            // 2. التوزيع حسب الحالة
            $byStatus = ['pending' => 0, 'preparing' => 0, 'delivering' => 0, 'completed' => 0, 'cancelled' => 0];
            // 3. التوزيع حسب طريقة الدفع
            $byPayment = [];
            // 4. الطلبات حسب اليوم (آخر 30 يوم)
            $byDay = [];
            // 5. إحصائيات المنتجات
            $productStats = [];
            // 6. إجمالي المبيعات
            $totalSales = 0;
            $completedSales = 0;

            foreach ($orders as $o) {
                $status = $o['status'] ?? 'pending';
                if (isset($byStatus[$status])) $byStatus[$status]++;
                else $byStatus[$status] = 1;

                $pm = $o['payment_method'] ?? 'COD';
                $byPayment[$pm] = ($byPayment[$pm] ?? 0) + 1;

                $day = substr($o['created_at'] ?? '', 0, 10);
                if ($day) {
                    $byDay[$day] = ($byDay[$day] ?? 0) + (float)($o['total_price'] ?? 0);
                }

                $totalSales += (float)($o['total_price'] ?? 0);
                if ($status === 'completed') {
                    $completedSales += (float)($o['total_price'] ?? 0);
                }

                // تحليل المنتجات من items JSON
                $items = $o['items'] ?? [];
                if (is_string($items)) {
                    $items = json_decode($items, true) ?: [];
                }
                foreach ($items as $item) {
                    $name = $item['name'] ?? 'غير معروف';
                    if (!isset($productStats[$name])) {
                        $productStats[$name] = ['qty' => 0, 'revenue' => 0];
                    }
                    $qty = (float)($item['qty'] ?? $item['quantity'] ?? 1);
                    $price = (float)($item['price'] ?? 0);
                    $productStats[$name]['qty'] += $qty;
                    $productStats[$name]['revenue'] += $qty * $price;
                }
            }

            // ترتيب المنتجات الأكثر مبيعاً
            uasort($productStats, fn($a, $b) => $b['qty'] - $a['qty']);
            $topProducts = array_slice($productStats, 0, 10, true);

            // آخر 30 يوم فقط
            ksort($byDay);
            $last30 = array_slice($byDay, -30, 30, true);

            // 3. العملاء
            $profiles = SupabaseSyncService::sendRequest(
                '/profiles?select=id,full_name,phone,points,created_at&order=created_at.desc',
                'GET'
            ) ?: [];

            // 4. العملاء الجدد آخر 7 أيام
            $newCustomers = 0;
            $weekAgo = date('Y-m-d', strtotime('-7 days'));
            foreach ($profiles as $p) {
                if (substr($p['created_at'] ?? '', 0, 10) >= $weekAgo) {
                    $newCustomers++;
                }
            }

            $this->json([
                'success' => true,
                'total_orders' => count($orders),
                'total_sales' => $totalSales,
                'completed_sales' => $completedSales,
                'by_status' => $byStatus,
                'by_payment' => $byPayment,
                'sales_by_day' => $last30,
                'top_products' => $topProducts,
                'total_customers' => count($profiles),
                'new_customers_7d' => $newCustomers,
                'top_customers' => array_slice($profiles, 0, 5),
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ============================================================
    //  صفحة التحليلات الكاملة
    // ============================================================
    public function analytics(): void
    {
        $this->view('online-store/analytics');
    }

    // ============================================================
    //  إدارة مستخدمي المتجر السحابي
    // ============================================================
    public function storeUsers(): void
    {
        $users = [];
        $online = false;
        try {
            $profiles = SupabaseSyncService::sendRequest('/profiles?order=created_at.desc', 'GET') ?: [];
            $authData = SupabaseSyncService::sendRequest('/admin/users', 'GET', [], [], true) ?: [];
            
            $authUsers = [];
            if (isset($authData['users'])) {
                foreach ($authData['users'] as $au) {
                    $authUsers[$au['id']] = [
                        'email' => $au['email'] ?? null,
                        'provider' => $au['app_metadata']['provider'] ?? 'email',
                        'providers' => $au['app_metadata']['providers'] ?? [],
                    ];
                }
            }

            foreach ($profiles as $p) {
                $uid = $p['id'];
                $p['email'] = $authUsers[$uid]['email'] ?? null;
                $p['provider'] = $authUsers[$uid]['provider'] ?? 'email';
                $p['providers'] = $authUsers[$uid]['providers'] ?? [];
                $users[] = $p;
            }
            $online = true;
        } catch (\Throwable $e) {
            try {
                $users = SupabaseSyncService::sendRequest('/profiles?order=created_at.desc', 'GET') ?: [];
                $online = true;
            } catch (\Throwable $e2) {
                $users = [];
                $online = false;
            }
        }

        $this->view('online-store/users', compact('users', 'online'));
    }

    public function updateUserPoints(): void
    {
        validate_csrf_or_abort();
        $userId = trim((string)input('user_id'));
        $points = (int)input('points', 0);

        if ($userId === '') {
            flash_error('معرف المستخدم غير صحيح');
            $this->redirect('/online-store/users');
        }

        try {
            SupabaseSyncService::sendRequest("/profiles?id=eq.{$userId}", 'PATCH', ['points' => $points]);
            flash_success('تم تحديث نقاط العميل الذهبية بنجاح');
        } catch (\Throwable $e) {
            flash_error('حدث خطأ أثناء تحديث النقاط: ' . $e->getMessage());
        }

        $this->redirect('/online-store/users');
    }

    public function deleteStoreUser(): void
    {
        validate_csrf_or_abort();
        $userId = trim((string)input('user_id'));

        if ($userId === '') {
            flash_error('معرف المستخدم غير صحيح');
            $this->redirect('/online-store/users');
        }

        try {
            SupabaseSyncService::sendRequest("/profiles?id=eq.{$userId}", 'DELETE');
            flash_success('تم حذف حساب العميل من المتجر بنجاح');
        } catch (\Throwable $e) {
            flash_error('حدث خطأ أثناء حذف العميل: ' . $e->getMessage());
        }

        $this->redirect('/online-store/users');
    }

    // ============================================================
    //  إدارة العروض والفاوشرز والخصومات
    // ============================================================
    public function storePromotions(): void
    {
        try {
            $coupons = SupabaseSyncService::sendRequest('/coupons?order=created_at.desc', 'GET') ?: [];
            $banners = SupabaseSyncService::sendRequest('/banners?order=created_at.desc', 'GET') ?: [];
            $online = true;
        } catch (\Throwable $e) {
            $coupons = [];
            $banners = [];
            $online = false;
        }

        $this->view('online-store/promotions', compact('coupons', 'banners', 'online'));
    }

    public function createBanner(): void
    {
        validate_csrf_or_abort();
        $title = trim((string)input('title'));
        $linkUrl = trim((string)input('link_url', '/'));
        $imageUrl = trim((string)input('image_url'));

        if ($title === '') {
            flash_error('عنوان العرض مطلوب');
            $this->redirect('/online-store/promotions');
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
                $name = 'banner_' . time() . '.' . $ext;
                $target = base_path('public/uploads/' . $name);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $appConfig = config('app');
                    $imageUrl = rtrim($appConfig['base_url'] ?? '', '/') . '/uploads/' . $name;
                }
            }
        }

        if ($imageUrl === '') {
            flash_error('يجب توفير رابط الصورة أو رفع ملف صورة');
            $this->redirect('/online-store/promotions');
        }

        try {
            SupabaseSyncService::sendRequest('/banners', 'POST', [
                'title' => $title,
                'image_url' => $imageUrl,
                'link_url' => $linkUrl
            ]);
            flash_success('تم إضافة بنر العرض بنجاح');
        } catch (\Throwable $e) {
            flash_error('فشل إضافة البنر: ' . $e->getMessage());
        }

        $this->redirect('/online-store/promotions');
    }

    public function deleteBanner(): void
    {
        validate_csrf_or_abort();
        $id = trim((string)input('id'));

        if ($id === '') {
            flash_error('معرف البنر غير صحيح');
            $this->redirect('/online-store/promotions');
        }

        try {
            SupabaseSyncService::sendRequest("/banners?id=eq.{$id}", 'DELETE');
            flash_success('تم حذف بنر العرض بنجاح');
        } catch (\Throwable $e) {
            flash_error('فشل حذف البنر: ' . $e->getMessage());
        }

        $this->redirect('/online-store/promotions');
    }

    public function createCoupon(): void
    {
        validate_csrf_or_abort();
        $code = strtoupper(trim((string)input('code')));
        $description = trim((string)input('description'));
        $discountType = trim((string)input('discount_type', 'percentage'));
        $discountValue = (float)input('discount_value', 0);
        $minOrderAmount = (float)input('min_order_amount', 0);
        $pointsCost = (int)input('points_cost', 0);
        $isActive = input('is_active') === '1';

        if ($code === '') {
            flash_error('كود الخصم مطلوب');
            $this->redirect('/online-store/promotions');
        }

        try {
            SupabaseSyncService::sendRequest('/coupons', 'POST', [
                'code' => $code,
                'description' => $description,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'min_order_amount' => $minOrderAmount,
                'points_cost' => $pointsCost,
                'is_active' => $isActive
            ]);
            flash_success("تم إنشاء كود الخصم {$code} بنجاح");
        } catch (\Throwable $e) {
            flash_error('فشل إنشاء كود الخصم: ' . $e->getMessage());
        }

        $this->redirect('/online-store/promotions');
    }

    public function toggleCoupon(): void
    {
        validate_csrf_or_abort();
        $code = trim((string)input('code'));
        $isActive = input('is_active') === '1';

        try {
            SupabaseSyncService::sendRequest("/coupons?code=eq.{$code}", 'PATCH', [
                'is_active' => $isActive
            ]);
            flash_success('تم تغيير حالة الكوبون بنجاح');
        } catch (\Throwable $e) {
            flash_error('فشل تغيير حالة الكوبون: ' . $e->getMessage());
        }

        $this->redirect('/online-store/promotions');
    }

    public function deleteCoupon(): void
    {
        validate_csrf_or_abort();
        $code = trim((string)input('code'));

        try {
            SupabaseSyncService::sendRequest("/coupons?code=eq.{$code}", 'DELETE');
            flash_success('تم حذف كود الخصم بنجاح');
        } catch (\Throwable $e) {
            flash_error('فشل حذف الكوبون: ' . $e->getMessage());
        }

        $this->redirect('/online-store/promotions');
    }

    // ============================================================
    //  إدارة مصاريف الشحن والتسليم
    // ============================================================
    public function storeShipping(): void
    {
        $shippingFee = (float)SettingsService::get('ecom_shipping_fee', '50');
        $freeShippingThreshold = (float)SettingsService::get('ecom_free_shipping_threshold', '800');

        $this->view('online-store/shipping', compact('shippingFee', 'freeShippingThreshold'));
    }

    public function updateShippingSettings(): void
    {
        validate_csrf_or_abort();
        $shippingFee = (float)input('shipping_fee', 50);
        $freeShippingThreshold = (float)input('free_shipping_threshold', 800);

        try {
            SettingsService::set('ecom_shipping_fee', (string)$shippingFee);
            SettingsService::set('ecom_free_shipping_threshold', (string)$freeShippingThreshold);

            $configPath = base_path('../public/ecom_config.json');
            $configData = [
                'shipping_fee' => $shippingFee,
                'free_shipping_threshold' => $freeShippingThreshold,
                'last_updated' => date('Y-m-d H:i:s')
            ];
            file_put_contents($configPath, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            flash_success('تم تحديث إعدادات شحن المتجر بنجاح ومزامنتها مع واجهة العملاء');
        } catch (\Throwable $e) {
            flash_error('فشل تحديث إعدادات الشحن: ' . $e->getMessage());
        }

        $this->redirect('/online-store/shipping');
    }
}
