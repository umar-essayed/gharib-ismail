import { NextResponse } from 'next/server';
import { supabaseServer, verifyAdmin } from '@/lib/serverSupabase';

// GET: Generate Dashboard Analytics Statistics for Admin Panel
export async function GET(req: Request) {
  try {
    const admin = await verifyAdmin(req);
    if (!admin) {
      return NextResponse.json({ error: 'غير مصرح بالدخول. هذه البيانات متوفرة لمشرفي النظام فقط.' }, { status: 403 });
    }

    // 1. Fetch orders data for sales metrics
    const { data: orders, error: ordersErr } = await supabaseServer
      .from('orders')
      .select('total_price, status');

    if (ordersErr) throw ordersErr;

    const totalOrders = orders?.length || 0;
    const completedOrders = orders?.filter(o => o.status === 'completed') || [];
    const pendingOrders = orders?.filter(o => o.status === 'pending') || [];
    const preparingOrders = orders?.filter(o => o.status === 'preparing') || [];
    const deliveringOrders = orders?.filter(o => o.status === 'delivering') || [];

    const totalSales = orders?.reduce((sum, o) => sum + Number(o.total_price), 0) || 0;
    const completedSales = completedOrders.reduce((sum, o) => sum + Number(o.total_price), 0);

    // 2. Fetch profiles count (customers)
    const { count: customersCount, error: profilesErr } = await supabaseServer
      .from('profiles')
      .select('*', { count: 'exact', head: true });

    if (profilesErr) throw profilesErr;

    // 3. Fetch products count
    const { count: productsCount, error: prodsErr } = await supabaseServer
      .from('products')
      .select('*', { count: 'exact', head: true });

    if (prodsErr) throw prodsErr;

    // 4. Fetch categories count
    const { count: categoriesCount, error: catsErr } = await supabaseServer
      .from('categories')
      .select('*', { count: 'exact', head: true });

    if (catsErr) throw catsErr;

    return NextResponse.json({
      sales: {
        total: totalSales,
        completed: completedSales
      },
      counts: {
        orders: totalOrders,
        pendingOrders: pendingOrders.length,
        preparingOrders: preparingOrders.length,
        deliveringOrders: deliveringOrders.length,
        completedOrders: completedOrders.length,
        customers: customersCount || 0,
        products: productsCount || 0,
        categories: categoriesCount || 0
      }
    });

  } catch (err: any) {
    console.error('Error generating admin stats via API:', err);
    return NextResponse.json({ error: err.message || 'حدث خطأ في الخادم أثناء تحميل الإحصائيات.' }, { status: 500 });
  }
}
