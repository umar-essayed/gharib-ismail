-- ============================================================
-- Supabase Migration: POS Integration
-- تشغيل هذا الكود في Supabase SQL Editor
-- ============================================================

-- 1. إضافة حقل pos_category_id لربط الأقسام بالكاشير
ALTER TABLE public.categories
  ADD COLUMN IF NOT EXISTS pos_category_id INTEGER UNIQUE;

-- 2. إضافة حقل pos_product_id لربط المنتجات بالكاشير
ALTER TABLE public.products
  ADD COLUMN IF NOT EXISTS pos_product_id INTEGER UNIQUE;

-- 3. إضافة حقول تتبع الطلبات مع الكاشير في جدول الطلبات
ALTER TABLE public.orders
  ADD COLUMN IF NOT EXISTS pos_sync_status TEXT DEFAULT 'pending'
    CHECK (pos_sync_status IN ('pending', 'synced'));

ALTER TABLE public.orders
  ADD COLUMN IF NOT EXISTS cashier_invoice_no TEXT;

-- 4. فهارس للأداء
CREATE INDEX IF NOT EXISTS idx_orders_pos_sync ON public.orders (pos_sync_status);
CREATE INDEX IF NOT EXISTS idx_orders_status ON public.orders (status);
CREATE INDEX IF NOT EXISTS idx_products_pos_id ON public.products (pos_product_id);
CREATE INDEX IF NOT EXISTS idx_categories_pos_id ON public.categories (pos_category_id);

-- 5. تحديث سياسة RLS للسماح لخادم الكاشير بالقراءة والكتابة
-- (Service Role Key يتخطى RLS تلقائياً — لا حاجة لسياسة إضافية)

-- 6. إضافة حالة cancelled للطلبات (اختياري للمستقبل)
-- ALTER TABLE public.orders DROP CONSTRAINT IF EXISTS orders_status_check;
-- ALTER TABLE public.orders ADD CONSTRAINT orders_status_check
--   CHECK (status IN ('pending', 'preparing', 'delivering', 'completed', 'cancelled'));

-- 7. تحقق من النتيجة
SELECT 
  column_name,
  data_type,
  is_nullable
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN ('orders', 'products', 'categories')
  AND column_name IN ('pos_product_id', 'pos_category_id', 'pos_sync_status', 'cashier_invoice_no')
ORDER BY table_name, column_name;

-- ============================================================
-- 8. جدول إعدادات الكاشير ونفق كلاود فلير (جديد)
-- ============================================================
CREATE TABLE IF NOT EXISTS public.pos_settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

-- تفعيل الحماية والوصول
ALTER TABLE public.pos_settings ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow public read access on pos_settings" ON public.pos_settings;
CREATE POLICY "Allow public read access on pos_settings" ON public.pos_settings
    FOR SELECT USING (true);

DROP POLICY IF EXISTS "Allow service_role write access on pos_settings" ON public.pos_settings;
CREATE POLICY "Allow service_role write access on pos_settings" ON public.pos_settings
    FOR ALL USING (true);

