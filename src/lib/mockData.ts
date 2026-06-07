import { Product, Category } from '@/types';

export const mockCategories: Category[] = [
  {
    id: 'cat-1',
    name: 'أرز ومكرونة وبقوليات',
    slug: 'rice-pasta-grains',
    image_url: 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80',
    created_at: new Date().toISOString()
  },
  {
    id: 'cat-2',
    name: 'زيوت وسمن وزبدة',
    slug: 'oils-ghee',
    image_url: 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=500&auto=format&fit=crop&q=80',
    created_at: new Date().toISOString()
  },
  {
    id: 'cat-3',
    name: 'ألبان واجبان',
    slug: 'dairy-cheese',
    image_url: 'https://images.unsplash.com/photo-1628088062854-d1870b4553da?w=500&auto=format&fit=crop&q=80',
    created_at: new Date().toISOString()
  },
  {
    id: 'cat-4',
    name: 'معلبات ومخللات',
    slug: 'canned-goods',
    image_url: 'https://images.unsplash.com/photo-1534482421-64566f976cfa?w=500&auto=format&fit=crop&q=80',
    created_at: new Date().toISOString()
  },
  {
    id: 'cat-5',
    name: 'مشروبات وعصائر',
    slug: 'beverages',
    image_url: 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=500&auto=format&fit=crop&q=80',
    created_at: new Date().toISOString()
  },
  {
    id: 'cat-6',
    name: 'شيكولاتة ومقرمشات وتسالي',
    slug: 'snacks-sweets',
    image_url: 'https://images.unsplash.com/photo-1599490659213-e2b9527b0876?w=500&auto=format&fit=crop&q=80',
    created_at: new Date().toISOString()
  }
];

export const mockProducts: Product[] = [
  {
    id: 'prod-1',
    category_id: 'cat-1',
    name: 'أرز مصري فاخر المطبخ ٥ كجم',
    description: 'أرز مصري عالي الجودة ونقي ١٠٠٪، حبة عريضة مثالية للطهي المنزلي والمطاعم.',
    price: 180.00,
    sale_price: 165.00,
    wholesale_price: 150.00,
    wholesale_min_qty: 10,
    stock: 120,
    image_url: 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600&auto=format&fit=crop&q=80,https://images.unsplash.com/photo-1590080875515-8a3a8dc5735e?w=600&auto=format&fit=crop&q=80,https://images.unsplash.com/photo-1536304997881-a372c179924b?w=600&auto=format&fit=crop&q=80',
    is_available: true
  },
  {
    id: 'prod-2',
    category_id: 'cat-1',
    name: 'مكرونة الملكة مرمرية ٤٠٠ جرام',
    description: 'مكرونة الملكة الشهيرة مصنوعة من أفضل أنواع السيمولينا، سريعة التحضير.',
    price: 12.00,
    sale_price: null,
    wholesale_price: 10.00,
    wholesale_min_qty: 20,
    stock: 350,
    image_url: 'https://images.unsplash.com/photo-1621961401349-46f40a936a51?w=600&auto=format&fit=crop&q=80,https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80,https://images.unsplash.com/photo-1612966608967-3e2b747ffede?w=600&auto=format&fit=crop&q=80',
    is_available: true
  },
  {
    id: 'prod-3',
    category_id: 'cat-2',
    name: 'زيت عباد الشمس كريستال ٢.٢ لتر',
    description: 'زيت عباد شمس نقي خفيف على المعدة ومثالي لجميع أنواع الطهي والقلي.',
    price: 240.00,
    sale_price: 225.00,
    wholesale_price: 210.00,
    wholesale_min_qty: 6,
    stock: 80,
    image_url: 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=500&auto=format&fit=crop&q=80',
    is_available: true
  },
  {
    id: 'prod-4',
    category_id: 'cat-2',
    name: 'سمن نباتي كريستال أصفر ٧٠٠ جرام',
    description: 'سمن نباتي برائحة الزبدة الفلاحي يضفي نكهة مميزة ومذاق لا يقاوم للحلويات والأطعمة.',
    price: 75.00,
    sale_price: 68.00,
    wholesale_price: 62.00,
    wholesale_min_qty: 12,
    stock: 150,
    image_url: 'https://images.unsplash.com/photo-1608686207856-001b95cf60ca?w=500&auto=format&fit=crop&q=80',
    is_available: true
  },
  {
    id: 'prod-5',
    category_id: 'cat-3',
    name: 'جبنة بيضاء دومتي فيتا ٥٠٠ جرام',
    description: 'جبنة دومتي فيتا طرية كريمية الطعم مناسبة للسندوتشات والفطار اليومي.',
    price: 45.00,
    sale_price: 41.00,
    wholesale_price: 37.00,
    wholesale_min_qty: 12,
    stock: 200,
    image_url: 'https://images.unsplash.com/photo-1628088062854-d1870b4553da?w=500&auto=format&fit=crop&q=80',
    is_available: true
  },
  {
    id: 'prod-6',
    category_id: 'cat-3',
    name: 'حليب جهينة كامل الدسم ١ لتر',
    description: 'حليب جهينة مبستر طبيعي ١٠٠٪ غني بالكالسيوم وخالي من المواد الحافظة.',
    price: 48.00,
    sale_price: null,
    wholesale_price: 43.50,
    wholesale_min_qty: 12,
    stock: 180,
    image_url: 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=500&auto=format&fit=crop&q=80',
    is_available: true
  },
  {
    id: 'prod-7',
    category_id: 'cat-4',
    name: 'تونة دولفين قطعة واحدة ٢٠٠ جرام',
    description: 'تونة دولفين فاخرة سهلة الفتح محفوظة بزيت دوار الشمس لحفظ المذاق.',
    price: 65.00,
    sale_price: 59.00,
    wholesale_price: 53.00,
    wholesale_min_qty: 24,
    stock: 400,
    image_url: 'https://images.unsplash.com/photo-1534482421-64566f976cfa?w=500&auto=format&fit=crop&q=80',
    is_available: true
  },
  {
    id: 'prod-8',
    category_id: 'cat-5',
    name: 'عصير بيتي مشكل ١ لتر',
    description: 'عصير طبيعي مشكل من الفواكه الطازجة، مغذي ومنعش في كل الأوقات.',
    price: 25.00,
    sale_price: null,
    wholesale_price: 21.00,
    wholesale_min_qty: 12,
    stock: 160,
    image_url: 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=500&auto=format&fit=crop&q=80',
    is_available: true
  },
  {
    id: 'prod-9',
    category_id: 'cat-6',
    name: 'شيبسي عائلي طعم الملح والخل',
    description: 'بطاطس مقرمشة طبيعية بطعم الملح والخل الحامض المميز، حجم عائلي للمشاركة.',
    price: 15.00,
    sale_price: 13.50,
    wholesale_price: 11.50,
    wholesale_min_qty: 25,
    stock: 300,
    image_url: 'https://images.unsplash.com/photo-1599490659213-e2b9527b0876?w=500&auto=format&fit=crop&q=80',
    is_available: true
  }
];
