import { createClient } from '@supabase/supabase-js';

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL || '';
const rawKey = process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY || '';
const supabaseKey = rawKey.includes(' ')
  ? rawKey.split(/\s+/).filter(Boolean).pop() || rawKey
  : rawKey;

// Server-side client using the service_role key to bypass RLS safely in serverless environments
export const supabaseServer = createClient(supabaseUrl, supabaseKey, {
  auth: {
    persistSession: false // No storage needed in serverless environments
  }
});

/**
 * Verifies the user access token passed in the Authorization header.
 * Returns the Auth User object if valid, otherwise null.
 */
export const verifyUser = async (req: Request) => {
  try {
    const authHeader = req.headers.get('Authorization');
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return null;
    }
    const token = authHeader.split(' ')[1];
    const { data: { user }, error } = await supabaseServer.auth.getUser(token);
    if (error || !user) {
      return null;
    }
    return user;
  } catch (e) {
    console.error('Error verifying user token:', e);
    return null;
  }
};

/**
 * Verifies the user token and asserts that the user is an admin.
 * Returns the Auth User object if admin, otherwise null.
 */
export const verifyAdmin = async (req: Request) => {
  try {
    const user = await verifyUser(req);
    if (!user) return null;

    const { data: profile } = await supabaseServer
      .from('profiles')
      .select('role')
      .eq('id', user.id)
      .maybeSingle();

    if (profile?.role === 'admin') {
      return user;
    }
    return null;
  } catch (e) {
    console.error('Error verifying admin role:', e);
    return null;
  }
};
