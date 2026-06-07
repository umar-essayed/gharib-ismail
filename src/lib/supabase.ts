import { createClient } from '@supabase/supabase-js';

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL || 'https://example-project.supabase.co';

const rawKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY || 
  process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY || 
  'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImV4YW1wbGUtcHJvamVjdCIsInJvbGUiOiJhbm9uIiwiaWF0IjoxNzE2ODQwMDAwLCJleHAiOjIwMzI0MTYwMDB9.placeholder';

// Clean the key: if it has spaces, take the JWT token portion (usually the last element)
const supabaseAnonKey = rawKey.includes(' ')
  ? rawKey.split(/\s+/).filter(Boolean).pop() || rawKey
  : rawKey;

export const supabase = createClient(supabaseUrl, supabaseAnonKey, {
  auth: {
    persistSession: true,
    autoRefreshToken: true,
    detectSessionInUrl: true
  }
});

