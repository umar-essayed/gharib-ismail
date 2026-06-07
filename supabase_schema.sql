-- Supabase Migration Script: Gharib & Ismail Trading Market (ماركت غريب وإسماعيل التجارية)
-- Enables database schema, triggers, RLS, and Realtime publications.

-- 1. EXTENSIONS
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 2. TABLES

-- PROFILES (Linked to Supabase Auth Users)
CREATE TABLE IF NOT EXISTS public.profiles (
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    full_name TEXT NOT NULL,
    phone TEXT,
    address TEXT,
    points INT DEFAULT 0 CHECK (points >= 0),
    role TEXT DEFAULT 'customer' CHECK(role IN ('admin', 'customer')),
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

-- CATEGORIES
CREATE TABLE IF NOT EXISTS public.categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    image_url TEXT,
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

-- PRODUCTS
CREATE TABLE IF NOT EXISTS public.products (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    category_id UUID REFERENCES public.categories(id) ON DELETE SET NULL,
    name TEXT NOT NULL,
    description TEXT,
    price NUMERIC(10, 2) NOT NULL CHECK (price >= 0),
    sale_price NUMERIC(10, 2) CHECK (sale_price >= 0), -- Discounted retail price (optional)
    wholesale_price NUMERIC(10, 2) NOT NULL CHECK (wholesale_price >= 0), -- For bulk tiers
    wholesale_min_qty INT NOT NULL DEFAULT 12 CHECK (wholesale_min_qty > 0),
    stock INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
    image_url TEXT,
    is_available BOOLEAN DEFAULT true NOT NULL,
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

-- ORDERS
CREATE TABLE IF NOT EXISTS public.orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    items JSONB NOT NULL, -- Array of items: [{ product_id: '...', name: '...', qty: X, price: Y }]
    total_price NUMERIC(10, 2) NOT NULL CHECK (total_price >= 0),
    status TEXT DEFAULT 'pending' NOT NULL CHECK(status IN ('pending', 'preparing', 'delivering', 'completed')),
    delivery_address TEXT NOT NULL,
    delivery_phone TEXT NOT NULL,
    payment_method TEXT DEFAULT 'COD' NOT NULL,
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

-- 3. ROW LEVEL SECURITY (RLS) POLICIES

ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.products ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.orders ENABLE ROW LEVEL SECURITY;

-- Profiles Policies
CREATE POLICY "Public profiles are readable by everyone" ON public.profiles
    FOR SELECT USING (true);

CREATE POLICY "Users can update their own profile" ON public.profiles
    FOR UPDATE USING (auth.uid() = id);

-- Helper function to check admin status without RLS recursion (SECURITY DEFINER runs with elevated privileges)
CREATE OR REPLACE FUNCTION public.is_admin(user_id UUID)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM public.profiles
        WHERE id = user_id AND role = 'admin'
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

CREATE POLICY "Admins have full access on profiles" ON public.profiles
    FOR ALL USING (public.is_admin(auth.uid()));

-- Categories Policies
CREATE POLICY "Categories are readable by everyone" ON public.categories
    FOR SELECT USING (true);

CREATE POLICY "Admins have full access on categories" ON public.categories
    FOR ALL USING (public.is_admin(auth.uid()));

-- Products Policies
CREATE POLICY "Products are readable by everyone" ON public.products
    FOR SELECT USING (true);

CREATE POLICY "Admins have full access on products" ON public.products
    FOR ALL USING (public.is_admin(auth.uid()));

-- Orders Policies
CREATE POLICY "Users can insert their own orders" ON public.orders
    FOR INSERT WITH CHECK (auth.uid() = user_id OR user_id IS NULL);

CREATE POLICY "Users can view their own orders" ON public.orders
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access on orders" ON public.orders
    FOR ALL USING (public.is_admin(auth.uid()));

-- 4. AUTOMATIC PROFILE CREATION TRIGGER (auth.signUp())

CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO public.profiles (id, full_name, phone, address, points, role)
    VALUES (
        new.id,
        COALESCE(new.raw_user_meta_data->>'full_name', 'عميل جديد'),
        COALESCE(new.raw_user_meta_data->>'phone', ''),
        COALESCE(new.raw_user_meta_data->>'address', ''),
        0,
        'customer'
    );
    RETURN new;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Trigger execution
CREATE OR REPLACE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

-- 5. REAL-TIME REPLICATION SETUP
DO $$
BEGIN
  -- Attempt to add tables to the publication
  ALTER PUBLICATION supabase_realtime ADD TABLE public.orders;
  ALTER PUBLICATION supabase_realtime ADD TABLE public.products;
  ALTER PUBLICATION supabase_realtime ADD TABLE public.profiles;
EXCEPTION
  WHEN others THEN
    -- If publication doesn't exist, create it
    CREATE PUBLICATION supabase_realtime FOR TABLE public.orders, public.products, public.profiles;
END;
$$;
