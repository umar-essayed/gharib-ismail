'use client';

import React from 'react';
import Navbar from '@/components/Navbar';
import Footer from '@/components/Footer';
import { Lock, Eye, CheckCircle2, ShieldCheck, Database } from 'lucide-react';

export default function PrivacyPage() {
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col justify-between">
      <div>
        <Navbar />
        
        {/* Page Hero Header */}
        <section className="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-[#064e3b] to-gray-900 text-white py-16 px-4">
          <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-primary/20 via-transparent to-transparent pointer-events-none" />
          <div className="max-w-4xl mx-auto text-center relative z-10 space-y-4">
            <div className="inline-flex p-3 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 mb-2">
              <Lock className="w-8 h-8 text-primary-light" />
            </div>
            <h1 className="text-3xl sm:text-4xl font-black tracking-tight font-sans">
              سياسة الخصوصية
            </h1>
            <p className="text-sm sm:text-base text-gray-300 max-w-xl mx-auto font-medium">
              خصوصيتك تهمنا. توضح هذه الصفحة كيفية جمع واستخدام وحماية بياناتك الشخصية.
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
                  <Database className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">١. البيانات التي نجمعها</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                نقوم بجمع بعض المعلومات الأساسية لتقديم خدمة تسوق مريحة وسريعة لك:
              </p>
              <ul className="list-disc list-inside space-y-2 text-xs text-gray-500 pr-4 font-semibold">
                <li>معلومات الحساب: رقم الهاتف، الاسم، عنوان التوصيل، والبريد الإلكتروني عند التسجيل.</li>
                <li>بيانات الطلب: تفاصيل المنتجات التي تشتريها، والأسعار، وتاريخ الطلبات والخصومات المستخدمة.</li>
                <li>بيانات التصفح: معلومات تقنية مبسطة مثل ملفات تعريف الارتباط (Cookies) لتحسين تجربة تصفحك للموقع وحفظ محتويات سلة التسوق.</li>
              </ul>
            </div>

            <hr className="border-gray-100" />

            {/* Section 2 */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <Eye className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">٢. كيف نستخدم بياناتك</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                تستخدم الناصرية جملة ماركت معلوماتك للأغراض التالية فقط:
              </p>
              <ul className="list-disc list-inside space-y-2 text-xs text-gray-500 pr-4 font-semibold">
                <li>معالجة وتوصيل طلبات البقالة والمجمدات إلى باب منزلك بدقة وسرعة.</li>
                <li>إدارة حسابك وحساب النقاط الذهبية المستحقة لك وتطبيق أكواد الخصم.</li>
                <li>التواصل معك بخصوص حالة طلبك أو في حال الحاجة لتأكيد تفاصيل التوصيل.</li>
                <li>تحسين خدماتنا وتوفير العروض الأقرب لاحتياجاتك اليومية.</li>
              </ul>
            </div>

            <hr className="border-gray-100" />

            {/* Section 3 */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">٣. حماية البيانات وأمانها</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                نحن ملتزمون بضمان أمن معلوماتك الشخصية. لحماية بياناتك من الوصول غير المصرح به أو الكشف عنها، قمنا بوضع إجراءات إلكترونية وتقنية صارمة (مثل تشفير الاتصال باستخدام بروتوكولات HTTPS الآمنة، واستخدام قواعد بيانات Supabase المحمية).
              </p>
            </div>

            <hr className="border-gray-100" />

            {/* Section 4 */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <CheckCircle2 className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">٤. مشاركة البيانات مع أطراف ثالثة</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                نحن لا نبيع أو نؤجر أو نشارك معلوماتك الشخصية مع أي جهات خارجية لأغراض تسويقية. يتم مشاركة عنوانك ورقم هاتفك فقط مع مندوبي التوصيل التابعين لنا لضمان إيصال الطلبات إليك بشكل صحيح.
              </p>
            </div>

            <hr className="border-gray-100" />

            {/* Section 5 */}
            <div className="space-y-4">
              <h2 className="text-xl font-bold text-gray-900">٥. التحكم في بياناتك الشخصية</h2>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                يمكنك مراجعة وتعديل بياناتك الشخصية (مثل الاسم وعناوين التوصيل ورقم الهاتف) في أي وقت عبر تسجيل الدخول والذهاب لصفحة حسابك الشخصي. إذا كنت ترغب في حذف حسابك نهائياً من أنظمتنا، يمكنك التواصل مع فريق الدعم الفني مباشرة وسنقوم بالاستجابة لطلبك فوراً.
              </p>
            </div>

          </div>
        </main>
      </div>

      <Footer />
    </div>
  );
}
