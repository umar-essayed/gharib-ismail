'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useCart } from '@/context/CartContext';
import { 
  ShoppingBag, 
  Search, 
  Trash2, 
  Plus, 
  Minus, 
  ShieldAlert,
  ChevronDown,
  X,
  User,
  Heart,
  Check,
  Menu
} from 'lucide-react';
import { supabase } from '@/lib/supabase';

export default function Navbar() {
  const { 
    cart, 
    updateQuantity, 
    removeFromCart, 
    subtotal, 
    profile,
    setProfile,
    refreshProfile,
    wishlist,
    toggleWishlist,
    addToCart
  } = useCart();
  
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [isWishlistOpen, setIsWishlistOpen] = useState(false);
  const [isCategoriesOpen, setIsCategoriesOpen] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [searchVal, setSearchVal] = useState('');
  const [wishlistAddedMap, setWishlistAddedMap] = useState<Record<string, boolean>>({});
  
  const [categories, setCategories] = useState<any[]>([]);
  const router = useRouter();
  const [threshold, setThreshold] = useState(800);

  React.useEffect(() => {
    fetch('/ecom_config.json')
      .then((r) => r.json())
      .then((data) => {
        if (data && data.free_shipping_threshold) {
          setThreshold(Number(data.free_shipping_threshold));
        }
      })
      .catch(() => {});
  }, []);

  const getCategoryEmoji = (name: string) => {
    const n = name.toLowerCase();
    if (n.includes('لبن') || n.includes('ألبان') || n.includes('جبن') || n.includes('جبنة') || n.includes('dairy')) return '🥛';
    if (n.includes('مجم') || n.includes('لحوم') || n.includes('فراخ') || n.includes('frozen')) return '❄️';
    if (n.includes('بقال') || n.includes('زيت') || n.includes('سمن') || n.includes('أرز') || n.includes('مكرون') || n.includes('grocery')) return '🥫';
    if (n.includes('حلو') || n.includes('شيكولات') || n.includes('بسكوت') || n.includes('سناك') || n.includes('sweets')) return '🍬';
    if (n.includes('منظف') || n.includes('صابون') || n.includes('كلور') || n.includes('cleaning')) return '🧼';
    if (n.includes('مشروب') || n.includes('عصير') || n.includes('بيبسي') || n.includes('beverage')) return '🥤';
    return '📦';
  };

  React.useEffect(() => {
    async function fetchCategories() {
      try {
        const { data, error } = await supabase
          .from('categories')
          .select('*')
          .order('name', { ascending: true });
        if (data && data.length > 0) {
          setCategories(data);
        } else {
          setCategories([
            { id: 'cat-1', name: 'أرز ومكرونة وبقوليات', slug: 'rice-pasta-grains' },
            { id: 'cat-2', name: 'زيوت وسمن وزبدة', slug: 'oils-ghee' },
            { id: 'cat-3', name: 'ألبان واجبان', slug: 'dairy-cheese' },
            { id: 'cat-4', name: 'معلبات ومخللات', slug: 'canned-goods' },
            { id: 'cat-5', name: 'مشروبات وعصائر', slug: 'beverages' },
            { id: 'cat-6', name: 'شيكولاتة ومقرمشات وتسالي', slug: 'snacks-sweets' }
          ]);
        }
      } catch (err) {
        setCategories([
          { id: 'cat-1', name: 'أرز ومكرونة وبقوليات', slug: 'rice-pasta-grains' },
          { id: 'cat-2', name: 'زيوت وسمن وزبدة', slug: 'oils-ghee' },
          { id: 'cat-3', name: 'ألبان واجبان', slug: 'dairy-cheese' },
          { id: 'cat-4', name: 'معلبات ومخللات', slug: 'canned-goods' },
          { id: 'cat-5', name: 'مشروبات وعصائر', slug: 'beverages' },
          { id: 'cat-6', name: 'شيكولاتة ومقرمشات وتسالي', slug: 'snacks-sweets' }
        ]);
      }
    }
    fetchCategories();
  }, []);

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchVal.trim()) {
      router.push(`/products?search=${encodeURIComponent(searchVal.trim())}`);
    } else {
      router.push('/products');
    }
  };

  const handleLogout = async () => {
    await supabase.auth.signOut();
    localStorage.removeItem('demo_profile');
    setProfile(null);
    router.refresh();
  };

  const handleAddFromWishlistToCart = (product: any) => {
    addToCart(product, 1);
    setWishlistAddedMap(prev => ({ ...prev, [product.id]: true }));
    setTimeout(() => {
      setWishlistAddedMap(prev => ({ ...prev, [product.id]: false }));
    }, 1500);
  };

  const cartItemsCount = cart.reduce((acc, item) => acc + item.quantity, 0);

  return (
    <>
      {/* Top Brand Stripe */}
      <div className="w-full h-1 bg-gradient-to-l from-primary via-emerald-500 to-primary-light" />

      <header className="w-full bg-white border-b border-gray-150 sticky top-0 z-40 shadow-xs">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          
          {/* 1. Desktop Layout (Hidden on mobile) */}
          <div className="hidden md:flex items-center justify-between h-20 gap-4">
            
            {/* Right: Brand Logo */}
            <div className="flex-shrink-0">
              <Link href="/" className="flex items-center gap-3 select-none">
                <img 
                  src="/logo.jpeg" 
                  alt="شعار الناصرية جملة ماركت" 
                  className="w-11 h-11 rounded-full object-cover border border-primary/20 shadow-xs"
                />
                <div className="flex flex-col items-start text-right">
                  <span className="text-lg font-black text-gray-900 leading-none">
                    الناصريه جمله ماركت
                  </span>
                  <span className="text-[10px] font-extrabold text-primary mt-1 tracking-wider">
                    التجارية - جملة ماركت
                  </span>
                </div>
              </Link>
            </div>

            {/* Center: Search Bar */}
            <div className="flex-1 max-w-lg mx-4">
              <form onSubmit={handleSearchSubmit} className="relative">
                <input
                  type="text"
                  placeholder="ابحث عن السلع والمنتجات هنا..."
                  value={searchVal}
                  onChange={(e) => setSearchVal(e.target.value)}
                  className="w-full bg-gray-50 border border-gray-200 text-gray-900 pr-10 pl-4 py-2 rounded-full text-xs focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white transition-all text-right font-bold"
                />
                <button type="submit" className="absolute right-3.5 top-2.5 text-gray-400 hover:text-primary">
                  <Search size={16} />
                </button>
              </form>
            </div>

            {/* Left: Cart, Wishlist & Auth Buttons */}
            <div className="flex items-center gap-3 select-none">
              
              {/* Admin Button Link */}
              {profile?.role === 'admin' && (
                <Link 
                  href="/admin/orders" 
                  className="px-3 py-1.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-[11px] font-bold hover:bg-amber-100 transition-colors flex items-center gap-1"
                >
                  <ShieldAlert size={12} className="text-amber-600 animate-pulse" />
                  لوحة التحكم
                </Link>
              )}

              {/* Wishlist Trigger */}
              <button
                onClick={() => setIsWishlistOpen(true)}
                className="relative p-2 text-gray-650 hover:text-primary transition-colors flex items-center gap-1 border border-gray-200 rounded-xl px-3.5 py-1.5 text-xs font-bold bg-gray-50 cursor-pointer"
              >
                <Heart size={16} className={wishlist.length > 0 ? "fill-accent text-accent" : ""} />
                <span>المفضلة</span>
                {wishlist.length > 0 && (
                  <span className="bg-primary text-white text-[9px] font-black w-4.5 h-4.5 rounded-full flex items-center justify-center border border-white">
                    {wishlist.length}
                  </span>
                )}
              </button>

              {/* Cart Trigger */}
              <button
                onClick={() => setIsCartOpen(true)}
                className="relative p-2 text-gray-650 hover:text-primary transition-colors flex items-center gap-1 border border-gray-200 rounded-xl px-3.5 py-1.5 text-xs font-bold bg-gray-50 cursor-pointer"
              >
                <ShoppingBag size={16} />
                <span>السلة</span>
                {cartItemsCount > 0 && (
                  <span className="bg-accent text-white text-[9px] font-black w-4.5 h-4.5 rounded-full flex items-center justify-center border border-white">
                    {cartItemsCount}
                  </span>
                )}
              </button>

              {/* Account / Login Trigger */}
              {profile ? (
                <div className="flex items-center gap-2">
                  <Link 
                    href="/profile"
                    className="px-4 py-1.5 bg-primary/10 text-primary hover:bg-primary/15 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                  >
                    <User size={14} />
                    <span>حسابي ({profile.full_name.split(' ')[0]})</span>
                  </Link>
                  <button
                    onClick={handleLogout}
                    className="px-3 py-1.5 border border-red-200 hover:bg-red-50 hover:text-red-650 text-red-600 rounded-xl text-xs font-bold transition-all cursor-pointer"
                  >
                    خروج
                  </button>
                </div>
              ) : (
                <Link 
                  href="/auth"
                  className="px-4 py-1.5 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-bold transition-all"
                >
                  تسجيل دخول
                </Link>
              )}

            </div>

          </div>

          {/* 2. Mobile Layout (Visible only on mobile) */}
          <div className="md:hidden flex flex-col py-3.5 gap-3">
            <div className="flex items-center justify-between w-full">
              
              {/* Right: Burger Menu & Small Logo */}
              <div className="flex items-center gap-3">
                <button
                  onClick={() => setIsMobileMenuOpen(true)}
                  className="p-2 text-gray-600 hover:text-primary border border-gray-200 rounded-xl bg-gray-50 focus:outline-none cursor-pointer"
                >
                  <Menu size={18} />
                </button>
                <Link href="/" className="flex items-center gap-2 select-none">
                  <img 
                    src="/logo.jpeg" 
                    alt="شعار" 
                    className="w-8 h-8 rounded-full object-cover border border-primary/20 shadow-xs"
                  />
                  <span className="text-xs font-black text-gray-900 leading-none">الناصرية جملة</span>
                </Link>
              </div>

              {/* Left: Cart & Wishlist icons (No text to fit) */}
              <div className="flex items-center gap-2">
                <Link
                  href="/products"
                  className="px-2.5 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 hover:border-primary/30 rounded-xl text-[10px] font-black transition-all whitespace-nowrap cursor-pointer"
                >
                  كل المنتجات 🛍️
                </Link>

                <button
                  onClick={() => setIsWishlistOpen(true)}
                  className="relative p-2 text-gray-650 hover:text-primary border border-gray-200 rounded-xl bg-gray-50 cursor-pointer"
                >
                  <Heart size={16} className={wishlist.length > 0 ? "fill-accent text-accent" : ""} />
                  {wishlist.length > 0 && (
                    <span className="absolute -top-1.5 -left-1.5 bg-primary text-white text-[8px] font-black w-4.5 h-4.5 rounded-full flex items-center justify-center border border-white">
                      {wishlist.length}
                    </span>
                  )}
                </button>

                <button
                  onClick={() => setIsCartOpen(true)}
                  className="relative p-2 text-gray-650 hover:text-primary border border-gray-200 rounded-xl bg-gray-50 cursor-pointer"
                >
                  <ShoppingBag size={16} />
                  {cartItemsCount > 0 && (
                    <span className="absolute -top-1.5 -left-1.5 bg-accent text-white text-[8px] font-black w-4.5 h-4.5 rounded-full flex items-center justify-center border border-white">
                      {cartItemsCount}
                    </span>
                  )}
                </button>
              </div>

            </div>

            {/* Mobile Search Bar below logo block */}
            <form onSubmit={handleSearchSubmit} className="relative w-full">
              <input
                type="text"
                placeholder="ابحث عن البقالة، المجمدات والأقسام..."
                value={searchVal}
                onChange={(e) => setSearchVal(e.target.value)}
                className="w-full bg-gray-50 border border-gray-200 text-gray-900 pr-10 pl-4 py-2.5 rounded-full text-xs focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white transition-all text-right font-bold"
              />
              <button type="submit" className="absolute right-3.5 top-3 text-gray-400 hover:text-primary">
                <Search size={14} />
              </button>
            </form>

            {/* Scrollable Categories on Mobile navbar */}
            <div className="flex items-center gap-2 overflow-x-auto pb-1.5 scrollbar-none text-[10px] font-bold mt-1">
              <button
                type="button"
                onClick={() => router.push('/products')}
                className="px-4 py-2 bg-primary text-white border border-primary/20 rounded-full whitespace-nowrap shadow-xs hover:bg-primary-dark transition-all"
              >
                الكل 📦
              </button>
              {categories.map((cat) => (
                <button
                  key={cat.id}
                  type="button"
                  onClick={() => router.push(`/products?category=${cat.id}`)}
                  className="px-4 py-2 bg-white text-gray-750 border border-gray-200/85 rounded-full whitespace-nowrap hover:bg-slate-50 transition-all flex items-center gap-1 shadow-xs cursor-pointer"
                >
                  <span>{cat.name}</span>
                  <span className="text-xs">{getCategoryEmoji(cat.name)}</span>
                </button>
              ))}
            </div>
          </div>

          {/* 3. Sub-nav menu bar (Hidden on mobile) */}
          <div className="hidden md:flex items-center justify-between h-10 pb-2 text-xs relative">
            
            {/* Categories dropdown */}
            <div className="relative font-bold">
              <button
                onClick={() => setIsCategoriesOpen(!isCategoriesOpen)}
                className="text-gray-700 hover:text-primary font-black flex items-center gap-1 select-none cursor-pointer"
              >
                <span>الأقسام الرئيسية</span>
                <ChevronDown size={12} />
              </button>

              {isCategoriesOpen && (
                <div className="absolute right-0 mt-2 w-52 bg-white border border-gray-100 rounded-xl shadow-md py-1.5 z-50 text-right">
                  {categories.map((cat) => (
                    <button
                      key={cat.id}
                      onClick={() => {
                        setIsCategoriesOpen(false);
                        router.push(`/products?category=${cat.id}`);
                      }}
                      className="w-full text-right px-4 py-2 hover:bg-primary/5 hover:text-primary text-gray-700 font-bold text-xs cursor-pointer flex items-center justify-between flex-row-reverse"
                    >
                      <span>{cat.name}</span>
                      <span className="text-xs">{getCategoryEmoji(cat.name)}</span>
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* Navigation links (Hides 'من نحن' & 'اتصل بنا' when logged in) */}
            <nav className="flex items-center gap-5 font-bold text-gray-500">
              <Link href="/" className="hover:text-primary transition-colors">عروض اليوم</Link>
              <Link href="/products" className="hover:text-primary transition-colors">جميع المنتجات</Link>
              <Link href="/products?search=جملة" className="hover:text-primary transition-colors">أسعار الجملة</Link>
              <Link href="/blog" className="hover:text-primary transition-colors text-primary font-black">مجلة التوفير 📰</Link>
              {!profile && (
                <>
                  <Link href="#about" className="hover:text-primary transition-colors">من نحن</Link>
                  <Link href="#contact" className="hover:text-primary transition-colors">اتصل بنا</Link>
                </>
              )}
            </nav>

          </div>

        </div>
      </header>

      {/* 4. Mobile Navigation Slide-Out Drawer */}
      {isMobileMenuOpen && (
        <div className="fixed inset-0 z-55 overflow-hidden md:hidden" role="dialog" aria-modal="true">
          <div className="absolute inset-0 overflow-hidden">
            <div 
              onClick={() => setIsMobileMenuOpen(false)}
              className="absolute inset-0 bg-gray-900/60 backdrop-blur-xs"
            />
            <div className="absolute inset-y-0 right-0 max-w-full flex">
              <div className="w-screen max-w-[270px] bg-white shadow-xl flex flex-col h-full text-right animate-slide-in-right">
                
                {/* Header of Drawer */}
                <div className="p-4 border-b border-gray-100 flex items-center justify-between bg-slate-50">
                  <span className="text-xs font-black text-slate-700">القائمة الرئيسية</span>
                  <button onClick={() => setIsMobileMenuOpen(false)} className="p-1.5 hover:bg-gray-200 rounded-full text-gray-400 bg-white border border-gray-150 cursor-pointer">
                    <X size={15} />
                  </button>
                </div>

                {/* Content of Drawer */}
                <div className="flex-1 overflow-y-auto p-4 space-y-5">
                  
                  {/* Account metadata block */}
                  {profile ? (
                    <div className="p-3 bg-primary/5 border border-primary/10 rounded-2xl space-y-2">
                      <p className="text-[9px] text-primary font-black uppercase tracking-wider">الحساب الشخصي 👤</p>
                      <h4 className="font-bold text-gray-900 text-xs truncate">{profile.full_name}</h4>
                      <p className="text-[10px] text-gray-500 font-semibold" dir="ltr">{profile.phone}</p>
                      
                      <div className="pt-2 flex flex-col gap-2">
                        <Link
                          href="/profile"
                          onClick={() => setIsMobileMenuOpen(false)}
                          className="w-full text-center py-2 bg-primary text-white rounded-xl font-bold text-[10px] shadow-xs cursor-pointer block"
                        >
                          لوحة تحكم حسابي ⚙️
                        </Link>
                        {profile.role === 'admin' && (
                          <Link
                            href="/admin/orders"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="w-full text-center py-2 bg-amber-500 text-white rounded-xl font-bold text-[10px] shadow-xs cursor-pointer block"
                          >
                            لوحة المشرف ⭐️
                          </Link>
                        )}
                      </div>
                    </div>
                  ) : (
                    <div className="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-2 text-center">
                      <p className="text-[10px] font-bold text-gray-700 leading-normal">سجل حسابك للطلب الفوري ومتابعة طلبياتك بالناصرية</p>
                      <Link
                        href="/auth"
                        onClick={() => setIsMobileMenuOpen(false)}
                        className="w-full text-center py-2 bg-primary text-white rounded-xl font-bold text-xs block shadow-xs"
                      >
                        تسجيل الدخول / إنشاء حساب 🛒
                      </Link>
                    </div>
                  )}

                  {/* Navigation Links */}
                  <div className="space-y-1">
                    <p className="text-[9px] text-slate-400 font-extrabold px-2 mb-1.5">روابط سريعة</p>
                    <Link 
                      href="/"
                      onClick={() => setIsMobileMenuOpen(false)}
                      className="block px-3 py-2 rounded-xl hover:bg-slate-50 font-bold text-xs text-gray-700 hover:text-primary transition-colors"
                    >
                      🔥 عروض اليوم
                    </Link>
                    <Link 
                      href="/products"
                      onClick={() => setIsMobileMenuOpen(false)}
                      className="block px-3 py-2 rounded-xl hover:bg-slate-50 font-bold text-xs text-gray-700 hover:text-primary transition-colors"
                    >
                      🛒 جميع المنتجات
                    </Link>
                    <Link 
                      href="/products?search=جملة"
                      onClick={() => setIsMobileMenuOpen(false)}
                      className="block px-3 py-2 rounded-xl hover:bg-slate-50 font-bold text-xs text-gray-700 hover:text-primary transition-colors"
                    >
                      📦 أسعار الجملة
                    </Link>
                    <Link 
                      href="/blog"
                      onClick={() => setIsMobileMenuOpen(false)}
                      className="block px-3 py-2 rounded-xl hover:bg-slate-50 font-bold text-xs text-primary hover:text-primary-dark transition-colors"
                    >
                      📰 مجلة التوفير (المدونة)
                    </Link>
                    
                    {!profile && (
                      <>
                        <Link 
                          href="#about"
                          onClick={() => setIsMobileMenuOpen(false)}
                          className="block px-3 py-2 rounded-xl hover:bg-slate-50 font-bold text-xs text-gray-700 hover:text-primary transition-colors"
                        >
                          🏛️ من نحن
                        </Link>
                        <Link 
                          href="#contact"
                          onClick={() => setIsMobileMenuOpen(false)}
                          className="block px-3 py-2 rounded-xl hover:bg-slate-50 font-bold text-xs text-gray-700 hover:text-primary transition-colors"
                        >
                          📞 اتصل بنا
                        </Link>
                      </>
                    )}
                  </div>

                  {/* Categories shortcuts in a beautiful 2-column grid */}
                  <div className="space-y-2">
                    <p className="text-[9px] text-slate-400 font-extrabold px-2 mb-0.5">الأقسام الرئيسية</p>
                    <div className="grid grid-cols-2 gap-2">
                      {categories.map((cat) => (
                        <button
                          key={cat.id}
                          onClick={() => {
                            setIsMobileMenuOpen(false);
                            router.push(`/products?category=${cat.id}`);
                          }}
                          className="text-right px-3 py-2 bg-slate-50 border border-slate-200/60 rounded-xl font-bold text-[11px] text-gray-750 hover:bg-primary/5 hover:text-primary hover:border-primary/20 transition-all cursor-pointer flex items-center justify-between flex-row-reverse gap-1 min-w-0"
                        >
                          <span className="truncate">{cat.name}</span>
                          <span className="text-xs flex-shrink-0">{getCategoryEmoji(cat.name)}</span>
                        </button>
                      ))}
                    </div>
                  </div>

                </div>

                {/* Footer of Drawer */}
                {profile && (
                  <div className="p-4 border-t border-gray-150 bg-slate-50">
                    <button
                      onClick={() => {
                        setIsMobileMenuOpen(false);
                        handleLogout();
                      }}
                      className="w-full py-2 bg-red-50 hover:bg-red-100 text-red-650 border border-red-200 rounded-xl font-bold text-xs transition-colors cursor-pointer text-center"
                    >
                      تسجيل الخروج
                    </button>
                  </div>
                )}

              </div>
            </div>
          </div>
        </div>
      )}

      {/* 5. Wishlist side-drawer */}
      {isWishlistOpen && (
        <div className="fixed inset-0 z-50 overflow-hidden" role="dialog" aria-modal="true">
          <div className="absolute inset-0 overflow-hidden">
            <div 
              onClick={() => setIsWishlistOpen(false)}
              className="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
            />
            <div className="absolute inset-y-0 left-0 max-w-full flex pr-10">
              <div className="w-screen max-w-md bg-white shadow-xl flex flex-col h-full">
                
                <div className="p-5 border-b border-gray-100 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <div className="p-2 bg-accent/10 text-accent rounded-lg">
                      <Heart size={18} className="fill-accent text-accent" />
                    </div>
                    <h2 className="text-base font-bold text-gray-900">المنتجات المفضلة</h2>
                    <span className="bg-accent/5 text-accent text-[10px] font-black px-2 py-0.5 rounded-full">
                      {wishlist.length} منتجات
                    </span>
                  </div>
                  <button onClick={() => setIsWishlistOpen(false)} className="p-1 hover:bg-gray-100 rounded-full text-gray-400">
                    <X size={18} />
                  </button>
                </div>

                <div className="flex-1 overflow-y-auto p-5 space-y-4">
                  {wishlist.length === 0 ? (
                    <div className="h-full flex flex-col items-center justify-center text-center space-y-2">
                      <span className="text-3xl">❤️</span>
                      <p className="text-gray-950 font-bold text-xs">قائمة المفضلة فارغة حالياً</p>
                      <p className="text-gray-500 text-[10px]">تصفح المنتجات وأضف ما يعجبك للمفضلة للوصول السريع إليها</p>
                    </div>
                  ) : (
                    wishlist.map((product) => {
                      const hasDiscount = product.sale_price !== null && product.sale_price !== undefined && product.sale_price > 0;
                      const activePrice = hasDiscount ? (product.sale_price as number) : product.price;
                      
                      return (
                        <div key={product.id} className="flex gap-3 p-3 bg-gray-50 border border-gray-100 rounded-xl">
                          <div className="w-14 h-14 rounded-lg overflow-hidden bg-white border border-gray-250 flex-shrink-0">
                            {product.image_url && (
                              <img src={product.image_url} alt={product.name} className="w-full h-full object-cover" />
                            )}
                          </div>
                          
                          <div className="flex-1 flex flex-col justify-between text-right">
                            <div>
                              <h4 className="font-bold text-gray-900 text-xs line-clamp-1">{product.name}</h4>
                              <div className="flex items-center gap-1.5 mt-0.5 justify-start">
                                <span className="text-xs font-bold text-primary">{activePrice.toFixed(2)} ج.م</span>
                                {hasDiscount && <span className="text-[8px] bg-red-100 text-accent px-1.5 py-0.2 rounded font-bold">خصم</span>}
                              </div>
                            </div>

                            <div className="flex items-center justify-between mt-1.5">
                              <button
                                onClick={() => handleAddFromWishlistToCart(product)}
                                className={`px-3 py-1 rounded-lg font-bold text-[10px] transition-all flex items-center gap-1 ${
                                  wishlistAddedMap[product.id]
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-primary hover:bg-primary-dark text-white'
                                }`}
                              >
                                {wishlistAddedMap[product.id] ? (
                                  <>
                                    <Check size={10} />
                                    تمت الإضافة
                                  </>
                                ) : (
                                  <>
                                    <Plus size={10} />
                                    أضف للسلة
                                  </>
                                )}
                              </button>
                              
                              <button 
                                onClick={() => toggleWishlist(product)} 
                                className="text-gray-400 hover:text-accent p-1.5"
                                title="إزالة من المفضلة"
                              >
                                <Trash2 size={14} />
                              </button>
                            </div>
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>

                <div className="border-t border-gray-150 p-5 bg-gray-50">
                  <button 
                    onClick={() => {
                      setIsWishlistOpen(false);
                      router.push('/products');
                    }}
                    className="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-xs transition-all text-center"
                  >
                    استمرار التصفح
                  </button>
                </div>

              </div>
            </div>
          </div>
        </div>
      )}

      {/* Cart side-drawer */}
      {isCartOpen && (
        <div className="fixed inset-0 z-50 overflow-hidden" role="dialog" aria-modal="true">
          <div className="absolute inset-0 overflow-hidden">
            <div 
              onClick={() => setIsCartOpen(false)}
              className="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
            />
            <div className="absolute inset-y-0 left-0 max-w-full flex pr-10">
              <div className="w-screen max-w-md bg-white shadow-xl flex flex-col h-full">
                
                <div className="p-5 border-b border-gray-100 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <div className="p-2 bg-primary/10 text-primary rounded-lg">
                      <ShoppingBag size={18} />
                    </div>
                    <h2 className="text-base font-bold text-gray-900">سلة التسوق</h2>
                    <span className="bg-primary/5 text-primary text-[10px] font-black px-2 py-0.5 rounded-full">
                      {cartItemsCount} أصناف
                    </span>
                  </div>
                  <button onClick={() => setIsCartOpen(false)} className="p-1 hover:bg-gray-100 rounded-full text-gray-400">
                    <X size={18} />
                  </button>
                </div>

                <div className="flex-1 overflow-y-auto p-5 space-y-4">
                  {cart.length === 0 ? (
                    <div className="h-full flex flex-col items-center justify-center text-center space-y-2">
                      <span className="text-3xl">🛒</span>
                      <p className="text-gray-900 font-bold text-xs">السلة فارغة حالياً</p>
                    </div>
                  ) : (
                    cart.map((item) => {
                      const isWholesale = item.quantity >= item.product.wholesale_min_qty;
                      const unitPrice = isWholesale ? item.product.wholesale_price : (item.product.sale_price || item.product.price);
                      
                      return (
                        <div key={item.product.id} className="flex gap-3 p-3 bg-gray-50 border border-gray-100 rounded-xl">
                          <div className="w-14 h-14 rounded-lg overflow-hidden bg-white border border-gray-250 flex-shrink-0">
                            {item.product.image_url && (
                              <img src={item.product.image_url} alt={item.product.name} className="w-full h-full object-cover" />
                            )}
                          </div>
                          
                          <div className="flex-1 flex flex-col justify-between text-right">
                            <div>
                              <h4 className="font-bold text-gray-900 text-xs line-clamp-1">{item.product.name}</h4>
                              <div className="flex items-center gap-1.5 mt-0.5 justify-start">
                                <span className="text-xs font-bold text-primary">{unitPrice.toFixed(2)} ج.م</span>
                                {isWholesale && <span className="text-[8px] bg-amber-100 text-amber-800 px-1.5 py-0.2 rounded">سعر جملة</span>}
                              </div>
                            </div>

                            <div className="flex items-center justify-between mt-1.5">
                              <div className="flex items-center border border-gray-250 bg-white rounded-md overflow-hidden">
                                <button
                                  onClick={() => updateQuantity(item.product.id, item.quantity - 1)}
                                  className="p-0.5 hover:bg-gray-50 text-gray-600"
                                >
                                  <Minus size={10} />
                                </button>
                                <span className="px-2 text-xs font-bold text-gray-900">{item.quantity}</span>
                                <button
                                  onClick={() => updateQuantity(item.product.id, item.quantity + 1)}
                                  className="p-0.5 hover:bg-gray-50 text-gray-600"
                                  disabled={item.product.stock > 0 && item.quantity >= item.product.stock}
                                >
                                  <Plus size={10} />
                                </button>
                              </div>
                              <button onClick={() => removeFromCart(item.product.id)} className="text-gray-400 hover:text-accent">
                                <Trash2 size={14} />
                              </button>
                            </div>
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>

                {cart.length > 0 && (() => {
                  const remaining = Math.max(0, threshold - subtotal);
                  const percentage = Math.min(100, (subtotal / threshold) * 100);
                  
                  return (
                    <div className="border-t border-gray-150 p-5 bg-gray-50 space-y-3.5 text-right">
                      {/* Free Shipping Alert & Progress Bar */}
                      <div className="space-y-1.5">
                        <p className="text-[10px] sm:text-xs font-bold text-slate-700 flex items-center justify-between flex-row-reverse">
                          {remaining > 0 ? (
                            <>
                              <span>باقي لك <span className="text-primary font-black">{remaining.toFixed(2)} ج.م</span> للحصول على توصيل مجاني!</span>
                              <span>🚚</span>
                            </>
                          ) : (
                            <>
                              <span className="text-primary font-black">تهانينا! حصلت على توصيل مجاني للعامرية والناصرية 🥳</span>
                              <span>🎉</span>
                            </>
                          )}
                        </p>
                        <div className="w-full h-2 bg-slate-200 rounded-full overflow-hidden relative">
                          <div
                            style={{ width: `${percentage}%` }}
                            className={`h-full rounded-full transition-all duration-500 ${
                              percentage >= 100 
                                ? 'bg-gradient-to-r from-emerald-500 to-primary' 
                                : 'bg-primary'
                            }`}
                          />
                        </div>
                      </div>

                      <div className="flex justify-between text-xs text-gray-500 font-bold pt-1">
                        <span>الإجمالي المطلوب:</span>
                        <span className="text-gray-900 text-sm font-black">{subtotal.toFixed(2)} ج.م</span>
                      </div>
                    <Link 
                      href="/checkout"
                      onClick={() => setIsCartOpen(false)}
                      className="w-full flex items-center justify-center py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl font-bold text-xs transition-all text-center shadow-xs"
                    >
                      إتمام الطلب والدفع عند الاستلام
                    </Link>
                    </div>
                  );
                })()}

              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
