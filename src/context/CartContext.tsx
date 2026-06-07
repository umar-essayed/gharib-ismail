'use client';

import React, { createContext, useContext, useState, useEffect } from 'react';
import { Product, CartItem, Profile } from '@/types';
import { supabase } from '@/lib/supabase';

interface CartContextType {
  cart: CartItem[];
  addToCart: (product: Product, quantity?: number) => void;
  removeFromCart: (productId: string) => void;
  updateQuantity: (productId: string, quantity: number) => void;
  clearCart: () => void;
  
  // Wishlist
  wishlist: Product[];
  toggleWishlist: (product: Product) => void;
  isInWishlist: (productId: string) => boolean;
  
  // Loyalty Points
  profile: Profile | null;
  setProfile: React.Dispatch<React.SetStateAction<Profile | null>>;
  pointsToRedeem: number;
  setPointsToRedeem: (points: number) => void;
  availablePoints: number;
  refreshProfile: () => Promise<void>;

  // Computed Values
  subtotal: number; // Total before points redemption
  discountFromPoints: number; // Discount from points (1 point = 1 EGP)
  finalTotal: number; // subtotal - discount
  earnedPoints: number; // points gained (1 point per 100 EGP of finalTotal)
}

const CartContext = createContext<CartContextType | undefined>(undefined);

export const CartProvider = ({ children }: { children: React.ReactNode }) => {
  const [cart, setCart] = useState<CartItem[]>([]);
  const [wishlist, setWishlist] = useState<Product[]>([]);
  const [profile, setProfile] = useState<Profile | null>(null);
  const [pointsToRedeemState, setPointsToRedeemState] = useState<number>(0);
  const [loading, setLoading] = useState<boolean>(true);

  // Synchronize cart and wishlist with localStorage to persist user selection
  useEffect(() => {
    const savedCart = localStorage.getItem('gharib_cart');
    if (savedCart) {
      try {
        setCart(JSON.parse(savedCart));
      } catch (e) {
        console.error('Failed to parse cart from localStorage', e);
      }
    }
    
    const savedWishlist = localStorage.getItem('gharib_wishlist');
    if (savedWishlist) {
      try {
        setWishlist(JSON.parse(savedWishlist));
      } catch (e) {
        console.error('Failed to parse wishlist from localStorage', e);
      }
    }
  }, []);

  const saveCart = (newCart: CartItem[]) => {
    setCart(newCart);
    localStorage.setItem('gharib_cart', JSON.stringify(newCart));
  };

  // Fetch Supabase user profile & points
  const refreshProfile = async () => {
    try {
      const { data: { session } } = await supabase.auth.getSession();
      if (session?.user) {
        let { data, error } = await supabase
          .from('profiles')
          .select('*')
          .eq('id', session.user.id)
          .maybeSingle();

        // Fallback: if database trigger failed, create profile row on the fly
        if (!data) {
          console.warn('Profile record missing, auto-creating row on the fly...');
          const userMeta = session.user.user_metadata || {};
          const phoneVal = userMeta.phone || session.user.email?.split('@')[0] || '';
          
          const { data: newProfile } = await supabase
            .from('profiles')
            .insert({
              id: session.user.id,
              full_name: userMeta.full_name || 'عميل جديد',
              phone: phoneVal,
              address: userMeta.address || '',
              points: 0,
              role: 'customer'
            })
            .select()
            .single();

          if (newProfile) {
            data = newProfile;
          }
        }

        if (data) {
          setProfile(data as Profile);
        }
      } else {
        // Fallback or demo user for testing purposes if not authenticated
        const demoUser = localStorage.getItem('demo_profile');
        if (demoUser) {
          setProfile(JSON.parse(demoUser));
        }
      }
    } catch (err) {
      console.error('Error refreshing profile:', err);
    } finally {
      setLoading(false);
    }
  };

  // Auth subscriber to fetch profile in real-time
  useEffect(() => {
    refreshProfile();
    const { data: { subscription } } = supabase.auth.onAuthStateChange((event, session) => {
      if (session?.user) {
        refreshProfile();
      } else {
        setProfile(null);
      }
    });

    return () => {
      subscription.unsubscribe();
    };
  }, []);

  const addToCart = (product: Product, quantity = 1) => {
    const existingIndex = cart.findIndex((item) => item.product.id === product.id);
    const newCart = [...cart];

    if (existingIndex > -1) {
      const newQty = newCart[existingIndex].quantity + quantity;
      // Cap at product stock if available
      newCart[existingIndex].quantity = product.stock > 0 ? Math.min(newQty, product.stock) : newQty;
    } else {
      const newQty = product.stock > 0 ? Math.min(quantity, product.stock) : quantity;
      newCart.push({ product, quantity: newQty });
    }
    saveCart(newCart);
  };

  const removeFromCart = (productId: string) => {
    const newCart = cart.filter((item) => item.product.id !== productId);
    saveCart(newCart);
    // Reset points calculation if cart is empty
    if (newCart.length === 0) {
      setPointsToRedeemState(0);
    }
  };

  const updateQuantity = (productId: string, quantity: number) => {
    if (quantity <= 0) {
      removeFromCart(productId);
      return;
    }
    const newCart = cart.map((item) => {
      if (item.product.id === productId) {
        const cappedQty = item.product.stock > 0 ? Math.min(quantity, item.product.stock) : quantity;
        return { ...item, quantity: cappedQty };
      }
      return item;
    });
    saveCart(newCart);
  };

  const clearCart = () => {
    saveCart([]);
    setPointsToRedeemState(0);
  };

  const toggleWishlist = (product: Product) => {
    const exists = wishlist.some((item) => item.id === product.id);
    let newWishlist;
    if (exists) {
      newWishlist = wishlist.filter((item) => item.id !== product.id);
    } else {
      newWishlist = [...wishlist, product];
    }
    setWishlist(newWishlist);
    localStorage.setItem('gharib_wishlist', JSON.stringify(newWishlist));
  };

  const isInWishlist = (productId: string) => {
    return wishlist.some((item) => item.id === productId);
  };

  // Calculated items helper
  const calculateItemPrice = (item: CartItem) => {
    const { product, quantity } = item;
    // B2B Dynamic Pricing: wholesale_price applies if quantity >= wholesale_min_qty
    if (quantity >= product.wholesale_min_qty) {
      return product.wholesale_price;
    }
    // B2C Discount Pricing: sale_price takes priority if exists
    if (product.sale_price !== undefined && product.sale_price !== null && product.sale_price > 0) {
      return product.sale_price;
    }
    // Regular Retail Price
    return product.price;
  };

  const subtotal = cart.reduce((total, item) => {
    const price = calculateItemPrice(item);
    return total + price * item.quantity;
  }, 0);

  const availablePoints = profile?.points || 0;

  // Cap points to redeem at available points and at subtotal
  const pointsToRedeem = Math.min(pointsToRedeemState, availablePoints, Math.floor(subtotal));

  const setPointsToRedeem = (points: number) => {
    const validPoints = Math.max(0, Math.min(points, availablePoints, Math.floor(subtotal)));
    setPointsToRedeemState(validPoints);
  };

  // 1 Gold Point = 1 EGP discount
  const discountFromPoints = pointsToRedeem;
  const finalTotal = Math.max(0, subtotal - discountFromPoints);

  // Loyalty earning rules: 1 Gold Point for every 100 EGP spent on the final price
  const earnedPoints = Math.floor(finalTotal / 100);

  return (
    <CartContext.Provider
      value={{
        cart,
        addToCart,
        removeFromCart,
        updateQuantity,
        clearCart,
        wishlist,
        toggleWishlist,
        isInWishlist,
        profile,
        setProfile,
        pointsToRedeem,
        setPointsToRedeem,
        availablePoints,
        refreshProfile,
        subtotal,
        discountFromPoints,
        finalTotal,
        earnedPoints,
      }}
    >
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
};
