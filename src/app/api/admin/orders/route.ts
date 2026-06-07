import { NextResponse } from 'next/server';
import { supabaseServer, verifyAdmin } from '@/lib/serverSupabase';

// GET: Retrieve all store orders (admin only)
export async function GET(req: Request) {
  try {
    const admin = await verifyAdmin(req);
    if (!admin) {
      return NextResponse.json({ error: 'غير مصرح بالدخول. هذه البيانات متوفرة لمشرفي النظام فقط.' }, { status: 403 });
    }

    const { data: orders, error } = await supabaseServer
      .from('orders')
      .select('*')
      .order('created_at', { ascending: false });

    if (error) throw error;
    return NextResponse.json(orders);
  } catch (err: any) {
    console.error('Error fetching admin orders via API:', err);
    return NextResponse.json({ error: err.message || 'حدث خطأ في الخادم أثناء تحميل الطلبات.' }, { status: 500 });
  }
}

// PUT: Update an order's status (admin only)
export async function PUT(req: Request) {
  try {
    const admin = await verifyAdmin(req);
    if (!admin) {
      return NextResponse.json({ error: 'غير مصرح بالدخول. لا تملك صلاحيات تعديل الطلبات.' }, { status: 403 });
    }

    const body = await req.json();
    const { orderId, status } = body;

    if (!orderId || !status) {
      return NextResponse.json({ error: 'معرّف الطلب وحالته حقول مطلوبة للتعديل.' }, { status: 400 });
    }

    const { data: updatedOrder, error } = await supabaseServer
      .from('orders')
      .update({ status })
      .eq('id', orderId)
      .select()
      .single();

    if (error) throw error;
    return NextResponse.json(updatedOrder);
  } catch (err: any) {
    console.error('Error updating order status via API:', err);
    return NextResponse.json({ error: err.message || 'حدث خطأ في الخادم أثناء تحديث حالة الطلب.' }, { status: 500 });
  }
}
