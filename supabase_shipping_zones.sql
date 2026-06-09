-- SQL Migration to add Shipping Zones table and policies
-- Run this in your Supabase SQL Editor (https://supabase.com/dashboard)

-- 1. CREATE TABLE
CREATE TABLE IF NOT EXISTS public.shipping_zones (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL UNIQUE,
    price NUMERIC(10, 2) NOT NULL CHECK (price >= 0),
    is_active BOOLEAN DEFAULT true NOT NULL,
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

-- 2. ENABLE ROW LEVEL SECURITY (RLS)
ALTER TABLE public.shipping_zones ENABLE ROW LEVEL SECURITY;

-- 3. DROP PREVIOUS POLICIES IF EXIST TO PREVENT DUPLICATES
DROP POLICY IF EXISTS "Shipping zones are readable by everyone" ON public.shipping_zones;
DROP POLICY IF EXISTS "Admins have full access on shipping zones" ON public.shipping_zones;

-- 4. CREATE POLICIES

-- Readable by everyone (including guests/anonymous visitors checking out)
CREATE POLICY "Shipping zones are readable by everyone" ON public.shipping_zones
    FOR SELECT USING (true);

-- Full access for admin users
CREATE POLICY "Admins have full access on shipping zones" ON public.shipping_zones
    FOR ALL USING (public.is_admin(auth.uid()));

-- 5. INSERT INITIAL SEED DATA
INSERT INTO public.shipping_zones (name, price) VALUES
('الناصرية القديمة', 20.00),
('الناصرية الجديدة', 25.00),
('العامرية أول', 35.00),
('الكنج مريوط', 50.00)
ON CONFLICT (name) DO UPDATE SET 
    price = EXCLUDED.price,
    is_active = EXCLUDED.is_active;
