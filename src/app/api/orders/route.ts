import { NextResponse } from 'next/server';
import { supabaseServer, verifyUser } from '@/lib/serverSupabase';

// GET: Retrieve logged in user's orders
export async function GET(req: Request) {
  try {
    const user = await verifyUser(req);
    if (!user) {
      return NextResponse.json({ error: 'غير مصرح بالدخول. يرجى تسجيل الدخول أولاً.' }, { status: 401 });
    }

    const { data: orders, error } = await supabaseServer
      .from('orders')
      .select('*')
      .eq('user_id', user.id)
      .order('created_at', { ascending: false });

    if (error) throw error;
    return NextResponse.json(orders);
  } catch (err: any) {
    console.error('Error fetching user orders via API:', err);
    return NextResponse.json({ error: err.message || 'حدث خطأ في الخادم.' }, { status: 500 });
  }
}

// POST: Create a new order
export async function POST(req: Request) {
  try {
    const user = await verifyUser(req);
    const body = await req.json();
    const { items, total_price, delivery_address, delivery_phone, payment_method, user_id } = body;

    if (!items || !total_price || !delivery_address || !delivery_phone) {
      return NextResponse.json({ error: 'جميع الحقول المطلوبة لإنشاء الطلب غير مكتملة.' }, { status: 400 });
    }

    const targetUserId = user ? user.id : user_id || null;

    // Insert order using server-side client
    const { data: newOrder, error } = await supabaseServer
      .from('orders')
      .insert({
        user_id: targetUserId,
        items,
        total_price,
        status: 'pending',
        delivery_address,
        delivery_phone,
        payment_method: payment_method || 'COD',
      })
      .select()
      .single();

    if (error) throw error;

    // إرسال تنبيه فوري وبشكل لحظي لسيستم الكاشير عبر نفق كلاود فلير
    try {
      // محاولة جلب رابط الويب هوك الديناميكي من جدول pos_settings
      const { data: settingData, error: settingError } = await supabaseServer
        .from('pos_settings')
        .select('value')
        .eq('key', 'webhook_url')
        .maybeSingle();

      // ترتيب الأولويات: 1. متغير البيئة | 2. الإعداد السحابي | 3. الفولباك المحلي
      const dbUrl = (!settingError && settingData?.value) ? settingData.value : null;
      const webhookUrl = process.env.POS_WEBHOOK_URL || dbUrl || 'http://127.0.0.1:8085/api/webhook/new-order';

      console.log('Triggering POS webhook at:', webhookUrl);

      fetch(webhookUrl, {
        method: 'POST',
        headers: {
          'X-Webhook-Token': 'nasriya_pos_webhook_secret_key_2026',
        },
      }).catch(err => console.error('Error triggering POS webhook:', err));
    } catch (webhookErr) {
      console.error('Failed to trigger POS webhook:', webhookErr);
    }

    // Loyalty points allocation logic
    if (targetUserId) {
      const earnedPoints = Math.floor(Number(total_price) / 100);
      if (earnedPoints > 0) {
        // Retrieve current points
        const { data: profile } = await supabaseServer
          .from('profiles')
          .select('points')
          .eq('id', targetUserId)
          .maybeSingle();

        const currentPoints = profile?.points || 0;
        
        // Update user points
        await supabaseServer
          .from('profiles')
          .update({ points: currentPoints + earnedPoints })
          .eq('id', targetUserId);
      }
    }

    return NextResponse.json(newOrder);
  } catch (err: any) {
    console.error('Error creating order via API:', err);
    return NextResponse.json({ error: err.message || 'حدث خطأ في الخادم أثناء إنشاء الطلب.' }, { status: 500 });
  }
}
