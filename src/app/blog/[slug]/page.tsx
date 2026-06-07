import React, { use } from 'react';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import Navbar from '@/components/Navbar';
import { mockBlogPosts } from '@/lib/blogData';
import { Calendar, Clock, ArrowRight, User } from 'lucide-react';
import type { Metadata } from 'next';

interface Props {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  const post = mockBlogPosts.find((p) => p.slug === slug);
  if (!post) {
    return {
      title: "المقال غير موجود",
    };
  }
  return {
    title: post.title,
    description: post.excerpt,
    openGraph: {
      title: post.title,
      description: post.excerpt,
      images: [{ url: post.image_url }],
    },
  };
}

export default function BlogPostPage({ params }: Props) {
  const { slug } = use(params);
  const post = mockBlogPosts.find((p) => p.slug === slug);

  if (!post) {
    notFound();
  }

  // Split content by paragraphs or render cleanly
  const paragraphs = post.content.split('\n\n').filter(Boolean);

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col font-sans text-right">
      <Navbar />

      <main className="flex-1 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full space-y-8">
        {/* Back navigation */}
        <div>
          <Link 
            href="/blog"
            className="flex items-center gap-1.5 text-xs text-slate-500 hover:text-primary font-bold transition-colors cursor-pointer inline-flex"
          >
            العودة لقائمة المقالات
            <ArrowRight size={14} className="rotate-180" />
          </Link>
        </div>

        {/* Article header */}
        <div className="space-y-4">
          <span className="px-3.5 py-1.5 bg-primary/10 text-primary border border-primary/20 rounded-full text-[10px] font-black inline-block">
            {post.category}
          </span>
          <h1 className="text-xl sm:text-3xl font-black text-slate-900 leading-snug">
            {post.title}
          </h1>

          {/* Metadata banner */}
          <div className="flex flex-wrap items-center gap-6 text-[10.5px] text-slate-500 font-bold border-y border-slate-200/60 py-3 justify-end">
            <span className="flex items-center gap-1">
              الكاتب: إدارة الناصرية
              <User size={13} className="text-primary" />
            </span>
            <span className="flex items-center gap-1">
              زمن القراءة: {post.read_time}
              <Clock size={13} className="text-primary" />
            </span>
            <span className="flex items-center gap-1">
              تاريخ النشر: {post.created_at}
              <Calendar size={13} className="text-primary" />
            </span>
          </div>
        </div>

        {/* Feature image */}
        <div className="relative aspect-video rounded-3xl overflow-hidden border border-slate-200/80 shadow-xs bg-slate-100">
          <img 
            src={post.image_url} 
            alt={post.title} 
            className="w-full h-full object-cover"
          />
        </div>

        {/* Article content markup */}
        <div className="bg-white border border-slate-200/50 rounded-3xl p-6 sm:p-10 shadow-3xs space-y-6">
          {paragraphs.map((para, idx) => {
            const trimmed = para.trim();
            if (trimmed.startsWith('###')) {
              return (
                <h3 key={idx} className="text-base sm:text-lg font-black text-slate-900 pt-3 border-r-4 border-primary pr-3 leading-normal">
                  {trimmed.replace('###', '').trim()}
                </h3>
              );
            }
            if (trimmed.startsWith('*')) {
              return (
                <div key={idx} className="bg-primary/5 border border-primary/10 p-4 rounded-2xl text-xs font-bold text-slate-800 leading-relaxed">
                  {trimmed.replace('*', '').trim()}
                </div>
              );
            }
            return (
              <p key={idx} className="text-xs sm:text-sm text-slate-650 leading-relaxed font-semibold text-right">
                {trimmed}
              </p>
            );
          })}
        </div>

        {/* Article footer block / CTA */}
        <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 text-white text-center space-y-4 shadow-sm">
          <h4 className="text-sm sm:text-base font-black">هل تبحث عن توفير فوري لبيتك أو مطعمك بالعامرية؟</h4>
          <p className="text-[11px] sm:text-xs text-slate-400 font-semibold leading-relaxed max-w-lg mx-auto">
            تسوّق الآن في **الناصرية جملة ماركت** واستمتع بأرخص أسعار الجملة والتجزئة للمواد الغذائية والمجمدات والمنظفات مع شحن فوري لباب البيت.
          </p>
          <div className="pt-2">
            <Link 
              href="/products"
              className="bg-primary hover:bg-primary-dark text-white font-extrabold px-8 py-3 rounded-xl text-xs transition-colors shadow-xs inline-block"
            >
              تصفح كتالوج السلع والأسعار 🛒
            </Link>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="bg-[#111827] text-white pt-12 pb-8 border-t-4 border-primary mt-16 text-center text-xs font-bold text-slate-500">
        <p>© {new Date().getFullYear()} الناصرية جملة ماركت. جميع الحقوق محفوظة لغرب الإسكندرية.</p>
      </footer>
    </div>
  );
}
