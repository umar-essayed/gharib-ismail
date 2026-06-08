'use client';

import React, { useState } from 'react';
import { Product } from '@/types';
import { useCart } from '@/context/CartContext';
import { Plus, Check, Heart, Share2 } from 'lucide-react';
import Link from 'next/link';
import { slugify } from '@/lib/utils';

interface ProductCardProps {
  product: Product;
  layout?: 'grid' | 'list';
}

export default function ProductCard({ product, layout = 'grid' }: ProductCardProps) {
  const { addToCart, toggleWishlist, isInWishlist } = useCart();
  const [added, setAdded] = useState(false);
  const [qtyToAdd, setQtyToAdd] = useState(1);
  const [shareSuccess, setShareSuccess] = useState(false);

  const handleShareProduct = async (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (typeof window === 'undefined') return;
    
    const shareUrl = `${window.location.origin}/products/${slugify(product.name)}`;
    const shareTitle = product.name;
    const activePrice = product.sale_price || product.price;
    const shareText = `شاهد هذا المنتج الرائع "${product.name}" في الناصرية جملة ماركت بسعر ${activePrice.toFixed(2)} ج.م!`;

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

  const handleAddToCart = () => {
    addToCart(product, qtyToAdd);
    setAdded(true);
    setTimeout(() => setAdded(false), 1500);
  };

  // Determine if a B2C discount is active
  const hasDiscount = product.sale_price !== null && product.sale_price !== undefined && product.sale_price > 0;
  const discountPct = hasDiscount 
    ? Math.round(((product.price - (product.sale_price as number)) / product.price) * 100) 
    : 0;

  // Active Retail Price
  const activeRetailPrice = hasDiscount ? (product.sale_price as number) : product.price;
  const isFavorited = isInWishlist(product.id);

  if (layout === 'list') {
    // Horizontal row layout ("عرض بالعرض")
    return (
      <div className="group relative bg-white border border-gray-100 rounded-2xl sm:rounded-3xl overflow-hidden shadow-xs hover:shadow-md transition-all duration-300 flex flex-row gap-3 p-3 items-center text-right text-gray-900 hover:-translate-y-0.5">
        
        {/* Product Image Left/Right */}
        <div className="w-24 h-24 sm:w-32 sm:h-32 bg-gray-50 border border-gray-100 rounded-xl sm:rounded-2xl overflow-hidden relative flex-shrink-0">
          <Link href={`/products/${slugify(product.name)}`} className="block w-full h-full">
            {product.image_url ? (
              <img 
                src={product.image_url} 
                alt={product.name} 
                className="w-full h-full object-cover group-hover:scale-102 transition-transform duration-500"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-gray-450 text-xl">📦</div>
            )}
          </Link>

          {/* Discount Badge */}
          {hasDiscount && (
            <span className="absolute top-1 right-1 z-10 bg-accent text-white text-[8px] sm:text-[10px] font-black px-1.5 py-0.5 rounded-full shadow-md">
              -{discountPct}%
            </span>
          )}
          
          {/* Out of Stock Overlay */}
          {product.stock <= 0 && (
            <div className="absolute inset-0 bg-white/80 backdrop-blur-xs flex items-center justify-center">
              <span className="bg-gray-800 text-white font-bold text-[8px] sm:text-[10px] px-2 py-0.5 rounded-full">
                نفذت
              </span>
            </div>
          )}
        </div>

        {/* Content Info Right */}
        <div className="flex-1 flex flex-col justify-between self-stretch py-0.5">
          <div className="space-y-1">
            <div className="flex items-start justify-between gap-2">
              {/* Wishlist Button */}
              <button
                onClick={() => toggleWishlist(product)}
                className="text-gray-400 hover:text-accent p-1 cursor-pointer"
                title={isFavorited ? "إزالة من المفضلة" : "إضافة للمفضلة"}
              >
                <Heart size={14} className={isFavorited ? "fill-accent text-accent" : ""} />
              </button>

              {/* Share Button */}
              <button
                onClick={handleShareProduct}
                className="text-gray-400 hover:text-primary p-1 cursor-pointer"
                title="مشاركة المنتج"
              >
                {shareSuccess ? (
                  <span className="text-[8px] font-bold text-emerald-600">نسخ ✓</span>
                ) : (
                  <Share2 size={13} />
                )}
              </button>
              <h3 className="text-xs sm:text-base font-bold text-gray-900 group-hover:text-primary transition-colors line-clamp-1 flex-1">
                <Link href={`/products/${slugify(product.name)}`} className="hover:text-primary transition-colors">
                  {product.name}
                </Link>
              </h3>
            </div>
            
            <p className="text-[10px] sm:text-xs text-gray-400 line-clamp-1">
              {product.description || 'لا يوجد وصف للمنتج.'}
            </p>

            {/* Price section */}
            <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5 pt-1">
              <div className="flex items-center gap-1 text-[11px] sm:text-sm font-black text-gray-900">
                <span className="text-[9px] text-gray-400 font-bold">قطاعي:</span>
                <span>{activeRetailPrice.toFixed(2)} ج.م</span>
                {hasDiscount && (
                  <span className="text-[9px] sm:text-xs text-gray-400 line-through mr-1">
                    {product.price.toFixed(2)}
                  </span>
                )}
              </div>

              <div className="bg-gold-light/40 border border-gold/15 px-2 py-0.5 rounded-lg text-[10px] sm:text-xs text-primary font-black flex items-center gap-1">
                <span>جملة: {product.wholesale_price.toFixed(2)} ج.م</span>
                <span className="text-[8px] text-gray-500 font-medium">({product.wholesale_min_qty}ق)</span>
              </div>
            </div>
          </div>

          {/* Add to Cart Controls */}
          <div className="flex items-center gap-2 pt-2 border-t border-gray-50/50 mt-1">
            {/* Quantity Selector */}
            <div className="w-14 sm:w-20">
              <input
                type="number"
                min="1"
                max={product.stock > 0 ? product.stock : 99}
                value={qtyToAdd}
                onChange={(e) => setQtyToAdd(Math.max(1, parseInt(e.target.value) || 1))}
                className="w-full bg-gray-50 border border-gray-200 text-gray-900 rounded-lg sm:rounded-xl px-1.5 py-1 text-center text-[10px] sm:text-xs font-bold focus:outline-none focus:ring-1 focus:ring-primary"
              />
            </div>

            {/* Add Button */}
            <button
              onClick={handleAddToCart}
              disabled={product.stock <= 0}
              className={`flex-1 py-1 sm:py-1.5 px-3 rounded-lg sm:rounded-xl font-bold text-[10px] sm:text-xs transition-all flex items-center justify-center gap-1 ${
                product.stock <= 0
                  ? 'bg-gray-100 text-gray-450 cursor-not-allowed border border-gray-200'
                  : added
                  ? 'bg-emerald-600 text-white shadow-xs'
                  : 'bg-primary hover:bg-primary-dark text-white'
              }`}
            >
              {added ? <Check size={11} /> : <Plus size={11} />}
              <span>{added ? 'تم' : 'أضف'}</span>
            </button>
          </div>
        </div>

      </div>
    );
  }

  // Vertical layout ("عرض بالطول" - Grid layout optimized for 2 columns on mobile)
  return (
    <div className="group relative bg-white border border-gray-100 rounded-2xl sm:rounded-3xl overflow-hidden shadow-xs hover:shadow-lg transition-all duration-300 flex flex-col h-full hover:-translate-y-1 text-right text-gray-900">
      
      {/* Wishlist Heart Button */}
      <button
        onClick={() => toggleWishlist(product)}
        className="absolute top-2 sm:top-4 left-2 sm:left-4 z-10 p-1.5 sm:p-2.5 rounded-full bg-white/95 hover:bg-white border border-gray-100/50 shadow-md text-gray-500 hover:text-accent transition-all duration-200 cursor-pointer"
        title={isFavorited ? "إزالة من المفضلة" : "إضافة للمفضلة"}
      >
        <Heart 
          size={12} 
          className={`transition-all duration-300 ${isFavorited ? "fill-accent text-accent scale-110" : "text-gray-400"}`} 
        />
      </button>

      {/* Share Button */}
      <button
        onClick={handleShareProduct}
        className="absolute top-2 sm:top-4 left-10 sm:left-14 z-10 p-1.5 sm:p-2.5 rounded-full bg-white/95 hover:bg-white border border-gray-100/50 shadow-md text-gray-500 hover:text-primary transition-all duration-200 cursor-pointer"
        title="مشاركة المنتج"
      >
        {shareSuccess ? (
          <span className="text-[8px] font-extrabold text-emerald-600">تم</span>
        ) : (
          <Share2 size={12} />
        )}
      </button>

      {/* Discount Badge */}
      {hasDiscount && (
        <span className="absolute top-2 sm:top-4 right-2 sm:right-4 z-10 bg-accent text-white text-[9px] sm:text-xs font-black px-2 py-0.5 sm:py-1 rounded-full shadow-md animate-pulse-subtle">
          خصم {discountPct}%
        </span>
      )}

      {/* Product Image */}
      <div className="w-full h-28 sm:h-44 bg-gray-50 border-b border-gray-100 overflow-hidden relative">
        <Link href={`/products/${slugify(product.name)}`} className="block w-full h-full animate-fade-in">
          {product.image_url ? (
            <img 
              src={product.image_url} 
              alt={product.name} 
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-gray-400 text-xl">📦</div>
          )}
        </Link>
        
        {/* Out of Stock Overlay */}
        {product.stock <= 0 && (
          <div className="absolute inset-0 bg-white/80 backdrop-blur-xs flex items-center justify-center">
            <span className="bg-gray-800 text-white font-bold text-[9px] sm:text-xs px-3 py-1 rounded-full">
              نفذت الكمية
            </span>
          </div>
        )}
      </div>

      {/* Card Info */}
      <div className="p-3 sm:p-5 flex-1 flex flex-col justify-between">
        <div>
          <h3 className="text-xs sm:text-base font-bold text-gray-900 group-hover:text-primary transition-colors line-clamp-1">
            <Link href={`/products/${slugify(product.name)}`} className="hover:text-primary transition-colors">
              {product.name}
            </Link>
          </h3>
          <p className="text-[10px] sm:text-xs text-gray-505 mt-1 line-clamp-1 sm:line-clamp-2 h-4 sm:h-9 leading-relaxed">
            {product.description || 'لا يوجد وصف للمنتج.'}
          </p>

          {/* Pricing Grid */}
          <div className="mt-2.5 space-y-1.5 border-t border-gray-50 pt-2.5">
            
            {/* Retail Price Row */}
            <div className="flex items-baseline justify-between gap-1">
              <span className="text-[9px] sm:text-[11px] text-gray-400 font-semibold whitespace-nowrap">سعر المفرد:</span>
              <div className="flex items-center gap-1 flex-wrap justify-end">
                {hasDiscount && (
                  <span className="text-[9px] sm:text-xs text-gray-455 line-through">
                    {product.price.toFixed(2)}
                  </span>
                )}
                <span className="text-xs sm:text-base font-black text-gray-900">
                  {activeRetailPrice.toFixed(2)} <span className="text-[9px] sm:text-xs font-bold text-gray-500">ج.م</span>
                </span>
              </div>
            </div>

            {/* Wholesale Price Row */}
            <div className="bg-gold-light/60 p-1.5 sm:p-2.5 rounded-xl border border-gold/15 flex flex-col sm:flex-row sm:items-center sm:justify-between text-right gap-0.5 sm:gap-2">
              <div className="text-right">
                <p className="text-[8px] sm:text-[10px] text-gold-dark font-black">
                  جملة التجار 👑
                </p>
                <p className="text-[7px] sm:text-[9px] text-gray-400 font-medium">
                  من {product.wholesale_min_qty} قطع
                </p>
              </div>
              <span className="text-xs sm:text-sm font-black text-primary whitespace-nowrap">
                {product.wholesale_price.toFixed(2)} <span className="text-[8px] sm:text-[10px] font-bold text-gray-600">ج.م</span>
              </span>
            </div>

          </div>
        </div>

        {/* Add to Cart Actions */}
        <div className="mt-3 pt-2.5 border-t border-gray-50 flex items-center gap-1.5">
          {/* Quantity selector */}
          <div className="w-14 sm:w-20">
            <input
              type="number"
              min="1"
              max={product.stock > 0 ? product.stock : 99}
              value={qtyToAdd}
              onChange={(e) => setQtyToAdd(Math.max(1, parseInt(e.target.value) || 1))}
              className="w-full bg-gray-50 border border-gray-200 text-gray-900 rounded-lg sm:rounded-xl px-1 py-1 sm:py-1.5 text-center text-[10px] sm:text-xs font-bold focus:outline-none focus:ring-1 focus:ring-primary"
            />
          </div>

          {/* CTA Add Button */}
          <button
            onClick={handleAddToCart}
            disabled={product.stock <= 0}
            className={`flex-1 py-1 sm:py-1.5 px-2 rounded-lg sm:rounded-xl font-bold text-[10px] sm:text-xs transition-all duration-300 flex items-center justify-center gap-1 shadow-xs hover:shadow-sm ${
              product.stock <= 0
                ? 'bg-gray-100 text-gray-450 cursor-not-allowed border border-gray-200'
                : added
                ? 'bg-emerald-600 text-white shadow-md'
                : 'bg-primary hover:bg-primary-dark text-white'
            }`}
          >
            {added ? <Check size={11} /> : <Plus size={11} />}
            <span>{added ? 'تم' : 'أضف'}</span>
          </button>
        </div>
      </div>

    </div>
  );
}
