import { MetadataRoute } from 'next';
import { supabase } from '@/lib/supabase';

import { slugify } from '@/lib/utils';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = 'https://www.nassryaa-gomla.markets';

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
    const allProducts: any[] = [];
    let page = 0;
    const pageSize = 1000;
    let keepFetching = true;

    while (keepFetching) {
      const from = page * pageSize;
      const to = from + pageSize - 1;
      const { data: batch, error } = await supabase
        .from('products')
        .select('id, name, created_at')
        .eq('is_available', true)
        .range(from, to);

      if (error) {
        console.error('Error fetching sitemap products batch:', error);
        break;
      }

      if (batch && batch.length > 0) {
        allProducts.push(...batch);
        if (batch.length < pageSize) {
          keepFetching = false;
        } else {
          page++;
        }
      } else {
        keepFetching = false;
      }
    }

    if (allProducts.length > 0) {
      const productRoutes = allProducts.map((prod) => ({
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
