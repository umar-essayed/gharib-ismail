#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
إسكربت تصنيف وترتيب المنتجات بالذكاء الاصطناعي (Groq API)
المراحل:
1. تطبيق التعديلات المحلية على هيكل الجداول (SQLite Migration).
2. تهيئة التصنيفات الـ 20 الدقيقة في قاعدة البيانات.
3. تصنيف المنتجات الـ 1963 على دفعات (كل دفعة 200 منتج) باستخدام Groq API.
4. تقييم المنتجات وإعطائها درجة أهمية (Score من 100 بكسور عشرية) داخل كل تصنيف.
5. تقييم التصنيفات الـ 20 نفسها لترتيب عرضها.
6. تشغيل المزامنة مع Supabase لتحديث المتجر الإلكتروني.
"""

import sqlite3
import json
import os
import sys
import time
import requests
from concurrent.futures import ThreadPoolExecutor, as_completed
import threading

# الإعدادات
DB_PATH = '/home/omar/Desktop/GHARIB/POS/database/posg.sqlite'
GROQ_API_KEY = 'PLACEHOLDER_GROQ_API_KEY'
MODEL_NAME = 'llama-3.1-8b-instant'
GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions'

# ألوان الطباعة
GREEN = '\033[92m'
RED = '\033[91m'
YELLOW = '\033[93m'
BLUE = '\033[94m'
CYAN = '\033[96m'
BOLD = '\033[1m'
RESET = '\033[0m'

# التصنيفات الـ 20 المحددة
CATEGORIES = [
    "أرز ومكرونة ودقيق",
    "زيوت وسمنة",
    "ألبان وأجبان",
    "شاي وقهوة ومشروبات ساخنة",
    "مشروبات غازية ومياه",
    "عصائر ومشروبات باردة",
    "بسكويت وشوكولاتة وحلويات",
    "شيبسي ومقرمشات وتسالي",
    "معلبات وأغذية محفوظة",
    "عسل ومربى وحلاوة",
    "صوصات وتوابل ومخللات",
    "بقوليات وحبوب معبأة",
    "مجمدات (لحوم وخضار)",
    "منظفات ومنعمات ملابس",
    "سوائل غسيل أطباق ومطهرات",
    "عناية شخصية وصابون",
    "مناديل وحفاضات ومستلزمات ورقية",
    "أدوات ومنتجات بلاستيكية",
    "ياميش ومكسرات وتسالي مقشرة",
    "خدمات وتوصيل وأخرى"
]

db_lock = threading.Lock()

def log(msg, color=RESET):
    print(f"{color}{msg}{RESET}", flush=True)

# ─── 1. هجرة الجداول المحلية ───────────────────────────────────
def apply_local_migrations():
    log("🛠️ جاري تطبيق تعديلات الجداول المحلية (SQLite Migration)...", CYAN)
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    # إضافة عمود importance_score لجدول المنتجات
    try:
        cursor.execute("ALTER TABLE products ADD COLUMN importance_score DECIMAL(5,2) DEFAULT 0.0")
        log("✅ تم إضافة عمود importance_score لجدول المنتجات بنجاح.", GREEN)
    except sqlite3.OperationalError as e:
        if "duplicate column name" in str(e):
            log("ℹ️ عمود importance_score موجود بالفعل في جدول المنتجات.", YELLOW)
        else:
            log(f"❌ خطأ أثناء إضافة عمود المنتجات: {str(e)}", RED)
            
    # إضافة عمود importance_score لجدول الأقسام
    try:
        cursor.execute("ALTER TABLE product_categories ADD COLUMN importance_score DECIMAL(5,2) DEFAULT 0.0")
        log("✅ تم إضافة عمود importance_score لجدول الأقسام بنجاح.", GREEN)
    except sqlite3.OperationalError as e:
        if "duplicate column name" in str(e):
            log("ℹ️ عمود importance_score موجود بالفعل في جدول الأقسام.", YELLOW)
        else:
            log(f"❌ خطأ أثناء إضافة عمود الأقسام: {str(e)}", RED)
            
    conn.commit()
    conn.close()

# ─── 2. تهيئة التصنيفات ───────────────────────────────────────
def initialize_categories():
    log("📂 جاري تهيئة التصنيفات الـ 20 الدقيقة في قاعدة البيانات...", CYAN)
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    # تعطيل الأقسام القديمة أو إخفائها (اختياري، هنا فقط نضمن وجود الـ 20 قسماً جديداً)
    for cat in CATEGORIES:
        cursor.execute("INSERT OR IGNORE INTO product_categories (name, is_active) VALUES (?, 1)", (cat,))
        
    conn.commit()
    
    # جلب المعرفات الجديدة ورسم خريطتها
    cursor.execute("SELECT id, name FROM product_categories WHERE deleted_at IS NULL")
    cat_map = {row[1]: row[0] for row in cursor.fetchall()}
    conn.close()
    
    log(f"✅ تم تهيئة {len(cat_map)} قسم نشط بنجاح.", GREEN)
    return cat_map

# ─── دالة مساعدة لطلب Groq API والتعامل مع الأخطاء ومعدل الاستهلاك ───
def call_groq_api(system_prompt, user_content):
    headers = {
        'Authorization': f'Bearer {GROQ_API_KEY}',
        'Content-Type': 'application/json'
    }
    payload = {
        'model': MODEL_NAME,
        'messages': [
            {'role': 'system', 'content': system_prompt},
            {'role': 'user', 'content': user_content}
        ],
        'temperature': 0.1,
        'max_tokens': 2048
    }
    
    max_retries = 7
    for attempt in range(max_retries):
        try:
            r = requests.post(GROQ_API_URL, json=payload, headers=headers, timeout=45)
            if r.status_code == 200:
                res_data = r.json()
                return res_data['choices'][0]['message']['content']
            
            # معالجة حدود TPM ومعدل الاستهلاك
            is_rate_limit = (r.status_code in [429, 413]) or ("rate_limit_exceeded" in r.text) or ("tokens" in r.text)
            if is_rate_limit:
                sleep_time = (attempt + 1) * 20
                log(f"⚠️ تم الوصول إلى حد الرموز أو معدل الاستهلاك ({r.status_code}). جاري الانتظار {sleep_time} ثانية...", YELLOW)
                time.sleep(sleep_time)
            else:
                log(f"❌ خطأ Groq API: {r.status_code} - {r.text}", RED)
                time.sleep(5)
        except Exception as e:
            log(f"❌ خطأ اتصال بالشبكة: {str(e)}", RED)
            time.sleep(5)
            
    return None

def extract_json_array(text):
    if not text:
        return []
    start = text.find('[')
    end = text.rfind(']')
    if start != -1 and end != -1:
        try:
            return json.loads(text[start:end+1])
        except Exception:
            pass
    start_obj = text.find('{')
    end_obj = text.rfind('}')
    if start_obj != -1 and end_obj != -1:
        try:
            data = json.loads(text[start_obj:end_obj+1])
            for val in data.values():
                if isinstance(val, list):
                    return val
        except Exception:
            pass
    log(f"⚠️ فشل استخراج JSON من النص التالي:\n{text[:300]}", RED)
    return []

# ─── 3. المرحلة الأولى: تصنيف المنتجات ─────────────────────────
def run_classification_phase(cat_map):
    log("\n" + "="*50)
    log("🚀 المرحلة الأولى: تصنيف المنتجات بالذكاء الاصطناعي", BOLD + BLUE)
    log("="*50)
    
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    # جلب المنتجات غير المصنفة أو التي تنتمي للتصنيفات الأربعة القديمة الأساسية فقط (لتسهيل الاستئناف)
    cursor.execute("SELECT id, name FROM products WHERE deleted_at IS NULL AND is_active = 1 AND (category_id IS NULL OR category_id IN (1, 2, 3, 4))")
    products = [{'id': row[0], 'name': row[1]} for row in cursor.fetchall()]
    conn.close()
    
    total_products = len(products)
    log(f"📦 تم تحميل {total_products} منتج بحاجة للتصنيف (تخطي المصنفين بالفعل).", CYAN)
    
    # تقسيم المنتجات لدفعات كل منها 35 منتج لتجنب تجاوز حد TPM
    batch_size = 35
    batches = [products[i:i + batch_size] for i in range(0, total_products, batch_size)]
    
    system_prompt = (
        "You are an expert cataloging system for Egyptian supermarkets.\n"
        "Your task is to assign each product to exactly one of the 20 predefined categories.\n"
        "Return your response ONLY as a valid JSON array of objects. Do not include markdown code block syntax or any text outside the JSON.\n"
        "Each object MUST have the keys: \"id\" (integer) and \"category\" (string, matching one of the 20 categories exactly).\n"
        "Example response:\n"
        "[{\"id\": 47, \"category\": \"ألبان وأجبان\"}]"
    )
    
    categories_str = "\n".join([f"- {c}" for c in CATEGORIES])
    
    processed = 0
    updated_count = 0
    
    for idx, batch in enumerate(batches):
        log(f"🔄 جاري تصنيف الدفعة {idx+1}/{len(batches)} (حجم الدفعة: {len(batch)})...", CYAN)
        
        # تحويل الدفعة لنص
        products_json = json.dumps(batch, ensure_ascii=False, indent=2)
        user_content = (
            f"Predefined Categories:\n{categories_str}\n\n"
            f"Products to classify:\n{products_json}"
        )
        
        response = call_groq_api(system_prompt, user_content)
        classifications = extract_json_array(response)
        
        if classifications:
            conn = sqlite3.connect(DB_PATH)
            cursor = conn.cursor()
            
            for item in classifications:
                pid = item.get('id')
                cat_name = item.get('category')
                
                if pid and cat_name:
                    cat_id = cat_map.get(cat_name)
                    if cat_id:
                        cursor.execute("UPDATE products SET category_id = ? WHERE id = ?", (cat_id, pid))
                        updated_count += 1
            
            conn.commit()
            conn.close()
            processed += len(batch)
            log(f"📈 تم تصنيف وتحديث {processed}/{total_products} منتج بنجاح.", GREEN)
        else:
            log(f"⚠️ فشل تصنيف الدفعة {idx+1}، سيتم تجاوزها.", RED)
            
        # الانتظار 16 ثانية بين كل دفعة للبقاء ضمن حدود الـ TPM
        time.sleep(16)
        
    log(f"✅ انتهت المرحلة الأولى: تم تصنيف {updated_count} منتج بنجاح.", BOLD + GREEN)

# ─── 4. المرحلة الثانية: تقييم أهمية المنتجات ────────────────────
def run_scoring_phase(cat_map):
    log("\n" + "="*50)
    log("🚀 المرحلة الثانية: تقييم المنتجات داخل كل تصنيف", BOLD + BLUE)
    log("="*50)
    
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    # جلب المنتجات حسب كل فئة
    for cat_name, cat_id in cat_map.items():
        cursor.execute("SELECT id, name FROM products WHERE deleted_at IS NULL AND is_active = 1 AND category_id = ?", (cat_id,))
        products = [{'id': row[0], 'name': row[1]} for row in cursor.fetchall()]
        
        total_prods = len(products)
        if total_prods == 0:
            continue
            
        log(f"📂 جاري تقييم {total_prods} منتج في قسم [{cat_name}]...", CYAN)
        
        # معالجة المنتجات في دفعات حجم 20 منتج لتفادي التقييد ومنع الاقتطاع
        batch_size = 20
        batches = [products[i:i + batch_size] for i in range(0, total_prods, batch_size)]
        
        system_prompt = (
            f"You are an expert system ranking products in the category '{cat_name}' in Egypt.\n"
            "Assign an importance/popularity/demand score from 0.0 to 100.0 (with one decimal digit precision, e.g. 84.7) to each product.\n"
            "High scores (85.0 to 100.0) go to brand-name essentials, household staples, or extremely common goods (like Juhayna, Lipton, Pepsi, Domty, Ariel, El-Doha, etc.).\n"
            "Medium scores (50.0 to 80.0) for average brands/goods.\n"
            "Low scores (10.0 to 45.0) for rare, niche, generic, or slow-moving items.\n"
            "Ensure the scores are widely distributed and precise (use decimal places) to avoid duplicates.\n"
            "Return your response ONLY as a valid JSON array of objects. Do not include markdown code block syntax.\n"
            "Each object MUST have: \"id\" (integer) and \"score\" (float).\n"
            "Example response:\n"
            "[{\"id\": 47, \"score\": 93.4}]"
        )
        
        for idx, batch in enumerate(batches):
            products_json = json.dumps(batch, ensure_ascii=False, indent=2)
            
            # محاولة جلب التقييم مع إعادة المحاولة في حالة الفشل
            scores = None
            for retry_attempt in range(3):
                response = call_groq_api(system_prompt, products_json)
                scores = extract_json_array(response)
                if scores:
                    break
                else:
                    sleep_retry = (retry_attempt + 1) * 25
                    log(f"  ⚠️ فشل فك JSON للدفعة {idx+1}. إعادة المحاولة ({retry_attempt+1}/3) بعد الانتظار {sleep_retry} ثانية...", YELLOW)
                    time.sleep(sleep_retry)
            
            if scores:
                conn_sub = sqlite3.connect(DB_PATH)
                cursor_sub = conn_sub.cursor()
                for item in scores:
                    pid = item.get('id')
                    score = item.get('score')
                    if pid is not None and score is not None:
                        cursor_sub.execute("UPDATE products SET importance_score = ? WHERE id = ?", (float(score), pid))
                conn_sub.commit()
                conn_sub.close()
                log(f"  📈 تم تقييم الدفعة {idx+1}/{len(batches)} لقسم [{cat_name}] بنجاح.", GREEN)
            else:
                log(f"  ❌ فشل تقييم دفعة {idx+1} لقسم [{cat_name}] نهائياً.", RED)
                
            time.sleep(20)
            
    conn.close()
    log("✅ انتهت المرحلة الثانية: تم تقييم جميع المنتجات داخل أقسامها بنجاح.", BOLD + GREEN)

# ─── 5. المرحلة الثالثة: تقييم الأقسام نفسها ────────────────────
def run_category_scoring_phase():
    log("\n" + "="*50)
    log("🚀 المرحلة الثالثة: تقييم وترتيب التصنيفات الـ 20 نفسها", BOLD + BLUE)
    log("="*50)
    
    system_prompt = (
        "You are an expert ordering grocery categories for an online supermarket app in Egypt.\n"
        "Assign an importance score from 0.0 to 100.0 (with one decimal digit precision) to each category.\n"
        "Daily staples (ألبان وأجبان, زيوت وسمنة, أرز ومكرونة ودقيق, مشروبات ساخنة) should get highest scores (90.0 to 100.0).\n"
        "Snacks and impulse buys (بسكويت وشوكولاتة وحلويات, مقرمشات وتسالي, كانز ومياه) get medium-high (70.0 to 88.0).\n"
        "Non-food essentials like detergents get medium (50.0 to 68.0).\n"
        "Plastics, papers, services get lowest scores (10.0 to 45.0).\n"
        "Return your response ONLY as a valid JSON array of objects. Do not include markdown code block syntax.\n"
        "Each object MUST have: \"category\" (string matching the category name exactly) and \"score\" (float).\n"
        "Example response:\n"
        "[{\"category\": \"ألبان وأجبان\", \"score\": 98.5}]"
    )
    
    categories_json = json.dumps(CATEGORIES, ensure_ascii=False, indent=2)
    response = call_groq_api(system_prompt, categories_json)
    scores = extract_json_array(response)
    
    if scores:
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        for item in scores:
            cat_name = item.get('category')
            score = item.get('score')
            if cat_name and score is not None:
                cursor.execute("UPDATE product_categories SET importance_score = ? WHERE name = ?", (float(score), cat_name))
        conn.commit()
        conn.close()
        log("✅ انتهت المرحلة الثالثة: تم تحديث درجات الأقسام بنجاح.", BOLD + GREEN)
    else:
        log("❌ فشل تقييم وترتيب الأقسام.", RED)

# ─── 6. مزامنة المتجر الإلكتروني ───────────────────────────────
def trigger_supabase_sync():
    log("\n🔄 جاري تشغيل المزامنة الشاملة لرفع التصنيفات والترتيب الجديد لـ Supabase...", CYAN)
    sync_command = "php -r \"define('APP_ROOT', '/home/omar/Desktop/GHARIB/POS'); require_once '/home/omar/Desktop/GHARIB/POS/bootstrap.php'; print_r(\\App\\Services\\SupabaseSyncService::fullSync());\""
    try:
        import subprocess
        res = subprocess.run(sync_command, shell=True, capture_output=True, text=True)
        if res.returncode == 0:
            log("✅ تمت المزامنة الشاملة مع Supabase بنجاح!", GREEN)
            log(res.stdout, RESET)
        else:
            log(f"❌ فشل تشغيل المزامنة تلقائياً. كود الخطأ: {res.returncode}", RED)
            log(res.stderr, RED)
    except Exception as sync_err:
        log(f"❌ حدث خطأ أثناء تشغيل أمر المزامنة: {str(sync_err)}", RED)

def main():
    log("🚀 بدء تشغيل نظام تصنيف وترتيب المنتجات الذكي...", BOLD + BLUE)
    
    # 1. تطبيق الهجرة المحلية
    apply_local_migrations()
    
    # 2. تهيئة التصنيفات
    cat_map = initialize_categories()
    
    # 3. تشغيل المرحلة الأولى: تصنيف المنتجات
    run_classification_phase(cat_map)
    
    # 4. تشغيل المرحلة الثانية: تقييم المنتجات
    run_scoring_phase(cat_map)
    
    # 5. تشغيل المرحلة الثالثة: تقييم الأقسام
    run_category_scoring_phase()
    
    # 6. المزامنة مع Supabase أونلاين
    trigger_supabase_sync()
    
    log("\n🎉 تم تنفيذ خطة التصنيف والترتيب الذكي بالكامل بنجاح!", BOLD + GREEN)

if __name__ == '__main__':
    main()
