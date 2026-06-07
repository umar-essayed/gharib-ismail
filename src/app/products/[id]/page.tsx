import React, { use } from 'react';
import ProductDetailClient from './ProductDetailClient';
import { supabase } from '@/lib/supabase';
import { mockProducts } from '@/lib/mockData';
import type { Metadata } from 'next';

interface Props {
  params: Promise<{ id: string }>;
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { id } = await params;
  
  let productName = 'تفاصيل المنتج';
  let productDesc = 'تصفح تفاصيل وأسعار المنتج في الناصرية جملة ماركت';
  let productImage = 'https://nasriya-jomla-market.com/logo.jpeg';
  let productPriceText = '';

  try {
    const { data: dbProd } = await supabase
      .from('products')
      .select('*')
      .eq('id', id)
      .single();

    if (dbProd) {
      productName = dbProd.name;
      productDesc = dbProd.description || productDesc;
      productImage = dbProd.image_url || productImage;
      const priceVal = Number(dbProd.sale_price || dbProd.price);
      productPriceText = ` - السعر: ${priceVal.toFixed(2)} ج.م`;
    } else {
      const mockP = mockProducts.find(p => p.id === id);
      if (mockP) {
        productName = mockP.name;
        productDesc = mockP.description || productDesc;
        productImage = mockP.image_url || productImage;
        const priceVal = Number(mockP.sale_price || mockP.price);
        productPriceText = ` - السعر: ${priceVal.toFixed(2)} ج.م`;
      }
    }
  } catch (e) {
    // Ignore
  }

  // Parse images and pick first one
  const cleanImage = productImage ? productImage.split(',')[0].trim() : 'https://nasriya-jomla-market.com/logo.jpeg';

  return {
    title: `${productName}${productPriceText}`,
    description: `${productDesc}. اطلبه الآن من الناصرية جملة ماركت بسعر التجزئة والجملة والتوصيل فوري.`,
    openGraph: {
      title: `${productName}${productPriceText} | الناصرية جملة ماركت`,
      description: `${productDesc}. اطلبه الآن بسعر التجزئة والجملة والتوصيل فوري.`,
      url: `https://nasriya-jomla-market.com/products/${id}`,
      siteName: "الناصرية جملة ماركت",
      images: [
        {
          url: cleanImage,
          alt: productName,
        }
      ],
      locale: "ar_EG",
      type: "website",
    },
    twitter: {
      card: "summary_large_image",
      title: `${productName}${productPriceText} | الناصرية جملة ماركت`,
      description: `${productDesc}. اطلبه الآن بسعر التجزئة والجملة.`,
      images: [cleanImage],
    }
  };
}

export default function ProductDetailPage({ params }: Props) {
  const { id } = use(params);
  return <ProductDetailClient id={id} />;
}
