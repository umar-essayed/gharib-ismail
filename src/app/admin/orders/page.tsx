'use client';

import React, { useState, useEffect, Suspense } from 'react';
import { useRouter } from 'next/navigation';
import { useCart } from '@/context/CartContext';
import Navbar from '@/components/Navbar';
import { supabase } from '@/lib/supabase';
import { confirmUserByPhone } from '@/lib/authHelper';
import { Order, Product, Category, Banner, Coupon } from '@/types';
import { 
  ShieldAlert, 
  Clock, 
  MapPin, 
  Phone, 
  CheckCircle,
  Truck,
  Package,
  Bell,
  RefreshCw,
  Search,
  User,
  Plus,
  Lock,
  Loader2,
  Edit,
  Trash2,
  BarChart3,
  Database,
  Tag,
  AlertTriangle,
  FileText,
  X
} from 'lucide-react';

function AdminOrdersContent() {
  const { profile, refreshProfile } = useCart();
  const router = useRouter();
  
  // Admin local login state
  const [adminPhone, setAdminPhone] = useState('');
  const [adminPassword, setAdminPassword] = useState('');
  const [adminLoading, setAdminLoading] = useState(false);
  const [adminError, setAdminError] = useState<string | null>(null);

  // Tab State
  const [activeTab, setActiveTab] = useState<'orders' | 'products' | 'categories' | 'stats' | 'banners'>('orders');

  // Core Lists
  const [orders, setOrders] = useState<Order[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [banners, setBanners] = useState<Banner[]>([]);
  const [coupons, setCoupons] = useState<Coupon[]>([]);
  const [dbErrorWarning, setDbErrorWarning] = useState<boolean>(false);
  const [loading, setLoading] = useState(true);
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [isMuted, setIsMuted] = useState(false);
  const [logs, setLogs] = useState<string[]>([]);

  // Banner Form States
  const [newBannerTitle, setNewBannerTitle] = useState('');
  const [newBannerImageUrl, setNewBannerImageUrl] = useState('');
  const [newBannerLinkUrl, setNewBannerLinkUrl] = useState('/');

  // Coupon Form States
  const [newCouponCode, setNewCouponCode] = useState('');
  const [newCouponDesc, setNewCouponDesc] = useState('');
  const [newCouponType, setNewCouponType] = useState<'percentage' | 'fixed' | 'points'>('percentage');
  const [newCouponValue, setNewCouponValue] = useState<number | ''>('');
  const [newCouponMinOrder, setNewCouponMinOrder] = useState<number | ''>('');
  const [newCouponPointsCost, setNewCouponPointsCost] = useState<number | ''>('');

  // Product Form Modal States
  const [showProductModal, setShowProductModal] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [prodName, setProdName] = useState('');
  const [prodDesc, setProdDesc] = useState('');
  const [prodPrice, setProdPrice] = useState(0);
  const [prodSalePrice, setProdSalePrice] = useState<number | ''>('');
  const [prodWholesalePrice, setProdWholesalePrice] = useState(0);
  const [prodWholesaleMinQty, setProdWholesaleMinQty] = useState(12);
  const [prodStock, setProdStock] = useState(0);
  const [prodImageUrl, setProdImageUrl] = useState('');
  const [prodCategoryId, setProdCategoryId] = useState('');
  const [prodIsAvailable, setProdIsAvailable] = useState(true);
  const [modalError, setModalError] = useState<string | null>(null);
  const [saveLoading, setSaveLoading] = useState(false);

  // Category Form Modal States
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);
  const [catName, setCatName] = useState('');
  const [catSlug, setCatSlug] = useState('');
  const [catImageUrl, setCatImageUrl] = useState('');

  // 1. Synthesize pleasant double bell sound using browser's Web Audio API
  const triggerAudioAlert = () => {
    if (isMuted) return;
    try {
      const AudioContext = window.AudioContext || (window as any).webkitAudioContext;
      if (!AudioContext) return;
      const ctx = new AudioContext();

      const playTone = (frequency: number, delay: number, duration: number) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(frequency, ctx.currentTime + delay);
        
        gain.gain.setValueAtTime(0.15, ctx.currentTime + delay);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + duration);

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start(ctx.currentTime + delay);
        osc.stop(ctx.currentTime + delay + duration);
      };

      playTone(587.33, 0, 0.3);
      playTone(880.00, 0.12, 0.55);
      
      addLog('🔔 تم تشغيل صوت التنبيه المستلم');
    } catch (err) {
      console.warn('Web Audio synthesis blocked by browser auto-play policy', err);
    }
  };

  const addLog = (msg: string) => {
    setLogs((prev) => [`[${new Date().toLocaleTimeString('ar-EG')}] ${msg}`, ...prev.slice(0, 4)]);
  };

  // 2. Fetch existing data from Supabase
  const fetchData = async () => {
    try {
      setLoading(true);
      
      // Load Orders
      const { data: orderData, error: orderErr } = await supabase
        .from('orders')
        .select('*')
        .order('created_at', { ascending: false });

      if (orderErr) throw orderErr;
      setOrders(orderData as Order[] || []);

      // Load Categories
      const { data: catData, error: catErr } = await supabase
        .from('categories')
        .select('*')
        .order('name', { ascending: true });

      if (catErr) throw catErr;
      setCategories(catData as Category[] || []);

      // Load Products
      const { data: prodData, error: prodErr } = await supabase
        .from('products')
        .select('*')
        .order('name', { ascending: true });

      if (prodErr) throw prodErr;
      if (prodData) {
        const normalized = prodData.map(p => ({
          ...p,
          price: Number(p.price),
          sale_price: p.sale_price !== null ? Number(p.sale_price) : null,
          wholesale_price: Number(p.wholesale_price)
        })) as Product[];
        setProducts(normalized);
      }

      // Load Banners
      try {
        const { data: bannerData, error: bannerErr } = await supabase
          .from('banners')
          .select('*')
          .order('created_at', { ascending: false });
        if (bannerErr) throw bannerErr;
        setBanners((bannerData as Banner[]) || []);
      } catch (bannerError) {
        console.warn('Banners table not found. Using default mock banners.', bannerError);
        setDbErrorWarning(true);
        setBanners([
          { id: 'slide-1', title: 'خصومات تصل إلى 30% على منتجات الألبان والأرز', image_url: 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80', link_url: '/products?search=أرز' },
          { id: 'slide-2', title: 'شحن مجاني للعامرية والناصرية للطلبات فوق 800 جنيه', image_url: 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=1200&auto=format&fit=crop&q=80', link_url: '/' },
          { id: 'slide-3', title: 'وفر أكتر مع أسعار الجملة لكرتونة المجمدات والمنظفات', image_url: 'https://images.unsplash.com/photo-1578916171728-46686eac8d58?w=1200&auto=format&fit=crop&q=80', link_url: '/products?search=جملة' }
        ]);
      }

      // Load Coupons
      try {
        const { data: couponData, error: couponErr } = await supabase
          .from('coupons')
          .select('*')
          .order('created_at', { ascending: false });
        if (couponErr) throw couponErr;
        setCoupons((couponData as Coupon[]) || []);
      } catch (couponError) {
        console.warn('Coupons table not found. Using default mock coupons.', couponError);
        setDbErrorWarning(true);
        setCoupons([
          { code: 'ARZ15', description: 'خصم 15% على إجمالي السلة', discount_type: 'percentage', discount_value: 15, min_order_amount: 0, points_cost: 0, is_active: true },
          { code: 'GHARIB50', description: 'خصم بقيمة 50 جنيه للطلبات بقيمة 300 جنيه أو أكثر', discount_type: 'fixed', discount_value: 50, min_order_amount: 300, points_cost: 0, is_active: true },
          { code: 'POINTS100', description: 'خصم بقيمة 100 جنيه مقابل 100 نقطة ذهبية من رصيدك', discount_type: 'points', discount_value: 100, min_order_amount: 0, points_cost: 100, is_active: true }
        ]);
      }
      
      addLog('🟢 تم مزامنة البيانات بالكامل مع قاعدة البيانات');
    } catch (err: any) {
      console.error('Error fetching admin data:', err);
      addLog(`⚠️ فشل تحميل البيانات: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (profile?.role === 'admin') {
      fetchData();

      // Realtime subscription for orders
      const channel = supabase
        .channel('public:orders')
        .on(
          'postgres_changes',
          { event: 'INSERT', schema: 'public', table: 'orders' },
          (payload) => {
            const newOrder = payload.new as Order;
            const cleanedOrder = {
              ...newOrder,
              total_price: Number(newOrder.total_price)
            };
            setOrders((prev) => [cleanedOrder, ...prev]);
            triggerAudioAlert();
            addLog(`⚡️ فاتورة جديدة مستلمة بالوقت الفعلي! رقم: #${cleanedOrder.id.substring(0,8).toUpperCase()}`);
          }
        )
        .subscribe((status) => {
          if (status === 'SUBSCRIBED') {
            addLog('🟢 قناة الاتصال بالوقت الفعلي (Real-time) نشطة وجاهزة');
          }
        });

      return () => {
        supabase.removeChannel(channel);
      };
    }
  }, [profile]);

  const handleAdminLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!adminPhone.trim() || !adminPassword) return;
    try {
      setAdminLoading(true);
      setAdminError(null);
      const virtualEmail = `${adminPhone.trim()}@gmail.com`;
      let { data, error } = await supabase.auth.signInWithPassword({
        email: virtualEmail,
        password: adminPassword
      });

      // Auto-confirm old accounts if they throw "Email not confirmed"
      if (error && (error.message?.toLowerCase().includes('confirm') || error.message?.toLowerCase().includes('verification'))) {
        try {
          const confirmed = await confirmUserByPhone(adminPhone);
          if (confirmed) {
            const retryResult = await supabase.auth.signInWithPassword({
              email: virtualEmail,
              password: adminPassword
            });
            data = retryResult.data;
            error = retryResult.error;
          }
        } catch (adminErr) {
          console.error('Failed to auto-confirm admin during login:', adminErr);
        }
      }

      if (error) throw error;
      
      // Verify role after sign in
      const { data: userProfile } = await supabase
        .from('profiles')
        .select('role')
        .eq('id', data.user!.id)
        .single();
        
      if (userProfile?.role !== 'admin') {
        await supabase.auth.signOut();
        throw new Error('عذراً، هذا الحساب ليس حساب مدير نظام.');
      }
      
      await refreshProfile();
      router.refresh();
    } catch (err: any) {
      setAdminError(err.message || 'فشل تسجيل الدخول كمدير. تحقق من البيانات.');
    } finally {
      setAdminLoading(false);
    }
  };

  // Update order status
  const handleUpdateStatus = async (orderId: string, newStatus: Order['status']) => {
    try {
      // Local update first
      setOrders((prev) =>
        prev.map((ord) => (ord.id === orderId ? { ...ord, status: newStatus } : ord))
      );

      const { error } = await supabase
        .from('orders')
        .update({ status: newStatus })
        .eq('id', orderId);

      if (error) throw error;
      addLog(`✓ تم تحديث حالة الفاتورة #${orderId.substring(0,8).toUpperCase()} إلى: ${getStatusArabic(newStatus)}`);
    } catch (err: any) {
      console.error('Error updating order status:', err);
      addLog(`❌ فشل تحديث حالة الفاتورة: ${err.message}`);
    }
  };

  // Product CRUD Handlers
  const openProductForm = (prod: Product | null) => {
    setEditingProduct(prod);
    setModalError(null);
    if (prod) {
      setProdName(prod.name);
      setProdDesc(prod.description || '');
      setProdPrice(prod.price);
      setProdSalePrice(prod.sale_price !== null && prod.sale_price !== undefined ? prod.sale_price : '');
      setProdWholesalePrice(prod.wholesale_price);
      setProdWholesaleMinQty(prod.wholesale_min_qty);
      setProdStock(prod.stock);
      setProdImageUrl(prod.image_url || '');
      setProdCategoryId(prod.category_id || '');
      setProdIsAvailable(prod.is_available);
    } else {
      setProdName('');
      setProdDesc('');
      setProdPrice(0);
      setProdSalePrice('');
      setProdWholesalePrice(0);
      setProdWholesaleMinQty(12);
      setProdStock(0);
      setProdImageUrl('');
      setProdCategoryId(categories[0]?.id || '');
      setProdIsAvailable(true);
    }
    setShowProductModal(true);
  };

  const handleSaveProduct = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!prodName.trim()) {
      setModalError('الرجاء إدخال اسم المنتج.');
      return;
    }

    try {
      setSaveLoading(true);
      setModalError(null);

      const payload = {
        name: prodName.trim(),
        description: prodDesc.trim(),
        price: Number(prodPrice),
        sale_price: prodSalePrice === '' ? null : Number(prodSalePrice),
        wholesale_price: Number(prodWholesalePrice),
        wholesale_min_qty: Number(prodWholesaleMinQty),
        stock: Number(prodStock),
        image_url: prodImageUrl.trim() || null,
        category_id: prodCategoryId || null,
        is_available: prodIsAvailable
      };

      if (editingProduct) {
        // Edit Product
        const { error } = await supabase
          .from('products')
          .update(payload)
          .eq('id', editingProduct.id);

        if (error) throw error;
        addLog(`✓ تم تعديل المنتج بنجاح: ${payload.name}`);
      } else {
        // Create Product
        const { error } = await supabase
          .from('products')
          .insert(payload);

        if (error) throw error;
        addLog(`✓ تم إضافة منتج جديد بنجاح: ${payload.name}`);
      }

      setShowProductModal(false);
      fetchData();
    } catch (err: any) {
      console.error('Error saving product:', err);
      setModalError(err.message || 'حدث خطأ أثناء الحفظ.');
    } finally {
      setSaveLoading(false);
    }
  };

  const handleDeleteProduct = async (id: string, name: string) => {
    if (!window.confirm(`هل أنت متأكد من حذف المنتج: "${name}"؟`)) return;
    try {
      const { error } = await supabase
        .from('products')
        .delete()
        .eq('id', id);

      if (error) throw error;
      addLog(`✓ تم حذف المنتج: ${name}`);
      fetchData();
    } catch (err: any) {
      console.error('Error deleting product:', err);
      addLog(`❌ فشل حذف المنتج: ${err.message}`);
    }
  };

  // Category CRUD Handlers
  const openCategoryForm = (cat: Category | null) => {
    setEditingCategory(cat);
    setModalError(null);
    if (cat) {
      setCatName(cat.name);
      setCatSlug(cat.slug);
      setCatImageUrl(cat.image_url || '');
    } else {
      setCatName('');
      setCatSlug('');
      setCatImageUrl('');
    }
    setShowCategoryModal(true);
  };

  const handleSaveCategory = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!catName.trim() || !catSlug.trim()) {
      setModalError('الرجاء إدخال اسم وقسم التصنيف.');
      return;
    }

    try {
      setSaveLoading(true);
      setModalError(null);

      const payload = {
        name: catName.trim(),
        slug: catSlug.trim().toLowerCase(),
        image_url: catImageUrl.trim() || null
      };

      if (editingCategory) {
        // Edit Category
        const { error } = await supabase
          .from('categories')
          .update(payload)
          .eq('id', editingCategory.id);

        if (error) throw error;
        addLog(`✓ تم تعديل التصنيف بنجاح: ${payload.name}`);
      } else {
        // Create Category
        const { error } = await supabase
          .from('categories')
          .insert(payload);

        if (error) throw error;
        addLog(`✓ تم إضافة تصنيف جديد بنجاح: ${payload.name}`);
      }

      setShowCategoryModal(false);
      fetchData();
    } catch (err: any) {
      console.error('Error saving category:', err);
      setModalError(err.message || 'حدث خطأ أثناء الحفظ.');
    } finally {
      setSaveLoading(false);
    }
  };

  const handleDeleteCategory = async (id: string, name: string) => {
    if (!window.confirm(`هل أنت متأكد من حذف التصنيف: "${name}"؟ جميع المنتجات تحت هذا القسم ستفقد ارتباطها.`)) return;
    try {
      const { error } = await supabase
        .from('categories')
        .delete()
        .eq('id', id);

      if (error) throw error;
      addLog(`✓ تم حذف التصنيف: ${name}`);
      fetchData();
    } catch (err: any) {
      console.error('Error deleting category:', err);
      addLog(`❌ فشل حذف التصنيف: ${err.message}`);
    }
  };

  // Helpers
  const getStatusArabic = (status: Order['status']) => {
    switch (status) {
      case 'pending': return 'قيد الانتظار';
      case 'preparing': return 'قيد التحضير';
      case 'delivering': return 'جاري التوصيل';
      case 'completed': return 'مكتملة';
    }
  };

  const getStatusColorClass = (status: Order['status']) => {
    switch (status) {
      case 'pending': return 'bg-amber-50 text-amber-700 border-amber-250';
      case 'preparing': return 'bg-indigo-50 text-indigo-700 border-indigo-200';
      case 'delivering': return 'bg-cyan-50 text-cyan-700 border-cyan-200';
      case 'completed': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    }
  };

  // Filter Logic for Orders
  const filteredOrders = orders.filter((o) => {
    const matchesStatus = filterStatus === 'all' ? true : o.status === filterStatus;
    const matchesSearch = searchQuery
      ? o.delivery_address.toLowerCase().includes(searchQuery.toLowerCase()) ||
        o.delivery_phone.includes(searchQuery) ||
        o.id.toLowerCase().includes(searchQuery.toLowerCase())
      : true;
    return matchesStatus && matchesSearch;
  });

  // Filter Logic for Products
  const filteredProducts = products.filter((p) => {
    return searchQuery
      ? p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (p.description && p.description.toLowerCase().includes(searchQuery.toLowerCase()))
      : true;
  });

  // Stats / Analytics Calculations
  const statsRevenue = orders.reduce((acc, o) => acc + Number(o.total_price), 0);
  const statsLowStock = products.filter(p => p.stock <= 5).length;
  const statsCompleted = orders.filter(o => o.status === 'completed').length;

  // Access check screen (Access gate)
  if (!profile || profile.role !== 'admin') {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col font-sans text-right">
        <Navbar />
        <div className="flex-1 flex items-center justify-center p-4">
          <div className="max-w-md w-full bg-white border border-gray-250 rounded-3xl p-8 text-center shadow-xl space-y-6">
            <div className="w-14 h-14 bg-red-50 border border-red-200 rounded-full flex items-center justify-center text-2xl text-red-650 mx-auto animate-pulse">
              ⚠️
            </div>
            <div>
              <h2 className="text-base font-black text-gray-900">بوابة كاشير ومدير النظام</h2>
              <p className="text-xs text-slate-500 mt-2 leading-relaxed font-semibold">
                يرجى تسجيل الدخول بحساب المدير لمتابعة وإدارة النظام بالكامل.
              </p>
            </div>

            {adminError && (
              <p className="text-[10px] bg-red-55 text-white font-bold p-2.5 rounded-lg text-right">
                {adminError}
              </p>
            )}

            <form onSubmit={handleAdminLogin} className="space-y-4 text-right">
              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5 justify-end">
                  رقم الهاتف للتواصل *
                  <Phone size={14} className="text-primary" />
                </label>
                <input
                  type="tel"
                  required
                  placeholder="مثال: 012XXXXXXXX"
                  value={adminPhone}
                  onChange={(e) => setAdminPhone(e.target.value)}
                  className="w-full bg-gray-50 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary"
                  dir="ltr"
                />
              </div>

              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5 justify-end">
                  أدخل كلمة المرور *
                  <Lock size={14} className="text-primary" />
                </label>
                <input
                  type="password"
                  required
                  placeholder="أدخل كلمة مرور حسابك"
                  value={adminPassword}
                  onChange={(e) => setAdminPassword(e.target.value)}
                  className="w-full bg-gray-50 border border-gray-250 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary"
                  dir="ltr"
                />
              </div>

              <button
                type="submit"
                disabled={adminLoading}
                className="w-full py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer shadow-md"
              >
                {adminLoading ? <Loader2 size={14} className="animate-spin" /> : 'تسجيل الدخول كمدير'}
              </button>
            </form>

            <button
              onClick={() => router.push('/')}
              className="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-750 font-bold text-xs rounded-xl transition-colors cursor-pointer"
            >
              العودة للرئيسية
            </button>
          </div>
        </div>
      </div>
    );
  }

  const handleAddBanner = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newBannerTitle.trim() || !newBannerImageUrl.trim()) return;
    
    try {
      setSaveLoading(true);
      const { data, error } = await supabase
        .from('banners')
        .insert({
          title: newBannerTitle.trim(),
          image_url: newBannerImageUrl.trim(),
          link_url: newBannerLinkUrl.trim() || '/'
        })
        .select()
        .single();
        
      if (error) throw error;
      setBanners(prev => [data as Banner, ...prev]);
      setNewBannerTitle('');
      setNewBannerImageUrl('');
      setNewBannerLinkUrl('/');
      addLog('🟢 تم إضافة البانر الإعلاني بنجاح');
    } catch (err: any) {
      console.error('Error adding banner:', err);
      alert('فشل إضافة البانر: ' + err.message);
    } finally {
      setSaveLoading(false);
    }
  };

  const handleDeleteBanner = async (id: string) => {
    if (!confirm('هل أنت متأكد من حذف هذا البانر؟')) return;
    try {
      const { error } = await supabase
        .from('banners')
        .delete()
        .eq('id', id);
      if (error) throw error;
      setBanners(prev => prev.filter(b => b.id !== id));
      addLog('🔴 تم حذف البانر الإعلاني');
    } catch (err: any) {
      console.error('Error deleting banner:', err);
      alert('فشل حذف البانر: ' + err.message);
    }
  };

  const handleAddCoupon = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCouponCode.trim() || !newCouponDesc.trim() || !newCouponValue) return;
    
    try {
      setSaveLoading(true);
      const { data, error } = await supabase
        .from('coupons')
        .insert({
          code: newCouponCode.trim().toUpperCase(),
          description: newCouponDesc.trim(),
          discount_type: newCouponType,
          discount_value: Number(newCouponValue),
          min_order_amount: Number(newCouponMinOrder || 0),
          points_cost: Number(newCouponPointsCost || 0),
          is_active: true
        })
        .select()
        .single();
        
      if (error) throw error;
      setCoupons(prev => [data as Coupon, ...prev]);
      setNewCouponCode('');
      setNewCouponDesc('');
      setNewCouponValue('');
      setNewCouponMinOrder('');
      setNewCouponPointsCost('');
      addLog('🟢 تم إضافة كود الخصم بنجاح');
    } catch (err: any) {
      console.error('Error adding coupon:', err);
      alert('فشل إضافة كود الخصم: ' + err.message);
    } finally {
      setSaveLoading(false);
    }
  };

  const handleDeleteCoupon = async (code: string) => {
    if (!confirm('هل أنت متأكد من حذف كود الخصم هذا؟')) return;
    try {
      const { error } = await supabase
        .from('coupons')
        .delete()
        .eq('code', code);
      if (error) throw error;
      setCoupons(prev => prev.filter(c => c.code !== code));
      addLog('🔴 تم حذف كود الخصم');
    } catch (err: any) {
      console.error('Error deleting coupon:', err);
      alert('فشل حذف كود الخصم: ' + err.message);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col font-sans text-right">
      <Navbar />

      <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full space-y-8">
        
        {/* Title Bar */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900 text-white p-6 rounded-3xl shadow-lg border-r-4 border-primary">
          <div className="space-y-1">
            <h1 className="text-lg sm:text-xl font-black flex items-center gap-2 justify-end md:flex-row-reverse text-right">
              <ShieldAlert size={22} className="text-primary animate-pulse" />
              لوحة التحكم والإشراف الفوري للمتجر
            </h1>
            <p className="text-[10px] text-slate-400 font-medium">
              الواجهة الرئيسية لكاشير ومدير الناصرية لإدارة الطلبات والكتالوج والبيانات
            </p>
          </div>

          <div className="flex items-center gap-2 self-end sm:self-auto">
            {/* Audio Toggle */}
            <button
              onClick={() => setIsMuted(!isMuted)}
              className={`p-2 rounded-xl border transition-all cursor-pointer ${
                isMuted 
                  ? 'bg-red-500/10 border-red-500/30 text-red-200' 
                  : 'bg-emerald-500/10 border-emerald-500/30 text-primary hover:bg-emerald-500/20'
              }`}
              title={isMuted ? 'تفعيل الصوت' : 'كتم الصوت'}
            >
              <Bell size={16} className={isMuted ? 'opacity-50' : 'animate-bounce'} />
            </button>

            {/* Manual Refresh */}
            <button
              onClick={fetchData}
              className="p-2 bg-slate-800 border border-slate-700 text-white rounded-xl hover:bg-slate-700 transition-all flex items-center gap-1.5 text-[11px] font-bold cursor-pointer"
            >
              <RefreshCw size={12} className={loading ? 'animate-spin' : ''} />
              تحديث
            </button>
          </div>
        </div>

        {/* Tab switcher */}
        <div className="flex border-b border-slate-200 font-bold text-xs">
          <button
            onClick={() => { setActiveTab('orders'); setSearchQuery(''); }}
            className={`pb-3.5 px-5 border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
              activeTab === 'orders' 
                ? 'border-primary text-primary font-black' 
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <FileText size={14} />
            طلبات الشحن ({orders.length})
          </button>
          <button
            onClick={() => { setActiveTab('products'); setSearchQuery(''); }}
            className={`pb-3.5 px-5 border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
              activeTab === 'products' 
                ? 'border-primary text-primary font-black' 
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Database size={14} />
            كتالوج المنتجات ({products.length})
          </button>
          <button
            onClick={() => { setActiveTab('categories'); setSearchQuery(''); }}
            className={`pb-3.5 px-5 border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
              activeTab === 'categories' 
                ? 'border-primary text-primary font-black' 
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Tag size={14} />
            إدارة التصنيفات ({categories.length})
          </button>
          <button
            onClick={() => { setActiveTab('stats'); setSearchQuery(''); }}
            className={`pb-3.5 px-5 border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
              activeTab === 'stats' 
                ? 'border-primary text-primary font-black' 
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <BarChart3 size={14} />
            إحصائيات المبيعات
          </button>
          <button
            onClick={() => { setActiveTab('banners'); setSearchQuery(''); }}
            className={`pb-3.5 px-5 border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
              activeTab === 'banners' 
                ? 'border-primary text-primary font-black' 
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Tag size={14} />
            البانرات والكوبونات
          </button>
        </div>

        {/* Tab 1 Content: Orders Feed */}
        {activeTab === 'orders' && (
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            {/* Orders Feed Column */}
            <div className="lg:col-span-8 space-y-6">
              {/* Filter Bar */}
              <div className="bg-white border border-slate-200/60 rounded-3xl p-4 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <div className="flex items-center gap-2 overflow-x-auto pb-1 max-w-full">
                  {['all', 'pending', 'preparing', 'delivering', 'completed'].map((status) => (
                    <button
                      key={`filter-${status}`}
                      onClick={() => setFilterStatus(status)}
                      className={`px-3 py-1.5 rounded-lg text-[10px] font-bold border transition-all whitespace-nowrap cursor-pointer ${
                        filterStatus === status
                          ? 'bg-primary text-white border-primary shadow-xs font-extrabold'
                          : 'bg-gray-50 text-slate-500 border-gray-200 hover:bg-gray-100'
                      }`}
                    >
                      {status === 'all' ? 'جميع الطلبات' : getStatusArabic(status as Order['status'])}
                    </button>
                  ))}
                </div>

                <div className="relative w-full sm:w-60">
                  <input
                    type="text"
                    placeholder="ابحث بالهاتف أو العنوان أو الفاتورة..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="w-full bg-slate-50/50 border border-slate-200 rounded-xl pr-9 pl-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 text-right"
                  />
                  <Search size={14} className="absolute right-3 top-2.5 text-slate-400" />
                </div>
              </div>

              {/* Feed Grid */}
              {loading ? (
                <div className="py-20 bg-white border border-slate-150 rounded-3xl text-center space-y-4 shadow-xs">
                  <div className="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto text-primary" />
                  <p className="text-xs text-slate-500 font-bold">جاري تحميل الفواتير الفورية...</p>
                </div>
              ) : filteredOrders.length === 0 ? (
                <div className="py-20 bg-white border border-slate-150 rounded-3xl text-center space-y-3 shadow-xs">
                  <span className="text-3xl">📄</span>
                  <p className="text-slate-900 font-bold text-sm">لا توجد طلبات تطابق هذا القسم حالياً</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {filteredOrders.map((order) => (
                    <div 
                      key={order.id} 
                      className="bg-white border border-slate-150 hover:border-slate-300 rounded-3xl p-5 shadow-xs transition-all duration-200 space-y-4"
                    >
                      {/* Header */}
                      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div className="flex items-center gap-2 justify-end flex-row-reverse">
                          <span className="font-black text-xs text-slate-900">فاتورة رقم:</span>
                          <span className="bg-slate-100 text-slate-700 text-xs font-black px-2 py-0.5 rounded-md border border-slate-200">
                            #{order.id.substring(0, 8).toUpperCase()}
                          </span>
                          <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full border ${getStatusColorClass(order.status)}`}>
                            {getStatusArabic(order.status)}
                          </span>
                        </div>
                        
                        <div className="flex items-center gap-2">
                          <span className="text-[10px] font-bold text-slate-400">تحديث الحالة:</span>
                          <select
                            value={order.status}
                            onChange={(e) => handleUpdateStatus(order.id, e.target.value as Order['status'])}
                            className="bg-slate-50 border border-slate-200 text-slate-900 rounded-lg px-2 py-1 text-[10px] font-bold focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer"
                          >
                            <option value="pending">قيد الانتظار</option>
                            <option value="preparing">قيد التحضير</option>
                            <option value="delivering">جاري التوصيل</option>
                            <option value="completed">مكتملة</option>
                          </select>
                        </div>
                      </div>

                      {/* Info */}
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold text-slate-650 leading-relaxed">
                        <div className="space-y-1 text-right">
                          <p className="flex items-center gap-1.5 justify-end">
                            {order.delivery_address}
                            <MapPin size={14} className="text-primary flex-shrink-0" />
                          </p>
                          <p className="flex items-center gap-1.5 justify-end" dir="ltr">
                            {order.delivery_phone}
                            <Phone size={14} className="text-primary flex-shrink-0" />
                          </p>
                        </div>
                        <div className="sm:text-left flex flex-col justify-end space-y-1">
                          <p>وسيلة الدفع: <span className="font-bold text-slate-900">{order.payment_method}</span></p>
                          <p className="text-sm font-black text-primary">المجموع: {order.total_price.toFixed(2)} ج.م</p>
                        </div>
                      </div>

                      {/* Items */}
                      <div className="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] font-bold text-slate-700 pr-1">
                          {order.items.map((item: any, idx: number) => (
                            <div key={idx} className="flex justify-between border-l border-slate-200/50 pl-2 pr-1">
                              <span>• {item.name} (×{item.qty})</span>
                              <span className="text-slate-900">{(item.price * item.qty).toFixed(2)} ج.م</span>
                            </div>
                          ))}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Activity Logs Sidebar */}
            <div className="lg:col-span-4 bg-white border border-slate-150 rounded-3xl p-5 shadow-xs space-y-4">
              <h3 className="text-xs font-black text-slate-900 border-b border-slate-100 pb-3.5 flex items-center gap-1.5 justify-end">
                سجل نشاط الخادم الفوري (Live Logs)
                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping" />
              </h3>

              <div className="bg-slate-950 text-emerald-400 font-mono text-[9px] p-4 rounded-2xl h-64 overflow-y-auto space-y-2 select-all leading-normal border border-emerald-950 text-left">
                {logs.length === 0 ? (
                  <p className="text-slate-500 text-center py-24 font-sans font-bold">لا توجد سجلات نشاط للأنظمة حالياً.</p>
                ) : (
                  logs.map((log, idx) => (
                    <p key={idx} className="break-all font-semibold">
                      {log}
                    </p>
                  ))
                )}
              </div>
              
              <div className="bg-slate-50 p-4 rounded-2xl border border-slate-200/60 leading-relaxed text-[10px] text-slate-500 font-semibold">
                🚨 تنبيه صوتي مستمر يعمل للطلبات الفورية الواردة بالوقت الحقيقي عبر Supabase.
              </div>
            </div>
          </div>
        )}

        {/* Tab 2 Content: Products CRUD */}
        {activeTab === 'products' && (
          <div className="space-y-6">
            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
              <button
                onClick={() => openProductForm(null)}
                className="bg-primary hover:bg-primary-dark text-white font-extrabold text-xs px-5 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5 cursor-pointer order-2 sm:order-1 self-stretch sm:self-auto justify-center"
              >
                <Plus size={14} />
                إضافة منتج جديد للكتالوج
              </button>

              <div className="relative w-full sm:w-80 order-1 sm:order-2">
                <input
                  type="text"
                  placeholder="ابحث عن منتج بالاسم أو الوصف..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded-xl pr-9 pl-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 text-right"
                />
                <Search size={14} className="absolute right-3 top-3 text-slate-400" />
              </div>
            </div>

            {loading ? (
              <div className="py-20 text-center space-y-4">
                <Loader2 className="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto text-primary" />
                <p className="text-xs text-slate-500 font-bold">جاري تحميل المنتجات...</p>
              </div>
            ) : filteredProducts.length === 0 ? (
              <div className="bg-white border border-slate-150 rounded-3xl p-12 text-center shadow-xs">
                <p className="text-slate-550 font-bold text-xs">لم نعثر على أي منتجات مطابقة لعملية البحث.</p>
              </div>
            ) : (
              <div className="bg-white border border-slate-150 rounded-3xl overflow-hidden shadow-xs">
                <div className="overflow-x-auto">
                  <table className="w-full text-right border-collapse text-xs font-semibold">
                    <thead>
                      <tr className="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                        <th className="p-3.5">المنتج</th>
                        <th className="p-3.5 text-center">القسم</th>
                        <th className="p-3.5 text-center">السعر (قطاعي)</th>
                        <th className="p-3.5 text-center">سعر الجملة</th>
                        <th className="p-3.5 text-center">الحد الأدنى للجملة</th>
                        <th className="p-3.5 text-center">المخزون</th>
                        <th className="p-3.5 text-center">الحالة</th>
                        <th className="p-3.5 text-center">الإجراءات</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {filteredProducts.map((prod) => {
                        const cat = categories.find(c => c.id === prod.category_id);
                        return (
                          <tr key={prod.id} className="hover:bg-slate-50/50">
                            <td className="p-3 flex items-center gap-3">
                              <div className="w-10 h-10 bg-slate-50 rounded-lg overflow-hidden border border-slate-200 flex-shrink-0">
                                {prod.image_url ? (
                                  <img src={prod.image_url} alt={prod.name} className="w-full h-full object-cover" />
                                ) : (
                                  <div className="w-full h-full flex items-center justify-center text-lg">📦</div>
                                )}
                              </div>
                              <div>
                                <h4 className="font-bold text-slate-900 text-xs line-clamp-1">{prod.name}</h4>
                                <p className="text-[10px] text-slate-400 line-clamp-1 mt-0.5">{prod.description || 'لا يوجد وصف'}</p>
                              </div>
                            </td>
                            <td className="p-3 text-center text-slate-600">{cat?.name || 'غير مصنف'}</td>
                            <td className="p-3 text-center text-slate-900">
                              {prod.sale_price ? (
                                <div className="space-y-0.5">
                                  <span className="text-primary font-bold">{prod.sale_price.toFixed(2)} ج.م</span>
                                  <span className="text-[9px] text-slate-400 line-through block">{prod.price.toFixed(2)} ج.م</span>
                                </div>
                              ) : (
                                <span className="font-bold">{prod.price.toFixed(2)} ج.م</span>
                              )}
                            </td>
                            <td className="p-3 text-center text-slate-900 font-bold">{prod.wholesale_price.toFixed(2)} ج.م</td>
                            <td className="p-3 text-center text-slate-500 font-bold">{prod.wholesale_min_qty} قطعة</td>
                            <td className="p-3 text-center">
                              <span className={`font-bold px-2 py-0.5 rounded text-[10px] ${
                                prod.stock <= 5 
                                  ? 'bg-red-50 text-red-750 border border-red-200' 
                                  : 'bg-slate-50 text-slate-700'
                              }`}>
                                {prod.stock} قطع
                              </span>
                            </td>
                            <td className="p-3 text-center">
                              <span className={`px-2 py-0.5 rounded-full text-[9px] font-bold ${
                                prod.is_available 
                                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' 
                                  : 'bg-red-50 text-red-600 border border-red-200'
                              }`}>
                                {prod.is_available ? 'متاح' : 'غير متاح'}
                              </span>
                            </td>
                            <td className="p-3 text-center">
                              <div className="flex items-center justify-center gap-2">
                                <button
                                  onClick={() => openProductForm(prod)}
                                  className="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg cursor-pointer transition-colors"
                                  title="تعديل منتج"
                                >
                                  <Edit size={12} />
                                </button>
                                <button
                                  onClick={() => handleDeleteProduct(prod.id, prod.name)}
                                  className="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg cursor-pointer transition-colors"
                                  title="حذف منتج"
                                >
                                  <Trash2 size={12} />
                                </button>
                              </div>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
            )}
          </div>
        )}

        {/* Tab 3 Content: Categories CRUD */}
        {activeTab === 'categories' && (
          <div className="space-y-6">
            <div>
              <button
                onClick={() => openCategoryForm(null)}
                className="bg-primary hover:bg-primary-dark text-white font-extrabold text-xs px-5 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5 cursor-pointer self-stretch sm:self-auto justify-center"
              >
                <Plus size={14} />
                إضافة قسم تصنيف جديد
              </button>
            </div>

            {loading ? (
              <div className="py-20 text-center space-y-4">
                <Loader2 className="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto text-primary" />
                <p className="text-xs text-slate-500 font-bold">جاري تحميل التصنيفات...</p>
              </div>
            ) : categories.length === 0 ? (
              <div className="bg-white border border-slate-150 rounded-3xl p-12 text-center shadow-xs">
                <p className="text-slate-500 font-bold text-xs">لا توجد أي تصنيفات مسجلة في كتالوج المتجر بعد.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {categories.map((cat) => (
                  <div key={cat.id} className="bg-white border border-slate-150 rounded-3xl p-5 shadow-xs flex justify-between items-center">
                    <div className="flex items-center gap-3">
                      <div className="w-12 h-12 rounded-full overflow-hidden border border-slate-200 flex-shrink-0 bg-slate-50 p-0.5">
                        <img 
                          src={cat.image_url || 'https://images.unsplash.com/photo-1534482421-64566f976cfa?w=100'} 
                          alt={cat.name} 
                          className="w-full h-full object-cover rounded-full" 
                        />
                      </div>
                      <div className="text-right">
                        <h4 className="font-black text-slate-900 text-xs">{cat.name}</h4>
                        <p className="text-[10px] text-slate-400 font-bold mt-0.5">Slug: {cat.slug}</p>
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      <button
                        onClick={() => openCategoryForm(cat)}
                        className="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg cursor-pointer"
                        title="تعديل"
                      >
                        <Edit size={12} />
                      </button>
                      <button
                        onClick={() => handleDeleteCategory(cat.id, cat.name)}
                        className="p-1.5 bg-red-50 hover:bg-red-100 text-red-650 rounded-lg cursor-pointer"
                        title="حذف"
                      >
                        <Trash2 size={12} />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {/* Tab 4 Content: Analytics & Stats */}
        {activeTab === 'stats' && (
          <div className="space-y-8">
            {/* KPI Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              
              <div className="bg-white border border-slate-150 rounded-3xl p-5 shadow-xs flex items-center justify-between text-right">
                <span className="p-3 bg-primary/10 text-primary rounded-2xl text-xl">💰</span>
                <div>
                  <p className="text-[10px] text-slate-400 font-bold">إجمالي الإيرادات (المبيعات)</p>
                  <p className="text-lg font-black text-slate-900 mt-1">{statsRevenue.toFixed(2)} ج.م</p>
                </div>
              </div>

              <div className="bg-white border border-slate-150 rounded-3xl p-5 shadow-xs flex items-center justify-between text-right">
                <span className="p-3 bg-blue-50 text-blue-600 rounded-2xl text-xl">📦</span>
                <div>
                  <p className="text-[10px] text-slate-400 font-bold">إجمالي الفواتير المسجلة</p>
                  <p className="text-lg font-black text-slate-900 mt-1">{orders.length} طلبات</p>
                </div>
              </div>

              <div className="bg-white border border-slate-150 rounded-3xl p-5 shadow-xs flex items-center justify-between text-right">
                <span className="p-3 bg-emerald-50 text-emerald-600 rounded-2xl text-xl">✓</span>
                <div>
                  <p className="text-[10px] text-slate-400 font-bold">الطلبات المكتملة (المسلمة)</p>
                  <p className="text-lg font-black text-slate-900 mt-1">{statsCompleted} طلبات</p>
                </div>
              </div>

              <div className="bg-white border border-slate-150 rounded-3xl p-5 shadow-xs flex items-center justify-between text-right">
                <span className="p-3 bg-red-50 text-red-650 rounded-2xl text-xl">⚠️</span>
                <div>
                  <p className="text-[10px] text-slate-400 font-bold">منتجات في نقص مخزون</p>
                  <p className="text-lg font-black text-slate-950 mt-1">{statsLowStock} أصناف</p>
                </div>
              </div>

            </div>

            {/* Low stock list details */}
            <div className="bg-white border border-slate-150 rounded-3xl p-6 shadow-xs max-w-2xl mx-auto space-y-4">
              <h3 className="text-xs font-black text-slate-900 flex items-center gap-1.5 justify-end">
                أصناف أوشكت على النفاد (المخزون 5 أو أقل)
                <AlertTriangle size={15} className="text-red-550" />
              </h3>

              {products.filter(p => p.stock <= 5).length === 0 ? (
                <p className="text-[11px] text-slate-500 font-bold text-center py-6">مستويات المخزون لجميع الأصناف آمنة وممتازة 👍</p>
              ) : (
                <div className="divide-y divide-slate-100 max-h-60 overflow-y-auto pr-1">
                  {products.filter(p => p.stock <= 5).map(p => (
                    <div key={p.id} className="py-2.5 flex justify-between items-center text-xs font-semibold text-slate-650">
                      <span className="bg-red-50 border border-red-200 text-red-700 px-2 py-0.5 rounded text-[10px] font-bold">المتبقي: {p.stock} قطع</span>
                      <span className="text-slate-800 font-bold text-right">{p.name}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {/* Tab 5 Content: Banners & Coupons Slider Manager */}
        {activeTab === 'banners' && (
          <div className="space-y-8 animate-in fade-in duration-200">
            {/* DB Connection Warning */}
            {dbErrorWarning && (
              <div className="bg-amber-50 border border-amber-250 p-4 rounded-3xl text-right text-xs text-amber-900 space-y-2.5">
                <div className="font-black flex items-center gap-1.5 justify-end">
                  تنبيه: جداول البانرات والكوبونات غير موجودة في قاعدة البيانات
                  <AlertTriangle size={15} className="text-amber-600" />
                </div>
                <p className="font-semibold leading-relaxed">
                  نحن نعمل حالياً بالبيانات الافتراضية المؤقتة. لتمكين لوحة التحكم من حفظ وتعديل البيانات مباشرة في قاعدة البيانات، يرجى نسخ كود SQL التالي وتشغيله في <strong>SQL Editor</strong> الخاص بـ Supabase:
                </p>
                <textarea
                  readOnly
                  rows={4}
                  value={`-- SQL to run in Supabase SQL Editor:
CREATE TABLE IF NOT EXISTS public.banners (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title TEXT NOT NULL,
    image_url TEXT NOT NULL,
    link_url TEXT DEFAULT '/' NOT NULL,
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

CREATE TABLE IF NOT EXISTS public.coupons (
    code TEXT PRIMARY KEY,
    description TEXT NOT NULL,
    discount_type TEXT NOT NULL,
    discount_value NUMERIC(10, 2) NOT NULL,
    min_order_amount NUMERIC(10, 2) DEFAULT 0 NOT NULL,
    points_cost INT DEFAULT 0 NOT NULL,
    is_active BOOLEAN DEFAULT true NOT NULL,
    created_at TIMESTAMPTZ DEFAULT now() NOT NULL
);

ALTER TABLE public.banners ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.coupons ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Banners are readable by everyone" ON public.banners FOR SELECT USING (true);
CREATE POLICY "Admins have full access on banners" ON public.banners FOR ALL USING (public.is_admin(auth.uid()));
CREATE POLICY "Coupons are readable by everyone" ON public.coupons FOR SELECT USING (true);
CREATE POLICY "Admins have full access on coupons" ON public.coupons FOR ALL USING (public.is_admin(auth.uid()));`}
                  className="w-full bg-slate-900 text-slate-100 p-3 rounded-xl font-mono text-[10px] focus:outline-none"
                  dir="ltr"
                />
              </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
              
              {/* Right Column: Manage Banners */}
              <div className="lg:col-span-6 space-y-6">
                <div className="bg-white border border-slate-150 rounded-3xl p-6 shadow-xs space-y-4">
                  <h3 className="text-xs font-black text-slate-900 border-b border-slate-100 pb-3">إضافة بانر إعلاني جديد 🖼️</h3>
                  <form onSubmit={handleAddBanner} className="space-y-4 text-xs font-semibold text-slate-700">
                    <div className="space-y-1">
                      <label className="block text-right">عنوان البانر *</label>
                      <input
                        type="text"
                        required
                        placeholder="مثال: خصم 30% على منتجات الأرز"
                        value={newBannerTitle}
                        onChange={(e) => setNewBannerTitle(e.target.value)}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-right focus:outline-none focus:ring-1 focus:ring-primary text-slate-950 font-bold"
                      />
                    </div>
                    <div className="space-y-1">
                      <label className="block text-right">رابط صورة البانر (URL) *</label>
                      <input
                        type="text"
                        required
                        placeholder="https://example.com/image.jpg"
                        value={newBannerImageUrl}
                        onChange={(e) => setNewBannerImageUrl(e.target.value)}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-left focus:outline-none focus:ring-1 focus:ring-primary text-slate-950 font-bold"
                        dir="ltr"
                      />
                    </div>
                    <div className="space-y-1">
                      <label className="block text-right">رابط التوجيه (Link URL)</label>
                      <input
                        type="text"
                        placeholder="/products?search=أرز"
                        value={newBannerLinkUrl}
                        onChange={(e) => setNewBannerLinkUrl(e.target.value)}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-left focus:outline-none focus:ring-1 focus:ring-primary text-slate-950 font-bold"
                        dir="ltr"
                      />
                    </div>
                    <button
                      type="submit"
                      disabled={saveLoading}
                      className="w-full py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
                    >
                      {saveLoading ? <Loader2 size={12} className="animate-spin" /> : 'إضافة البانر 🖼️'}
                    </button>
                  </form>
                </div>

                <div className="bg-white border border-slate-150 rounded-3xl p-6 shadow-xs space-y-4">
                  <h3 className="text-xs font-black text-slate-900 border-b border-slate-100 pb-3">البانرات الحالية ({banners.length})</h3>
                  <div className="space-y-3.5 max-h-96 overflow-y-auto pr-1">
                    {banners.length === 0 ? (
                      <p className="text-[11px] text-slate-400 font-bold text-center py-6">لا يوجد بانرات معروضة حالياً</p>
                    ) : (
                      banners.map((banner) => (
                        <div key={banner.id} className="border border-slate-150 rounded-2xl overflow-hidden shadow-xs relative group bg-slate-50 flex items-center gap-3 p-3">
                          <div className="w-16 h-12 bg-slate-200 rounded-lg overflow-hidden flex-shrink-0">
                            <img src={banner.image_url} alt={banner.title} className="w-full h-full object-cover" />
                          </div>
                          <div className="flex-1 min-w-0 text-right text-xs">
                            <h4 className="font-bold text-slate-850 truncate">{banner.title}</h4>
                            <p className="text-[9px] text-slate-400 mt-0.5 truncate" dir="ltr">{banner.link_url}</p>
                          </div>
                          <button
                            onClick={() => handleDeleteBanner(banner.id)}
                            className="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition-all cursor-pointer flex-shrink-0"
                            title="حذف البانر"
                          >
                            <Trash2 size={15} />
                          </button>
                        </div>
                      ))
                    )}
                  </div>
                </div>
              </div>

              {/* Left Column: Manage Coupons */}
              <div className="lg:col-span-6 space-y-6">
                <div className="bg-white border border-slate-150 rounded-3xl p-6 shadow-xs space-y-4">
                  <h3 className="text-xs font-black text-slate-900 border-b border-slate-100 pb-3">إضافة كود خصم جديد 🎫</h3>
                  <form onSubmit={handleAddCoupon} className="space-y-4 text-xs font-semibold text-slate-700">
                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-1">
                        <label className="block text-right">كود الخصم *</label>
                        <input
                          type="text"
                          required
                          placeholder="مثال: OFFER20"
                          value={newCouponCode}
                          onChange={(e) => setNewCouponCode(e.target.value)}
                          className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-center focus:outline-none focus:ring-1 focus:ring-primary text-slate-950 font-black uppercase"
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="block text-right">نوع الخصم *</label>
                        <select
                          value={newCouponType}
                          onChange={(e) => setNewCouponType(e.target.value as any)}
                          className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-right focus:outline-none focus:ring-1 focus:ring-primary text-slate-955 font-bold"
                        >
                          <option value="percentage">نسبة مئوية (%)</option>
                          <option value="fixed">خصم ثابت (ج.م)</option>
                          <option value="points">استبدال بنقاط ذهبية</option>
                        </select>
                      </div>
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                      <div className="space-y-1">
                        <label className="block text-right">قيمة الخصم *</label>
                        <input
                          type="number"
                          required
                          min="1"
                          placeholder="مثال: 15 أو 50"
                          value={newCouponValue}
                          onChange={(e) => setNewCouponValue(e.target.value ? Number(e.target.value) : '')}
                          className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-center focus:outline-none focus:ring-1 focus:ring-primary text-slate-950 font-bold"
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="block text-right">أقل قيمة طلب (ج.م)</label>
                        <input
                          type="number"
                          min="0"
                          placeholder="مثال: 300"
                          value={newCouponMinOrder}
                          onChange={(e) => setNewCouponMinOrder(e.target.value ? Number(e.target.value) : '')}
                          className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-center focus:outline-none focus:ring-1 focus:ring-primary text-slate-950 font-bold"
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="block text-right">تكلفة النقاط</label>
                        <input
                          type="number"
                          min="0"
                          disabled={newCouponType !== 'points'}
                          placeholder={newCouponType === 'points' ? 'مثال: 100' : 'غير متوفر'}
                          value={newCouponPointsCost}
                          onChange={(e) => setNewCouponPointsCost(e.target.value ? Number(e.target.value) : '')}
                          className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-center focus:outline-none focus:ring-1 focus:ring-primary text-slate-955 font-bold disabled:opacity-50"
                        />
                      </div>
                    </div>

                    <div className="space-y-1">
                      <label className="block text-right">وصف كود الخصم *</label>
                      <input
                        type="text"
                        required
                        placeholder="مثال: خصم بقيمة 50 جنيه للطلبات فوق 300 جنيه"
                        value={newCouponDesc}
                        onChange={(e) => setNewCouponDesc(e.target.value)}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-right focus:outline-none focus:ring-1 focus:ring-primary text-slate-950 font-bold"
                      />
                    </div>

                    <button
                      type="submit"
                      disabled={saveLoading}
                      className="w-full py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
                    >
                      {saveLoading ? <Loader2 size={12} className="animate-spin" /> : 'إضافة كود الخصم 🎫'}
                    </button>
                  </form>
                </div>

                <div className="bg-white border border-slate-150 rounded-3xl p-6 shadow-xs space-y-4">
                  <h3 className="text-xs font-black text-slate-900 border-b border-slate-100 pb-3">أكواد الخصم الحالية ({coupons.length})</h3>
                  <div className="space-y-3.5 max-h-96 overflow-y-auto pr-1">
                    {coupons.length === 0 ? (
                      <p className="text-[11px] text-slate-400 font-bold text-center py-6">لا يوجد أكواد خصم مفعلة حالياً</p>
                    ) : (
                      coupons.map((coupon) => (
                        <div key={coupon.code} className="border border-dashed border-primary/30 rounded-2xl bg-white p-3.5 relative overflow-hidden flex items-center justify-between text-right text-xs group">
                          {/* Dashed ticket stub cutouts */}
                          <div className="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-4 bg-slate-50 border-r border-slate-200 rounded-full" />
                          <div className="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 bg-slate-50 border-l border-slate-200 rounded-full" />
                          
                          <div className="flex items-center gap-3">
                            <span className="p-2.5 bg-primary/10 text-primary rounded-xl font-black text-[10px] sm:text-xs">
                              {coupon.discount_type === 'percentage' ? `${coupon.discount_value}%` : coupon.discount_type === 'points' ? '⭐' : 'ج.م'}
                            </span>
                            <div>
                              <h4 className="font-black text-slate-800 flex items-center gap-1.5 flex-row-reverse">
                                <code>{coupon.code}</code>
                                {coupon.min_order_amount > 0 && (
                                  <span className="text-[9px] bg-amber-50 border border-amber-200 text-amber-800 px-1.5 py-0.2 rounded">طلب {coupon.min_order_amount}+</span>
                                )}
                              </h4>
                              <p className="text-[10px] text-slate-500 mt-0.5">{coupon.description}</p>
                            </div>
                          </div>

                          <button
                            onClick={() => handleDeleteCoupon(coupon.code)}
                            className="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition-all cursor-pointer flex-shrink-0"
                            title="حذف الكود"
                          >
                            <Trash2 size={15} />
                          </button>
                        </div>
                      ))
                    )}
                  </div>
                </div>
              </div>

            </div>
          </div>
        )}

      </main>

      {/* Product CRUD Modal Popup */}
      {showProductModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div onClick={() => setShowProductModal(false)} className="absolute inset-0 bg-slate-950/40 backdrop-blur-xs" />
          <div className="bg-white border border-slate-200 rounded-3xl p-6 max-w-lg w-full relative z-10 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
            
            <div className="flex justify-between items-center border-b border-slate-100 pb-3">
              <button onClick={() => setShowProductModal(false)} className="text-slate-400 hover:text-slate-800 transition-colors">
                <X size={18} />
              </button>
              <h3 className="font-black text-sm text-slate-900">
                {editingProduct ? 'تعديل منتج في الكتالوج' : 'إضافة منتج جديد للكتالوج'}
              </h3>
            </div>

            {modalError && (
              <p className="text-[10px] bg-red-50 text-red-650 font-bold p-2.5 rounded-lg border border-red-100 text-right">{modalError}</p>
            )}

            <form onSubmit={handleSaveProduct} className="space-y-4 text-right text-xs">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <div className="space-y-1">
                  <label className="font-bold text-slate-700 block">اسم المنتج *</label>
                  <input
                    type="text"
                    required
                    value={prodName}
                    onChange={(e) => setProdName(e.target.value)}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:outline-none focus:ring-1 focus:ring-primary text-slate-900"
                  />
                </div>

                <div className="space-y-1">
                  <label className="font-bold text-slate-700 block">القسم الرئيسي *</label>
                  <select
                    value={prodCategoryId}
                    onChange={(e) => setProdCategoryId(e.target.value)}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 font-bold cursor-pointer"
                  >
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>

                <div className="space-y-1">
                  <label className="font-bold text-slate-700 block">السعر الأساسي (قطاعي) *</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    value={prodPrice}
                    onChange={(e) => setProdPrice(Number(e.target.value))}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-left focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 font-bold"
                  />
                </div>

                <div className="space-y-1">
                  <label className="font-bold text-slate-700 block">السعر المخفض (sale_price - اختياري)</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    value={prodSalePrice}
                    onChange={(e) => setProdSalePrice(e.target.value === '' ? '' : Number(e.target.value))}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-left focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 font-bold"
                  />
                </div>

                <div className="space-y-1">
                  <label className="font-bold text-slate-700 block">سعر الجملة للتاجر *</label>
                  <input
                    type="number"
                    step="0.01;0"
                    min="0"
                    required
                    value={prodWholesalePrice}
                    onChange={(e) => setProdWholesalePrice(Number(e.target.value))}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-left focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 font-bold"
                  />
                </div>

                <div className="space-y-1">
                  <label className="font-bold text-slate-700 block">الحد الأدنى لطلب الجملة *</label>
                  <input
                    type="number"
                    min="1"
                    required
                    value={prodWholesaleMinQty}
                    onChange={(e) => setProdWholesaleMinQty(Number(e.target.value))}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-left focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 font-bold"
                  />
                </div>

                <div className="space-y-1">
                  <label className="font-bold text-slate-700 block">المخزون المتاح حالياً *</label>
                  <input
                    type="number"
                    min="0"
                    required
                    value={prodStock}
                    onChange={(e) => setProdStock(Number(e.target.value))}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-left focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 font-bold"
                  />
                </div>

                <div className="space-y-1">
                  <label className="font-bold text-slate-700 block">رابط الصورة (URL)</label>
                  <input
                    type="text"
                    value={prodImageUrl}
                    onChange={(e) => setProdImageUrl(e.target.value)}
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 text-left"
                  />
                </div>

              </div>

              <div className="space-y-1">
                <label className="font-bold text-slate-700 block">وصف المنتج</label>
                <textarea
                  rows={2}
                  value={prodDesc}
                  onChange={(e) => setProdDesc(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 leading-relaxed"
                />
              </div>

              <div className="flex items-center gap-2 justify-end py-1">
                <label className="font-bold text-slate-700 cursor-pointer" htmlFor="is-available-check">متاح للعرض والبيع في الموقع</label>
                <input
                  type="checkbox"
                  id="is-available-check"
                  checked={prodIsAvailable}
                  onChange={(e) => setProdIsAvailable(e.target.checked)}
                  className="text-primary focus:ring-primary rounded cursor-pointer"
                />
              </div>

              <div className="flex gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setShowProductModal(false)}
                  className="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold transition-all cursor-pointer text-center"
                >
                  إلغاء
                </button>
                <button
                  type="submit"
                  disabled={saveLoading}
                  className="flex-1 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                >
                  {saveLoading ? <Loader2 size={12} className="animate-spin" /> : 'حفظ المنتج'}
                </button>
              </div>

            </form>
          </div>
        </div>
      )}

      {/* Category CRUD Modal Popup */}
      {showCategoryModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div onClick={() => setShowCategoryModal(false)} className="absolute inset-0 bg-slate-950/40 backdrop-blur-xs" />
          <div className="bg-white border border-slate-200 rounded-3xl p-6 max-w-sm w-full relative z-10 shadow-2xl space-y-4">
            
            <div className="flex justify-between items-center border-b border-slate-100 pb-3">
              <button onClick={() => setShowCategoryModal(false)} className="text-slate-400 hover:text-slate-800 transition-colors">
                <X size={18} />
              </button>
              <h3 className="font-black text-sm text-slate-900">
                {editingCategory ? 'تعديل قسم تصنيف' : 'إضافة قسم تصنيف جديد'}
              </h3>
            </div>

            {modalError && (
              <p className="text-[10px] bg-red-50 text-red-650 font-bold p-2.5 rounded-lg border border-red-100 text-right">{modalError}</p>
            )}

            <form onSubmit={handleSaveCategory} className="space-y-4 text-right text-xs">
              <div className="space-y-1">
                <label className="font-bold text-slate-700 block">اسم التصنيف *</label>
                <input
                  type="text"
                  required
                  value={catName}
                  onChange={(e) => setCatName(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 font-bold"
                />
              </div>

              <div className="space-y-1">
                <label className="font-bold text-slate-700 block">الرابط الفرعي (Slug) *</label>
                <input
                  type="text"
                  required
                  placeholder="مثال: frozen, grocery"
                  value={catSlug}
                  onChange={(e) => setCatSlug(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 text-left"
                />
              </div>

              <div className="space-y-1">
                <label className="font-bold text-slate-700 block">رابط صورة القسم</label>
                <input
                  type="text"
                  value={catImageUrl}
                  onChange={(e) => setCatImageUrl(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:outline-none focus:ring-1 focus:ring-primary text-slate-900 text-left"
                />
              </div>

              <div className="flex gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setShowCategoryModal(false)}
                  className="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold transition-all cursor-pointer text-center"
                >
                  إلغاء
                </button>
                <button
                  type="submit"
                  disabled={saveLoading}
                  className="flex-1 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                >
                  {saveLoading ? <Loader2 size={12} className="animate-spin" /> : 'حفظ التصنيف'}
                </button>
              </div>

            </form>
          </div>
        </div>
      )}

    </div>
  );
}

export default function AdminOrdersPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center space-y-4">
          <div className="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto text-primary" />
          <p className="text-sm font-bold text-slate-650 font-sans">تحميل بوابة الكاشير والكتالوج...</p>
        </div>
      </div>
    }>
      <AdminOrdersContent />
    </Suspense>
  );
}
