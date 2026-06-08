export interface Profile {
  id: string;
  full_name: string;
  phone: string;
  address: string;
  points: number;
  role: 'admin' | 'customer';
  created_at: string;
}

export interface Category {
  id: string;
  name: string;
  slug: string;
  image_url: string;
  created_at: string;
  importance_score?: number;
}

export interface Product {
  id: string;
  category_id?: string;
  name: string;
  description?: string;
  price: number;
  sale_price?: number | null;
  wholesale_price: number;
  wholesale_min_qty: number;
  stock: number;
  image_url?: string;
  is_available: boolean;
  created_at?: string;
  importance_score?: number;
}

export interface CartItem {
  product: Product;
  quantity: number;
}

export interface Order {
  id: string;
  user_id?: string | null;
  items: {
    product_id: string;
    name: string;
    qty: number;
    price: number;
    image_url?: string;
  }[];
  total_price: number;
  status: 'pending' | 'preparing' | 'delivering' | 'completed';
  delivery_address: string;
  delivery_phone: string;
  payment_method: string;
  created_at: string;
}

export interface Banner {
  id: string;
  title: string;
  image_url: string;
  link_url: string;
  created_at?: string;
}

export interface Coupon {
  code: string;
  description: string;
  discount_type: 'percentage' | 'fixed' | 'points';
  discount_value: number;
  min_order_amount: number;
  points_cost: number;
  is_active: boolean;
  usage_count?: number;
  created_at?: string;
}

export interface ProductReview {
  id: string;
  product_id: string;
  user_name: string;
  rating: number;
  comment?: string;
  created_at: string;
}
