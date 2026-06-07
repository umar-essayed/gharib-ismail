'use client';

import React, { useState, useEffect, Suspense } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import Navbar from '@/components/Navbar';
import Footer from '@/components/Footer';
import ProductCard from '@/components/ProductCard';
import { Product, Category } from '@/types';
import { supabase } from '@/lib/supabase';
import { mockCategories, mockProducts } from '@/lib/mockData';
import { Search, MapPin, Phone, Mail, Send } from 'lucide-react';
import Link from 'next/link';

function ProductsContent() {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [searchVal, setSearchVal] = useState('');
  const [loading, setLoading] = useState(true);
  const [layoutMode, setLayoutMode] = useState<'grid' | 'list'>('grid');

  // Pagination states
  const [page, setPage] = useState(0);
  const [totalCount, setTotalCount] = useState(0);
  const [loadingMore, setLoadingMore] = useState(false);
  
  const searchParams = useSearchParams();
  const router = useRouter();

  // Read URL search params
  const urlSearch = searchParams.get('search') || '';
  const urlCategory = searchParams.get('category') || '';

  const PAGE_SIZE = 50;

  // Synchronize URL query params with component state
  useEffect(() => {
    if (urlSearch) {
      setSearchVal(urlSearch);
    } else {
      setSearchVal('');
    }

    if (urlCategory) {
      setSelectedCategory(urlCategory);
    } else {
      setSelectedCategory(null);
    }
  }, [urlSearch, urlCategory]);

  // Fetch products function
  async function fetchProducts(pageNum: number, categoryId: string | null, searchString: string, append: boolean) {
    try {
      if (pageNum === 0) {
        setLoading(true);
      } else {
        setLoadingMore(true);
      }

      let query = supabase
        .from('products')
        .select('*', { count: 'exact' })
        .eq('is_available', true);

      if (categoryId) {
        query = query.eq('category_id', categoryId);
      }

      if (searchString) {
        query = query.or(`name.ilike.%${searchString}%,description.ilike.%${searchString}%`);
      }

      // Order by id desc (newest first)
      query = query.order('id', { ascending: false });

      const from = pageNum * PAGE_SIZE;
      const to = from + PAGE_SIZE - 1;
      query = query.range(from, to);

      const { data: dbProds, count, error } = await query;

      if (error) throw error;

      const normalizedProds = (dbProds || []).map(p => ({
        ...p,
        price: Number(p.price),
        sale_price: p.sale_price !== null ? Number(p.sale_price) : null,
        wholesale_price: Number(p.wholesale_price)
      })) as Product[];

      if (append) {
        setProducts(prev => [...prev, ...normalizedProds]);
      } else {
        setProducts(normalizedProds);
      }

      if (count !== null) {
        setTotalCount(count);
      }
    } catch (err) {
      console.error('Failed to load products list', err);
      if (pageNum === 0) {
        setProducts(mockProducts);
        setTotalCount(mockProducts.length);
      }
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }

  // Load categories once
  useEffect(() => {
    async function loadCategories() {
      try {
        const { data: dbCats } = await supabase
          .from('categories')
          .select('*')
          .order('name', { ascending: true });
          
        if (dbCats && dbCats.length > 0) {
          setCategories(dbCats as Category[]);
        } else {
          setCategories(mockCategories);
        }
      } catch (err) {
        console.error('Failed to load categories', err);
        setCategories(mockCategories);
      }
    }
    loadCategories();
  }, []);

  // Trigger product fetch on search or category url change
  useEffect(() => {
    setPage(0);
    fetchProducts(0, urlCategory || null, urlSearch || '', false);
  }, [urlSearch, urlCategory]);

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    router.push(`/products?search=${encodeURIComponent(searchVal.trim())}${selectedCategory ? `&category=${selectedCategory}` : ''}`);
  };

  const handleCategorySelect = (categoryId: string | null) => {
    setSelectedCategory(categoryId);
    const searchPart = searchVal ? `search=${encodeURIComponent(searchVal)}` : '';
    const catPart = categoryId ? `category=${categoryId}` : '';
    const query = [searchPart, catPart].filter(Boolean).join('&');
    router.push(`/products${query ? `?${query}` : ''}`);
  };

  const handleLoadMore = () => {
    const nextPage = page + 1;
    setPage(nextPage);
    fetchProducts(nextPage, urlCategory || null, urlSearch || '', true);
  };

  return (
    <div className="flex-1 flex flex-col min-h-screen bg-[#f9fafb]">
      <Navbar />

      <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full space-y-8">
        
        {/* Page title */}
        <div className="text-right space-y-1">
          <h1 className="text-2xl font-black text-gray-900">جميع المنتجات والمواد الغذائية</h1>
          <p className="text-xs text-gray-500 font-semibold">تصفح كتالوج السلع بالكامل بأسعار التجزئة والجملة للجميع</p>
        </div>

        {/* Filter Toolbar (Search input & category tabs) */}
        <div className="bg-white border border-gray-150 rounded-2xl p-5 shadow-xs space-y-4">
          
          {/* Inner Search bar */}
          <form onSubmit={handleSearchSubmit} className="relative max-w-md">
            <input
              type="text"
              placeholder="ابحث في قائمة المنتجات..."
              value={searchVal}
              onChange={(e) => setSearchVal(e.target.value)}
              className="w-full bg-gray-50 border border-gray-200 text-gray-900 pr-10 pl-4 py-2 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-right font-bold"
            />
            <Search size={14} className="absolute right-3.5 top-3 text-gray-400" />
          </form>

          <div className="w-full h-px bg-gray-100" />

          {/* Category Tabs list */}
          <div className="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none text-xs flex-row-reverse">
            <button
              onClick={() => handleCategorySelect(null)}
              className={`px-4 py-2 rounded-lg font-bold border transition-all whitespace-nowrap cursor-pointer ${
                selectedCategory === null
                  ? 'bg-primary text-white border-primary shadow-xs'
                  : 'bg-gray-50 text-gray-550 border-gray-200 hover:bg-gray-100'
              }`}
            >
              الكل
            </button>
            {categories.map((cat) => (
              <button
                key={cat.id}
                onClick={() => handleCategorySelect(cat.id)}
                className={`px-4 py-2 rounded-lg font-bold border transition-all whitespace-nowrap cursor-pointer ${
                  selectedCategory === cat.id
                    ? 'bg-primary text-white border-primary shadow-xs'
                    : 'bg-gray-50 text-gray-555 border-gray-200 hover:bg-gray-100'
                }`}
              >
                {cat.name}
              </button>
            ))}
          </div>

        </div>

        {/* Layout Mode selector & count indicator */}
        <div className="flex flex-col sm:flex-row items-center justify-between border-b border-gray-200 pb-3 gap-3 text-right">
          {/* Layout Toggle Buttons */}
          <div className="flex bg-gray-100 p-1 rounded-xl border border-gray-200/50">
            <button
              onClick={() => setLayoutMode('list')}
              className={`px-3.5 py-1.5 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer ${
                layoutMode === 'list' 
                  ? 'bg-white text-primary shadow-xs' 
                  : 'text-gray-550 hover:text-gray-800'
              }`}
            >
              <span>عرض بالعرض ☰</span>
            </button>
            <button
              onClick={() => setLayoutMode('grid')}
              className={`px-3.5 py-1.5 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer ${
                layoutMode === 'grid' 
                  ? 'bg-white text-primary shadow-xs' 
                  : 'text-gray-550 hover:text-gray-800'
              }`}
            >
              <span>عرض بالطول ⚏</span>
            </button>
          </div>
          <span className="text-xs text-gray-500 font-bold">
            وجدنا {totalCount} صنف متاح (معروض حالياً {products.length})
          </span>
        </div>

        {/* Products Catalog Display Grid */}
        {loading ? (
          <div className="py-20 flex flex-col items-center justify-center text-center space-y-4">
            <div className="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin" />
            <p className="text-xs text-gray-550 font-bold">جاري تحميل المنتجات...</p>
          </div>
        ) : products.length === 0 ? (
          <div className="py-20 bg-white border border-gray-150 rounded-2xl text-center space-y-3 max-w-sm mx-auto shadow-xs">
            <span className="text-3xl">🔍</span>
            <p className="text-gray-905 font-bold text-xs">لم نعثر على أي منتجات مطابقة لخيارات الفلترة.</p>
          </div>
        ) : (
          <>
              <div className={`grid gap-4 sm:gap-6 ${
                layoutMode === 'list' 
                  ? 'grid-cols-1 md:grid-cols-2' 
                  : 'grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
              }`}>
                {products.map((product) => (
                  <ProductCard key={product.id} product={product} layout={layoutMode} />
                ))}
              </div>

            {/* Load More Button */}
            {products.length < totalCount && (
              <div className="flex justify-center pt-8">
                <button
                  onClick={handleLoadMore}
                  disabled={loadingMore}
                  className="px-6 py-3 bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded-xl shadow-xs hover:bg-gray-50 transition-all cursor-pointer flex items-center gap-2 disabled:opacity-50"
                >
                  {loadingMore ? (
                    <>
                      <div className="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin" />
                      <span>جاري التحميل...</span>
                    </>
                  ) : (
                    <span>عرض المزيد من المنتجات 🡓</span>
                  )}
                </button>
              </div>
            )}
          </>
        )}

      </main>

      <Footer />
    </div>
  );
}

export default function ProductsClient() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center space-y-4">
          <div className="w-12 h-12 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="text-sm font-bold text-gray-600">تحميل كتالوج المنتجات...</p>
        </div>
      </div>
    }>
      <ProductsContent />
    </Suspense>
  );
}
