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

// Helper function to validate order calculations, coupons, and shipping fees on the backend
async function validateOrder({
  items,
  coupon_code,
  selected_region,
  user
}: {
  items: any[];
  coupon_code: string | null;
  selected_region: string;
  user: any;
}) {
  let subtotal = 0;

  // 1. Validate items and compute subtotal
  for (const item of items) {
    const { data: product, error } = await supabaseServer
      .from('products')
      .select('*')
      .eq('id', item.product_id)
      .maybeSingle();

    if (error || !product) {
      throw new Error(`الصنف المحدد غير موجود في المتجر: ${item.name || item.product_id}`);
    }

    if (!product.is_available) {
      throw new Error(`الصنف ${product.name} غير متوفر حالياً.`);
    }

    // Determine unit price based on rules
    const isWholesale = item.qty >= product.wholesale_min_qty;
    const isWeight = item.weight_grams && product.accepts_weight;
    
    let dbUnitPrice = 0;
    if (isWeight) {
      dbUnitPrice = (Number(product.price) / 1000) * Number(item.weight_grams);
    } else if (isWholesale) {
      dbUnitPrice = Number(product.wholesale_price);
    } else {
      dbUnitPrice = Number(product.sale_price || product.price);
    }

    // Verify item price passed from frontend matches backend calculated price
    if (Math.abs(Number(item.price) - dbUnitPrice) > 0.02) {
      throw new Error(`خطأ في تسعير الصنف: ${product.name}. السعر المتوقع: ${dbUnitPrice}، السعر المرسل: ${item.price}`);
    }

    subtotal += dbUnitPrice * Number(item.qty);
  }

  // 2. Validate coupon and calculate discount
  let discount_amount = 0;
  if (coupon_code) {
    const { data: coupon, error } = await supabaseServer
      .from('coupons')
      .select('*')
      .eq('code', coupon_code)
      .eq('is_active', true)
      .maybeSingle();

    if (error || !coupon) {
      throw new Error('الكوبون المستخدم غير فعال أو غير موجود.');
    }

    if (subtotal < Number(coupon.min_order_amount)) {
      throw new Error(`الحد الأدنى لاستخدام الكوبون هو ${coupon.min_order_amount} جنيه.`);
    }

    if (coupon.discount_type === 'points') {
      if (!user) {
        throw new Error('يجب تسجيل الدخول لاستخدام كوبونات النقاط.');
      }
      const { data: profile } = await supabaseServer
        .from('profiles')
        .select('points')
        .eq('id', user.id)
        .maybeSingle();
      
      const userPoints = profile?.points || 0;
      if (userPoints < Number(coupon.points_cost)) {
        throw new Error('نقاطك الذهبية غير كافية لاستخدام هذا الكوبون.');
      }
    }

    // Calculate discount
    if (coupon.discount_type === 'percentage') {
      discount_amount = subtotal * (Number(coupon.discount_value) / 100);
    } else {
      discount_amount = Number(coupon.discount_value);
    }
  }

  // 3. Load configurations from pos_settings in Supabase
  const { data: settingsData } = await supabaseServer
    .from('pos_settings')
    .select('key, value');

  const settings: Record<string, string> = {};
  if (settingsData) {
    settingsData.forEach(s => {
      settings[s.key] = s.value;
    });
  }

  const defaultShippingFee = Number(settings['ecom_shipping_fee'] || 50);
  const freeShippingThreshold = Number(settings['ecom_free_shipping_threshold'] || 800);

  const deliveryStart = settings['delivery_start_time'] || '17:00';
  const deliveryEnd = settings['delivery_end_time'] || '04:00';
  const deliveryMode = settings['delivery_outside_hours_mode'] || 'warn';

  if (deliveryMode === 'block') {
    const parseTimeToMinutes = (time: string) => {
      const [h, m] = time.split(':').map(Number);
      return h * 60 + m;
    };

    const now = new Date();
    const formatter = new Intl.DateTimeFormat('en-US', {
      timeZone: 'Africa/Cairo',
      hour: 'numeric',
      minute: 'numeric',
      hour12: false
    });
    const formattedEgyptTime = formatter.format(now);
    const [egyptHour, egyptMin] = formattedEgyptTime.split(':').map(Number);
    const current = egyptHour * 60 + egyptMin;

    const start = parseTimeToMinutes(deliveryStart);
    const end = parseTimeToMinutes(deliveryEnd);

    let isWorking = false;
    if (start < end) {
      isWorking = current >= start && current <= end;
    } else {
      isWorking = current >= start || current <= end;
    }

    if (!isWorking) {
      const formatTimeStr = (timeStr: string) => {
        const [h, m] = timeStr.split(':').map(Number);
        const suffix = h >= 12 ? 'مساءً' : 'صباحاً';
        const displayHour = h % 12 === 0 ? 12 : h % 12;
        const displayMin = m.toString().padStart(2, '0');
        return `${displayHour}:${displayMin} ${suffix}`;
      };
      throw new Error(`نعتذر عن استقبال الطلبات حالياً. خدمة التوصيل مغلقة. ساعات عمل التوصيل من ${formatTimeStr(deliveryStart)} إلى ${formatTimeStr(deliveryEnd)}.`);
    }
  }

  // 4. Load shipping zone price
  let zonePrice = defaultShippingFee;
  if (selected_region) {
    const { data: zone, error } = await supabaseServer
      .from('shipping_zones')
      .select('price')
      .eq('name', selected_region)
      .eq('is_active', true)
      .maybeSingle();

    if (zone && !error) {
      zonePrice = Number(zone.price);
    }
  }

  // Determine if free shipping threshold is met
  const isFreeShipping = subtotal >= freeShippingThreshold;
  const shipping_fee = isFreeShipping ? 0 : zonePrice;

  // Calculate final total
  const expectedTotal = Math.max(0, subtotal - discount_amount + shipping_fee);

  return {
    subtotal,
    discount_amount,
    shipping_fee,
    expectedTotal
  };
}

// POST: Create a new order
export async function POST(req: Request) {
  try {
    const user = await verifyUser(req);
    const body = await req.json();
    const { 
      items, 
      total_price, 
      delivery_address, 
      delivery_phone, 
      payment_method, 
      user_id,
      coupon_code,
      selected_region
    } = body;

    if (!items || total_price === undefined || !delivery_address || !delivery_phone) {
      return NextResponse.json({ error: 'جميع الحقول المطلوبة لإنشاء الطلب غير مكتملة.' }, { status: 400 });
    }

    const targetUserId = user ? user.id : user_id || null;

    // Validate calculations, coupons and shipping on the backend
    let validatedData;
    try {
      validatedData = await validateOrder({
        items,
        coupon_code: coupon_code || null,
        selected_region: selected_region || '',
        user
      });
    } catch (valErr: any) {
      console.error('Validation error on order create:', valErr.message);
      return NextResponse.json({ error: valErr.message || 'فشل التحقق من صحة بيانات الطلب.' }, { status: 400 });
    }

    const { subtotal, discount_amount, shipping_fee, expectedTotal } = validatedData;

    // Verify total_price matches expected total (allow small rounding tolerance)
    if (Math.abs(Number(total_price) - expectedTotal) > 0.05) {
      return NextResponse.json({
        error: `خطأ في حساب إجمالي الطلب. الإجمالي المتوقع: ${expectedTotal}، الإجمالي المرسل: ${total_price}`
      }, { status: 400 });
    }

    const orderPayload = {
      user_id: targetUserId,
      items,
      total_price: expectedTotal,
      status: 'pending',
      delivery_address,
      delivery_phone,
      payment_method: payment_method || 'COD',
    };

    let newOrder;
    let error;

    // Try inserting with detailed columns first
    const { data: orderWithDetails, error: detailsError } = await supabaseServer
      .from('orders')
      .insert({
        ...orderPayload,
        subtotal,
        shipping_fee,
        discount_amount,
        coupon_code: coupon_code || null,
      })
      .select()
      .single();

    if (detailsError) {
      console.warn('Failed to insert order with details (migration might not be run yet):', detailsError.message);
      // Fallback: insert without detailed columns
      const { data: fallbackOrder, error: fallbackError } = await supabaseServer
        .from('orders')
        .insert(orderPayload)
        .select()
        .single();
      
      newOrder = fallbackOrder;
      error = fallbackError;
    } else {
      newOrder = orderWithDetails;
    }

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
      const earnedPoints = Math.floor(Number(expectedTotal) / 100);
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
