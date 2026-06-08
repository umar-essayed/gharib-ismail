import { MetadataRoute } from 'next';
import { supabase } from '@/lib/supabase';

import { slugify } from '@/lib/utils';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = 'https://nasriya-jomla-market.com';

  const routes = [
    {
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: 'daily' as const,
      priority: 1.0,
    },
    {
      url: `${baseUrl}/products`,
      lastModified: new Date(),
      changeFrequency: 'daily' as const,
      priority: 0.9,
    },
    {
      url: `${baseUrl}/auth`,
      lastModified: new Date(),
      changeFrequency: 'monthly' as const,
      priority: 0.5,
    },
    {
      url: `${baseUrl}/blog`,
      lastModified: new Date(),
      changeFrequency: 'weekly' as const,
      priority: 0.8,
    },
  ];

  try {
    const { data: products } = await supabase
      .from('products')
      .select('id, name, created_at')
      .eq('is_available', true);

    if (products) {
      const productRoutes = products.map((prod) => ({
        url: `${baseUrl}/products/${slugify(prod.name)}`,
        lastModified: prod.created_at ? new Date(prod.created_at) : new Date(),
        changeFrequency: 'weekly' as const,
        priority: 0.7,
      }));
      return [...routes, ...productRoutes];
    }
  } catch (e) {
    console.error('Sitemap dynamic products load failed:', e);
  }

  return routes;
}
