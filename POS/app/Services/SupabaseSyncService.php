<?php

namespace App\Services;

use App\Core\Database;
use Exception;

/**
 * SupabaseSyncService
 * محرك المزامنة بين الكاشير المحلي وقاعدة بيانات المتجر السحابي (Supabase)
 */
class SupabaseSyncService
{
    // ============================================================
    //  إعدادات الاتصال
    // ============================================================
    private static string $supabaseUrl  = 'https://wihdriufidvpdbtiqzxx.supabase.co/rest/v1';
    private static string $supabaseAuth = 'https://wihdriufidvpdbtiqzxx.supabase.co/auth/v1';
    // Service-Role JWT — يتخطى كل سياسات RLS
    private static string $serviceKey   =
        'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.' .
        'eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6IndpaGRyaXVmaWR2cGRidGlxenh4Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4MDYwMTQwMywiZXhwIjoyMDk2MTc3NDAzfQ.' .
        'vBHR-QRGWDYvSITLEaVCDrdMPv3RF9ikSygRVDT7hZI';

    // ============================================================
    //  دالة HTTP الأساسية
    // ============================================================
    public static function sendRequest(
        string $endpoint,
        string $method   = 'GET',
        array  $data     = [],
        array  $extra    = [],
        bool   $isAuth   = false
    ): ?array {
        $base = $isAuth ? self::$supabaseAuth : self::$supabaseUrl;
        $url  = $base . $endpoint;

        $ch = curl_init($url);
        $headers = array_merge([
            'apikey: '       . self::$serviceKey,
            'Authorization: Bearer ' . self::$serviceKey,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ], $extra);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => 3,   // فشل الاتصال بعد 3 ثوانٍ
            CURLOPT_TIMEOUT        => 5,   // فشل الطلب كاملاً بعد 5 ثوانٍ
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method !== 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        if ($code >= 400) {
            throw new Exception("Supabase HTTP {$code}: " . $response);
        }

        return $response ? json_decode($response, true) : [];
    }

    // ============================================================
    //  مزامنة التصنيفات (Categories)
    // ============================================================
    public static function syncCategory(int $posId, string $name, string $description = '', int $isActive = 1): void
    {
        try {
            $db   = Database::pdo();
            $slug = self::slugify($name);

            $payload = [
                'pos_category_id' => $posId,
                'name'            => $name,
                'slug'            => $slug,
            ];

            // هل عندنا UUID محفوظ مسبقاً؟
            $stmt = $db->prepare('SELECT supabase_id FROM product_categories WHERE id = :id');
            $stmt->execute(['id' => $posId]);
            $sid  = $stmt->fetchColumn();

            if ($sid) {
                self::sendRequest("/categories?id=eq.{$sid}", 'PATCH', $payload);
            } else {
                $res = self::sendRequest('/categories', 'POST', $payload);
                if (!empty($res[0]['id'])) {
                    $upd = $db->prepare('UPDATE product_categories SET supabase_id = :sid WHERE id = :id');
                    $upd->execute(['sid' => $res[0]['id'], 'id' => $posId]);
                }
            }
        } catch (\Throwable $e) {
            self::queue('category', $posId, 'upsert',
                compact('posId', 'name', 'description', 'isActive'), $e->getMessage());
        }
    }

    public static function deleteCategory(int $posId): void
    {
        try {
            $db   = Database::pdo();
            $stmt = $db->prepare('SELECT supabase_id FROM product_categories WHERE id = :id');
            $stmt->execute(['id' => $posId]);
            $sid  = $stmt->fetchColumn();
            if ($sid) {
                self::sendRequest("/categories?id=eq.{$sid}", 'DELETE');
            }
        } catch (\Throwable $e) {
            self::queue('category', $posId, 'delete', [], $e->getMessage());
        }
    }

    // ============================================================
    //  مزامنة المنتجات (Products)
    // ============================================================
    public static function syncProduct(array $product): void
    {
        try {
            $db = Database::pdo();

            // جلب UUID القسم
            $catSid = null;
            if (!empty($product['category_id'])) {
                $s = $db->prepare('SELECT supabase_id FROM product_categories WHERE id = :id');
                $s->execute(['id' => $product['category_id']]);
                $catSid = $s->fetchColumn() ?: null;
            }

            // جلب آخر رصيد من المخزون
            $stockStmt = $db->prepare(
                'SELECT balance_after FROM stock_movements
                 WHERE product_id = :pid ORDER BY id DESC LIMIT 1'
            );
            $stockStmt->execute(['pid' => $product['id']]);
            $currentStock = (int) ($stockStmt->fetchColumn() ?: $product['opening_stock'] ?? 0);
            $safeStock    = (int) min(2147483647, max(0, $currentStock));

            // بناء الـ image URL إذا وُجدت صورة محلية
            $imageUrl = null;
            if (!empty($product['image_path'])) {
                $appConfig = config('app');
                $imageUrl  = rtrim($appConfig['base_url'] ?? '', '/') . '/' . ltrim($product['image_path'], '/');
            }

            $payload = [
                'pos_product_id'    => (int)  $product['id'],
                'category_id'       => $catSid,
                'name'              => $product['name'],
                'description'       => $product['description'] ?? '',
                'price'             => (float) $product['sale_price'],
                'sale_price'        => !empty($product['sale_price']) ? (float) $product['sale_price'] : null,
                'wholesale_price'   => !empty($product['wholesale_price'])
                                           ? (float) $product['wholesale_price']
                                           : (float) $product['sale_price'],
                'wholesale_min_qty' => !empty($product['package_size'])
                                           ? (int) $product['package_size']
                                           : 12,
                'stock'             => $safeStock,
                'is_available'      => (int) $product['is_active'] === 1,
                'image_url'         => $imageUrl,
            ];

            $s2  = $db->prepare('SELECT supabase_id FROM products WHERE id = :id');
            $s2->execute(['id' => $product['id']]);
            $sid = $s2->fetchColumn();

            if ($sid) {
                self::sendRequest("/products?id=eq.{$sid}", 'PATCH', $payload);
            } else {
                $res = self::sendRequest('/products', 'POST', $payload);
                if (!empty($res[0]['id'])) {
                    $upd = $db->prepare('UPDATE products SET supabase_id = :sid WHERE id = :id');
                    $upd->execute(['sid' => $res[0]['id'], 'id' => $product['id']]);
                }
            }
        } catch (\Throwable $e) {
            self::queue('product', $product['id'], 'upsert', $product, $e->getMessage());
        }
    }

    public static function deleteProduct(int $posId): void
    {
        try {
            $db   = Database::pdo();
            $stmt = $db->prepare('SELECT supabase_id FROM products WHERE id = :id');
            $stmt->execute(['id' => $posId]);
            $sid  = $stmt->fetchColumn();
            if ($sid) {
                self::sendRequest("/products?id=eq.{$sid}", 'PATCH', ['is_available' => false]);
            }
        } catch (\Throwable $e) {
            self::queue('product', $posId, 'delete', [], $e->getMessage());
        }
    }

    // ============================================================
    //  مزامنة رصيد المخزون (Stock)
    // ============================================================
    public static function syncStock(int $posProductId, float $newBalance): void
    {
        try {
            $db   = Database::pdo();
            $stmt = $db->prepare('SELECT supabase_id FROM products WHERE id = :id');
            $stmt->execute(['id' => $posProductId]);
            $sid  = $stmt->fetchColumn();
            if ($sid) {
                self::sendRequest("/products?id=eq.{$sid}", 'PATCH', [
                    'stock' => (int) min(2147483647, max(0, (int) round($newBalance))),
                ]);
            }
        } catch (\Throwable $e) {
            self::queue('stock', $posProductId, 'upsert', ['stock' => $newBalance], $e->getMessage());
        }
    }

    // ============================================================
    //  سحب الطلبات الإلكترونية الجديدة (Online Orders)
    // ============================================================
    public static function pullOrders(): array
    {
        try {
            $res = self::sendRequest(
                '/orders?status=eq.pending&pos_sync_status=eq.pending&order=created_at.asc&limit=50',
                'GET'
            );
            return is_array($res) ? $res : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getOrder(string $supabaseOrderId): ?array
    {
        try {
            $res = self::sendRequest("/orders?id=eq.{$supabaseOrderId}", 'GET');
            return !empty($res[0]) ? $res[0] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getProfile(string $userId): ?array
    {
        try {
            $res = self::sendRequest("/profiles?id=eq.{$userId}&select=id,full_name,phone", 'GET');
            return !empty($res[0]) ? $res[0] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function updateOrderStatus(string $orderId, string $status, ?string $invoiceNo = null): void
    {
        $payload = ['status' => $status];
        if ($invoiceNo) {
            $payload['cashier_invoice_no'] = $invoiceNo;
            $payload['pos_sync_status']    = 'synced';
        }
        self::sendRequest("/orders?id=eq.{$orderId}", 'PATCH', $payload);
    }

    // ============================================================
    //  مزامنة كاملة لكل المنتجات والتصنيفات (Full Sync)
    // ============================================================
    public static function fullSync(): array
    {
        $db      = Database::pdo();
        $results = ['categories' => 0, 'products' => 0, 'errors' => []];

        // 1. مزامنة تصنيفات المنتجات (Categories Bulk Upsert)
        $cats = $db->query('SELECT * FROM product_categories WHERE deleted_at IS NULL')->fetchAll();
        if (!empty($cats)) {
            $catPayloads = [];
            foreach ($cats as $cat) {
                $catPayloads[] = [
                    'pos_category_id' => (int) $cat['id'],
                    'name'            => $cat['name'],
                    'description'     => $cat['description'] ?? '',
                    'is_active'       => (int) $cat['is_active'] === 1,
                ];
            }

            try {
                // إرسال طلب UPSERT مجمع للتصنيفات
                $res = self::sendRequest(
                    '/categories?on_conflict=pos_category_id',
                    'POST',
                    $catPayloads,
                    ['Prefer: resolution=merge-duplicates']
                );

                if (is_array($res)) {
                    $results['categories'] = count($res);
                    // تحديث الـ supabase_id محلياً للتصنيفات
                    $db->beginTransaction();
                    $upd = $db->prepare('UPDATE product_categories SET supabase_id = :sid WHERE id = :id');
                    foreach ($res as $row) {
                        if (!empty($row['id']) && !empty($row['pos_category_id'])) {
                            $upd->execute([
                                'sid' => $row['id'],
                                'id'  => (int) $row['pos_category_id'],
                            ]);
                        }
                    }
                    $db->commit();
                }
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $results['errors'][] = 'Categories Bulk Sync: ' . $e->getMessage();
            }
        }

        // 2. مزامنة المنتجات (Products Bulk Upsert)
        $prods = $db->query('SELECT * FROM products WHERE deleted_at IS NULL')->fetchAll();
        if (!empty($prods)) {
            // جلب خريطة معرفات التصنيفات (Local ID -> Supabase UUID) لتجنب الاستعلامات المتكررة
            $catMap = $db->query('SELECT id, supabase_id FROM product_categories WHERE supabase_id IS NOT NULL')
                         ->fetchAll(\PDO::FETCH_KEY_PAIR);

            // جلب أحدث كميات المخزون لكافة المنتجات دفعة واحدة
            $stockMovements = $db->query(
                'SELECT sm1.product_id, sm1.balance_after 
                 FROM stock_movements sm1
                 INNER JOIN (
                     SELECT product_id, MAX(id) as max_id 
                     FROM stock_movements 
                     GROUP BY product_id
                 ) sm2 ON sm1.id = sm2.max_id'
            )->fetchAll(\PDO::FETCH_KEY_PAIR);

            $prodPayloads = [];
            $appConfig = config('app');
            $baseUrl   = rtrim($appConfig['base_url'] ?? '', '/');

            foreach ($prods as $prod) {
                $prodId = (int) $prod['id'];
                
                $catSid = null;
                if (!empty($prod['category_id'])) {
                    $catSid = $catMap[$prod['category_id']] ?? null;
                }

                $currentStock = isset($stockMovements[$prodId]) ? (int) $stockMovements[$prodId] : (int) ($prod['opening_stock'] ?? 0);
                $safeStock    = (int) min(2147483647, max(0, $currentStock));

                $imageUrl = null;
                if (!empty($prod['image_path'])) {
                    $imageUrl = $baseUrl . '/' . ltrim($prod['image_path'], '/');
                }

                $prodPayloads[] = [
                    'pos_product_id'    => $prodId,
                    'category_id'       => $catSid,
                    'name'              => $prod['name'],
                    'description'       => $prod['description'] ?? '',
                    'price'             => (float) $prod['sale_price'],
                    'sale_price'        => !empty($prod['sale_price']) ? (float) $prod['sale_price'] : null,
                    'wholesale_price'   => !empty($prod['wholesale_price'])
                                               ? (float) $prod['wholesale_price']
                                               : (float) $prod['sale_price'],
                    'wholesale_min_qty' => !empty($prod['package_size'])
                                               ? (int) $prod['package_size']
                                               : 12,
                    'stock'             => $safeStock,
                    'is_available'      => (int) $prod['is_active'] === 1,
                    'image_url'         => $imageUrl,
                ];
            }

            // تقسيم المنتجات إلى حزم (Chunks) بحجم 500 منتج لكل حزمة لتفادي حجم طلبات HTTP الكبير
            $chunks = array_chunk($prodPayloads, 500);
            foreach ($chunks as $chunk) {
                try {
                    // إرسال طلب UPSERT مجمع للمنتجات
                    $res = self::sendRequest(
                        '/products?on_conflict=pos_product_id',
                        'POST',
                        $chunk,
                        ['Prefer: resolution=merge-duplicates']
                    );

                    if (is_array($res)) {
                        $results['products'] += count($res);
                        // تحديث الـ supabase_id محلياً للمنتجات
                        $db->beginTransaction();
                        $upd = $db->prepare('UPDATE products SET supabase_id = :sid WHERE id = :id');
                        foreach ($res as $row) {
                            if (!empty($row['id']) && !empty($row['pos_product_id'])) {
                                $upd->execute([
                                    'sid' => $row['id'],
                                    'id'  => (int) $row['pos_product_id'],
                                ]);
                            }
                        }
                        $db->commit();
                    }
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    $results['errors'][] = 'Products Bulk Sync Chunk: ' . $e->getMessage();
                }
            }
        }

        return $results;
    }

    // ============================================================
    //  طابور المزامنة المحلي (Offline Queue)
    // ============================================================
    public static function queue(string $type, int $id, string $action, array $payload, string $error): void
    {
        try {
            $db   = Database::pdo();
            $stmt = $db->prepare(
                'INSERT INTO supabase_sync_queue (entity_type, entity_id, action, payload, last_error)
                 VALUES (:type, :id, :action, :payload, :err)
                 ON CONFLICT DO NOTHING'
            );
            $stmt->execute([
                'type'    => $type,
                'id'      => $id,
                'action'  => $action,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'err'     => $error,
            ]);
        } catch (\Throwable $e) {
            // لو حتى الـ queue فشل نسجل في error_log فقط
            error_log('[SupabaseSync] Queue failed: ' . $e->getMessage());
        }
    }

    public static function processQueue(): array
    {
        $db      = Database::pdo();
        $items   = $db->query(
            'SELECT * FROM supabase_sync_queue WHERE attempts < 5 ORDER BY id ASC LIMIT 30'
        )->fetchAll();

        $done   = 0;
        $failed = 0;

        foreach ($items as $item) {
            $payload = json_decode($item['payload'], true) ?: [];
            $ok      = false;
            $err     = '';

            try {
                match ($item['entity_type']) {
                    'category' => match ($item['action']) {
                        'upsert' => self::syncCategory(
                            $item['entity_id'],
                            $payload['name']        ?? '',
                            $payload['description'] ?? '',
                            $payload['isActive']    ?? 1
                        ),
                        'delete' => self::deleteCategory($item['entity_id']),
                        default  => null,
                    },
                    'product' => match ($item['action']) {
                        'upsert' => self::syncProduct($payload),
                        'delete' => self::deleteProduct($item['entity_id']),
                        default  => null,
                    },
                    'stock' => self::syncStock($item['entity_id'], (float) ($payload['stock'] ?? 0)),
                    default  => null,
                };
                $ok = true;
            } catch (\Throwable $e) {
                $err = $e->getMessage();
            }

            if ($ok) {
                $db->prepare('DELETE FROM supabase_sync_queue WHERE id = :id')->execute(['id' => $item['id']]);
                $done++;
            } else {
                $db->prepare(
                    'UPDATE supabase_sync_queue SET attempts = attempts + 1, last_error = :err WHERE id = :id'
                )->execute(['err' => $err, 'id' => $item['id']]);
                $failed++;
            }
        }

        return ['processed' => $done, 'failed' => $failed, 'total' => count($items)];
    }

    // ============================================================
    //  حالة الاتصال
    // ============================================================
    public static function isOnline(): bool
    {
        try {
            self::sendRequest('/categories?limit=1', 'GET');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ============================================================
    //  رفع النسخ الاحتياطية إلى Supabase Storage
    // ============================================================
    public static function uploadBackup(string $filePath): bool
    {
        try {
            if (!file_exists($filePath)) {
                return false;
            }

            $fileName = basename($filePath);
            $bucketName = 'backups';
            
            // استخلاص عنوان تخزين سوبابيس (Storage URL) من الرابط الأساسي للـ API
            $storageUrl = str_replace('/rest/v1', '/storage/v1', self::$supabaseUrl);
            $url = $storageUrl . "/object/{$bucketName}/{$fileName}";

            $fileData = file_get_contents($filePath);
            if ($fileData === false) {
                return false;
            }

            $ch = curl_init($url);
            $headers = [
                'apikey: ' . self::$serviceKey,
                'Authorization: Bearer ' . self::$serviceKey,
                'Content-Type: application/zip',
            ];

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $fileData,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return false;
            }

            return $code === 200;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * تحديث رابط الويب هوك في قاعدة بيانات Supabase
     */
    public static function updateWebhookUrl(string $domain): bool
    {
        try {
            if (empty($domain)) {
                return false;
            }
            $cleanDomain = preg_replace('/^(https?:\/\/)?/', 'https://', trim($domain));
            $webhookUrl = rtrim($cleanDomain, '/') . '/api/webhook/new-order';

            $payload = [
                'key'   => 'webhook_url',
                'value' => $webhookUrl
            ];

            self::sendRequest('/pos_settings?on_conflict=key', 'POST', $payload, [
                'Prefer: resolution=merge-duplicates'
            ]);

            return true;
        } catch (\Throwable $e) {
            error_log('Failed to update webhook URL in Supabase: ' . $e->getMessage());
            return false;
        }
    }

    // ============================================================
    //  مساعدات
    // ============================================================
    private static function slugify(string $text): string
    {
        // عربي → translit بسيط
        $map = ['أ'=>'a','إ'=>'i','آ'=>'a','ا'=>'a','ب'=>'b','ت'=>'t','ث'=>'th','ج'=>'j','ح'=>'h','خ'=>'kh',
                'د'=>'d','ذ'=>'dh','ر'=>'r','ز'=>'z','س'=>'s','ش'=>'sh','ص'=>'s','ض'=>'d','ط'=>'t','ظ'=>'z',
                'ع'=>'a','غ'=>'g','ف'=>'f','ق'=>'q','ك'=>'k','ل'=>'l','م'=>'m','ن'=>'n','ه'=>'h',
                'و'=>'w','ي'=>'y','ى'=>'a','ة'=>'a','ء'=>'','ئ'=>'y','ؤ'=>'w'];
        $text = str_replace(array_keys($map), array_values($map), $text);
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'cat-' . random_int(1000, 9999);
    }
}

