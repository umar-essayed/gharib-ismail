'use client';

import React, { useState, useEffect, Suspense } from 'react';
import { useRouter } from 'next/navigation';
import { useCart } from '@/context/CartContext';
import Navbar from '@/components/Navbar';
import { supabase } from '@/lib/supabase';
import { Order } from '@/types';
import { 
  User, 
  MapPin, 
  Phone, 
  Clock, 
  LogOut, 
  Loader2, 
  CheckCircle2, 
  ShoppingBag,
  Truck,
  Package,
  Award,
  MessageSquare,
  Calendar,
  ChevronLeft
} from 'lucide-react';
import Link from 'next/link';

function ProfileContent() {
  const { profile, setProfile, refreshProfile } = useCart();
  const router = useRouter();

  const [orders, setOrders] = useState<Order[]>([]);
  const [loadingOrders, setLoadingOrders] = useState(true);
  const [activeTab, setActiveTab] = useState<'orders' | 'settings'>('orders');

  // Profile Form States
  const [fullName, setFullName] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [updating, setUpdating] = useState(false);
  const [msg, setMsg] = useState<{ type: 'success' | 'error', text: string } | null>(null);

  // Redirect if not logged in
  useEffect(() => {
    supabase.auth.getSession().then(({ data: { session } }) => {
      if (!session) {
        router.push('/auth');
      }
    });
  }, [router]);

  // Load profile inputs
  useEffect(() => {
    if (profile) {
      setFullName(profile.full_name || '');
      setPhone(profile.phone || '');
      setAddress(profile.address || '');
      loadOrders(profile.id);
    }
  }, [profile]);

  const loadOrders = async (userId: string) => {
    try {
      setLoadingOrders(true);
      const { data, error } = await supabase
        .from('orders')
        .select('*')
        .eq('user_id', userId)
        .order('created_at', { ascending: false });

      if (error) throw error;
      setOrders(data as Order[] || []);
    } catch (err) {
      console.error('Error loading profile orders:', err);
    } finally {
      setLoadingOrders(false);
    }
  };

  const handleUpdateProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!profile) return;
    if (!fullName.trim() || !phone.trim() || !address.trim()) {
      setMsg({ type: 'error', text: 'الرجاء ملء جميع البيانات المطلوبة.' });
      return;
    }

    try {
      setUpdating(true);
      setMsg(null);

      const { error } = await supabase
        .from('profiles')
        .update({
          full_name: fullName.trim(),
          phone: phone.trim(),
          address: address.trim()
        })
        .eq('id', profile.id);

      if (error) throw error;

      setMsg({ type: 'success', text: 'تم تحديث بياناتك بنجاح!' });
      await refreshProfile();
    } catch (err: any) {
      console.error('Failed to update profile:', err);
      setMsg({ type: 'error', text: err.message || 'فشل التحديث. الرجاء المحاولة مجدداً.' });
    } finally {
      setUpdating(false);
    }
  };

  const handleLogout = async () => {
    await supabase.auth.signOut();
    localStorage.removeItem('demo_profile');
    setProfile(null);
    router.push('/');
  };

  const shareInvoiceOnWhatsApp = (ord: Order) => {
    const orderDisplayId = ord.id.substring(0, 8).toUpperCase();
    const itemsList = ord.items.map((it: any) => `- ${it.name} (الكمية: ${it.qty})`).join('\n');
    const text = encodeURIComponent(
      `مرحباً الناصرية ماركت، أود الاستفسار عن طلبي رقم:\n\n` +
      `*رقم الفاتورة:* #${orderDisplayId}\n` +
      `*العنوان:* ${ord.delivery_address}\n` +
      `*القيمة الإجمالية:* ${ord.total_price.toFixed(2)} ج.م\n\n` +
      `*قائمة الأصناف:*\n${itemsList}`
    );
    window.open(`https://wa.me/201211879341?text=${text}`, '_blank');
  };

  // Stepper status builder
  const renderStatusStepper = (status: Order['status']) => {
    const steps = [
      { id: 'pending', label: 'قيد المراجعة', icon: Clock },
      { id: 'preparing', label: 'قيد التحضير', icon: Package },
      { id: 'delivering', label: 'جاري التوصيل', icon: Truck },
      { id: 'completed', label: 'تم التسليم', icon: CheckCircle2 }
    ];

    const currentIdx = steps.findIndex(s => s.id === status);

    return (
      <div className="w-full py-4 relative">
        <div className="flex items-center justify-between relative">
          {/* Background line */}
          <div className="absolute top-1/2 left-6 right-6 h-0.5 bg-slate-200 -translate-y-1/2 z-0" />
          
          {/* Active progress line */}
          <div 
            className="absolute top-1/2 right-6 h-0.5 bg-primary -translate-y-1/2 z-0 transition-all duration-500" 
            style={{ 
              left: `${100 - (currentIdx / (steps.length - 1)) * 100}%`,
              right: '24px'
            }}
          />

          {steps.map((step, idx) => {
            const StepIcon = step.icon;
            const isCompleted = idx <= currentIdx;
            const isCurrent = idx === currentIdx;

            return (
              <div key={step.id} className="flex flex-col items-center z-10 relative flex-1">
                <div 
                  className={`w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center border-2 transition-all duration-300 ${
                    isCurrent 
                      ? 'bg-white border-primary text-primary shadow-lg scale-110' 
                      : isCompleted 
                      ? 'bg-primary border-primary text-white shadow-xs' 
                      : 'bg-white border-slate-200 text-slate-450'
                  }`}
                >
                  <StepIcon size={14} className="sm:w-4 sm:h-4" />
                </div>
                <span className={`text-[8px] sm:text-[10px] font-black mt-2 transition-colors ${
                  isCurrent ? 'text-primary' : isCompleted ? 'text-slate-800' : 'text-slate-400'
                }`}>
                  {step.label}
                </span>
              </div>
            );
          })}
        </div>
      </div>
    );
  };

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col font-sans text-right text-slate-850">
      <Navbar />

      <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">
        
        {/* Modern Glassmorphic Profile Header Banner */}
        <div className="relative bg-gradient-to-l from-slate-900 via-slate-850 to-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl overflow-hidden text-white flex flex-col md:flex-row items-center justify-between gap-6">
          <div className="absolute top-0 right-0 w-32 h-32 bg-primary/10 rounded-full blur-3xl pointer-events-none" />
          <div className="absolute bottom-0 left-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none" />
          
          <div className="flex flex-col sm:flex-row items-center gap-4 text-center sm:text-right flex-1 w-full sm:w-auto">
            <div className="w-16 h-16 sm:w-20 sm:h-20 bg-primary hover:bg-primary-dark text-white rounded-2xl flex items-center justify-center font-black text-2xl sm:text-3xl shadow-lg border border-primary/20 transform hover:rotate-3 transition-transform duration-300 flex-shrink-0">
              {fullName ? fullName.charAt(0) : 'U'}
            </div>
            <div className="space-y-1">
              <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                <h1 className="text-lg sm:text-2xl font-black">{fullName || 'عميل جملة ماركت'}</h1>
                <span className="bg-primary/20 text-primary border border-primary/30 text-[9px] font-black px-2 py-0.5 rounded-full">عضو ذهبي 👑</span>
              </div>
              <p className="text-xs text-slate-400 flex items-center justify-center sm:justify-start gap-1.5" dir="ltr">
                <span>{phone}</span>
                <Phone size={12} className="text-primary" />
              </p>
              {profile?.created_at && (
                <p className="text-[9px] text-slate-400 flex items-center justify-center sm:justify-start gap-1">
                  <span>تاريخ الانضمام: {new Date(profile.created_at).toLocaleDateString('ar-EG', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                  <Calendar size={11} className="text-primary" />
                </p>
              )}
            </div>
          </div>

          <div className="flex items-center gap-3 w-full sm:w-auto justify-center">
            <button
              onClick={handleLogout}
              className="px-4 py-2.5 bg-slate-800 hover:bg-slate-750 text-red-400 border border-slate-700/60 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-md"
            >
              <LogOut size={13} />
              تسجيل الخروج
            </button>
            
            {profile?.role === 'admin' && (
              <Link
                href="/admin/orders"
                className="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-md"
              >
                ⭐️ إدارة المتجر
              </Link>
            )}
          </div>
        </div>

        {/* Dashboard Analytics & Leveling Grid */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-stretch">
          
          {/* Left Block: Loyalty points tracker with premium progress bar */}
          <div className="md:col-span-5 bg-white border border-slate-150 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <span className="text-[10px] text-slate-400 font-black flex items-center gap-1">
                رصيد النقاط والمكافآت
                <Award size={12} className="text-amber-500" />
              </span>
              <span className="text-xs font-black text-amber-600">كاش باك ⭐</span>
            </div>
            
            <div className="space-y-2 text-center md:text-right">
              <p className="text-xs text-slate-500 font-bold">نقاطك المجمعة من فواتير الشراء:</p>
              <h3 className="text-3xl font-black text-amber-600 tracking-tight">{profile?.points || 0} <span className="text-xs font-bold text-slate-400">نقطة</span></h3>
              
              {/* Gamified VIP levels progress bar */}
              <div className="pt-2">
                <div className="flex justify-between items-center text-[8px] font-black text-slate-400 mb-1">
                  <span>المستوى التالي: VIP 💎</span>
                  <span>التقدم: {Math.min(100, Math.round(((profile?.points || 0) / 1000) * 100))}%</span>
                </div>
                <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                  <div className="bg-amber-500 h-full rounded-full transition-all duration-700" style={{ width: `${Math.min(100, ((profile?.points || 0) / 1000) * 100)}%` }} />
                </div>
                <p className="text-[8px] text-slate-400 mt-1.5 leading-normal">تحصل على 1 نقطة مقابل كل 100 ج.م شراء. اجمع 1000 نقطة لتفعيل كود خصم إضافي بقيمة 100 ج.م.</p>
              </div>
            </div>
          </div>

          {/* Right Block: Shipping Address Details card */}
          <div className="md:col-span-7 bg-white border border-slate-150 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <span className="text-[10px] text-slate-400 font-black flex items-center gap-1">
                عنوان التوصيل الافتراضي
                <MapPin size={12} className="text-primary" />
              </span>
              <button 
                onClick={() => setActiveTab('settings')}
                className="text-[10px] text-primary hover:underline font-black cursor-pointer"
              >
                تعديل العنوان ✏️
              </button>
            </div>
            
            <div className="space-y-2.5">
              <p className="text-[10px] text-slate-500 font-extrabold leading-normal">
                يتم تحديد هذا العنوان تلقائياً عند طلب سلع الجملة لضمان التوصيل للناصرية والعامرية بأسرع وقت:
              </p>
              <div className="p-3.5 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold text-slate-800 leading-relaxed text-right min-h-[64px]">
                {profile?.address || 'لم تقم بحفظ عنوان شحن بعد. انقر على تعديل بالمنطقة لتحديثه.'}
              </div>
            </div>
          </div>

        </div>

        {/* Tab switchers bar */}
        <div className="flex bg-white border border-slate-150 p-1 rounded-2xl shadow-xs">
          <button
            onClick={() => setActiveTab('orders')}
            className={`flex-1 py-3.5 rounded-xl font-black text-xs transition-all duration-300 cursor-pointer ${
              activeTab === 'orders' 
                ? 'bg-primary text-white shadow-md' 
                : 'text-slate-500 hover:text-slate-800'
            }`}
          >
            طلبات الشراء وفواتيرك ({orders.length})
          </button>
          <button
            onClick={() => setActiveTab('settings')}
            className={`flex-1 py-3.5 rounded-xl font-black text-xs transition-all duration-300 cursor-pointer ${
              activeTab === 'settings' 
                ? 'bg-primary text-white shadow-md' 
                : 'text-slate-500 hover:text-slate-800'
            }`}
          >
            إعدادات الحساب وبيانات الشحن
          </button>
        </div>

        {/* Tab Content: Orders */}
        {activeTab === 'orders' && (
          <div className="space-y-6">
            {loadingOrders ? (
              <div className="py-20 text-center space-y-4">
                <Loader2 className="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto text-primary" />
                <p className="text-xs text-slate-500 font-bold">جاري جلب فواتيرك التاريخية...</p>
              </div>
            ) : orders.length === 0 ? (
              <div className="bg-white border border-slate-150 rounded-3xl p-12 text-center space-y-4 max-w-md mx-auto shadow-xs">
                <span className="text-4xl block">🛒</span>
                <h3 className="font-black text-slate-900 text-sm">لا توجد فواتير سابقة</h3>
                <p className="text-xs text-slate-500 leading-relaxed font-semibold">
                  سلتك خالية ولم تقم بتأكيد أي طلب حتى الآن. ابدأ بالاطلاع على عروض الأسبوع وتسوّق الآن!
                </p>
                <Link
                  href="/products"
                  className="bg-primary hover:bg-primary-dark text-white font-extrabold px-6 py-2.5 rounded-xl text-xs inline-block shadow-xs transition-colors"
                >
                  تصفح كتالوج السلع 🛒
                </Link>
              </div>
            ) : (
              <div className="space-y-6 animate-fade-in">
                {orders.map((order) => {
                  const displayId = order.id.substring(0, 8).toUpperCase();
                  const formattedDate = new Date(order.created_at).toLocaleDateString('ar-EG', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  });

                  return (
                    <div 
                      key={order.id} 
                      className="bg-white border border-slate-150 rounded-3xl p-5 sm:p-6 shadow-xs space-y-5 transition-all hover:border-slate-300"
                    >
                      {/* Timeline header */}
                      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-100 pb-4">
                        <div className="space-y-1">
                          <div className="flex items-center gap-2">
                            <span className="font-black text-slate-800 text-xs sm:text-sm">فاتورة رقم:</span>
                            <span className="bg-primary/5 text-primary text-xs font-black px-2 py-0.5 rounded-lg border border-primary/10">
                              #{displayId}
                            </span>
                            <span className="text-[9px] sm:text-[10px] text-slate-400 font-bold" dir="rtl">
                              ({formattedDate})
                            </span>
                          </div>
                        </div>

                        <div className="flex items-center gap-3 font-black text-sm text-primary w-full sm:w-auto justify-between sm:justify-end">
                          <span>الإجمالي: {order.total_price.toFixed(2)} ج.م</span>
                        </div>
                      </div>

                      {/* Stepper Status tracker */}
                      <div className="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        {renderStatusStepper(order.status)}
                      </div>

                      {/* Stepper Footer actions */}
                      <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-start text-xs font-semibold text-slate-650">
                        {/* Delivery address details */}
                        <div className="md:col-span-6 space-y-2">
                          <p className="font-bold text-slate-900 text-[11px] flex items-center gap-1.5 justify-end">
                            عنوان الشحن لهذه الفاتورة
                            <MapPin size={13} className="text-primary" />
                          </p>
                          <p className="bg-slate-50 p-3 rounded-xl border border-slate-100 leading-relaxed text-right text-slate-700 text-[11px]">
                            {order.delivery_address}
                          </p>
                        </div>

                        {/* Items count summary */}
                        <div className="md:col-span-6 space-y-2">
                          <p className="font-bold text-slate-900 text-[11px] flex items-center gap-1.5 justify-end">
                            قائمة السلع والكميات
                            <ShoppingBag size={13} className="text-primary" />
                          </p>
                          <div className="bg-slate-50 rounded-xl p-3 border border-slate-100 divide-y divide-slate-200/50 max-h-36 overflow-y-auto pr-1">
                            {order.items.map((it: any, index: number) => (
                              <div key={index} className="py-2 flex justify-between gap-4 text-[11px] font-bold">
                                <span className="text-slate-400">×{it.qty}</span>
                                <span className="text-slate-700 flex-1 text-right truncate pr-2">{it.name}</span>
                                <span className="text-slate-900 font-extrabold whitespace-nowrap">{(it.price * it.qty).toFixed(2)} ج.م</span>
                              </div>
                            ))}
                          </div>
                        </div>
                      </div>

                      {/* Action buttons including WhatsApp details inquiries */}
                      <div className="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <span className="text-[10px] text-slate-400 font-bold">طريقة الدفع: الدفع عند الاستلام كاش للمندوب 💵</span>
                        <button
                          onClick={() => shareInvoiceOnWhatsApp(order)}
                          className="w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
                        >
                          <MessageSquare size={13} />
                          الاستفسار عن الفاتورة عبر الواتساب 💬
                        </button>
                      </div>

                    </div>
                  );
                })}
              </div>
            )}
          </div>
        )}

        {/* Tab Content: Settings */}
        {activeTab === 'settings' && (
          <div className="max-w-xl mx-auto bg-white border border-slate-150 rounded-3xl p-6 sm:p-8 shadow-xs">
            <h2 className="text-base font-black text-slate-900 border-b border-slate-100 pb-3 mb-6 flex items-center justify-end gap-2">
              تحديث بيانات الملف الشخصي والشحن
              <span className="p-1 bg-primary/10 text-primary rounded-lg text-xs">👤</span>
            </h2>

            {msg && (
              <div className={`p-3.5 rounded-2xl text-xs font-bold mb-5 text-right flex items-center justify-end gap-2 border ${
                msg.type === 'success' 
                  ? 'bg-emerald-50 border-emerald-250 text-emerald-700' 
                  : 'bg-red-50 border-red-250 text-red-750'
              }`}>
                <span>{msg.text}</span>
                <span>{msg.type === 'success' ? '✓' : '⚠️'}</span>
              </div>
            )}

            <form onSubmit={handleUpdateProfile} className="space-y-4 text-right">
              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5 justify-end">
                  الاسم بالكامل *
                  <User size={14} className="text-primary" />
                </label>
                <input
                  type="text"
                  required
                  value={fullName}
                  onChange={(e) => setFullName(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-250 rounded-xl px-4 py-2.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-slate-800 transition-all font-bold"
                />
              </div>

              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5 justify-end">
                  رقم الهاتف للتواصل *
                  <Phone size={14} className="text-primary" />
                </label>
                <input
                  type="tel"
                  required
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  className="w-full bg-slate-100 border border-slate-200 text-slate-450 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none cursor-not-allowed"
                  disabled
                  dir="ltr"
                />
                <p className="text-[8px] text-slate-400 mt-1">لا يمكن تعديل رقم الموبايل المسجل لحفظ تاريخ المبيعات والطلبات بأمان.</p>
              </div>

              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5 justify-end">
                  عنوان التسليم التفصيلي (الناصرية / العامرية) *
                  <MapPin size={14} className="text-primary" />
                </label>
                <textarea
                  required
                  rows={3}
                  value={address}
                  onChange={(e) => setAddress(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-250 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-slate-800 transition-all leading-relaxed font-bold"
                  placeholder="الرجاء كتابة اسم الشارع والمعالم القريبة بدقة"
                />
              </div>

              <button
                type="submit"
                disabled={updating}
                className="w-full py-3 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-extrabold transition-all duration-300 shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer"
              >
                {updating ? (
                  <>
                    <Loader2 size={14} className="animate-spin" />
                    جاري تحديث بياناتك...
                  </>
                ) : (
                  'حفظ تعديلات الحساب ✨'
                )}
              </button>
            </form>
          </div>
        )}

      </main>
    </div>
  );
}

export default function ProfilePage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-slate-50 flex items-center justify-center">
        <div className="text-center space-y-4">
          <div className="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto text-primary" />
          <p className="text-sm font-bold text-slate-600 font-sans">تحميل لوحة تحكم حسابك...</p>
        </div>
      </div>
    }>
      <ProfileContent />
    </Suspense>
  );
}
