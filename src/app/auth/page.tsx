'use client';

import React, { useState, useEffect, Suspense } from 'react';
import { useRouter } from 'next/navigation';
import { useCart } from '@/context/CartContext';
import Navbar from '@/components/Navbar';
import Canvas3DGrid from '@/components/Canvas3DGrid';
import { supabase } from '@/lib/supabase';
import { confirmUserByPhone } from '@/lib/authHelper';
import { Phone, Lock, User, Loader2, ArrowRight, CheckCircle2 } from 'lucide-react';
import Link from 'next/link';

function AuthContent() {
  const { profile, refreshProfile } = useCart();
  const router = useRouter();

  const [activeTab, setActiveTab] = useState<'login' | 'register'>('login');
  const [phone, setPhone] = useState('');
  const [fullName, setFullName] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const handleGoogleLogin = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const { error: googleErr } = await supabase.auth.signInWithOAuth({
        provider: 'google',
        options: {
          redirectTo: `${window.location.origin}/auth/callback`,
        }
      });

      if (googleErr) throw googleErr;
    } catch (err: any) {
      console.error('Google Auth login failed:', err);
      setError(err.message || 'فشل تسجيل الدخول باستخدام حساب جوجل.');
      setLoading(false);
    }
  };

  // Redirect if already logged in
  useEffect(() => {
    if (profile) {
      if (profile.role === 'admin') {
        router.push('/admin/orders');
      } else {
        router.push('/profile');
      }
    }
  }, [profile, router]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!phone.trim() || !password) {
      setError('الرجاء تعبئة جميع الحقول المطلوبة.');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      setSuccess(null);

      const virtualEmail = `${phone.trim()}@gmail.com`;

      if (activeTab === 'login') {
        // Sign In
        let { data, error: signInErr } = await supabase.auth.signInWithPassword({
          email: virtualEmail,
          password: password,
        });

        // Auto-confirm old accounts if they throw "Email not confirmed"
        if (signInErr && (signInErr.message?.toLowerCase().includes('confirm') || signInErr.message?.toLowerCase().includes('verification'))) {
          try {
            const confirmed = await confirmUserByPhone(phone);
            if (confirmed) {
              const retryResult = await supabase.auth.signInWithPassword({
                email: virtualEmail,
                password: password,
              });
              signInErr = retryResult.error;
            }
          } catch (adminErr) {
            console.error('Failed to auto-confirm user during login:', adminErr);
          }
        }

        if (signInErr) throw signInErr;

        setSuccess('تم تسجيل الدخول بنجاح! جاري تحويلك...');
        await refreshProfile();
        setTimeout(() => {
          router.push('/');
        }, 1000);
      } else {
        // Sign Up
        if (!fullName.trim()) {
          setError('الرجاء إدخال الاسم بالكامل.');
          setLoading(false);
          return;
        }

        // Use admin.createUser to bypass confirmation checks since we hold service_role
        const { data, error: signUpErr } = await supabase.auth.admin.createUser({
          email: virtualEmail,
          password: password,
          email_confirm: true,
          user_metadata: {
            full_name: fullName.trim(),
            phone: phone.trim(),
            address: '',
          },
        });

        if (signUpErr) throw signUpErr;

        // Auto log in after creation
        const { error: signInErr } = await supabase.auth.signInWithPassword({
          email: virtualEmail,
          password: password,
        });

        if (signInErr) throw signInErr;

        setSuccess('تم إنشاء حسابك وتفعيل الدخول بنجاح! جاري تحويلك...');
        await refreshProfile();
        setTimeout(() => {
          router.push('/');
        }, 1000);
      }
    } catch (err: any) {
      console.error('Auth operation failed:', err);
      let friendlyMessage = err.message || 'حدث خطأ غير متوقع. الرجاء التحقق من البيانات والمحاولة مجدداً.';
      if (err.message?.includes('already exists') || err.message?.includes('already registered') || err.message?.includes('email_exists')) {
        friendlyMessage = 'رقم الهاتف هذا مسجل بالفعل لحساب آخر. يرجى تسجيل الدخول بدلاً من ذلك.';
      }
      setError(friendlyMessage);
    } finally {
      setLoading(false);
    }
  };

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
      <Canvas3DGrid opacity={0.65} />

      <Navbar />

      <main className="flex-1 flex items-center justify-center px-4 py-12 relative z-10">
        <div className="max-w-md w-full bg-white/90 backdrop-blur-lg border border-slate-200/60 rounded-3xl p-8 shadow-2xl space-y-6 relative overflow-hidden">
          
          {/* Decorative glows */}
          <div className="absolute -top-20 -right-20 w-40 h-40 bg-primary/10 rounded-full blur-3xl pointer-events-none" />
          <div className="absolute -bottom-20 -left-20 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" />

          {/* Heading */}
          <div className="text-center space-y-2">
            <h2 className="text-2xl font-black text-slate-900">مرحباً بك في الناصرية جملة ماركت</h2>
            <p className="text-xs text-slate-500 font-medium leading-relaxed">
              سجل دخولك لمتابعة طلباتك، وإدارة عناوين الشحن والتمتع بأسعار الجملة للجميع
            </p>
          </div>

          {/* Tab Switcher */}
          <div className="flex bg-slate-100 p-1.5 rounded-2xl border border-slate-200/40">
            <button
              onClick={() => { setActiveTab('login'); setError(null); setSuccess(null); }}
              className={`flex-1 py-2.5 rounded-xl font-bold text-xs transition-all duration-300 cursor-pointer ${
                activeTab === 'login' 
                  ? 'bg-white text-primary shadow-sm' 
                  : 'text-slate-500 hover:text-slate-800'
              }`}
            >
              تسجيل دخول
            </button>
            <button
              onClick={() => { setActiveTab('register'); setError(null); setSuccess(null); }}
              className={`flex-1 py-2.5 rounded-xl font-bold text-xs transition-all duration-300 cursor-pointer ${
                activeTab === 'register' 
                  ? 'bg-white text-primary shadow-sm' 
                  : 'text-slate-500 hover:text-slate-800'
              }`}
            >
              إنشاء حساب جديد
            </button>
          </div>

          {/* Feedback alerts */}
          {error && (
            <div className="p-3.5 bg-red-50 border border-red-200 rounded-2xl text-red-650 text-xs font-bold text-right flex items-center justify-end gap-2">
              <span>{error}</span>
              <span className="text-red-500 text-base">⚠️</span>
            </div>
          )}
          {success && (
            <div className="p-3.5 bg-emerald-50 border border-emerald-250 rounded-2xl text-emerald-700 text-xs font-bold text-right flex items-center justify-end gap-2">
              <span>{success}</span>
              <CheckCircle2 size={16} className="text-emerald-500" />
            </div>
          )}

          {/* Auth Form */}
          <form onSubmit={handleSubmit} className="space-y-4 text-right">
            
            {/* Full Name (For Register Only) */}
            {activeTab === 'register' && (
              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5 justify-end">
                  الاسم بالكامل *
                  <User size={14} className="text-primary" />
                </label>
                <input
                  type="text"
                  required
                  placeholder="اكتب اسمك الثلاثي للتسليم الفوري"
                  value={fullName}
                  onChange={(e) => setFullName(e.target.value)}
                  className="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-slate-800 transition-all font-medium"
                />
              </div>
            )}

            {/* Phone Number */}
            <div className="space-y-1.5">
              <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5 justify-end">
                رقم الهاتف للتواصل *
                <Phone size={14} className="text-primary" />
              </label>
              <input
                type="tel"
                required
                placeholder="مثال: 012XXXXXXXX"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                className="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-slate-800 transition-all font-medium"
                dir="ltr"
              />
            </div>

            {/* Password */}
            <div className="space-y-1.5">
              <label className="text-xs font-bold text-slate-700 flex items-center gap-1.5 justify-end">
                أدخل كلمة المرور *
                <Lock size={14} className="text-primary" />
              </label>
              <input
                type="password"
                required
                placeholder="أدخل كلمة المرور الخاصة بك"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-left focus:outline-none focus:ring-1 focus:ring-primary focus:bg-white text-slate-800 transition-all font-medium"
                dir="ltr"
              />
              {activeTab === 'login' && (
                <p className="text-[9px] text-slate-400 mt-1">
                  تلميح: إذا تم إنشاء حسابك تلقائياً عند طلبك السابق، كلمة المرور هي رقم هاتفك نفسه.
                </p>
              )}
            </div>

            {/* Submit CTA */}
            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-extrabold transition-all duration-300 shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer transform hover:-translate-y-0.5"
            >
              {loading ? (
                <>
                  <Loader2 size={14} className="animate-spin" />
                  جاري المعالجة...
                </>
              ) : activeTab === 'login' ? (
                'تسجيل الدخول'
              ) : (
                'إنشاء حساب جديد'
              )}
            </button>
          </form>

          <div className="relative flex py-2 items-center">
            <div className="flex-grow border-t border-slate-200"></div>
            <span className="flex-shrink mx-4 text-[10px] text-slate-400 font-bold">أو بواسطة</span>
            <div className="flex-grow border-t border-slate-200"></div>
          </div>

          <button
            onClick={handleGoogleLogin}
            disabled={loading}
            className="w-full py-2.5 border border-slate-200 hover:border-slate-350 bg-slate-50/50 hover:bg-white text-slate-700 rounded-xl text-xs font-bold transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer shadow-2xs hover:shadow-sm"
          >
            <svg className="w-4 h-4" viewBox="0 0 24 24">
              <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              />
              <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              />
              <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
              />
              <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
              />
            </svg>
            تسجيل الدخول باستخدام جوجل
          </button>

          {/* Footer Back Link */}
          <div className="pt-2 border-t border-slate-100 flex justify-center">
            <Link 
              href="/"
              className="text-[11px] text-slate-500 hover:text-primary transition-colors font-bold flex items-center gap-1"
            >
              العودة للرئيسية والتسوق
              <ArrowRight size={12} />
            </Link>
          </div>

        </div>
      </main>
    </div>
  );
}

export default function AuthPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-slate-50 flex items-center justify-center">
        <div className="text-center space-y-4">
          <div className="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="text-sm font-bold text-slate-650">تحميل بوابة الأمان والتحقق...</p>
        </div>
      </div>
    }>
      <AuthContent />
    </Suspense>
  );
}
