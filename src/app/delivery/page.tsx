'use client';

import React from 'react';
import Navbar from '@/components/Navbar';
import Footer from '@/components/Footer';
import { Truck, MapPin, Clock, ShieldCheck, CheckCircle2 } from 'lucide-react';

export default function DeliveryPage() {
  const [threshold, setThreshold] = React.useState(800);

  React.useEffect(() => {
    fetch('/ecom_config.json')
      .then((r) => r.json())
      .then((data) => {
        if (data && data.free_shipping_threshold) {
          setThreshold(Number(data.free_shipping_threshold));
        }
      })
      .catch(() => {});
  }, []);

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col justify-between">
      <div>
        <Navbar />
        
        {/* Page Hero Header */}
        <section className="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-[#064e3b] to-gray-900 text-white py-16 px-4">
          <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-primary/20 via-transparent to-transparent pointer-events-none" />
          <div className="max-w-4xl mx-auto text-center relative z-10 space-y-4">
            <div className="inline-flex p-3 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 mb-2">
              <Truck className="w-8 h-8 text-primary-light" />
            </div>
            <h1 className="text-3xl sm:text-4xl font-black tracking-tight font-sans">
              معلومات التوصيل والشحن
            </h1>
            <p className="text-sm sm:text-base text-gray-300 max-w-xl mx-auto font-medium">
              كل ما تود معرفته عن سرعة التوصيل، التكلفة، ومناطق التغطية لطلباتك.
            </p>
            <div className="text-xs text-primary-light/85 font-semibold bg-primary-light/10 inline-block px-3 py-1.5 rounded-full border border-primary-light/20">
              شحن آمن وسريع لباب بيتك
            </div>
          </div>
        </section>

        {/* Page Content */}
        <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div className="bg-white rounded-3xl border border-gray-150 shadow-sm p-6 sm:p-10 space-y-10">
            
            {/* Section 1: Zones */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <MapPin className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">مناطق التوصيل الحالية</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                نقوم بالتوصيل إلى المناطق التالية في غرب الإسكندرية والعامرية بصفة يومية:
              </p>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="p-4 bg-emerald-50/50 rounded-2xl border border-emerald-100/50 text-center">
                  <span className="block text-sm font-bold text-emerald-800">العامرية</span>
                  <span className="text-[10px] text-emerald-600/80 font-bold">توصيل خلال ٢-٤ ساعات</span>
                </div>
                <div className="p-4 bg-emerald-50/50 rounded-2xl border border-emerald-100/50 text-center">
                  <span className="block text-sm font-bold text-emerald-800">الناصرية القديمة والجديدة</span>
                  <span className="text-[10px] text-emerald-600/80 font-bold">توصيل خلال ١-٣ ساعات</span>
                </div>
                <div className="p-4 bg-emerald-50/50 rounded-2xl border border-emerald-100/50 text-center">
                  <span className="block text-sm font-bold text-emerald-800">المناطق المجاورة بالعامرية</span>
                  <span className="text-[10px] text-emerald-600/80 font-bold">شحن سريع وآمن</span>
                </div>
              </div>
            </div>

            <hr className="border-gray-100" />

            {/* Section 2: Pricing */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <Truck className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">تكلفة التوصيل والشحن المجاني</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                نحرص على تقديم أسعار توصيل تنافسية ورمزية لتخفيف الأعباء عن عملائنا الكرام:
              </p>
              <ul className="list-disc list-inside space-y-2 text-xs text-gray-500 pr-4 font-semibold">
                <li>
                  <strong className="text-gray-700">شحن مجاني بالكامل:</strong> لجميع الطلبات التي تتجاوز قيمتها <span className="text-primary font-bold">{threshold} جنيه</span>.
                </li>
                <li>
                  <strong className="text-gray-700">تكلفة الشحن للطلبات الأقل من {threshold} جنيه:</strong> تتراوح بين ١٥ إلى ٣٠ جنيهاً فقط حسب البعد الجغرافي للمنطقة.
                </li>
                <li>لا توجد أي مصاريف أو رسوم خفية أخرى، السعر الظاهر عند إتمام الطلب هو ما يتم دفعه.</li>
              </ul>
            </div>

            <hr className="border-gray-100" />

            {/* Section 3: Delivery Times */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <Clock className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">أوقات العمل وجدولة الطلبات</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed font-medium">
                نستقبل طلباتكم الإلكترونية على مدار ٢٤ ساعة طوال أيام الأسبوع. ويتم التوصيل الفعلي خلال الفترات التالية:
              </p>
              <ul className="list-disc list-inside space-y-2 text-xs text-gray-500 pr-4 font-semibold">
                <li>مواعيد انطلاق مناديب التوصيل: يومياً من الساعة <strong className="text-gray-750">٩:٠٠ صباحاً وحتى ١١:٠٠ مساءً</strong>.</li>
                <li>الطلبات المقدمة قبل الساعة ٩:٠٠ مساءً يتم شحنها وتوصيلها في نفس اليوم.</li>
                <li>الطلبات التي تتم بعد الساعة ٩:٠٠ مساءً قد يتم جدولتها للتسليم في صباح اليوم التالي مباشرة.</li>
              </ul>
            </div>

            <hr className="border-gray-100" />

            {/* Section 4: Safe Handling */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <h2 className="text-xl font-bold text-gray-900">توصيل آمن للمجمدات والألبان</h2>
              </div>
              <p className="text-sm text-gray-650 leading-relaxed font-medium">
                لأننا نهتم بجودة منتجاتنا وسلامتكم، نقوم بنقل الأغذية الحساسة للحرارة (مثل اللحوم المجمدة، الدواجن، الأجبان، ومنتجات الألبان) في صناديق عازلة للحرارة ومبردة بالكامل لضمان وصولها إليكم طازجة ومجمدة كما هي في المتجر تماماً دون أي تأثر بالحرارة الخارجية.
              </p>
            </div>

          </div>
        </main>
      </div>

      <Footer />
    </div>
  );
}
