'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { supabase } from '@/lib/supabase';
import { useCart } from '@/context/CartContext';
import Navbar from '@/components/Navbar';

export default function AuthCallbackPage() {
  const router = useRouter();
  const { refreshProfile } = useCart();

  const [session, setSession] = useState<any>(null);
  const [needPhone, setNeedPhone] = useState(false);
  const [fullName, setFullName] = useState('');
  const [phone, setPhone] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function checkSession() {
      try {
        setLoading(true);
        const { data: { session: currentSession } } = await supabase.auth.getSession();
        if (!currentSession) {
          router.push('/auth');
          return;
        }

        setSession(currentSession);

        // Fetch user profile from public.profiles
        const { data: dbProfile } = await supabase
          .from('profiles')
          .select('*')
          .eq('id', currentSession.user.id)
          .maybeSingle();

        // Check if phone number is valid (Egyptian mobile number: 11 digits, starts with 01)
        const phoneRegex = /^01[0125][0-9]{8}$/;
        const hasValidPhone = dbProfile && dbProfile.phone && phoneRegex.test(dbProfile.phone.trim());

        if (hasValidPhone) {
          // Sync account metadata and redirect to home
          await refreshProfile();
          router.push('/');
        } else {
          // Pre-populate name from Google profile metadata
          const metaName = currentSession.user.user_metadata?.full_name || currentSession.user.user_metadata?.name || '';
          setFullName(metaName);
          setNeedPhone(true);
          setLoading(false);
        }
      } catch (err) {
        console.error('Session callback error:', err);
        router.push('/auth');
      }
    }
    checkSession();
  }, [router, refreshProfile]);

  const handleCompleteSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!session) return;
    
    // Validate phone number
    const trimmedPhone = phone.trim();
    const phoneRegex = /^01[0125][0-9]{8}$/;
    if (!phoneRegex.test(trimmedPhone)) {
      setError('الرجاء إدخال رقم هاتف مصري صحيح مكون من 11 رقماً ويبدأ بـ 01 (مثل: 01234567890).');
      return;
    }

    if (!fullName.trim()) {
      setError('الرجاء إدخال الاسم بالكامل.');
      return;
    }

    try {
      setSubmitting(true);
      setError(null);

      // Check if profile exists
      const { data: existingProfile } = await supabase
        .from('profiles')
        .select('id')
        .eq('id', session.user.id)
        .maybeSingle();

      if (existingProfile) {
        // Update existing profile
        const { error: updateErr } = await supabase
          .from('profiles')
          .update({
            full_name: fullName.trim(),
            phone: trimmedPhone,
          })
          .eq('id', session.user.id);

        if (updateErr) throw updateErr;
      } else {
        // Insert new profile
        const { error: insertErr } = await supabase
          .from('profiles')
          .insert({
            id: session.user.id,
            full_name: fullName.trim(),
            phone: trimmedPhone,
            address: '',
            points: 0,
            role: 'customer',
          });

        if (insertErr) throw insertErr;
      }

      // Sync account profile state in app context
      await refreshProfile();
      
      // Redirect to home
      router.push('/');
    } catch (err: any) {
      console.error('Failed to complete profile registration:', err);
      setError(err.message || 'حدث خطأ أثناء حفظ البيانات. الرجاء المحاولة مجدداً.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 flex flex-col items-center justify-center space-y-4 font-sans text-right">
        <div className="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin text-primary" />
        <p className="text-sm font-bold text-slate-550">جاري تسجيل الدخول والتحقق من حسابك بجوجل...</p>
      </div>
    );
  }

  return (
    <div className="min-h-screen relative overflow-hidden flex flex-col bg-white font-sans text-right">
      {/* Premium backdrop image */}
      <div 
        className="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-75 scale-102 transition-all duration-1000 pointer-events-none"
        style={{ backgroundImage: "url('/hero-bg.png')" }}
      />
      
      {/* Light overlay for clean reading */}
      <div className="absolute inset-0 bg-gradient-to-b from-white/90 via-white/85 to-white/95 backdrop-blur-[0.5px] pointer-events-none" />

      {/* 3D Grid floor overlay */}
      <div className="absolute inset-0 opacity-15 bg-[radial-gradient(#04ba06_1px,transparent_1px)] [background-size:16px_16px] pointer-events-none" />

      <Navbar />

      <main className="flex-1 flex items-center justify-center px-4 py-12 relative z-10">
        <div className="max-w-md w-full bg-white/90 backdrop-blur-lg border border-slate-200/60 rounded-3xl p-8 shadow-2xl space-y-6 relative overflow-hidden">
          
          {/* Decorative glows */}
          <div className="absolute -top-20 -right-20 w-40 h-40 bg-primary/10 rounded-full blur-3xl pointer-events-none" />
          <div className="absolute -bottom-20 -left-20 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" />

          {/* Heading */}
          <div className="text-center space-y-2">
            <h2 className="text-xl font-black text-slate-900">إكمال بيانات الحساب 👤</h2>
            <p className="text-xs text-slate-500 font-medium leading-relaxed">
              شكراً لتسجيل دخولك عبر جوجل. يرجى إدخال الاسم بالكامل ورقم الهاتف لإكمال تسجيل الحساب وضمان شحن طلباتك بالعامرية والناصرية.
            </p>
          </div>

          {/* Feedback alerts */}
          {error && (
            <div className="p-3.5 bg-red-50 border border-red-200 rounded-2xl text-red-650 text-xs font-bold text-right flex items-center justify-end gap-2">
              <span>{error}</span>
              <span className="text-red-500 text-base">⚠️</span>
            </div>
          )}

          {/* Complete Profile Form */}
          <form onSubmit={handleCompleteSubmit} className="space-y-4 text-right font-bold">
            
            {/* Full Name */}
            <div className="space-y-1.5 font-bold">
              <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5 justify-end">
                الاسم بالكامل *
                <span className="text-primary text-xs">👤</span>
              </label>
              <input
                type="text"
                required
                placeholder="اكتب اسمك الثلاثي للتسليم الفوري"
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
                className="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-slate-800 transition-all font-medium font-bold"
              />
            </div>

            {/* Phone Number */}
            <div className="space-y-1.5 font-bold">
              <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5 justify-end">
                رقم الهاتف للتواصل والطلبات *
                <span className="text-primary text-xs">📞</span>
              </label>
              <input
                type="tel"
                required
                placeholder="مثال: 012XXXXXXXX"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                className="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-slate-800 transition-all font-medium font-bold"
                dir="ltr"
              />
            </div>

            {/* Submit button */}
            <button
              type="submit"
              disabled={submitting}
              className="w-full py-3 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-extrabold transition-all duration-300 shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer transform hover:-translate-y-0.5"
            >
              {submitting ? (
                <>
                  <span className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin inline-block mr-1" />
                  جاري حفظ البيانات...
                </>
              ) : (
                'إكمال الحساب والبدء بالتسوق 🛒'
              )}
            </button>
          </form>

        </div>
      </main>
    </div>
  );
}
