-- SQL Migration: Add Category Groups (مجموعات التصنيفات)
-- Execute this script in Supabase's SQL Editor

-- 1. Create Category Groups Table
CREATE TABLE IF NOT EXISTS public.category_groups (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

-- 2. Add group_id to Categories Table (to link categories to their groups)
ALTER TABLE public.categories 
ADD COLUMN IF NOT EXISTS group_id UUID REFERENCES public.category_groups(id) ON DELETE SET NULL;

-- 3. Enable Row Level Security (RLS) on Category Groups
ALTER TABLE public.category_groups ENABLE ROW LEVEL SECURITY;

-- 4. Create RLS Policies for Category Groups
CREATE POLICY "Category groups are readable by everyone" ON public.category_groups
    FOR SELECT USING (true);

CREATE POLICY "Admins have full access on category groups" ON public.category_groups
    FOR ALL USING (public.is_admin(auth.uid()));

-- 5. Update Realtime publications if needed (optional)
DO $$
BEGIN
  ALTER PUBLICATION supabase_realtime ADD TABLE public.category_groups;
EXCEPTION
  WHEN others THEN
    -- Table already added or publication handled
    NULL;
END;
$$;
