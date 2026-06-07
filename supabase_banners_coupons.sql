-- SQL Migration to add Banners and Coupons
-- Run this in your Supabase SQL Editor (https://supabase.com/dashboard)

-- 1. CREATE TABLES

-- BANNERS
CREATE TABLE IF NOT EXISTS public.banners (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title TEXT NOT NULL,
    image_url TEXT NOT NULL,
    link_url TEXT DEFAULT '/' NOT NULL,
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

-- COUPONS
CREATE TABLE IF NOT EXISTS public.coupons (
    code TEXT PRIMARY KEY,
    description TEXT NOT NULL,
    discount_type TEXT NOT NULL CHECK (discount_type IN ('percentage', 'fixed', 'points')),
    discount_value NUMERIC(10, 2) NOT NULL CHECK (discount_value >= 0),
    min_order_amount NUMERIC(10, 2) DEFAULT 0 NOT NULL CHECK (min_order_amount >= 0),
    points_cost INT DEFAULT 0 NOT NULL CHECK (points_cost >= 0),
    is_active BOOLEAN DEFAULT true NOT NULL,
    usage_count INT DEFAULT 0 NOT NULL CHECK (usage_count >= 0),
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

-- Run this to update existing coupons table:
-- ALTER TABLE public.coupons ADD COLUMN IF NOT EXISTS usage_count INT DEFAULT 0 NOT NULL CHECK (usage_count >= 0);


-- 2. ENABLE ROW LEVEL SECURITY (RLS)
ALTER TABLE public.banners ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.coupons ENABLE ROW LEVEL SECURITY;

-- 3. DROP PREVIOUS POLICIES IF EXIST TO PREVENT DUPLICATES
DROP POLICY IF EXISTS "Banners are readable by everyone" ON public.banners;
DROP POLICY IF EXISTS "Admins have full access on banners" ON public.banners;
DROP POLICY IF EXISTS "Coupons are readable by everyone" ON public.coupons;
DROP POLICY IF EXISTS "Admins have full access on coupons" ON public.coupons;

-- 4. CREATE POLICIES

-- Banners policies
CREATE POLICY "Banners are readable by everyone" ON public.banners
    FOR SELECT USING (true);

CREATE POLICY "Admins have full access on banners" ON public.banners
    FOR ALL USING (public.is_admin(auth.uid()));

-- Coupons policies
CREATE POLICY "Coupons are readable by everyone" ON public.coupons
    FOR SELECT USING (true);

CREATE POLICY "Admins have full access on coupons" ON public.coupons
    FOR ALL USING (public.is_admin(auth.uid()));

-- 5. INSERT INITIAL SEED DATA

-- Insert default banners
INSERT INTO public.banners (title, image_url, link_url) VALUES
('خصومات تصل إلى 30% على منتجات الألبان والأرز', 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80', '/products?search=أرز'),
('شحن مجاني للعامرية والناصرية للطلبات فوق 800 جنيه', 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=1200&auto=format&fit=crop&q=80', '/'),
('وفر أكتر مع أسعار الجملة لكرتونة المجمدات والمنظفات', 'https://images.unsplash.com/photo-1578916171728-46686eac8d58?w=1200&auto=format&fit=crop&q=80', '/products?search=جملة')
ON CONFLICT DO NOTHING;

-- Insert default coupons
INSERT INTO public.coupons (code, description, discount_type, discount_value, min_order_amount, points_cost, is_active) VALUES
('ARZ15', 'خصم 15% على إجمالي السلة', 'percentage', 15, 0, 0, true),
('GHARIB50', 'خصم بقيمة 50 جنيه للطلبات بقيمة 300 جنيه أو أكثر', 'fixed', 50, 300, 0, true),
('POINTS100', 'خصم بقيمة 100 جنيه مقابل 100 نقطة ذهبية من رصيدك', 'points', 100, 0, 100, true)
ON CONFLICT (code) DO UPDATE SET 
    description = EXCLUDED.description,
    discount_type = EXCLUDED.discount_type,
    discount_value = EXCLUDED.discount_value,
    min_order_amount = EXCLUDED.min_order_amount,
    points_cost = EXCLUDED.points_cost,
    is_active = EXCLUDED.is_active;

-- 6. PRODUCT REVIEWS TABLE AND POLICIES
CREATE TABLE IF NOT EXISTS public.product_reviews (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id UUID REFERENCES public.products(id) ON DELETE CASCADE,
    user_name TEXT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

ALTER TABLE public.product_reviews ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Reviews are readable by everyone" ON public.product_reviews;
DROP POLICY IF EXISTS "Anyone can insert reviews" ON public.product_reviews;

CREATE POLICY "Reviews are readable by everyone" ON public.product_reviews
    FOR SELECT USING (true);

CREATE POLICY "Anyone can insert reviews" ON public.product_reviews
    FOR INSERT WITH CHECK (true);

