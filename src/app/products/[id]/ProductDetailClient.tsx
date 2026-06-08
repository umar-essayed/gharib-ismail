'use client';

import React, { use, useState, useEffect } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useCart } from '@/context/CartContext';
import Navbar from '@/components/Navbar';
import ProductCard from '@/components/ProductCard';
import { Product } from '@/types';
import { supabase } from '@/lib/supabase';
import { isUuid, slugify } from '@/lib/utils';
import { mockProducts } from '@/lib/mockData';
import { 
  ArrowRight, 
  ShoppingBag, 
  Heart, 
  Truck, 
  ShieldCheck, 
  CornerUpLeft, 
  Plus, 
  Minus,
  Sparkles,
  Layers,
  ChevronLeft,
  ChevronRight,
  Star,
  Share2
} from 'lucide-react';

export default function ProductDetailClient({ id }: { id: string }) {
  const decodedId = decodeURIComponent(id);
  const router = useRouter();
  const { addToCart, toggleWishlist, isInWishlist } = useCart();

  const [product, setProduct] = useState<Product | null>(null);
  const [relatedProducts, setRelatedProducts] = useState<Product[]>([]);
  const [quantity, setQuantity] = useState(1);
  const [loading, setLoading] = useState(true);
  const [addedMessage, setAddedMessage] = useState(false);

  // Gallery active index state
  const [activeImageIndex, setActiveImageIndex] = useState(0);

  // Share state
  const [shareSuccess, setShareSuccess] = useState(false);

  // Reviews state
  const [reviews, setReviews] = useState<any[]>([]);
  const [loadingReviews, setLoadingReviews] = useState(true);
  const [submittingReview, setSubmittingReview] = useState(false);

  // Review form inputs
  const [reviewName, setReviewName] = useState('');
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewComment, setReviewComment] = useState('');
  const [reviewSuccess, setReviewSuccess] = useState(false);
  const [reviewsDbWarning, setReviewsDbWarning] = useState(false);

  // Parse product images
  const getProductImages = (url: string | null | undefined): string[] => {
    if (!url) return [];
    const trimmed = url.trim();
    if (trimmed.startsWith('[') && trimmed.endsWith(']')) {
      try {
        const parsed = JSON.parse(trimmed);
        if (Array.isArray(parsed)) {
          return parsed.map(item => String(item).trim()).filter(Boolean);
        }
      } catch (e) {
        // Fallback
      }
    }
    return trimmed.split(',').map(item => item.trim()).filter(Boolean);
  };

  const images = product ? getProductImages(product.image_url) : [];

  const handlePrevImage = () => {
    setActiveImageIndex(prev => (prev === 0 ? images.length - 1 : prev - 1));
  };

  const handleNextImage = () => {
    setActiveImageIndex(prev => (prev === images.length - 1 ? 0 : prev + 1));
  };

  const handleShareProduct = async (e?: React.MouseEvent) => {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    if (typeof window === 'undefined') return;
    const shareUrl = `${window.location.origin}/products/${id}`;
    const shareTitle = product?.name || 'تفاصيل المنتج بالناصرية جملة ماركت';
    const activePrice = product?.sale_price || product?.price || 0;
    const shareText = `شاهد هذا المنتج الرائع "${product?.name}" في الناصرية جملة ماركت بسعر ${activePrice.toFixed(2)} ج.م!`;

    if (navigator.share) {
      try {
        await navigator.share({
          title: shareTitle,
          text: shareText,
          url: shareUrl,
        });
      } catch (err) {
        console.warn('Web Share API failed, copying instead.', err);
        copyLink(shareUrl);
      }
    } else {
      copyLink(shareUrl);
    }
  };

  const copyLink = (url: string) => {
    navigator.clipboard.writeText(url);
    setShareSuccess(true);
    setTimeout(() => setShareSuccess(false), 2000);
  };

  const handleSubmitReview = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!reviewName.trim() || !product) return;

    const activeProdId = product.id;

    try {
      setSubmittingReview(true);
      const newReviewObj = {
        product_id: activeProdId,
        user_name: reviewName.trim(),
        rating: reviewRating,
        comment: reviewComment.trim() || null
      };

      // Try database insertion
      const { data, error } = await supabase
        .from('product_reviews')
        .insert([newReviewObj])
        .select();

      if (error) {
        console.warn('Could not insert review in remote database, saving locally. Error:', error);
        // Fallback to local storage review adding
        const localReviewsKey = `mock_reviews_${activeProdId}`;
        const existingReviews = [...reviews];
        const localReview = {
          id: `rev-${Date.now()}`,
          product_id: activeProdId,
          user_name: reviewName.trim(),
          rating: reviewRating,
          comment: reviewComment.trim() || undefined,
          created_at: new Date().toISOString()
        };
        const updatedReviews = [localReview, ...existingReviews];
        localStorage.setItem(localReviewsKey, JSON.stringify(updatedReviews));
        setReviews(updatedReviews);
      } else if (data && data[0]) {
        setReviews(prev => [data[0], ...prev]);
      } else {
        // Fallback if data is empty but no error
        const localReviewsKey = `mock_reviews_${activeProdId}`;
        const existingReviews = [...reviews];
        const localReview = {
          id: `rev-${Date.now()}`,
          product_id: activeProdId,
          user_name: reviewName.trim(),
          rating: reviewRating,
          comment: reviewComment.trim() || undefined,
          created_at: new Date().toISOString()
        };
        const updatedReviews = [localReview, ...existingReviews];
        localStorage.setItem(localReviewsKey, JSON.stringify(updatedReviews));
        setReviews(updatedReviews);
      }

      // Reset form and show success
      setReviewName('');
      setReviewComment('');
      setReviewRating(5);
      setReviewSuccess(true);
      setTimeout(() => setReviewSuccess(false), 3000);
    } catch (err) {
      console.error('Submit review error:', err);
      // Local storage fallback
      const localReviewsKey = `mock_reviews_${activeProdId}`;
      const existingReviews = [...reviews];
      const localReview = {
        id: `rev-${Date.now()}`,
        product_id: activeProdId,
        user_name: reviewName.trim(),
        rating: reviewRating,
        comment: reviewComment.trim() || undefined,
        created_at: new Date().toISOString()
      };
      const updatedReviews = [localReview, ...existingReviews];
      localStorage.setItem(localReviewsKey, JSON.stringify(updatedReviews));
      setReviews(updatedReviews);

      setReviewName('');
      setReviewComment('');
      setReviewRating(5);
      setReviewSuccess(true);
      setTimeout(() => setReviewSuccess(false), 3000);
    } finally {
      setSubmittingReview(false);
    }
  };

  // Star rendering helper function
  const renderStars = (rating: number, interactive = false, onSelect?: (r: number) => void) => {
    return (
      <div className="flex items-center gap-0.5" dir="ltr">
        {[1, 2, 3, 4, 5].map((star) => {
          const isFilled = star <= rating;
          return (
            <button
              key={star}
              type="button"
              disabled={!interactive}
              onClick={() => interactive && onSelect && onSelect(star)}
              className={`${interactive ? 'hover:scale-115 cursor-pointer p-0.5' : ''} transition-transform`}
            >
              <Star 
                size={interactive ? 24 : 14} 
                className={`${
                  isFilled 
                    ? 'fill-amber-400 text-amber-400' 
                    : 'text-slate-200'
                }`} 
              />
            </button>
          );
        })}
      </div>
    );
  };

  useEffect(() => {
    async function loadProductData() {
      try {
        setLoading(true);
        
        let dbProd: any = null;
        if (isUuid(decodedId)) {
          const { data } = await supabase
            .from('products')
            .select('*')
            .eq('id', decodedId)
            .single();
          dbProd = data;
        } else {
          const pattern = decodedId.split('-').join('%');
          const { data } = await supabase
            .from('products')
            .select('*')
            .ilike('name', pattern)
            .limit(1);
          if (data && data.length > 0) {
            dbProd = data[0];
          }
        }

        let currentProd: Product | null = null;

        if (dbProd) {
          currentProd = {
            ...dbProd,
            price: Number(dbProd.price),
            sale_price: dbProd.sale_price !== null ? Number(dbProd.sale_price) : null,
            wholesale_price: Number(dbProd.wholesale_price)
          } as Product;
        } else {
          // Fallback to mock data
          currentProd = mockProducts.find(p => p.id === decodedId || slugify(p.name) === decodedId) || null;
        }

        if (currentProd) {
          setProduct(currentProd);

          // Fetch related products (same category, excluding current product)
          const { data: dbRelated } = await supabase
            .from('products')
            .select('*')
            .eq('category_id', currentProd.category_id)
            .neq('id', currentProd.id)
            .order('importance_score', { ascending: false })
            .limit(4);

          if (dbRelated && dbRelated.length > 0) {
            const normalized = dbRelated.map(p => ({
              ...p,
              price: Number(p.price),
              sale_price: p.sale_price !== null ? Number(p.sale_price) : null,
              wholesale_price: Number(p.wholesale_price)
            })) as Product[];
            setRelatedProducts(normalized);
          } else {
            // Fallback related products
            const related = mockProducts
              .filter(p => p.category_id === currentProd?.category_id && p.id !== currentProd?.id)
              .slice(0, 4);
            setRelatedProducts(related);
          }
        }

        // Load reviews
        if (currentProd) {
          try {
            const { data: dbReviews, error: reviewsError } = await supabase
              .from('product_reviews')
              .select('*')
              .eq('product_id', currentProd.id)
              .order('created_at', { ascending: false });

            if (reviewsError) {
              console.warn('Reviews table might be missing or inaccessible, falling back to mock storage. Error:', reviewsError);
              setReviewsDbWarning(true);
              const localReviewsKey = `mock_reviews_${currentProd.id}`;
              const localReviews = localStorage.getItem(localReviewsKey);
              if (localReviews) {
                setReviews(JSON.parse(localReviews));
              } else {
                const initialMockReviews = [
                  {
                    id: 'rev-1',
                    product_id: currentProd.id,
                    user_name: 'أحمد محمود',
                    rating: 5,
                    comment: 'المنتج ممتاز جداً وتوصيل سريع للغاية! الجودة عالية ومطابق للوصف تماماً.',
                    created_at: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString()
                  },
                  {
                    id: 'rev-2',
                    product_id: currentProd.id,
                    user_name: 'سارة أحمد',
                    rating: 4,
                    comment: 'جودة ممتازة وسعر مناسب جداً مقارنة بالسوق المحلي. سأكرر التجربة بالتأكيد.',
                    created_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString()
                  }
                ];
                localStorage.setItem(localReviewsKey, JSON.stringify(initialMockReviews));
                setReviews(initialMockReviews);
              }
            } else if (dbReviews) {
              setReviews(dbReviews);
            }
          } catch (reviewsErr) {
            console.error('Failed to load reviews:', reviewsErr);
            setReviewsDbWarning(true);
            const localReviewsKey = `mock_reviews_${currentProd.id}`;
            const localReviews = localStorage.getItem(localReviewsKey);
            if (localReviews) {
              setReviews(JSON.parse(localReviews));
            } else {
              const initialMockReviews = [
                {
                  id: 'rev-1',
                  product_id: currentProd.id,
                  user_name: 'أحمد محمود',
                  rating: 5,
                  comment: 'المنتج ممتاز جداً وتوصيل سريع للغاية! الجودة عالية ومطابق للوصف تماماً.',
                  created_at: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString()
                },
                {
                  id: 'rev-2',
                  product_id: currentProd.id,
                  user_name: 'سارة أحمد',
                  rating: 4,
                  comment: 'جودة ممتازة وسعر مناسب جداً مقارنة بالسوق المحلي. سأكرر التجربة بالتأكيد.',
                  created_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString()
                }
              ];
              localStorage.setItem(localReviewsKey, JSON.stringify(initialMockReviews));
              setReviews(initialMockReviews);
            }
          } finally {
            setLoadingReviews(false);
          }
        } else {
          setLoadingReviews(false);
        }

      } catch (err) {
        console.error('Failed to load product details:', err);
        // Direct mock fallback on crash
        const mockP = mockProducts.find(p => p.id === decodedId || slugify(p.name) === decodedId) || null;
        if (mockP) {
          setProduct(mockP);
          setRelatedProducts(mockProducts.filter(p => p.category_id === mockP.category_id && p.id !== mockP.id).slice(0, 4));
        }
      } finally {
        setLoading(false);
      }
    }

    loadProductData();
  }, [decodedId]);

  const handleAddToCart = () => {
    if (product) {
      addToCart(product, quantity);
      setAddedMessage(true);
      setTimeout(() => setAddedMessage(false), 2000);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 flex flex-col font-sans text-right">
        <Navbar />
        <div className="flex-1 flex flex-col items-center justify-center py-20 space-y-4">
          <div className="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin text-primary" />
          <p className="text-sm font-bold text-slate-500">جاري تحميل تفاصيل المنتج...</p>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-slate-50 flex flex-col font-sans text-right">
        <Navbar />
        <div className="flex-1 flex flex-col items-center justify-center py-20 space-y-4 max-w-md mx-auto text-center px-4">
          <span className="text-4xl">🔍</span>
          <h2 className="text-lg font-black text-slate-900">المنتج غير موجود</h2>
          <p className="text-xs text-slate-500 font-semibold leading-relaxed">
            عذراً، لم نتمكن من العثور على الصنف الذي تبحث عنه. قد يكون تم إزالته أو تغيير رابطه.
          </p>
          <Link href="/products" className="bg-primary hover:bg-primary-dark text-white font-extrabold px-6 py-2.5 rounded-xl text-xs shadow-md transition-colors">
            العودة لكتالوج المنتجات
          </Link>
        </div>
      </div>
    );
  }

  const hasDiscount = product.sale_price !== null && product.sale_price !== undefined && product.sale_price > 0;
  const originalPrice = product.price;
  const currentPrice = hasDiscount ? (product.sale_price as number) : product.price;
  const savings = hasDiscount ? originalPrice - currentPrice : 0;
  const isFavorite = isInWishlist(product.id);

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col font-sans text-right">
      <Navbar />

      <main className="flex-1 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-12">
        {/* Breadcrumbs / Back button */}
        <div className="flex items-center justify-between">
          <button 
            onClick={() => router.back()}
            className="flex items-center gap-1.5 text-xs text-slate-500 hover:text-primary font-bold transition-colors cursor-pointer"
          >
            الرجوع للصفحة السابقة
            <ArrowRight size={14} />
          </button>
          
          <Link 
            href="/products"
            className="text-xs text-primary hover:underline font-bold"
          >
            تصفح كل السلع 🛒
          </Link>
        </div>

        {/* Product Details Section Grid */}
        <div className="bg-white border border-slate-200/60 rounded-3xl p-6 sm:p-8 shadow-xs grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
          
          {/* Right: Product Image Panel (Gallery Slider) */}
          <div className="md:col-span-5 space-y-4">
            <div className="relative w-full aspect-square rounded-2xl overflow-hidden bg-slate-50 border border-slate-100 group flex items-center justify-center p-4">
              {images.length > 0 ? (
                <img 
                  src={images[activeImageIndex]} 
                  alt={product.name}
                  className="max-h-full max-w-full object-contain transition-all duration-300 transform scale-100 group-hover:scale-105 animate-fade-in"
                />
              ) : (
                <span className="text-slate-350 text-6xl">📦</span>
              )}
              
              {hasDiscount && (
                <span className="absolute top-4 right-4 bg-accent text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-sm z-10 animate-pulse-subtle">
                  خصم {((savings / originalPrice) * 100).toFixed(0)}% 🔥
                </span>
              )}

              {/* Slider Arrows */}
              {images.length > 1 && (
                <>
                  <button 
                    onClick={handlePrevImage}
                    className="absolute left-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white/80 hover:bg-white border border-slate-250/50 flex items-center justify-center text-slate-700 shadow-xs transition-all hover:scale-110 cursor-pointer z-10"
                    title="الصورة السابقة"
                  >
                    <ChevronLeft size={16} />
                  </button>
                  <button 
                    onClick={handleNextImage}
                    className="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white/80 hover:bg-white border border-slate-250/50 flex items-center justify-center text-slate-700 shadow-xs transition-all hover:scale-110 cursor-pointer z-10"
                    title="الصورة التالية"
                  >
                    <ChevronRight size={16} />
                  </button>
                </>
              )}

              {/* Dots indicator */}
              {images.length > 1 && (
                <div className="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-1.5 bg-black/35 backdrop-blur-xs px-2.5 py-1 rounded-full z-10">
                  {images.map((_, idx) => (
                    <button
                      key={idx}
                      onClick={() => setActiveImageIndex(idx)}
                      className={`w-1.5 h-1.5 rounded-full transition-all cursor-pointer ${
                        idx === activeImageIndex ? 'bg-white scale-125' : 'bg-white/50'
                      }`}
                    />
                  ))}
                </div>
              )}
            </div>

            {/* Thumbnails row below */}
            {images.length > 1 && (
              <div className="flex items-center gap-2 overflow-x-auto py-1 scrollbar-thin scrollbar-thumb-slate-250 scrollbar-track-transparent justify-center">
                {images.map((img, idx) => (
                  <button
                    key={idx}
                    onClick={() => setActiveImageIndex(idx)}
                    className={`relative w-16 h-16 rounded-lg overflow-hidden border-2 bg-slate-50 flex items-center justify-center p-1 shrink-0 transition-all cursor-pointer ${
                      idx === activeImageIndex 
                        ? 'border-primary shadow-xs scale-95' 
                        : 'border-slate-100 hover:border-slate-350'
                    }`}
                  >
                    <img 
                      src={img} 
                      alt={`${product.name} - ${idx + 1}`}
                      className="max-h-full max-w-full object-contain"
                    />
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Left: Product Info Details */}
          <div className="md:col-span-7 space-y-6 text-right">
            
            {/* Title & Category */}
            <div className="space-y-2.5">
              <span className="px-3 py-1 bg-primary/10 text-primary border border-primary/20 rounded-full text-[10px] font-extrabold inline-block">
                صنف متوفر بجملة ماركت
              </span>
              <h1 className="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
                {product.name}
              </h1>
              {product.description && (
                <p className="text-xs text-slate-500 leading-relaxed font-semibold">
                  {product.description}
                </p>
              )}
            </div>

            <div className="w-full h-px bg-slate-100" />

            {/* Price Tiers (B2C & B2B) */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
              {/* Retail Price Card */}
              <div className="bg-white p-3.5 rounded-xl border border-slate-150 space-y-1">
                <p className="text-[10px] text-slate-400 font-extrabold">سعر التجزئة (للقطعة)</p>
                <div className="flex items-baseline gap-2 justify-end">
                  <span className="text-lg font-black text-slate-900">{currentPrice.toFixed(2)} ج.م</span>
                  {hasDiscount && (
                    <span className="text-xs text-slate-400 line-through">{originalPrice.toFixed(2)} ج.م</span>
                  )}
                </div>
                {hasDiscount && (
                  <p className="text-[9px] text-emerald-600 font-bold">وفرت {savings.toFixed(2)} ج.م من السعر الأصلي</p>
                )}
              </div>

              {/* Wholesale B2B Price Card */}
              <div className="bg-white p-3.5 rounded-xl border border-primary/25 border-r-4 border-r-primary space-y-1">
                <div className="flex items-center justify-between">
                  <span className="bg-primary/10 text-primary text-[8px] font-black px-1.5 py-0.2 rounded">عرض جملة</span>
                  <p className="text-[10px] text-slate-400 font-extrabold">سعر الجملة (عند طلب {product.wholesale_min_qty} قطع أو أكثر)</p>
                </div>
                <div className="flex items-baseline gap-1 justify-end">
                  <span className="text-lg font-black text-primary">{product.wholesale_price.toFixed(2)} ج.م</span>
                  <span className="text-[10px] text-slate-400 font-bold">/ للقطعة</span>
                </div>
                <p className="text-[9px] text-primary font-bold">وفر {((currentPrice - product.wholesale_price) / currentPrice * 100).toFixed(0)}% إضافية عند الشراء بالجملة!</p>
              </div>
            </div>

            {/* Stock status & Delivery Guarantee */}
            <div className="flex flex-wrap items-center justify-end gap-x-6 gap-y-2 text-[10px] text-slate-500 font-bold">
              <span className="flex items-center gap-1">
                دفع نقدي عند الاستلام (COD)
                <ShieldCheck size={14} className="text-primary" />
              </span>
              <span className="flex items-center gap-1">
                توصيل فوري للناصرية والعامرية
                <Truck size={14} className="text-primary" />
              </span>
              <span className="flex items-center gap-1">
                حالة المخزون: {product.stock > 0 ? `متوفر (${product.stock} قطعة)` : 'غير متوفر مؤقتاً'}
                <Layers size={14} className="text-primary" />
              </span>
            </div>

            <div className="w-full h-px bg-slate-100" />

            {/* Controls: Quantity Selector & CTAs */}
            <div className="flex flex-col sm:flex-row gap-4 items-stretch sm:items-center">
              
              {/* Quantity selector */}
              <div className="flex items-center justify-between border border-slate-200 rounded-xl px-4 py-2 bg-slate-50">
                <span className="text-xs font-bold text-slate-500">الكمية:</span>
                <div className="flex items-center gap-4">
                  <button 
                    onClick={() => setQuantity(prev => Math.max(1, prev - 1))}
                    className="p-1 hover:bg-slate-200 rounded-md text-slate-600 transition-colors cursor-pointer"
                  >
                    <Minus size={14} />
                  </button>
                  <span className="font-black text-sm text-slate-900 w-6 text-center">{quantity}</span>
                  <button 
                    onClick={() => setQuantity(prev => (product.stock > 0 ? Math.min(product.stock, prev + 1) : prev + 1))}
                    className="p-1 hover:bg-slate-200 rounded-md text-slate-600 transition-colors cursor-pointer"
                    disabled={product.stock > 0 && quantity >= product.stock}
                  >
                    <Plus size={14} />
                  </button>
                </div>
              </div>

              {/* Add to Cart Button */}
              <button
                onClick={handleAddToCart}
                disabled={product.stock <= 0}
                className="flex-1 bg-primary hover:bg-primary-dark disabled:bg-slate-200 text-white font-extrabold py-3.5 rounded-xl shadow-lg hover:shadow-primary/25 transition-all flex items-center justify-center gap-2 text-xs cursor-pointer transform hover:-translate-y-0.5"
              >
                <ShoppingBag size={15} />
                {product.stock <= 0 ? 'نفذت الكمية مؤقتاً' : 'إضافة إلى سلة التسوق 🛒'}
              </button>

              {/* Share button */}
              <button
                onClick={handleShareProduct}
                className={`p-3.5 border rounded-xl transition-all cursor-pointer flex items-center justify-center border-slate-200 hover:border-primary text-slate-400 hover:text-primary bg-white ${
                  shareSuccess ? 'bg-emerald-50 text-emerald-600 border-emerald-300' : ''
                }`}
                title="مشاركة هذا المنتج مع الأصدقاء"
              >
                {shareSuccess ? (
                  <span className="text-[10px] font-black text-emerald-600">✓ تم نسخ الرابط</span>
                ) : (
                  <Share2 size={16} />
                )}
              </button>

              {/* Wishlist toggle */}
              <button
                onClick={() => toggleWishlist(product)}
                className={`p-3.5 border rounded-xl transition-all cursor-pointer flex items-center justify-center ${
                  isFavorite 
                    ? 'border-accent bg-accent/5 text-accent shadow-xs' 
                    : 'border-slate-200 hover:border-primary text-slate-400 hover:text-primary bg-white'
                }`}
                title={isFavorite ? 'إزالة من المفضلة' : 'إضافة للمفضلة'}
              >
                <Heart size={16} className={isFavorite ? 'fill-accent text-accent' : ''} />
              </button>

            </div>

            {/* Success notification banner */}
            {addedMessage && (
              <div className="bg-emerald-50 border border-emerald-200 text-emerald-750 p-3.5 rounded-xl text-xs font-bold text-center flex items-center justify-center gap-2 animate-bounce">
                <span>تمت إضافة {quantity} قطع من السلعة إلى سلتك بنجاح! 🛒</span>
              </div>
            )}

          </div>

        </div>

        {/* Product Reviews Section */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-8 border-t border-slate-200/80 pt-10">
          
          {/* Left / Submit Review Form */}
          <div className="md:col-span-5 bg-white border border-slate-200/60 rounded-3xl p-6 shadow-xs space-y-5">
            <h3 className="text-base font-black text-slate-900 flex items-center gap-1.5 justify-end">
              أضف تقييمك للمنتج
              <Sparkles size={16} className="text-primary" />
            </h3>
            <p className="text-[11px] text-slate-500 font-semibold leading-relaxed text-right">
              رأيك يهمنا ويساعد الآخرين في اتخاذ قرار الشراء. الرجاء مشاركة تجربتك بكل أمانة.
            </p>

            <form onSubmit={handleSubmitReview} className="space-y-4 text-right">
              {/* Rating selection */}
              <div className="space-y-2">
                <label className="block text-xs font-bold text-slate-700">تقييمك بالنجوم:</label>
                <div className="flex justify-end py-1">
                  {renderStars(reviewRating, true, setReviewRating)}
                </div>
              </div>

              {/* User Name input */}
              <div className="space-y-1.5">
                <label htmlFor="user_name" className="block text-xs font-bold text-slate-750">الاسم الكريم:</label>
                <input
                  type="text"
                  id="user_name"
                  required
                  placeholder="مثال: محمد أحمد"
                  value={reviewName}
                  onChange={(e) => setReviewName(e.target.value)}
                  className="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden font-bold text-right"
                />
              </div>

              {/* Comment text area */}
              <div className="space-y-1.5">
                <label htmlFor="comment" className="block text-xs font-bold text-slate-750">التعليق أو الملاحظات:</label>
                <textarea
                  id="comment"
                  rows={3}
                  placeholder="اكتب هنا رأيك في جودة المنتج، التغليف، أو أي ملاحظات أخرى..."
                  value={reviewComment}
                  onChange={(e) => setReviewComment(e.target.value)}
                  className="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden font-bold text-right resize-none"
                />
              </div>


              {/* Success Banner */}
              {reviewSuccess && (
                <div className="bg-emerald-50 border border-emerald-250/50 text-emerald-800 p-2.5 rounded-lg text-[10px] font-bold text-center">
                  تم إرسال تقييمك بنجاح! شكرًا لمشاركتنا رأيك. 🌟
                </div>
              )}

              {/* Submit button */}
              <button
                type="submit"
                disabled={submittingReview}
                className="w-full bg-primary hover:bg-primary-dark disabled:bg-slate-200 text-white font-extrabold py-2.5 rounded-xl shadow-xs transition-colors text-xs cursor-pointer flex items-center justify-center gap-1.5"
              >
                {submittingReview ? 'جاري الإرسال...' : 'إرسال التقييم الآن 📝'}
              </button>
            </form>
          </div>

          {/* Right / Reviews List */}
          <div className="md:col-span-7 space-y-5 text-right">
            <div className="flex items-center justify-between border-b border-slate-250/60 pb-3">
              <span className="text-[10px] bg-slate-100 text-slate-650 px-2 py-0.5 rounded-full font-extrabold">
                {reviews.length} تقييمات
              </span>
              <h3 className="text-base font-black text-slate-900 flex items-center gap-1.5">
                آراء وتجارب العملاء
                <Layers size={16} className="text-primary" />
              </h3>
            </div>

            {loadingReviews ? (
              <div className="flex flex-col items-center justify-center py-12 space-y-2">
                <div className="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin text-primary" />
                <p className="text-[10px] text-slate-500 font-bold">جاري تحميل الآراء والتقييمات...</p>
              </div>
            ) : reviews.length === 0 ? (
              <div className="bg-white border border-slate-150 rounded-2xl p-8 text-center text-slate-450 space-y-2">
                <p className="text-2xl">⭐</p>
                <p className="text-xs font-black text-slate-700">لا توجد تقييمات لهذا المنتج بعد</p>
                <p className="text-[10px] text-slate-450 leading-relaxed font-semibold">
                  كن أول من يقيم هذا المنتج ويشارك تجربته مع مجتمع جملة ماركت!
                </p>
              </div>
            ) : (
              <div className="space-y-4 max-h-[420px] overflow-y-auto pr-1">
                {reviews.map((rev) => (
                  <div key={rev.id} className="bg-white border border-slate-200/50 hover:border-slate-300/80 rounded-2xl p-4 shadow-2xs space-y-2 transition-all">
                    <div className="flex items-center justify-between">
                      <span className="text-[9px] text-slate-400 font-bold">
                        {new Date(rev.created_at).toLocaleDateString('ar-EG', {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric'
                        })}
                      </span>
                      <div className="flex items-center gap-2">
                        <span className="text-xs font-black text-slate-800">{rev.user_name}</span>
                        <div className="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">
                          {rev.user_name.charAt(0)}
                        </div>
                      </div>
                    </div>

                    <div className="flex justify-end">
                      {renderStars(rev.rating)}
                    </div>

                    {rev.comment && (
                      <p className="text-xs text-slate-650 leading-relaxed font-bold">
                        {rev.comment}
                      </p>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>

        </div>

        {/* Similar / Suggested Products Section */}
        <div className="space-y-6">
          <div className="flex items-center justify-between border-b border-slate-200 pb-3">
            <span className="text-xs text-slate-450 font-bold">قد يعجبك أيضاً في نفس القسم</span>
            <h2 className="text-base sm:text-lg font-black text-slate-900 flex items-center gap-1.5">
              منتجات واقتراحات أخرى
              <Sparkles size={16} className="text-primary" />
            </h2>
          </div>

          {relatedProducts.length === 0 ? (
            <p className="text-xs text-slate-400 font-bold text-center py-6">لا توجد منتجات مشابهة مقترحة حالياً.</p>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {relatedProducts.map((prod) => (
                <ProductCard key={prod.id} product={prod} />
              ))}
            </div>
          )}
        </div>

      </main>

      {/* Footer */}
      <footer className="bg-[#111827] text-white pt-12 pb-8 border-t-4 border-primary mt-12 text-center text-xs font-bold text-slate-500">
        <p>© {new Date().getFullYear()} الناصرية جملة ماركت. جميع الحقوق محفوظة لغرب الإسكندرية.</p>
      </footer>
    </div>
  );
}
