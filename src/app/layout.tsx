import type { Metadata } from "next";
import "./globals.css";
import { CartProvider } from "@/context/CartContext";

export const metadata: Metadata = {
  metadataBase: new URL('https://www.nassryaa-gomla.markets'),
  title: {
    default: "الناصرية جملة ماركت | أرخص أسعار الجملة والتجزئة بالعامرية والإسكندرية",
    template: "%s | الناصرية جملة ماركت"
  },
  description: "الوجهة الأولى لتسوق المواد الغذائية، البقالة، المجمدات، والمنظفات بسعر الجملة والتجزئة بغرب الإسكندرية والعامرية. احصل على نقاط ذهبية وتوصيل فوري لباب بيتك.",
  keywords: [
    "الناصرية جملة ماركت",
    "الناصرية ماركت",
    "جملة ماركت الاسكندرية",
    "ارخص ماركت بالعامرية",
    "مؤسسة الناصرية التجارية",
    "توصيل بقالة العامرية",
    "اسعار الجملة للمواد الغذائية",
    "منظفات جملة الاسكندرية",
    "موزع مجمدات العامرية",
    "الناصرية القديمة ماركت"
  ],
  authors: [{ name: "الناصرية جملة ماركت" }],
  creator: "الناصرية جملة ماركت",
  publisher: "الناصرية جملة ماركت",
  formatDetection: {
    email: false,
    address: true,
    telephone: true,
  },
  openGraph: {
    title: "الناصرية جملة ماركت | أرخص أسعار الجملة والتجزئة بالعامرية والإسكندرية",
    description: "الوجهة الأولى لتسوق المواد الغذائية، البقالة، المجمدات، والمنظفات بسعر الجملة والتجزئة بغرب الإسكندرية والعامرية. احصل على نقاط ذهبية وتوصيل فوري لباب بيتك.",
    url: "https://www.nassryaa-gomla.markets",
    siteName: "الناصرية جملة ماركت",
    images: [
      {
        url: "/logo.jpeg",
        width: 800,
        height: 800,
        alt: "شعار الناصرية جملة ماركت الرسمية",
      },
    ],
    locale: "ar_EG",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "الناصرية جملة ماركت | أرخص أسعار الجملة والتجزئة بالعامرية والإسكندرية",
    description: "الوجهة الأولى لتسوق المواد الغذائية، البقالة، المجمدات، والمنظفات بسعر الجملة والتجزئة بغرب الإسكندرية والعامرية. احصل على نقاط ذهبية وتوصيل فوري لباب بيتك.",
    images: ["/logo.jpeg"],
  },
  alternates: {
    canonical: "https://www.nassryaa-gomla.markets",
  },
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      "max-video-preview": -1,
      "max-image-preview": "large",
      "max-snippet": -1,
    },
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="ar"
      dir="rtl"
      className="h-full antialiased"
    >
      <head>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{
            __html: JSON.stringify({
              "@context": "https://schema.org",
              "@type": "LocalBusiness",
              "name": "الناصرية جملة ماركت",
              "image": "https://www.nassryaa-gomla.markets/logo.jpeg",
              "@id": "https://www.nassryaa-gomla.markets/#localbusiness",
              "url": "https://www.nassryaa-gomla.markets",
              "telephone": "+201211879341",
              "priceRange": "$$",
              "address": {
                "@type": "PostalAddress",
                "streetAddress": "الناصرية القديمة",
                "addressLocality": "العامرية، الإسكندرية",
                "postalCode": "5334310",
                "addressCountry": "EG"
              },
              "geo": {
                "@type": "GeoCoordinates",
                "latitude": 31.0210214,
                "longitude": 29.8143431
              },
              "openingHoursSpecification": {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": [
                  "Monday",
                  "Tuesday",
                  "Wednesday",
                  "Thursday",
                  "Friday",
                  "Saturday",
                  "Sunday"
                ],
                "opens": "08:00",
                "closes": "23:59"
              },
              "sameAs": [
                "https://www.facebook.com/nasriya.jomla.market"
              ]
            })
          }}
        />
      </head>
      <body className="min-h-full flex flex-col bg-gray-50 text-gray-900">
        <CartProvider>
          {children}
        </CartProvider>
      </body>
    </html>
  );
}
