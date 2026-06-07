import React from 'react';
import Link from 'next/link';
import Navbar from '@/components/Navbar';
import { mockBlogPosts } from '@/lib/blogData';
import { Calendar, BookOpen, Clock, ArrowRight, Sparkles } from 'lucide-react';
import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: "مدونة توفير المال والأسعار | الناصرية جملة ماركت",
  description: "أحدث المقالات والنصائح الحصرية لتوفير ميزانية بقالة المنزل، الشراء بسعر الجملة بالعامرية، وأدلة تخزين الأغذية المجمدة في غرب الإسكندرية.",
};

export default function BlogListingPage() {
  return (
    <div className="min-h-screen bg-slate-50 flex flex-col font-sans text-right">
      <Navbar />

      <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full space-y-12">
        {/* Header section */}
        <div className="text-center space-y-4 max-w-2xl mx-auto">
          <span className="px-3.5 py-1.5 bg-primary/10 text-primary border border-primary/20 rounded-full text-xs font-black inline-flex items-center gap-1.5">
            <Sparkles size={13} className="animate-spin-slow" />
            مجلة التوفير والمعرفة بالعامرية
          </span>
          <h1 className="text-2xl sm:text-4xl font-black text-slate-900 leading-tight">
            مدونة الناصرية جملة ماركت
          </h1>
          <p className="text-xs sm:text-sm text-slate-550 font-bold leading-relaxed">
            نصائح عملية، أدلة شراء ذكية، وأسرار إدارة ميزانية السلع الغذائية والمجمدات والمنظفات لبيتك ومحلك التجاري بالإسكندرية.
          </p>
        </div>

        {/* Blog Posts Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {mockBlogPosts.map((post) => (
            <article 
              key={post.slug}
              className="bg-white rounded-3xl overflow-hidden border border-slate-200/60 shadow-2xs hover:shadow-md hover:border-slate-350/50 transition-all group flex flex-col h-full"
            >
              {/* Image preview */}
              <div className="relative aspect-video overflow-hidden bg-slate-100 shrink-0">
                <img 
                  src={post.image_url} 
                  alt={post.title}
                  className="w-full h-full object-cover group-hover:scale-103 transition-transform duration-500"
                />
                <span className="absolute top-4 right-4 bg-primary text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-sm">
                  {post.category}
                </span>
              </div>

              {/* Card content */}
              <div className="p-6 flex-1 flex flex-col justify-between space-y-4">
                <div className="space-y-2.5">
                  {/* Meta coordinates */}
                  <div className="flex items-center gap-4 text-[10px] text-slate-400 font-bold justify-end">
                    <span className="flex items-center gap-1">
                      {post.read_time}
                      <Clock size={12} />
                    </span>
                    <span className="flex items-center gap-1">
                      {post.created_at}
                      <Calendar size={12} />
                    </span>
                  </div>

                  <h2 className="text-base font-black text-slate-900 leading-snug group-hover:text-primary transition-colors line-clamp-2">
                    <Link href={`/blog/${post.slug}`}>
                      {post.title}
                    </Link>
                  </h2>

                  <p className="text-xs text-slate-500 font-semibold leading-relaxed line-clamp-3">
                    {post.excerpt}
                  </p>
                </div>

                <div className="pt-2 border-t border-slate-100 flex items-center justify-between">
                  <span className="text-[10px] text-slate-400 font-bold flex items-center gap-1">
                    كتب بواسطة الناصرية ماركت
                    <BookOpen size={12} />
                  </span>
                  <Link 
                    href={`/blog/${post.slug}`}
                    className="text-xs text-primary hover:text-primary-dark font-extrabold flex items-center gap-1.5 transition-colors cursor-pointer group-hover:translate-x-[-4px]"
                  >
                    اقرأ المقال بالكامل
                    <ArrowRight size={14} className="rotate-180" />
                  </Link>
                </div>
              </div>
            </article>
          ))}
        </div>
      </main>

      {/* Footer */}
      <footer className="bg-[#111827] text-white pt-12 pb-8 border-t-4 border-primary mt-16 text-center text-xs font-bold text-slate-500">
        <p>© {new Date().getFullYear()} الناصرية جملة ماركت. جميع الحقوق محفوظة لغرب الإسكندرية.</p>
      </footer>
    </div>
  );
}
