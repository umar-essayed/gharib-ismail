import React, { use } from 'react';
import ProductDetailClient from './ProductDetailClient';
import { supabase } from '@/lib/supabase';
import { mockProducts } from '@/lib/mockData';
import type { Metadata } from 'next';

import { isUuid, slugify } from '@/lib/utils';

interface Props {
  params: Promise<{ id: string }>;
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { id } = await params;
  const decodedId = decodeURIComponent(id);
  
  let productName = 'تفاصيل المنتج';
  let productDesc = 'تصفح تفاصيل وأسعار المنتج في الناصرية جملة ماركت';
  let productImage = 'https://www.nassryaa-gomla.markets/logo.jpeg';
  let productPriceText = '';

  try {
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

    if (dbProd) {
      productName = dbProd.name;
      productDesc = dbProd.description || productDesc;
      productImage = dbProd.image_url || productImage;
      const priceVal = Number(dbProd.sale_price || dbProd.price);
      productPriceText = ` - السعر: ${priceVal.toFixed(2)} ج.م`;
    } else {
      const mockP = mockProducts.find(p => p.id === decodedId || slugify(p.name) === decodedId);
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
  const cleanImage = productImage ? productImage.split(',')[0].trim() : 'https://www.nassryaa-gomla.markets/logo.jpeg';

  return {
    title: `${productName}${productPriceText}`,
    description: `${productDesc}. اطلبه الآن من الناصرية جملة ماركت بسعر التجزئة والجملة والتوصيل فوري.`,
    openGraph: {
      title: `${productName}${productPriceText} | الناصرية جملة ماركت`,
      description: `${productDesc}. اطلبه الآن بسعر التجزئة والجملة والتوصيل فوري.`,
      url: `https://www.nassryaa-gomla.markets/products/${decodedId}`,
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

export default async function ProductDetailPage({ params }: Props) {
  const { id } = await params;
  const decodedId = decodeURIComponent(id);

  let dbProd: any = null;
  try {
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
  } catch (e) {
    // Ignore
  }

  const prod = dbProd || mockProducts.find(p => p.id === decodedId || slugify(p.name) === decodedId);

  let schemaJson = null;
  if (prod) {
    const cleanImage = prod.image_url ? prod.image_url.split(',')[0].trim() : 'https://www.nassryaa-gomla.markets/logo.jpeg';
    const activePrice = Number(prod.sale_price || prod.price);
    schemaJson = {
      "@context": "https://schema.org",
      "@type": "Product",
      "name": prod.name,
      "image": cleanImage,
      "description": prod.description || prod.name,
      "offers": {
        "@type": "Offer",
        "url": `https://www.nassryaa-gomla.markets/products/${decodedId}`,
        "priceCurrency": "EGP",
        "price": activePrice,
        "availability": prod.stock > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
        "priceValidUntil": "2030-12-31"
      }
    };
  }

  return (
    <>
      {schemaJson && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(schemaJson) }}
        />
      )}
      <ProductDetailClient id={id} />
    </>
  );
}
