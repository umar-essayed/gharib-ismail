'use client';

import React from 'react';
import Navbar from '@/components/Navbar';
import Footer from '@/components/Footer';
import { ShieldCheck, FileText, CheckCircle2, AlertTriangle, HelpCircle } from 'lucide-react';

export default function TermsPage() {
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col justify-between">
      <div>
        <Navbar />
        
        {/* Page Hero Header */}
        <section className="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-[#064e3b] to-gray-900 text-white py-16 px-4">
          <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-primary/20 via-transparent to-transparent pointer-events-none" />
          <div className="max-w-4xl mx-auto text-center relative z-10 space-y-4">
            <div className="inline-flex p-3 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 mb-2">
              <FileText className="w-8 h-8 text-primary-light" />
            </div>
            <h1 className="text-3xl sm:text-4xl font-black tracking-tight font-sans">
              الشروط والأحكام
            </h1>
            <p className="text-sm sm:text-base text-gray-300 max-w-xl mx-auto font-medium">
              يرجى قراءة شروط الخدمة هذه بعناية قبل استخدام موقع الناصرية جملة ماركت.
            </p>
            <div className="text-xs text-primary-light/85 font-semibold bg-primary-light/10 inline-block px-3 py-1.5 rounded-full border border-primary-light/20">
              آخر تحديث: ٨ يونيو ٢٠٢٦
            </div>
          </div>
        </section>

        {/* Page Content */}
        <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div className="bg-white rounded-3xl border border-gray-150 shadow-sm p-6 sm:p-10 space-y-10">
            
            {/* Section 1 */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">١. مقدمة عامة</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                أهلاً بكم في موقع <strong>الناصرية جملة ماركت</strong>. بتصفحكم واستخدامكم لهذا الموقع، فإنكم توافقون على الالتزام بالشروط والأحكام التالية وشروط سياسة الخصوصية الخاصة بنا. تحكم هذه الشروط علاقة الناصرية جملة ماركت معكم فيما يتعلق بهذا الموقع.
              </p>
            </div>

            <hr className="border-gray-100" />

            {/* Section 2 */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <CheckCircle2 className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">٢. شروط الحساب والتسجيل</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                للاستفادة من بعض الخدمات مثل تجميع النقاط الذهبية وإتمام الطلبات بشكل أسرع، قد يُطلب منك إنشاء حساب برقم هاتفك أو عبر حساب جوجل.
              </p>
              <ul className="list-disc list-inside space-y-2 text-xs text-gray-500 pr-4 font-semibold">
                <li>يجب أن تكون المعلومات المقدمة أثناء التسجيل دقيقة ومحدثة دائمًا.</li>
                <li>أنت مسؤول بالكامل عن الحفاظ على سرية معلومات حسابك وكلمة المرور الخاصة بك.</li>
                <li>يحتفظ الموقع بالحق في رفض الخدمة، أو إنهاء الحسابات، أو إلغاء الطلبات وفقًا لتقديره الخاص في حال ثبوت إساءة الاستخدام.</li>
              </ul>
            </div>

            <hr className="border-gray-100" />

            {/* Section 3 */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <AlertTriangle className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">٣. سياسة الطلبات والدفع</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                نسعى جاهدين لتوفير جميع المنتجات المعروضة على الموقع بأسعار الجملة والتجزئة المناسبة للجميع.
              </p>
              <ul className="list-disc list-inside space-y-2 text-xs text-gray-500 pr-4 font-semibold">
                <li>طريقة الدفع المعتمدة حالياً هي الدفع نقداً عند الاستلام (Cash on Delivery) أو باستخدام النقاط الذهبية المستبدلة بكود خصم.</li>
                <li>في حال عدم توفر منتج معين بعد إتمام الطلب، سنقوم بالتواصل معك لتعديل الطلب أو استبدال المنتج ببديل مناسب.</li>
                <li>يتم حساب أسعار منتجات الوزن (مثل اللحوم والمجمدات التي تباع بالوزن) بشكل تقريبي وتحديد السعر النهائي بعد الوزن الفعلي في المتجر.</li>
              </ul>
            </div>

            <hr className="border-gray-100" />

            {/* Section 4 */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <HelpCircle className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">٤. النقاط الذهبية وكوبونات الخصم</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                يقدم موقع الناصرية جملة ماركت نظام مكافآت حصري للعملاء المسجلين يتيح لهم كسب نقاط ذهبية عند التسوق.
              </p>
              <ul className="list-disc list-inside space-y-2 text-xs text-gray-500 pr-4 font-semibold">
                <li>يمكن استبدال النقاط الذهبية بكوبونات خصم نقدية عبر صفحة الحساب الشخصي.</li>
                <li>الكوبونات صالحة للاستخدام مرة واحدة فقط ولا يمكن استبدالها بنقد حقيقي خارج نطاق الشراء من الموقع.</li>
                <li>نحتفظ بالحق في تعديل قيم استرداد النقاط أو إلغائها في حال الكشف عن عمليات احتيالية أو غير شرعية لجمع النقاط.</li>
              </ul>
            </div>

            <hr className="border-gray-100" />

            {/* Section 5 */}
            <div className="space-y-4">
              <h2 className="text-xl font-bold text-gray-900">٥. حدود المسؤولية والتعديلات</h2>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                لا نتحمل المسؤولية عن أي أضرار مباشرة أو غير مباشرة تنتج عن استخدام أو عدم القدرة على استخدام الموقع، أو الأخطاء التقنية الخارجة عن إرادتنا. كما نحتفظ بالحق في تحديث أو تعديل هذه الشروط والأحكام في أي وقت دون إشعار مسبق.
              </p>
            </div>

          </div>
        </main>
      </div>

      <Footer />
    </div>
  );
}
