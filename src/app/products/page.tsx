import React from 'react';
import ProductsClient from './ProductsClient';
import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: "كتالوج المنتجات وأسعار الجملة والتجزئة | الناصرية جملة ماركت",
  description: "تصفح جميع السلع الغذائية، البقالة، المجمدات، والمنظفات بأرخص أسعار الجملة والتجزئة بالعامرية والناصرية القديمة بالإسكندرية. اطلب الآن واستفد من العروض والخصومات.",
  keywords: [
    "منتجات الناصرية جملة ماركت",
    "اسعار السلع التموينية بالعامرية",
    "مجمدات جملة الاسكندرية",
    "ارخص منظفات بالعامرية",
    "شراء ارز ومكرونة جملة",
    "بقالة التجزئة الاسكندرية"
  ],
  openGraph: {
    title: "كتالوج المنتجات وأسعار الجملة والتجزئة | الناصرية جملة ماركت",
    description: "تصفح جميع السلع الغذائية، البقالة، المجمدات، والمنظفات بأرخص أسعار الجملة والتجزئة بالعامرية والناصرية القديمة بالإسكندرية.",
    url: "https://www.nassryaa-gomla.markets/products",
  }
};

export default function ProductsPage() {
  return <ProductsClient />;
}
