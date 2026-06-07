import { supabase } from '@/lib/supabase';

/**
 * Automatically confirms an unconfirmed phone-based user account by email-confirming them
 * using Supabase admin privileges and verifying their profiles row is created.
 */
export const confirmUserByPhone = async (phone: string): Promise<boolean> => {
  try {
    const cleanedPhone = phone.trim();
    const virtualEmail = `${cleanedPhone}@gmail.com`;
    
    // 1. Try to find the user ID in the public profiles table
    const { data: prof } = await supabase
      .from('profiles')
      .select('id')
      .eq('phone', cleanedPhone)
      .maybeSingle();

    let userId = prof?.id;

    // 2. If not found (e.g. database trigger didn't run), search auth users directly via listUsers
    if (!userId) {
      const { data: adminData } = await supabase.auth.admin.listUsers({
        perPage: 100
      });
      const targetUser = adminData?.users?.find(u => u.email === virtualEmail);
      if (targetUser) {
        userId = targetUser.id;
      }
    }

    // 3. If we found the user, update confirmation and guarantee profiles record exists
    if (userId) {
      // Auto-confirm the user in Supabase auth
      await supabase.auth.admin.updateUserById(userId, {
        email_confirm: true
      });

      // Confirm the profiles row exists. If missing, insert it on the fly
      const { data: existingProfile } = await supabase
        .from('profiles')
        .select('id')
        .eq('id', userId)
        .maybeSingle();

      if (!existingProfile) {
        const { data: adminUser } = await supabase.auth.admin.getUserById(userId);
        if (adminUser?.user) {
          const userMeta = adminUser.user.user_metadata || {};
          await supabase.from('profiles').insert({
            id: userId,
            full_name: userMeta.full_name || 'عميل جديد',
            phone: cleanedPhone,
            address: userMeta.address || '',
            points: 0,
            role: 'customer'
          });
        }
      }
      return true;
    }
  } catch (err) {
    console.error('Error in confirmUserByPhone helper:', err);
  }
  return false;
};
