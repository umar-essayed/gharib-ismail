'use client';

import React, { useState, useEffect, Suspense } from 'react';
import { useRouter } from 'next/navigation';
import { useCart } from '@/context/CartContext';
import Navbar from '@/components/Navbar';
import { supabase } from '@/lib/supabase';
import { confirmUserByPhone } from '@/lib/authHelper';
import { Order, Coupon } from '@/types';
import { 
  ShoppingBag, 
  MapPin, 
  Phone, 
  User, 
  CreditCard,
  ChevronRight,
  CheckCircle,
  AlertCircle,
  Lock,
  Loader2,
  Clock
} from 'lucide-react';

function CheckoutContent() {
  const { 
    cart, 
    subtotal, 
    profile,
    clearCart,
    refreshProfile
  } = useCart();

  const router = useRouter();

  // Multi-step Auth State
  const [checkoutStep, setCheckoutStep] = useState<'auth' | 'address'>('auth');
  const [authTab, setAuthTab] = useState<'login' | 'register' | 'guest'>('login');
  const [authLoading, setAuthLoading] = useState(false);
  const [authError, setAuthError] = useState<string | null>(null);

  // Form inputs
  const [fullName, setFullName] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [selectedRegion, setSelectedRegion] = useState('الناصرية القديمة');
  const [shippingZones, setShippingZones] = useState<{ name: string; price: number }[]>([
    { name: 'الناصرية القديمة', price: 20 },
    { name: 'الناصرية الجديدة', price: 25 },
    { name: 'العامرية أول', price: 35 },
    { name: 'الكنج مريوط', price: 50 },
  ]);
  const [detailedAddress, setDetailedAddress] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('COD');
  
  // Checkout Processing States
  const [orderLoading, setOrderLoading] = useState(false);
  const [orderSuccess, setOrderSuccess] = useState(false);
  const [successOrderId, setSuccessOrderId] = useState('');
  const [pastOrders, setPastOrders] = useState<Order[]>([]);

  // Coupons State
  const [coupons, setCoupons] = useState<Coupon[]>([]);
  const [selectedCoupon, setSelectedCoupon] = useState<string | null>(null);

  const [shippingFee, setShippingFee] = useState(50);
  const [threshold, setThreshold] = useState(800);

  useEffect(() => {
    fetch('/ecom_config.json')
      .then((r) => r.json())
      .then((data) => {
        if (data) {
          if (data.shipping_fee !== undefined) setShippingFee(Number(data.shipping_fee));
          if (data.free_shipping_threshold !== undefined) setThreshold(Number(data.free_shipping_threshold));
        }
      })
      .catch(() => {});
  }, []);

  useEffect(() => {
    async function fetchCoupons() {
      try {
        const { data, error } = await supabase
          .from('coupons')
          .select('*')
          .eq('is_active', true);
        if (data && data.length > 0) {
          setCoupons(data as Coupon[]);
        } else {
          setCoupons([
            { code: 'ARZ15', description: 'خصم 15% على إجمالي السلة', discount_type: 'percentage', discount_value: 15, min_order_amount: 0, points_cost: 0, is_active: true },
            { code: 'GHARIB50', description: 'خصم بقيمة 50 جنيه للطلبات بقيمة 300 جنيه أو أكثر', discount_type: 'fixed', discount_value: 50, min_order_amount: 300, points_cost: 0, is_active: true },
            { code: 'POINTS100', description: 'خصم بقيمة 100 جنيه مقابل 100 نقطة ذهبية من رصيدك', discount_type: 'points', discount_value: 100, min_order_amount: 0, points_cost: 100, is_active: true }
          ]);
        }
      } catch (err) {
        setCoupons([
          { code: 'ARZ15', description: 'خصم 15% على إجمالي السلة', discount_type: 'percentage', discount_value: 15, min_order_amount: 0, points_cost: 0, is_active: true },
          { code: 'GHARIB50', description: 'خصم بقيمة 50 جنيه للطلبات بقيمة 300 جنيه أو أكثر', discount_type: 'fixed', discount_value: 50, min_order_amount: 300, points_cost: 0, is_active: true },
          { code: 'POINTS100', description: 'خصم بقيمة 100 جنيه مقابل 100 نقطة ذهبية من رصيدك', discount_type: 'points', discount_value: 100, min_order_amount: 0, points_cost: 100, is_active: true }
        ]);
      }
    }
    fetchCoupons();
  }, []);

  useEffect(() => {
    async function fetchShippingZones() {
      try {
        const { data, error } = await supabase
          .from('shipping_zones')
          .select('name, price')
          .eq('is_active', true);
        if (data && data.length > 0) {
          setShippingZones(data.map(z => ({ name: z.name, price: Number(z.price) })));
          
          // Autofill the first zone name as default if it exists
          if (data[0] && data[0].name) {
            setSelectedRegion(data[0].name);
          }
        }
      } catch (err) {
        console.warn('Failed to fetch shipping zones, using fallback:', err);
      }
    }
    fetchShippingZones();
  }, []);

  const calculateDiscount = (couponCode: string | null) => {
    if (!couponCode) return 0;
    const coupon = coupons.find(c => c.code === couponCode);
    if (!coupon) return 0;
    if (subtotal < coupon.min_order_amount) return 0;
    if (coupon.discount_type === 'points' && (profile?.points || 0) < coupon.points_cost) return 0;
    
    if (coupon.discount_type === 'percentage') {
      return subtotal * (coupon.discount_value / 100);
    }
    if (coupon.discount_type === 'fixed' || coupon.discount_type === 'points') {
      return coupon.discount_value;
    }
    return 0;
  };

  const activeDiscount = calculateDiscount(selectedCoupon);
  const activeZone = shippingZones.find(z => z.name === selectedRegion);
  const zonePrice = activeZone ? activeZone.price : shippingFee;
  const isFreeShipping = subtotal >= threshold;
  const activeShippingFee = isFreeShipping ? 0 : zonePrice;
  const finalTotal = Math.max(0, subtotal - activeDiscount + activeShippingFee);

  // 1. Prefill details if profile exists
  useEffect(() => {
    if (profile) {
      setFullName(profile.full_name || '');
      setPhone(profile.phone || '');
      
      const savedAddress = profile.address || '';
      const regionsList = shippingZones.map(z => z.name);
      let matchedRegion = shippingZones.length > 0 ? shippingZones[0].name : 'الناصرية القديمة';
      let details = savedAddress;

      for (const r of regionsList) {
        if (savedAddress.startsWith(r)) {
          matchedRegion = r;
          let remainder = savedAddress.substring(r.length);
          if (remainder.startsWith('،') || remainder.startsWith(',')) {
            remainder = remainder.substring(1).trim();
          }
          details = remainder.trim();
          break;
        }
      }
      setSelectedRegion(matchedRegion);
      setDetailedAddress(details);

      setCheckoutStep('address');
      loadPastOrders(profile.id);
    } else {
      setCheckoutStep('auth');
      setAuthTab('login');
      setPastOrders([]);
    }
  }, [profile]);

  // Load past orders for logged in customer
  const loadPastOrders = async (userId: string) => {
    try {
      const { data, error } = await supabase
        .from('orders')
        .select('*')
        .eq('user_id', userId)
        .order('created_at', { ascending: false });
      if (data) {
        setPastOrders(data as Order[]);
      }
    } catch (err) {
      console.error('Error loading past orders:', err);
    }
  };

  // Protect page from empty cart
  useEffect(() => {
    if (cart.length === 0 && !orderSuccess) {
      router.push('/');
    }
  }, [cart, orderSuccess, router]);

  // Step 1A: Handle login
  const handleLoginSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!phone.trim()) {
      setAuthError('الرجاء إدخال رقم الهاتف.');
      return;
    }
    if (!password) {
      setAuthError('الرجاء إدخال كلمة المرور.');
      return;
    }

    try {
      setAuthLoading(true);
      setAuthError(null);

      const virtualEmail = `${phone.trim()}@gmail.com`;
      let { data, error } = await supabase.auth.signInWithPassword({
        email: virtualEmail,
        password: password
      });

      // Auto-confirm old accounts if they throw "Email not confirmed"
      if (error && (error.message?.toLowerCase().includes('confirm') || error.message?.toLowerCase().includes('verification'))) {
        try {
          const confirmed = await confirmUserByPhone(phone);
          if (confirmed) {
            const retryResult = await supabase.auth.signInWithPassword({
              email: virtualEmail,
              password: password
            });
            error = retryResult.error;
          }
        } catch (adminErr) {
          console.error('Failed to auto-confirm user during checkout login:', adminErr);
        }
      }

      if (error) throw error;

      await refreshProfile();
      setCheckoutStep('address');
    } catch (err: any) {
      console.error('Error in login checkout:', err);
      setAuthError(err.message || 'رقم الهاتف أو كلمة المرور غير صحيحة. يرجى المحاولة مجدداً.');
    } finally {
      setAuthLoading(false);
    }
  };

  // Step 1B: Handle register
  const handleRegisterSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!phone.trim()) {
      setAuthError('الرجاء إدخال رقم الهاتف.');
      return;
    }
    if (!fullName.trim()) {
      setAuthError('الرجاء إدخال الاسم بالكامل.');
      return;
    }
    if (!password) {
      setAuthError('الرجاء إدخال كلمة المرور.');
      return;
    }

    try {
      setAuthLoading(true);
      setAuthError(null);

      const virtualEmail = `${phone.trim()}@gmail.com`;

      // Use admin.createUser to bypass confirmation checks since we hold service_role
      const { data, error: signUpErr } = await supabase.auth.admin.createUser({
        email: virtualEmail,
        password: password,
        email_confirm: true,
        user_metadata: {
          full_name: fullName.trim(),
          phone: phone.trim(),
          address: '',
        },
      });

      if (signUpErr) throw signUpErr;

      // Auto log in after creation
      const { error: signInErr } = await supabase.auth.signInWithPassword({
        email: virtualEmail,
        password: password,
      });

      if (signInErr) throw signInErr;

      await refreshProfile();
      setCheckoutStep('address');
    } catch (err: any) {
      console.error('Error in register checkout:', err);
      let friendlyMessage = err.message || 'حدث خطأ أثناء إنشاء الحساب. يرجى المحاولة لاحقاً.';
      if (err.message?.includes('already exists') || err.message?.includes('already registered') || err.message?.includes('email_exists')) {
        friendlyMessage = 'رقم الهاتف هذا مسجل بالفعل لحساب آخر. يرجى تسجيل الدخول بدلاً من ذلك.';
      }
      setAuthError(friendlyMessage);
    } finally {
      setAuthLoading(false);
    }
  };

  // Step 1C: Handle guest checkout initiation
  const handleGuestSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!fullName.trim()) {
      setAuthError('الرجاء إدخال اسم المستلم.');
      return;
    }
    if (!phone.trim()) {
      setAuthError('الرجاء إدخال رقم الهاتف للتواصل.');
      return;
    }
    setAuthError(null);
    setCheckoutStep('address');
  };

  // Step 2: Handle Finalizing Order Submission
  const handleOrderSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!detailedAddress.trim()) {
      setAuthError('الرجاء كتابة تفاصيل العنوان بالكامل لضمان وصول الشحنة.');
      return;
    }

    // Function to join the selected region and detailed address, including the recipient's name as a prefix
    const combinedAddress = `الاسم: ${fullName.trim()} | ${selectedRegion}، ${detailedAddress.trim()}`;

    try {
      setOrderLoading(true);
      setAuthError(null);

      // Create Order items payload
      const orderItems = cart.map(item => {
        const isWholesale = item.quantity >= item.product.wholesale_min_qty;
        const unitPrice = isWholesale 
          ? item.product.wholesale_price 
          : (item.product.sale_price || item.product.price);
        return {
          product_id: item.product.id,
          name: item.product.name,
          qty: item.quantity,
          price: unitPrice,
          pricing_type: isWholesale ? 'wholesale' : (item.product.sale_price ? 'sale' : 'regular')
        };
      });

      // Fetch supabase auth token to pass in headers
      const sessionRes = await supabase.auth.getSession();
      const token = sessionRes.data.session?.access_token;

      // 1. POST to serverless API endpoint to update loyalty points correctly on the server side
      const response = await fetch('/api/orders', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        },
        body: JSON.stringify({
          items: orderItems,
          total_price: finalTotal,
          delivery_address: combinedAddress,
          delivery_phone: phone,
          payment_method: paymentMethod,
          user_id: profile?.id || null
        })
      });

      if (!response.ok) {
        const errData = await response.json();
        throw new Error(errData.error || 'فشل إتمام عملية الشراء.');
      }

      const orderData = await response.json();
      const orderId = orderData.id;
      const displayId = orderId.substring(0, 8).toUpperCase();

      // 2. Decrement product stock in inventory
      for (const item of cart) {
        const remainingStock = Math.max(0, item.product.stock - item.quantity);
        await supabase
          .from('products')
          .update({ stock: remainingStock })
          .eq('id', item.product.id);
      }

      // 3. Update customer address and points in profile if points coupon was used
      if (profile) {
        let newPoints = profile.points || 0;
        const coupon = coupons.find(c => c.code === selectedCoupon);
        if (coupon && coupon.discount_type === 'points') {
          newPoints = Math.max(0, newPoints - coupon.points_cost);
        }
        await supabase
          .from('profiles')
          .update({ 
            address: `${selectedRegion}، ${detailedAddress.trim()}`,
            points: newPoints
          })
          .eq('id', profile.id);
      }

      // 3.5. Increment coupon usage count if a coupon was used
      if (selectedCoupon) {
        try {
          const { data: cp } = await supabase
            .from('coupons')
            .select('usage_count')
            .eq('code', selectedCoupon)
            .maybeSingle();

          const currentCount = cp?.usage_count || 0;
          await supabase
            .from('coupons')
            .update({ usage_count: currentCount + 1 })
            .eq('code', selectedCoupon);
        } catch (couponErr) {
          console.warn('Could not update coupon usage count:', couponErr);
        }
      }

      // 4. Update UI State
      setSuccessOrderId(displayId);
      setOrderSuccess(true);
      clearCart();
      await refreshProfile();

    } catch (err: any) {
      console.error('Error processing checkout:', err);
      setAuthError(err.message || 'حدث خطأ أثناء إتمام الطلب. يرجى المحاولة لاحقاً.');
    } finally {
      setOrderLoading(false);
    }
  };

  // Arabic order status mapping helper
  const getStatusArabic = (status: Order['status']) => {
    switch (status) {
      case 'pending': return 'قيد الانتظار';
      case 'preparing': return 'قيد التحضير';
      case 'delivering': return 'جاري التوصيل';
      case 'completed': return 'مكتمل';
    }
  };

  if (orderSuccess) {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col font-sans">
        <Navbar />
        <div className="flex-1 flex items-center justify-center p-4">
          <div className="max-w-md w-full bg-white border border-gray-150 rounded-3xl p-8 text-center shadow-md space-y-6 animate-in fade-in zoom-in-95 duration-200">
            <div className="w-16 h-16 bg-emerald-50 border-2 border-emerald-500 rounded-full flex items-center justify-center text-2xl text-emerald-600 mx-auto animate-bounce">
              ✓
            </div>
            <div>
              <h2 className="text-xl font-black text-gray-900">تم تسجيل طلبك بنجاح!</h2>
              <p className="text-sm font-bold text-primary mt-1.5">رقم الفاتورة: #{successOrderId}</p>
              <p className="text-xs text-gray-550 mt-2 leading-relaxed">
                تم تسجيل الفاتورة بنجاح في النظام! يمكنك الآن متابعة حالة التوصيل والطلب مباشرة من لوحة تحكم حسابك.
              </p>
            </div>
            <div className="flex flex-col gap-2 pt-2">
              <button
                onClick={() => router.push('/profile')}
                className="w-full py-3 bg-primary hover:bg-primary-dark text-white font-bold text-xs rounded-xl transition-all shadow-xs cursor-pointer"
              >
                تتبع حالة طلبك الفوري 🚚
              </button>
              <button
                onClick={() => router.push('/')}
                className="w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition-all cursor-pointer"
              >
                مواصلة التسوق 🛒
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <Navbar />

      <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full space-y-8">
        
        {/* Breadcrumb indicator */}
        <div className="flex items-center gap-2 text-xs font-bold text-gray-500 border-b border-gray-200 pb-3">
          <button onClick={() => router.push('/')} className="hover:text-primary transition-colors">الرئيسية</button>
          <ChevronRight size={12} />
          <span className="text-gray-950">إتمام الطلب والشحن</span>
        </div>

        {authError && (
          <div className="p-3.5 bg-red-50 border border-red-200 rounded-xl flex items-start gap-2.5 text-red-700 text-xs font-semibold">
            <AlertCircle size={16} className="text-accent flex-shrink-0 mt-0.5" />
            <p>{authError}</p>
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          {/* Right Column: Checkout & Authentication panels */}
          <div className="lg:col-span-7 bg-white border border-gray-150 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
            
            {/* STEP 1: AUTHENTICATION (Name & Phone Verification) */}
            {checkoutStep === 'auth' && (
              <div className="space-y-6">
                <div className="text-center space-y-1.5 pb-4 border-b border-gray-100">
                  <h2 className="text-lg font-black text-gray-900 flex items-center gap-2 justify-center">
                    تسجيل الدخول أو إنشاء حساب
                    <span className="p-1 bg-primary/10 text-primary rounded-lg">🔐</span>
                  </h2>
                  <p className="text-[11px] text-gray-550 max-w-sm mx-auto leading-relaxed">
                    يرجى تسجيل الدخول أو إنشاء حساب جديد لتتمكن من إتمام الطلب ومتابعة الشحنة.
                  </p>
                </div>

                {/* Tab Switcher */}
                <div className="flex bg-gray-100 p-1.5 rounded-2xl border border-gray-200/40 gap-1">
                  <button
                    type="button"
                    onClick={() => { setAuthTab('login'); setAuthError(null); }}
                    className={`flex-1 py-2.5 rounded-xl font-bold text-[11px] sm:text-xs transition-all duration-300 cursor-pointer ${
                      authTab === 'login' 
                        ? 'bg-white text-primary shadow-xs' 
                        : 'text-gray-500 hover:text-gray-800'
                    }`}
                  >
                    تسجيل دخول
                  </button>
                  <button
                    type="button"
                    onClick={() => { setAuthTab('register'); setAuthError(null); }}
                    className={`flex-1 py-2.5 rounded-xl font-bold text-[11px] sm:text-xs transition-all duration-300 cursor-pointer ${
                      authTab === 'register' 
                        ? 'bg-white text-primary shadow-xs' 
                        : 'text-gray-500 hover:text-gray-800'
                    }`}
                  >
                    إنشاء حساب
                  </button>
                  <button
                    type="button"
                    onClick={() => { setAuthTab('guest'); setAuthError(null); }}
                    className={`flex-1 py-2.5 rounded-xl font-bold text-[11px] sm:text-xs transition-all duration-300 cursor-pointer ${
                      authTab === 'guest' 
                        ? 'bg-white text-primary shadow-xs' 
                        : 'text-gray-500 hover:text-gray-800'
                    }`}
                  >
                    الشراء كزائر
                  </button>
                </div>

                {authTab === 'login' && (
                  <form onSubmit={handleLoginSubmit} className="space-y-4 text-right">
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                        رقم الهاتف للتواصل *
                        <Phone size={14} className="text-primary" />
                      </label>
                      <input
                        type="tel"
                        required
                        placeholder="أدخل رقم الموبايل الخاص بك"
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        className="w-full bg-gray-50 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-gray-900 transition-all"
                        dir="ltr"
                      />
                    </div>

                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                        أدخل كلمة المرور *
                        <Lock size={14} className="text-primary" />
                      </label>
                      <input
                        type="password"
                        required
                        placeholder="أدخل كلمة المرور الخاصة بك"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="w-full bg-gray-55 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-gray-900 transition-all"
                        dir="ltr"
                      />
                      <p className="text-[10px] text-gray-400 mt-1 font-semibold">
                        تلميح: إذا تم إنشاء حسابك تلقائياً عند طلبك السابق، كلمة المرور هي رقم هاتفك نفسه.
                      </p>
                    </div>

                    <button
                      type="submit"
                      disabled={authLoading}
                      className="w-full py-3 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer shadow-md hover:shadow-lg"
                    >
                      {authLoading ? <Loader2 size={14} className="animate-spin" /> : 'تسجيل الدخول والمتابعة'}
                    </button>
                  </form>
                )}

                {authTab === 'register' && (
                  <form onSubmit={handleRegisterSubmit} className="space-y-4 text-right">
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                        الاسم بالكامل *
                        <User size={14} className="text-primary" />
                      </label>
                      <input
                        type="text"
                        required
                        placeholder="اكتب اسمك الثلاثي للتسليم الفوري"
                        value={fullName}
                        onChange={(e) => setFullName(e.target.value)}
                        className="w-full bg-gray-55 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-gray-900 transition-all"
                      />
                    </div>

                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                        رقم الهاتف للتواصل *
                        <Phone size={14} className="text-primary" />
                      </label>
                      <input
                        type="tel"
                        required
                        placeholder="أدخل رقم الموبايل الخاص بك"
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        className="w-full bg-gray-50 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-gray-900 transition-all"
                        dir="ltr"
                      />
                    </div>

                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                        كلمة المرور *
                        <Lock size={14} className="text-primary" />
                      </label>
                      <input
                        type="password"
                        required
                        placeholder="اختر كلمة مرور لحسابك"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="w-full bg-gray-50 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-gray-900 transition-all"
                        dir="ltr"
                      />
                    </div>

                    <button
                      type="submit"
                      disabled={authLoading}
                      className="w-full py-3 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer shadow-md hover:shadow-lg"
                    >
                      {authLoading ? <Loader2 size={14} className="animate-spin" /> : 'إنشاء حساب جديد والمتابعة'}
                    </button>
                  </form>
                )}

                {authTab === 'guest' && (
                  <form onSubmit={handleGuestSubmit} className="space-y-4 text-right">
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                        الاسم بالكامل *
                        <User size={14} className="text-primary" />
                      </label>
                      <input
                        type="text"
                        required
                        placeholder="اكتب الاسم بالكامل للتسليم الفوري"
                        value={fullName}
                        onChange={(e) => setFullName(e.target.value)}
                        className="w-full bg-gray-55 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-gray-900 transition-all"
                      />
                    </div>

                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                        رقم الهاتف للتواصل *
                        <Phone size={14} className="text-primary" />
                      </label>
                      <input
                        type="tel"
                        required
                        placeholder="أدخل رقم الموبايل الخاص بك"
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        className="w-full bg-gray-55 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-gray-900 transition-all"
                        dir="ltr"
                      />
                    </div>

                    <button
                      type="submit"
                      className="w-full py-3 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer shadow-md hover:shadow-lg font-sans"
                    >
                      المتابعة كزائر لإدخال العنوان 🚚
                    </button>
                  </form>
                )}
              </div>
            )}

            {/* STEP 2: ADDRESS & SELECTION */}
            {checkoutStep === 'address' && (
              <div className="space-y-6">
                <div>
                  <h2 className="text-base font-black text-gray-900 flex items-center gap-2 justify-end text-right">
                    عنوان الشحن والتسليم
                    <span className="p-1 bg-primary/10 text-primary rounded-lg">📍</span>
                  </h2>
                </div>

                <form onSubmit={handleOrderSubmit} className="space-y-5 text-right">
                  {/* Prefilled Profile display */}
                  <div className="bg-gray-50 p-4 rounded-2xl border border-gray-150 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="space-y-1.5 text-right">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end font-sans">
                        اسم المستلم *
                        <User size={14} className="text-primary" />
                      </label>
                      <input
                        type="text"
                        required
                        placeholder="اسم المستلم"
                        value={fullName}
                        onChange={(e) => setFullName(e.target.value)}
                        className="w-full bg-white border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-primary text-gray-900 transition-all font-bold font-sans"
                      />
                    </div>
                    <div className="space-y-1.5 text-right">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end font-sans">
                        رقم هاتف المستلم *
                        <Phone size={14} className="text-primary" />
                      </label>
                      <input
                        type="tel"
                        required
                        placeholder="رقم هاتف المستلم"
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        className="w-full bg-white border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary text-gray-900 transition-all font-bold font-sans"
                        dir="ltr"
                      />
                    </div>
                  </div>

                  {/* Shipping Address */}
                  <div className="space-y-4">
                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                        منطقة الشحن والتوصيل *
                        <span className="text-primary">🚚</span>
                      </label>
                      <select
                        value={selectedRegion}
                        onChange={(e) => setSelectedRegion(e.target.value)}
                        className="w-full bg-gray-55 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-gray-900 transition-all font-bold cursor-pointer font-sans"
                      >
                        {shippingZones.map((zone) => (
                          <option key={zone.name} value={zone.name}>
                            {zone.name} ({isFreeShipping ? 'توصيل مجاني 🚚' : `${zone.price.toFixed(2)} ج.م`})
                          </option>
                        ))}
                      </select>
                    </div>

                    <div className="space-y-1.5">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                        تفاصيل العنوان بالكامل *
                        <MapPin size={14} className="text-primary" />
                      </label>
                      <textarea
                        required
                        rows={3}
                        placeholder="اكتب اسم الشارع، رقم العمارة أو المحل، وأي علامات مميزة قريبة للتسليم الفوري"
                        value={detailedAddress}
                        onChange={(e) => setDetailedAddress(e.target.value)}
                        className="w-full bg-gray-55 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-gray-900 transition-all leading-relaxed font-sans"
                      />
                    </div>
                  </div>

                  {/* Coupon section */}
                  {coupons.length > 0 && (
                    <div className="space-y-2 text-right">
                      <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                        كوبونات الخصم المتاحة لحسابك (اضغط للتطبيق)
                        <span className="text-primary">🎫</span>
                      </label>
                      <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        {coupons.map((coupon) => {
                          const isApplied = selectedCoupon === coupon.code;
                          const isPoints = coupon.discount_type === 'points';
                          const hasEnoughPoints = !isPoints || (profile?.points || 0) >= coupon.points_cost;
                          const minOrderMet = subtotal >= coupon.min_order_amount;
                          const isDisabled = (isPoints && !hasEnoughPoints) || !minOrderMet;
                          
                          return (
                            <button
                              key={coupon.code}
                              type="button"
                              disabled={isDisabled}
                              onClick={() => setSelectedCoupon(isApplied ? null : coupon.code)}
                              className={`p-2.5 border rounded-xl text-[10px] sm:text-xs font-bold transition-all text-center flex flex-col items-center justify-center cursor-pointer ${
                                isApplied
                                  ? 'border-primary bg-primary/5 text-primary shadow-xs font-black'
                                  : isDisabled
                                  ? 'border-gray-100 bg-gray-50 text-gray-300 opacity-60 cursor-not-allowed'
                                  : 'border-gray-250 bg-gray-50 hover:bg-gray-100 text-gray-750'
                              }`}
                            >
                              <span className="font-extrabold">{coupon.code}</span>
                              <span className="text-[9px] mt-0.5 line-clamp-1">{coupon.description}</span>
                              {!minOrderMet && (
                                <span className="text-[8px] text-accent mt-0.5">أقل طلب: {coupon.min_order_amount} ج.م</span>
                              )}
                              {isPoints && (
                                <span className="text-[8px] text-amber-600 mt-0.5">تكلفة: {coupon.points_cost} نقطة</span>
                              )}
                            </button>
                          );
                        })}
                      </div>
                    </div>
                  )}

                  {/* Payment selections */}
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-gray-700 flex items-center gap-1.5 justify-end">
                      طريقة الدفع
                      <CreditCard size={14} className="text-primary" />
                    </label>
                    <div className="p-3 bg-gray-50 border border-primary/20 rounded-xl flex items-center gap-3 cursor-pointer">
                      <input
                        type="radio"
                        checked
                        readOnly
                        className="text-primary focus:ring-primary"
                      />
                      <div>
                        <p className="text-xs font-bold text-gray-900">الدفع عند الاستلام (COD)</p>
                        <p className="text-[10px] text-gray-500 mt-0.5">ادفع كاش للمندوب فور وصول الشحنة لباب محلك أو بيتك</p>
                      </div>
                    </div>
                  </div>

                  {/* Submit checkout CTA */}
                  <div className="pt-2">
                    <button
                      type="submit"
                      disabled={orderLoading}
                      className="w-full py-3.5 bg-primary hover:bg-primary-dark text-white font-black text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
                    >
                      {orderLoading ? (
                        <>
                          <Loader2 size={14} className="animate-spin" />
                          جاري تسجيل طلبك...
                        </>
                      ) : (
                        'تأكيد الطلب'
                      )}
                    </button>
                  </div>
                </form>
              </div>
            )}

          </div>

          {/* Left Column: Cart pricing card */}
          <div className="lg:col-span-5 bg-white border border-gray-155 rounded-3xl p-6 shadow-xs space-y-5">
            <h3 className="text-xs font-black text-gray-900 border-b border-gray-50 pb-3 flex items-center justify-between">
              <span className="flex items-center gap-2">
                <ShoppingBag size={16} className="text-primary" />
                ملخص السلة
              </span>
              <span className="bg-primary/5 text-primary text-xs px-2 py-0.5 rounded-full">
                {cart.reduce((a,c) => a + c.quantity, 0)} قطعة
              </span>
            </h3>

            <div className="divide-y divide-gray-50 max-h-48 overflow-y-auto pr-1">
              {cart.map((item) => {
                const isWholesale = item.quantity >= item.product.wholesale_min_qty;
                const unitPrice = isWholesale ? item.product.wholesale_price : (item.product.sale_price || item.product.price);
                return (
                  <div key={item.product.id} className="py-2.5 flex justify-between gap-4 text-right text-xs">
                    <div>
                      <p className="font-bold text-gray-900 line-clamp-1">{item.product.name}</p>
                      <p className="text-[10px] text-gray-400 mt-0.5 font-semibold">
                        الكمية: {item.quantity} × {unitPrice.toFixed(2)} ج.م
                        {isWholesale && <span className="bg-amber-50 text-amber-800 text-[8px] font-black px-1 rounded-sm border border-amber-250 mr-1.5 inline-block">سعر جملة</span>}
                      </p>
                    </div>
                    <span className="font-bold text-gray-900 whitespace-nowrap">{(unitPrice * item.quantity).toFixed(2)} ج.م</span>
                  </div>
                );
              })}
            </div>

            <div className="border-t border-gray-100 pt-3.5 space-y-2.5 text-xs text-gray-600 font-semibold">
              <div className="flex justify-between">
                <span>إجمالي الأصناف:</span>
                <span className="text-gray-900">{subtotal.toFixed(2)} ج.م</span>
              </div>
              {activeDiscount > 0 && (
                <div className="flex justify-between text-accent font-bold">
                  <span>الخصم المطبق ({selectedCoupon}):</span>
                  <span>-{activeDiscount.toFixed(2)} ج.م</span>
                </div>
              )}
              <div className="flex justify-between">
                <span>رسوم شحن الناصرية والعامرية:</span>
                {activeShippingFee === 0 ? (
                  <span className="text-primary font-bold">توصيل مجاني 🚚</span>
                ) : (
                  <span className="text-gray-900 font-bold">{activeShippingFee.toFixed(2)} ج.م</span>
                )}
              </div>
              <div className="border-t border-gray-100 pt-3 flex justify-between items-baseline font-black text-sm text-gray-900">
                <span>المبلغ المستحق:</span>
                <span className="text-lg font-black text-primary">{finalTotal.toFixed(2)} <span className="text-xs font-bold text-gray-600">ج.م</span></span>
              </div>
            </div>
          </div>

        </div>

        {/* 4. Past Orders Dashboard Panel (Visible only when logged in) */}
        {profile && pastOrders.length > 0 && (
          <div className="bg-white border border-gray-150 rounded-3xl p-6 sm:p-8 shadow-xs space-y-4 animate-in fade-in duration-200">
            <h3 className="text-sm font-black text-gray-900 border-b border-gray-50 pb-3.5 flex items-center gap-2 justify-end text-right">
              طلباتك السابقة والمحفوظة
              <Clock size={16} className="text-primary" />
            </h3>

            <div className="space-y-4">
              {pastOrders.map((ord) => (
                <div key={ord.id} className="border border-gray-100 rounded-2xl p-4 bg-gray-50 text-right text-xs space-y-3 font-semibold text-gray-600">
                  <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-gray-200/50 pb-2">
                    <div className="flex items-center gap-2">
                      <span className="font-bold text-gray-900">رقم الفاتورة:</span>
                      <span className="bg-primary/10 text-primary font-black px-2 py-0.5 rounded">#{ord.id.substring(0, 8).toUpperCase()}</span>
                    </div>
                    <p className="text-[10px] text-gray-450">بتاريخ: {new Date(ord.created_at).toLocaleDateString('ar-EG')}</p>
                  </div>
                  
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 leading-relaxed">
                    <div>
                      <p className="text-gray-900 font-bold mb-1">الأصناف المطلوبة:</p>
                      <ul className="list-disc list-inside text-gray-500 space-y-0.5 text-[11px] pr-2">
                        {ord.items.map((it: any, idx: number) => (
                          <li key={idx}>
                            {it.name} (عدد: {it.qty}) - {it.price.toFixed(2)} ج.م
                          </li>
                        ))}
                      </ul>
                    </div>
                    <div className="sm:text-left flex flex-col justify-end space-y-1">
                      <p>عنوان التوصيل: <span className="text-gray-900">{ord.delivery_address}</span></p>
                      <p>حالة التوصيل: <span className="bg-primary/5 text-primary px-2 py-0.5 rounded-full text-[10px] font-black">{getStatusArabic(ord.status)}</span></p>
                      <p className="text-sm font-black text-primary mt-1">الإجمالي: {ord.total_price.toFixed(2)} ج.م</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

      </main>
    </div>
  );
}

export default function CheckoutPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center space-y-4">
          <div className="w-12 h-12 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="text-sm font-bold text-gray-600">تحميل صفحة الدفع والتحقق...</p>
        </div>
      </div>
    }>
      <CheckoutContent />
    </Suspense>
  );
}
