'use client';

import React, { useState, useEffect, Suspense } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import Navbar from '@/components/Navbar';
import Footer from '@/components/Footer';
import ProductCard from '@/components/ProductCard';
import ScrollReveal from '@/components/ScrollReveal';
import Canvas3DGrid from '@/components/Canvas3DGrid';
import { Product, Category, Order, Banner, Coupon } from '@/types';
import { supabase } from '@/lib/supabase';
import { mockCategories, mockProducts } from '@/lib/mockData';
import { useCart } from '@/context/CartContext';
import { 
  MapPin, 
  Phone, 
  ShoppingBag, 
  Send,
  Sparkles
} from 'lucide-react';

function HomeContent() {
  const { profile } = useCart();
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [banners, setBanners] = useState<Banner[]>([]);
  const [coupons, setCoupons] = useState<Coupon[]>([]);
  const [latestOrder, setLatestOrder] = useState<Order | null>(null);
  const [userOrders, setUserOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [layoutMode, setLayoutMode] = useState<'grid' | 'list'>('grid');
  
  const [currentSlide, setCurrentSlide] = useState(0);
  const [copiedCode, setCopiedCode] = useState<string | null>(null);
  
  const router = useRouter();

  const handleCopyCode = (code: string) => {
    navigator.clipboard.writeText(code);
    setCopiedCode(code);
    setTimeout(() => setCopiedCode(null), 2000);
  };

  // Fetch the latest order status and history for logged-in users
  useEffect(() => {
    if (profile) {
      // Fetch latest order for active tracking status
      supabase
        .from('orders')
        .select('*')
        .eq('user_id', profile.id)
        .order('created_at', { ascending: false })
        .limit(1)
        .maybeSingle()
        .then(({ data }) => {
          if (data) setLatestOrder(data as Order);
        });

      // Fetch all past orders to build the personalized recommendation feed
      supabase
        .from('orders')
        .select('*')
        .eq('user_id', profile.id)
        .order('created_at', { ascending: false })
        .then(({ data }) => {
          if (data) setUserOrders(data as Order[]);
        });
    } else {
      setLatestOrder(null);
      setUserOrders([]);
    }
  }, [profile]);

  useEffect(() => {
    async function loadData() {
      try {
        setLoading(true);
        const { data: dbCats } = await supabase
          .from('categories')
          .select('*')
          .order('importance_score', { ascending: false });
          
        const { data: dbProds } = await supabase
          .from('products')
          .select('*')
          .eq('is_available', true)
          .order('importance_score', { ascending: false });

        const { data: dbBanners } = await supabase
          .from('banners')
          .select('*')
          .order('created_at', { ascending: false });

        const { data: dbCoupons } = await supabase
          .from('coupons')
          .select('*')
          .eq('is_active', true);

        if (dbCats && dbCats.length > 0) {
          setCategories(dbCats as Category[]);
        } else {
          setCategories(mockCategories);
        }

        if (dbProds && dbProds.length > 0) {
          const normalizedProds = dbProds.map(p => ({
            ...p,
            price: Number(p.price),
            sale_price: p.sale_price !== null ? Number(p.sale_price) : null,
            wholesale_price: Number(p.wholesale_price)
          })) as Product[];
          setProducts(normalizedProds);
        } else {
          setProducts(mockProducts);
        }

        if (dbBanners && dbBanners.length > 0) {
          setBanners(dbBanners as Banner[]);
        } else {
          setBanners([
            { id: 'slide-1', title: 'خصومات تصل إلى 30% على منتجات الألبان والأرز', image_url: 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=[...]
            { id: 'slide-2', title: 'شحن مجاني للعامرية والناصرية للطلبات فوق 800 جنيه', image_url: 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d[...]
            { id: 'slide-3', title: 'وفر أكتر مع أسعار الجملة لكرتونة المجمدات والمنظفات', image_url: 'https://images.unsplash.com/photo-1578916171728-46686[...]
          ]);
        }

        if (dbCoupons && dbCoupons.length > 0) {
          setCoupons(dbCoupons as Coupon[]);
        } else {
          setCoupons([
            { code: 'ARZ15', description: 'خصم 15% على إجمالي السلة', discount_type: 'percentage', discount_value: 15, min_order_amount: 0, points_cost: 0, is_active: true },
            { code: 'GHARIB50', description: 'خصم بقيمة 50 جنيه للطلبات بقيمة 300 جنيه أو أكثر', discount_type: 'fixed', discount_value: 50, min_order_amount: 300, po[...]
            { code: 'POINTS100', description: 'خصم بقيمة 100 جنيه مقابل 100 نقطة ذهبية من رصيدك', discount_type: 'points', discount_value: 100, min_order_amount: 0, po[...]
          ]);
        }
      } catch (err) {
        console.error('Failed to load store data, using mock data fallback', err);
        setCategories(mockCategories);
        setProducts(mockProducts);
        setBanners([
          { id: 'slide-1', title: 'خصومات تصل إلى 30% على منتجات الألبان والأرز', image_url: 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=fo[...]
          { id: 'slide-2', title: 'شحن مجاني للعامرية والناصرية للطلبات فوق 800 جنيه', image_url: 'https://images.unsplash.com/photo-1586528116311-ad8dd3c83[...]
          { id: 'slide-3', title: 'وفر أكتر مع أسعار الجملة لكرتونة المجمدات والمنظفات', image_url: 'https://images.unsplash.com/photo-1578916171728-46[...]
        ]);
        setCoupons([
          { code: 'ARZ15', description: 'خصم 15% على إجمالي السلة', discount_type: 'percentage', discount_value: 15, min_order_amount: 0, points_cost: 0, is_active: true },
          { code: 'GHARIB50', description: 'خصم بقيمة 50 جنيه للطلبات بقيمة 300 جنيه أو أكثر', discount_type: 'fixed', discount_value: 50, min_order_amount: 300, poin[...]
          { code: 'POINTS100', description: 'خصم بقيمة 100 جنيه مقابل 100 نقطة ذهبية من رصيدك', discount_type: 'points', discount_value: 100, min_order_amount: 0, poin[...]
        ]);
      } finally {
        setLoading(false);
      }
    }
    loadData();
  }, []);

  useEffect(() => {
    if (banners.length <= 1) return;
    const interval = setInterval(() => {
      setCurrentSlide(prev => (prev + 1) % banners.length);
    }, 5000);
    return () => clearInterval(interval);
  }, [banners]);

  // Personalized feed & recommendation engine
  const recommendations = React.useMemo(() => {
    if (!profile || products.length === 0) return { personalizedFeed: [], buyItAgain: [] };

    // 1. Calculate frequency of each product purchased
    const productPurchaseCount: Record<string, number> = {};
    userOrders.forEach(order => {
      const items = Array.isArray(order.items) ? order.items : [];
      items.forEach((item: any) => {
        if (item.product_id) {
          productPurchaseCount[item.product_id] = (productPurchaseCount[item.product_id] || 0) + (item.qty || 1);
        }
      });
    });

    // 2. Calculate category affinity (total purchases per category)
    const categoryPurchaseCount: Record<string, number> = {};
    const productMap = new Map<string, Product>();
    products.forEach(p => productMap.set(p.id, p));

    Object.entries(productPurchaseCount).forEach(([productId, qty]) => {
      const product = productMap.get(productId);
      if (product && product.category_id) {
        categoryPurchaseCount[product.category_id] = (categoryPurchaseCount[product.category_id] || 0) + qty;
      }
    });

    // 3. Score every available product
    const scoredProducts = products
      .filter(p => p.is_available)
      .map(product => {
        // Baseline score: global importance score
        let score = Number(product.importance_score || 0) * 0.1;

        // Boost 1: Category Affinity (up to 100 points)
        if (product.category_id && categoryPurchaseCount[product.category_id]) {
          score += Math.min(100, categoryPurchaseCount[product.category_id] * 15);
        }

        // Boost 2: Repeat Purchases (high repeat purchase frequency gets prioritized)
        const hasBought = productPurchaseCount[product.id];
        if (hasBought) {
          score += hasBought * 40;
        }

        return { product, score, hasBought: !!hasBought };
      });

    // 4. "Buy It Again" feed (previously purchased products sorted by frequency)
    const buyItAgain = scoredProducts
      .filter(item => item.hasBought)
      .sort((a, b) => (productPurchaseCount[b.product.id] || 0) - (productPurchaseCount[a.product.id] || 0))
      .map(item => item.product);

    // 5. "Personalized Feed" (discovery and preference matching sorted by recommendation score)
    const personalizedFeed = scoredProducts
      .sort((a, b) => b.score - a.score)
      .map(item => item.product);

    return { personalizedFeed, buyItAgain };
  }, [profile, products, userOrders]);

  const featuredProducts = products.slice(0, 8);

  return (
    <div className="flex-1 flex flex-col min-h-screen bg-slate-50 font-sans text-right">
      <Navbar />

      {/* Dynamic Banner Slider */}
      {profile && banners.length > 0 && (
        <ScrollReveal className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 w-full">
          <div className="relative rounded-3xl overflow-hidden shadow-xs h-48 sm:h-72 lg:h-96 group bg-slate-900">
            {/* Slides */}
            {banners.map((banner, index) => (
              <div
                key={banner.id}
                className={`absolute inset-0 transition-opacity duration-1000 ease-in-out ${
                  index === currentSlide ? 'opacity-100 z-10' : 'opacity-0 z-0'
                }`}
              >
                <img
                  src={banner.image_url}
                  alt={banner.title}
                  className="w-full h-full object-cover opacity-85 scale-102 transition-transform duration-700 hover:scale-105"
                />
                {/* Gradient overlay */}
                <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-black/10 flex flex-col justify-end p-6 sm:p-12 text-right" />
                
                {/* Slide content */}
                <div className="absolute bottom-6 right-6 left-6 sm:bottom-12 sm:right-12 sm:left-12 z-20 text-white space-y-2 sm:space-y-4 max-w-xl">
                  <h2 className="text-sm sm:text-2xl lg:text-3xl font-black drop-shadow-md leading-tight">
                    {banner.title}
                  </h2>
                  <div className="pt-1 sm:pt-2">
                    <Link
                      href={banner.link_url}
                      className="bg-primary hover:bg-primary-dark text-white font-extrabold px-4 py-2 sm:px-6 sm:py-2.5 rounded-xl text-[10px] sm:text-xs shadow-md transition-all inline-block hover:-t[...]
                    >
                      اكتشف العرض الآن 🛒
                    </Link>
                  </div>
                </div>
              </div>
            ))}

            {/* Navigation Arrows */}
            {banners.length > 1 && (
              <>
                <button
                  onClick={() => setCurrentSlide(prev => (prev - 1 + banners.length) % banners.length)}
                  className="absolute left-4 top-1/2 -translate-y-1/2 z-20 bg-white/20 hover:bg-white/40 text-white w-8 h-8 rounded-full flex items-center justify-center backdrop-blur-xs transition-al[...]
                >
                  ❮
                </button>
                <button
                  onClick={() => setCurrentSlide(prev => (prev + 1) % banners.length)}
                  className="absolute right-4 top-1/2 -translate-y-1/2 z-20 bg-white/20 hover:bg-white/40 text-white w-8 h-8 rounded-full flex items-center justify-center backdrop-blur-xs transition-a[...]
                >
                  ❯
                </button>
              </>
            )}

            {/* Navigation Dots */}
            {banners.length > 1 && (
              <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex gap-2">
                {banners.map((_, index) => (
                  <button
                    key={index}
                    onClick={() => setCurrentSlide(index)}
                    className={`w-2 h-2 rounded-full transition-all cursor-pointer ${
                      index === currentSlide ? 'bg-primary w-6' : 'bg-white/50'
                    }`}
                  />
                ))}
              </div>
            )}
          </div>
        </ScrollReveal>
      )}

      {/* 1. Hero Section (Clean Light Grey & Vegetable/Grains Pile Style) */}
      {!profile && (
        <section className="relative overflow-hidden text-slate-900 py-24 sm:py-32 bg-white border-b border-slate-200/50">
          {/* Premium backdrop image */}
          <div 
            className="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-75 scale-102 transition-all duration-1000"
            style={{ backgroundImage: "url('/hero-bg.png')" }}
          />
          
          {/* Light overlay for clean reading */}
          <div className="absolute inset-0 bg-gradient-to-b from-white/90 via-white/85 to-white/95 backdrop-blur-[0.5px] pointer-events-none" />

          {/* 3D Grid floor overlay */}
          <Canvas3DGrid opacity={0.65} />

          {/* Dynamic green gradient glows */}
          <div className="absolute top-1/4 -right-20 w-80 h-80 bg-primary/10 rounded-full blur-3xl pointer-events-none" />
          <div className="absolute bottom-1/4 -left-20 w-80 h-80 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none" />

          <div className="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center flex flex-col items-center space-y-6 z-10">
            
            {/* Brand logo container */}
            <div className="relative w-24 h-24 sm:w-28 sm:h-28 rounded-full overflow-hidden border-4 border-primary shadow-2xl bg-white p-1 hover:rotate-6 transition-transform duration-500">
              <img 
                src="/logo.jpeg" 
                alt="الناصريه جمله ماركت"
                className="w-full h-full object-cover rounded-full"
              />
            </div>

            {/* Brand titles */}
            <div className="space-y-3 max-w-2xl">
              <h1 className="text-3xl sm:text-5xl font-black tracking-tight leading-tight text-slate-900 drop-shadow-xs">
                الناصرية جملة ماركت
              </h1>
              <p className="text-xs sm:text-sm font-extrabold text-primary tracking-widest uppercase bg-primary/10 border border-primary/20 px-4 py-1.5 rounded-full inline-block mt-2">
                منبع الجملة والأسعار المميزة لغرب الإسكندرية 👑
              </p>
            </div>

            <p className="text-xs sm:text-sm text-slate-600 max-w-md font-bold leading-relaxed">
              كل الي هتحلم بيه هتلاقيه اعلا جوده باقل سعر
            </p>

            {/* Shop Now button */}
            <div className="pt-2">
              <Link
                href="/products"
                className="bg-primary hover:bg-primary-dark text-white font-extrabold px-10 py-3.5 rounded-xl shadow-lg hover:shadow-primary/25 transition-all duration-300 transform hover:-translate-y[...]
              >
                تسوّق الكتالوج بالكامل 🛒
              </Link>
            </div>

          </div>
        </section>
      )}

      {/* 2. Brand Value Cards Section */}
      {!profile && (
        <section className="py-12 bg-white border-b border-slate-200/60">
          <ScrollReveal className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              
              <div className="bg-slate-50/50 border border-slate-100 p-6 rounded-2xl flex flex-col items-center md:items-start text-center md:text-right gap-3 shadow-xs hover:shadow-lg transition-all [...]
                <span className="text-2xl p-3 bg-primary/10 text-primary rounded-2xl group-hover:bg-primary group-hover:text-white transition-colors duration-300">📦</span>
                <div>
                  <h3 className="font-black text-slate-900 text-sm">توزيع المواد الغذائية والمجمدات</h3>
                  <p className="text-[10px] text-slate-500 mt-1 font-semibold leading-relaxed">تغطية شاملة لقطاع الفنادق، المطاعم، ومحلات التجزئة بأعلى[...]
                </div>
              </div>

              <div className="bg-slate-50/50 border border-slate-100 p-6 rounded-2xl flex flex-col items-center md:items-start text-center md:text-right gap-3 shadow-xs hover:shadow-lg transition-all [...]
                <span className="text-2xl p-3 bg-primary/10 text-primary rounded-2xl group-hover:bg-primary group-hover:text-white transition-colors duration-300">👑</span>
                <div>
                  <h3 className="font-black text-slate-900 text-sm">أسعار الجملة للجميع</h3>
                  <p className="text-[10px] text-slate-500 mt-1 font-semibold leading-relaxed">نوفر للبيوت والمحلات التجارية ميزة التسوق بسعر الجملة اب�[...]
                </div>
              </div>

              <div className="bg-slate-50/50 border border-slate-100 p-6 rounded-2xl flex flex-col items-center md:items-start text-center md:text-right gap-3 shadow-xs hover:shadow-lg transition-all [...]
                <span className="text-2xl p-3 bg-primary/10 text-primary rounded-2xl group-hover:bg-primary group-hover:text-white transition-colors duration-300">🏛️</span>
                <div>
                  <h3 className="font-black text-slate-900 text-sm">مؤسسة الناصرية التجارية</h3>
                  <p className="text-[10px] text-slate-500 mt-1 font-semibold leading-relaxed">الوجهة الرسمية الموثوقة لخدمة أهالي الناصرية والعامرية ب[...]
                </div>
              </div>

            </div>
          </ScrollReveal>
        </section>
      )}

      {/* Member-Only Authenticated Dashboard Panel */}
      {profile && (
        <section className="py-8 bg-slate-50 border-b border-slate-200/60 text-right">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            {/* Status bar */}
            <div className="flex flex-col sm:flex-row items-center justify-between gap-4 border-b border-slate-200 pb-4">
              <div className="flex items-center gap-2 flex-row-reverse">
                <span className="text-sm font-black text-slate-800">مرحباً بك، {profile.full_name.split(' ')[0]} 👋</span>
                <span className="bg-amber-100 text-amber-850 px-2.5 py-0.5 rounded-full text-[10px] font-black">⭐ {profile.points || 0} نقطة ذهبية</span>
              </div>
              <div className="text-[11px] text-slate-500 font-bold">لوحة العروض والتخفيضات للأعضاء</div>
            </div>

            {/* Active Order Alert Banner */}
            {latestOrder && latestOrder.status !== 'completed' && (
              <div className="bg-emerald-50 border border-emerald-250 rounded-3xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4 text-right shadow-xs">
                <div className="flex items-center gap-3 justify-end flex-row-reverse">
                  <div className="p-2.5 bg-emerald-600/10 text-emerald-650 rounded-2xl animate-pulse">
                    📦
                  </div>
                  <div>
                    <h4 className="text-xs font-black text-slate-800">طلبك رقم #{latestOrder.id.substring(0,8).toUpperCase()} في حالة نشطة!</h4>
                    <p className="text-[10px] text-slate-500 font-bold mt-0.5">الحالة الحالية: {
                      latestOrder.status === 'pending' ? 'قيد الانتظار والمراجعة ⏳' :
                      latestOrder.status === 'preparing' ? 'قيد التحضير والتعبئة 📦' :
                      'جاري التوصيل لباب بيتك 🚚'
                    }</p>
                  </div>
                </div>
                <Link 
                  href="/profile" 
                  className="bg-primary hover:bg-primary-dark text-white text-[10px] font-extrabold px-5 py-2.5 rounded-xl transition-all shadow-xs cursor-pointer whitespace-nowrap block"
                >
                  تتبع طلبك الآن 🚚
                </Link>
              </div>
            )}

            {/* Exclusive Coupons Grid */}
            <div className="space-y-4">
              <h3 className="text-xs font-black text-slate-450 uppercase tracking-wider">كوبونات الخصم الحصرية لك 🎫</h3>
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                {coupons.map((coupon) => (
                  <div 
                    key={coupon.code}
                    className="bg-white border-2 border-dashed border-primary/20 p-3 sm:p-4 rounded-2xl flex items-center gap-2.5 sm:gap-3 shadow-xs hover:border-primary/45 transition-all relative ove[...]
                  >
                    {/* Dashed ticket stub cutouts */}
                    <div className="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-4 bg-slate-50 border-r border-slate-200/50 rounded-full" />
                    <div className="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 bg-slate-50 border-l border-slate-200/50 rounded-full" />
                    <div className="w-8 h-8 sm:w-10 sm:h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center text-[10px] sm:text-xs font-black flex-shrink-0">
                      {coupon.discount_type === 'percentage' ? `${coupon.discount_value}%` : coupon.discount_type === 'points' ? '⭐' : 'ج.م'}
                    </div>
                    <div className="flex-1 text-right min-w-0 pr-1 sm:pr-2">
                      <h4 className="font-bold text-slate-800 text-[10px] sm:text-xs truncate">{coupon.code}</h4>
                      <p className="text-[8px] sm:text-[9.5px] text-slate-400 mt-0.5 line-clamp-1">{coupon.description}</p>
                    </div>
                    <button
                      onClick={() => handleCopyCode(coupon.code)}
                      className={`absolute left-2 bottom-1.5 px-2 py-0.5 rounded text-[8px] font-black transition-all ${
                        copiedCode === coupon.code ? 'bg-emerald-600 text-white opacity-100' : 'bg-primary/10 text-primary hover:bg-primary hover:text-white opacity-0 group-hover:opacity-100'
                      }`}
                    >
                      {copiedCode === coupon.code ? 'تم النسخ ✓' : 'نسخ الكود'}
                    </button>
                  </div>
                ))}
              </div>
            </div>

            {/* Member-Only Flash Deals */}
            {products.filter(p => p.sale_price !== null && p.sale_price !== undefined && p.sale_price > 0).length > 0 && (
              <div className="space-y-4 pt-4 border-t border-slate-200/50">
                <div className="flex items-center justify-between">
                  <Link href="/products?search=خصم" className="text-[10px] text-primary hover:underline font-bold">عرض جميع التخفيضات</Link>
                  <h3 className="text-xs font-black text-slate-450 uppercase tracking-wider flex items-center gap-1.5">
                    عروض وتخفيضات اليوم الحصرية لأعضاء جملة ماركت 🔥
                  </h3>
                </div>
                <div className={`grid gap-4 sm:gap-6 ${
                  layoutMode === 'list' 
                    ? 'grid-cols-1 md:grid-cols-2' 
                    : 'grid-cols-2 sm:grid-cols-2 lg:grid-cols-4'
                }`}>
                  {products.filter(p => p.sale_price !== null && p.sale_price !== undefined && p.sale_price > 0).slice(0, 4).map((prod) => (
                    <ProductCard key={prod.id} product={prod} layout={layoutMode} />
                  ))}
                </div>
              </div>
            )}

            {/* Buy It Again Section */}
            {profile && recommendations.buyItAgain.length > 0 && (
              <div className="space-y-4 pt-6 border-t border-slate-200/50">
                <h3 className="text-xs font-black text-slate-450 uppercase tracking-wider flex items-center gap-1.5 justify-end">
                  أصناف تشتريها بشكل متكرر (أعد الشراء) 🔁
                </h3>
                <div className="flex gap-4 overflow-x-auto pb-3 scrollbar-none flex-row-reverse">
                  {recommendations.buyItAgain.slice(0, 10).map((prod) => (
                    <div key={prod.id} className="w-48 flex-shrink-0">
                      <ProductCard product={prod} layout="grid" />
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Personalized Recommendations Feed */}
            {profile && (
              <div className="space-y-4 pt-6 border-t border-slate-200/50">
                <div className="flex items-center justify-between">
                  <span className="text-[10px] text-primary font-bold">
                    {userOrders.length > 0 ? '✨ مخصصة بناءً على مشترياتك السابقة' : '🔥 المنتجات الأكثر مبيعاً اليوم'}
                  </span>
                  <h3 className="text-xs font-black text-slate-450 uppercase tracking-wider flex items-center gap-1.5">
                    مقترحات خاصة لك اليوم 🎯
                  </h3>
                </div>
                <div className={`grid gap-4 sm:gap-6 ${
                  layoutMode === 'list' 
                    ? 'grid-cols-1 md:grid-cols-2' 
                    : 'grid-cols-2 sm:grid-cols-2 lg:grid-cols-4'
                }`}>
                  {recommendations.personalizedFeed.slice(0, 4).map((prod) => (
                    <ProductCard key={prod.id} product={prod} layout={layoutMode} />
                  ))}
                </div>
              </div>
            )}

          </div>
        </section>
      )}

      {/* 3. Categories Circular Navigation Grid ("عروض الأسبوع") */}
      <section className="py-12 bg-white border-b border-slate-200/60">
        <ScrollReveal className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          
          <div className="text-center mb-8">
            <h2 className="text-lg sm:text-xl font-black text-slate-900">عروض الأسبوع والأقسام</h2>
            <p className="text-[11px] text-slate-500 mt-1 font-bold">تصفح سلع الجملة والمجمدات والمنظفات مباشرة</p>
          </div>

          {/* Circular Category Pills */}
          <div className="flex items-center justify-center gap-5 overflow-x-auto pb-4 scrollbar-none">
            <button
              onClick={() => router.push('/products')}
              className="flex flex-col items-center gap-2.5 flex-shrink-0 group focus:outline-none cursor-pointer"
            >
              <div className="w-16 h-16 sm:w-18 sm:h-18 rounded-full border border-slate-200 group-hover:border-primary flex items-center justify-center p-1.5 transition-all duration-300 bg-white shad[...]
                <div className="w-full h-full bg-primary/10 rounded-full flex items-center justify-center text-primary text-xs font-black">الكل</div>
              </div>
              <span className="text-xs font-black text-slate-700 group-hover:text-primary transition-colors">عرض الكل</span>
            </button>

            {categories.map((cat) => (
              <button
                key={cat.id}
                onClick={() => router.push(`/products?category=${cat.slug || cat.id}`)}
                className="flex flex-col items-center gap-2.5 flex-shrink-0 group focus:outline-none cursor-pointer"
              >
                <div className="w-16 h-16 sm:w-18 sm:h-18 rounded-full border border-slate-200 group-hover:border-primary flex items-center justify-center overflow-hidden p-1 transition-all duration-3[...]
                  <img 
                    src={cat.image_url || 'https://images.unsplash.com/photo-1534482421-64566f976cfa?w=100'} 
                    alt={cat.name}
                    className="w-full h-full object-cover rounded-full group-hover:scale-105 transition-transform duration-300"
                  />
                </div>
                <span className="text-xs font-black text-slate-700 group-hover:text-primary transition-colors">{cat.name}</span>
              </button>
            ))}
          </div>

        </ScrollReveal>
      </section>

      {/* 3.5. Coupons / Discount Codes Section */}
      <section className="py-12 bg-slate-50 border-b border-slate-200/60 text-right">
        <ScrollReveal className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-8">
            <h2 className="text-lg sm:text-xl font-black text-slate-900">كوبونات الخصم وعروض اليوم 🎫</h2>
            <p className="text-[11px] text-slate-500 mt-1 font-bold">انسخ الكود المفضل لديك واستخدمه في صفحة إتمام الطلب لتوفير فوري</p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {coupons.map((coupon) => (
              <div 
                key={coupon.code}
                className="bg-white border-2 border-dashed border-primary/20 p-5 rounded-3xl flex flex-col justify-between shadow-xs hover:shadow-md transition-all relative overflow-hidden group"
              >
                {/* Dashed ticket stub cutouts */}
                <div className="absolute -left-3.5 top-1/2 -translate-y-1/2 w-7 h-7 bg-slate-50 border-r border-slate-250 rounded-full" />
                <div className="absolute -right-3.5 top-1/2 -translate-y-1/2 w-7 h-7 bg-slate-50 border-l border-slate-250 rounded-full" />

                <div className="flex items-start justify-between flex-row-reverse gap-4">
                  <div className="p-3 bg-primary/10 text-primary rounded-2xl text-lg flex-shrink-0 font-black">
                    {coupon.discount_type === 'percentage' ? `${coupon.discount_value}%` : coupon.discount_type === 'points' ? '⭐' : 'ج.م'}
                  </div>
                  <div className="flex-1 space-y-1">
                    <h4 className="font-black text-slate-800 text-sm flex items-center gap-1.5 justify-end">
                      <code className="bg-slate-50 border border-slate-100 px-2 py-0.5 rounded font-black text-primary text-xs">{coupon.code}</code>
                    </h4>
                    <p className="text-xs text-slate-500 font-bold leading-normal">{coupon.description}</p>
                    {coupon.min_order_amount > 0 && (
                      <p className="text-[10px] text-amber-600 font-bold">الحد الأدنى للطلب: {coupon.min_order_amount} ج.م</p>
                    )}
                  </div>
                </div>

                <div className="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                  <span className="text-[9px] text-slate-400 font-bold">كوبون فعال وموثوق ✓</span>
                  <button
                    onClick={() => handleCopyCode(coupon.code)}
                    className={`px-4 py-1.5 rounded-xl text-[10px] font-black transition-all cursor-pointer ${
                      copiedCode === coupon.code
                        ? 'bg-emerald-600 text-white'
                        : 'bg-primary/10 text-primary hover:bg-primary hover:text-white'
                    }`}
                  >
                    {copiedCode === coupon.code ? 'تم النسخ ✓' : 'نسخ الكود 📋'}
                  </button>
                </div>
              </div>
            ))}
          </div>
        </ScrollReveal>
      </section>

      {/* 4. Most Selling Products Grid ("المنتجات الأكثر مبيعاً") */}
      <section id="catalog" className="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <ScrollReveal>
        
        <div className="flex flex-col sm:flex-row items-center justify-between border-b border-slate-200 pb-4 mb-8 gap-4">
          {/* Layout Toggle Buttons */}
          <div className="flex bg-slate-100 p-1 rounded-xl border border-slate-200/50">
            <button
              onClick={() => setLayoutMode('list')}
              className={`px-3.5 py-1.5 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer ${
                layoutMode === 'list' 
                  ? 'bg-white text-primary shadow-xs' 
                  : 'text-slate-500 hover:text-slate-800'
              }`}
            >
              <span>عرض بالعرض ☰</span>
            </button>
            <button
              onClick={() => setLayoutMode('grid')}
              className={`px-3.5 py-1.5 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer ${
                layoutMode === 'grid' 
                  ? 'bg-white text-primary shadow-xs' 
                  : 'text-slate-500 hover:text-slate-800'
              }`}
            >
              <span>عرض بالطول ⚏</span>
            </button>
          </div>

          <div className="text-right">
            <h2 className="text-lg sm:text-xl font-black text-slate-900">المنتجات الأكثر مبيعاً</h2>
            <p className="text-xs text-slate-500 mt-1 font-bold">تسوق الأصناف الأكثر طلباً وأسعار الجملة للجميع</p>
          </div>
        </div>

        {loading ? (
          <div className="py-20 flex flex-col items-center justify-center text-center space-y-4">
            <div className="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin text-primary" />
            <p className="text-xs text-slate-500 font-bold">جاري تحميل كتالوج الأصناف المميزة...</p>
          </div>
        ) : featuredProducts.length === 0 ? (
          <div className="py-20 bg-white border border-slate-150 rounded-3xl text-center space-y-3 max-w-sm mx-auto shadow-xs">
            <span className="text-3xl">🔍</span>
            <p className="text-slate-900 font-bold text-sm">لم نعثر على أي أصناف مطابقة للبحث</p>
          </div>
        ) : (
          <div className="space-y-12">
            <div className={`grid gap-4 sm:gap-6 ${
              layoutMode === 'list' 
                ? 'grid-cols-1 md:grid-cols-2' 
                : 'grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
            }`}>
              {featuredProducts.map((product) => (
                <ProductCard key={product.id} product={product} layout={layoutMode} />
              ))}
            </div>
            
            <div className="flex justify-center pt-4">
              <Link 
                href="/products"
                className="bg-primary hover:bg-primary-dark text-white font-extrabold px-10 py-3.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-0.5 inl[...]
              >
                عرض جميع المنتجات والسلع 🛒
              </Link>
            </div>
          </div>
        )}
        </ScrollReveal>

      </section>

      {/* 5. Google Maps Location Console (Split design: Left Map Mockup, Right store block) */}
      <section id="contact" className="py-16 bg-slate-50/50 border-t border-b border-slate-200/60">
        <ScrollReveal className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-12 gap-8 items-stretch">
          
          {/* Left: Google Map View Mockup */}
          <div className="md:col-span-7 rounded-3xl overflow-hidden shadow-lg border border-slate-200/80 h-72 sm:h-auto min-h-[350px] relative transition-transform duration-500 hover:scale-[1.01]">
            <iframe 
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3290.891572559634!2d29.789759360246514!3d30.99744417289808!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14f5937e2d3273b7[...]
              className="w-full h-full border-0 absolute inset-0"
              allowFullScreen
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
            />
          </div>

          {/* Right: Modern Green & White Shop Info Panel */}
          <div className="md:col-span-5 bg-white p-8 rounded-3xl shadow-xl border border-emerald-100 flex flex-col justify-between text-right space-y-6 relative overflow-hidden group">
            {/* Top decorative gradient bar */}
            <div className="absolute top-0 right-0 left-0 h-1.5 bg-gradient-to-l from-primary via-emerald-400 to-primary/40" />
            
            {/* Background decorative watermark */}
            <div className="absolute -bottom-10 -left-10 w-40 h-40 bg-primary/5 rounded-full blur-2xl pointer-events-none" />
            
            {/* Storefront header info */}
            <div className="space-y-5 relative z-10">
              <div className="flex items-center gap-4 justify-end">
                <div>
                  <h3 className="font-black text-slate-900 text-base sm:text-xl leading-tight">الناصرية جملة ماركت</h3>
                  <p className="text-[10px] sm:text-xs text-primary font-black mt-1 bg-primary/10 border border-primary/20 px-2.5 py-0.5 rounded-full inline-block">
                    مؤسسة الناصرية التجارية
                  </p>
                </div>
                <div className="w-14 h-14 rounded-2xl overflow-hidden bg-white p-1 border border-slate-100 shadow-md group-hover:rotate-3 transition-transform duration-300">
                  <img src="/logo.jpeg" alt="شعار الماركت" className="w-full h-full object-cover rounded-xl" />
                </div>
              </div>
              
              <div className="w-full h-px bg-slate-100" />
            </div>

            {/* Address & Tel coordinates */}
            <div className="space-y-5 text-slate-600 text-xs sm:text-sm font-bold relative z-10">
              <div className="flex items-start gap-3 justify-end leading-relaxed">
                <span>الإسكندرية، حي ثان العامرية، الناصرية القديمة أمام الميدان</span>
                <div className="p-2.5 bg-primary/10 text-primary rounded-xl flex-shrink-0">
                  <MapPin size={18} />
                </div>
              </div>
              
              <div className="flex items-center gap-3 justify-end">
                <span dir="ltr" className="text-slate-800 font-extrabold text-base">01019786034</span>
                <div className="p-2.5 bg-primary/10 text-primary rounded-xl flex-shrink-0">
                  <Phone size={18} />
                </div>
              </div>

              <div className="flex items-start gap-3 justify-end text-[11px] sm:text-xs text-slate-450 leading-normal">
                <span>جاهزون لخدمتكم وتوصيل طلباتكم إلى العامرية والناصرية يومياً.</span>
                <span className="p-1 bg-emerald-500/10 text-emerald-600 rounded-full flex-shrink-0">✓</span>
              </div>
            </div>

            {/* CTA coordinates map link */}
            <div className="relative z-10">
              <a 
                href="https://www.google.com/maps/search/?api=1&query=30.9974441,29.7897593&query_place_id=ChIJYVLKpmnD9RQRsODX063FS20"
                target="_blank"
                rel="noopener noreferrer"
                className="w-full bg-primary hover:bg-primary-dark text-white font-extrabold py-3.5 rounded-2xl transition-all shadow-md hover:shadow-lg hover:shadow-primary/20 text-center inline-bloc[...]
              >
                🗺️ فتح الاتجاهات في خرائط جوجل
              </a>
            </div>

          </div>

        </ScrollReveal>
      </section>

      {/* 6. Contact and Footer spacer */}
      <div className="w-full h-8" />

      {/* 7. Footer (Newsletter Signup & Store Information blocks) */}
      <Footer />
    </div>
  );
}

export default function HomePage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center space-y-4">
          <div className="w-12 h-12 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="text-sm font-bold text-gray-600">تحميل الناصرية جملة ماركت...</p>
        </div>
      </div>
    }>
      <HomeContent />
    </Suspense>
  );
}
