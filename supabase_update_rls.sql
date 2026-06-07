-- 1. DROP EXISTING POLICIES TO PREVENT DUPLICATES
DROP POLICY IF EXISTS "Public profiles are readable by everyone" ON public.profiles;
DROP POLICY IF EXISTS "Users can update their own profile" ON public.profiles;
DROP POLICY IF EXISTS "Admins have full access on profiles" ON public.profiles;
DROP POLICY IF EXISTS "Admins have full access on categories" ON public.categories;
DROP POLICY IF EXISTS "Admins have full access on products" ON public.products;
DROP POLICY IF EXISTS "Admins have full access on orders" ON public.orders;

-- 2. RE-CREATE BASIC USER POLICIES
CREATE POLICY "Public profiles are readable by everyone" ON public.profiles
    FOR SELECT USING (true);

CREATE POLICY "Users can update their own profile" ON public.profiles
    FOR UPDATE USING (auth.uid() = id);

CREATE POLICY "Users can insert their own profile" ON public.profiles
    FOR INSERT WITH CHECK (auth.uid() = id);

-- 3. CREATE NON-RECURSIVE SECURITY DEFINER ADMIN CHECK FUNCTION
CREATE OR REPLACE FUNCTION public.is_admin(user_id UUID)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM public.profiles
        WHERE id = user_id AND role = 'admin'
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 4. APPLY ADMIN RULES FOR ALL TABLES
CREATE POLICY "Admins have full access on profiles" ON public.profiles
    FOR ALL USING (public.is_admin(auth.uid()));

CREATE POLICY "Admins have full access on categories" ON public.categories
    FOR ALL USING (public.is_admin(auth.uid()));

CREATE POLICY "Admins have full access on products" ON public.products
    FOR ALL USING (public.is_admin(auth.uid()));

CREATE POLICY "Admins have full access on orders" ON public.orders
    FOR ALL USING (public.is_admin(auth.uid()));
