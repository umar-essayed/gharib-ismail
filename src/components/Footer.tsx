'use client';

import React from 'react';
import Link from 'next/link';
import { Send, MapPin, Phone, Mail } from 'lucide-react';

export default function Footer() {
  const currentYear = new Date().getFullYear();

  const handleSubscribe = (e: React.FormEvent) => {
    e.preventDefault();
    alert('تم الاشتراك بالنشرة البريدية بنجاح!');
  };

  return (
    <footer id="about" className="bg-[#111827] text-white pt-16 pb-8 border-t-4 border-primary">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8 pb-12 border-b border-slate-800">
        
        {/* Block 1: Contact Info */}
        <div className="space-y-4 text-right">
          <h4 className="text-sm font-black text-primary">بيانات التواصل</h4>
          <div className="space-y-2.5 text-xs text-slate-400 font-semibold">
            <p className="flex items-center justify-start gap-2 dir-rtl">
              <span>📍</span>
              <span>الإسكندرية، العامرية، الناصرية القديمة</span>
            </p>
            <p className="flex items-center justify-start gap-2" dir="ltr">
              <span>📞 +20 1211879341</span>
            </p>
            <p className="flex items-center justify-start gap-2">
              <span>الرمز البريدي: 5334310</span>
            </p>
          </div>
        </div>

        {/* Block 2: Quick Links */}
        <div className="space-y-4 text-right">
          <h4 className="text-sm font-black text-primary">روابط سريعة</h4>
          <div className="grid grid-cols-2 gap-2 text-xs text-slate-400 font-bold">
            <Link href="/terms" className="hover:text-primary transition-colors">الشروط والأحكام</Link>
            <Link href="/privacy" className="hover:text-primary transition-colors">سياسة الخصوصية</Link>
            <Link href="/delivery" className="hover:text-primary transition-colors">معلومات التوصيل</Link>
            <Link href="/products?search=جملة" className="hover:text-primary transition-colors">عروض الجملة</Link>
          </div>
        </div>

        {/* Block 3: Newsletter Sign Up */}
        <div className="space-y-4 text-right">
          <h4 className="text-sm font-black text-primary">النشرة البريدية</h4>
          <p className="text-xs text-slate-400 leading-normal font-semibold">
            سجل بريدك الإلكتروني لتصلك عروض المجمدات والبقالة الأسبوعية أولاً بأول.
          </p>
          <form onSubmit={handleSubscribe} className="flex items-center gap-2">
            <input
              type="email"
              required
              placeholder="البريد الإلكتروني..."
              className="bg-slate-900 border border-slate-800 text-white rounded-xl px-4 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary flex-1 text-right"
            />
            <button 
              type="submit"
              className="bg-primary hover:bg-primary-dark text-white p-2 rounded-xl transition-all shadow-xs cursor-pointer"
            >
              <Send size={15} />
            </button>
          </form>
        </div>

      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 font-bold gap-4 text-center sm:text-right">
        <p>© {currentYear} الناصرية جملة ماركت. جميع الحقوق محفوظة.</p>
        <p className="text-[10px] text-slate-600">تم التطوير بجودة متناهية لتطبيقات الويب الحديثة</p>
      </div>
    </footer>
  );
}
