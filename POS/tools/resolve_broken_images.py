#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
إسكربت حل مشاكل الصور التالفة والمفقودة تلقائياً
يقوم بقراءة الصور التالفة والمفقودة من التقرير أو قاعدة البيانات،
ثم يبحث لها عن صور جديدة صالحة ويعمل لها تحديث مباشر في قاعدة البيانات مع التحقق من الرابط.
"""

import sqlite3
import csv
import os
import re
import sys
import time
import random
import threading
import urllib.request
import urllib.error
import requests
from urllib.parse import urlparse, urlunparse, quote
from concurrent.futures import ThreadPoolExecutor, as_completed

# مسارات الملفات
DB_PATH = '/home/omar/Desktop/GHARIB/POS/database/posg.sqlite'
BROKEN_REPORT_PATH = '/home/omar/Desktop/GHARIB/POS/storage/broken_images_report.csv'
RESOLVED_REPORT_PATH = '/home/omar/Desktop/GHARIB/POS/storage/resolved_images_report.csv'
RESOLVED_SQL_PATH = '/home/omar/Desktop/GHARIB/POS/storage/resolved_images_update.sql'

MAX_WORKERS = 4
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept-Language': 'ar-EG,ar;q=0.9,en;q=0.8',
}

# نطاقات موثوقة
TRUSTED_TIERS = [
    {
        'domains': [
            'mafrservices.com', 'mafretailproxy.com', 'hyperone.com.eg',
            'hypermousa.com', 'spinneys.com', 'metro-markets.com',
            'b-tech.com', 'elmezantrade.com', 'elmezan.com'
        ],
        'score': 95,
        'label': 'سوبرماركت-مصري'
    },
    {
        'domains': [
            'media-amazon.com', 'ssl-images-amazon.com', 'images-amazon.com',
            'openfoodfacts.org', 'openbeautyfacts.org', 'buycott.com',
            'barcodelookup.com'
        ],
        'score': 90,
        'label': 'amazon-دولي'
    },
    {
        'domains': [
            'oxygen-mart.com', 'alkhan-mart.com', 'multicst.com',
            'shakomako.co', 'wootfi.com', 'qebox.app', 'yaoota.com',
            'jumia.com.eg', 'noon.com', 'talabat.com', 'benselemanmarket.com',
            'riccostores.com', 'zid.store', 'osmanmarket.com', 'dokkan-albalady.com'
        ],
        'score': 75,
        'label': 'متجر-عربي'
    }
]

# نطاقات ممنوعة
BAD_DOMAINS = {
    'youtube.com', 'ytimg.com', 'facebook.com', 'instagram.com',
    'twitter.com', 'tiktok.com', 'scribd.com', 'scribdassets.com',
    'slideshare.net', 'shutterstock.com', 'gettyimages.com', 'freepik.com',
    'unsplash.com', 'pexels.com', 'pngtree.com', 'pixabay.com',
    'medium.com', 'blogspot.com', 'celler-presse.de'
}

# كلمات تدل على صور وهمية
BAD_URL_PATTERNS = [
    'stickybanner', 'banner', 'placeholder', 'logo', 'icon',
    'default', 'no-image', 'noimage', 'blank', 'spinner',
    '76f12a04', 'nooncdn.com/mpcms', '1170D60', 'cms/Home', 'cms/home'
]

# ألوان للطباعة في الطرفية
GREEN = '\033[92m'
RED = '\033[91m'
YELLOW = '\033[93m'
BLUE = '\033[94m'
CYAN = '\033[96m'
BOLD = '\033[1m'
RESET = '\033[0m'

print_lock = threading.Lock()
db_lock = threading.Lock()
csv_lock = threading.Lock()
csv_initialized = False

def log(msg, color=RESET):
    with print_lock:
        print(f"{color}{msg}{RESET}", flush=True)

def safe_encode_url(url):
    try:
        parts = urlparse(url)
        quoted_path = quote(parts.path, safe='/')
        quoted_query = quote(parts.query, safe='=&')
        return urlunparse((parts.scheme, parts.netloc, quoted_path, parts.params, quoted_query, parts.fragment))
    except Exception:
        return url

def verify_image_url(url):
    """التحقق من أن رابط الصورة يعمل ويرجع كود 200"""
    if not url or not (url.startswith('http://') or url.startswith('https://')):
        return False
    
    # فلترة الصور الوهمية
    url_lower = url.lower()
    for pattern in BAD_URL_PATTERNS:
        if pattern in url_lower:
            return False
            
    url = safe_encode_url(url)
    try:
        req = urllib.request.Request(url, headers={'User-Agent': HEADERS['User-Agent']}, method='HEAD')
        with urllib.request.urlopen(req, timeout=5) as response:
            if response.status == 200:
                return True
    except urllib.error.HTTPError as e:
        if e.code in [405, 403, 501]: # بعض المواقع تمنع HEAD، نجرب GET خفيف
            try:
                req_get = urllib.request.Request(url, headers={'User-Agent': HEADERS['User-Agent']}, method='GET')
                with urllib.request.urlopen(req_get, timeout=5) as response:
                    if response.status == 200:
                        return True
            except Exception:
                pass
    except Exception:
        pass
    return False

def get_domain(url):
    try:
        return urlparse(url).netloc.lower().replace('www.', '')
    except Exception:
        return ''

def score_url(url):
    domain = get_domain(url)
    for bad in BAD_DOMAINS:
        if bad in domain:
            return -1
    if not re.search(r'\.(jpg|jpeg|png|webp)(\?|$)', url, re.IGNORECASE):
        return -1
    for tier in TRUSTED_TIERS:
        for td in tier['domains']:
            if td in domain:
                return tier['score']
    return 40

def clean_product_name_for_query(name):
    q = name.strip()
    # إزالة الأوزان والأحجام والتعبئة لتبسيط البحث
    q = re.sub(r'\d+[\s]*(g|gm|gm\.|gram|ml|ml\.|l|liters?|kg|kilo|لتر|مل|جم|جرام|كجم|كيلو|ق|قطع|قطعة)\b', '', q, flags=re.IGNORECASE)
    q = re.sub(r'[\-\+\*\(\)\[\]]', ' ', q)
    q = re.sub(r'\s+', ' ', q).strip()
    return q

# ─── مصادر جلب الصور ──────────────────────────────────────────

def fetch_openfoodfacts_barcode(barcode):
    try:
        url = f"https://world.openfoodfacts.org/api/v0/product/{barcode}.json"
        r = requests.get(url, timeout=5, headers={'User-Agent': 'POS-ImageFetcher/3.0'})
        if r.status_code == 200:
            data = r.json()
            if data.get('status') == 1:
                p = data.get('product', {})
                img = p.get('image_front_url') or p.get('image_url') or p.get('image_front_small_url')
                if img:
                    return img
    except Exception:
        pass
    return None

def fetch_upcitemdb(barcode):
    try:
        url = f"https://api.upcitemdb.com/prod/trial/lookup?upc={barcode}"
        r = requests.get(url, timeout=5, headers={'User-Agent': 'POS-ImageFetcher/3.0', 'Accept': 'application/json'})
        if r.status_code == 200:
            data = r.json()
            items = data.get('items', [])
            if items:
                for img in items[0].get('images', []):
                    return img
    except Exception:
        pass
    return None

def fetch_openbeautyfacts(barcode):
    try:
        url = f"https://world.openbeautyfacts.org/api/v0/product/{barcode}.json"
        r = requests.get(url, timeout=5, headers={'User-Agent': 'POS-ImageFetcher/3.0'})
        if r.status_code == 200:
            data = r.json()
            if data.get('status') == 1:
                p = data.get('product', {})
                img = p.get('image_front_url') or p.get('image_url')
                if img:
                    return img
    except Exception:
        pass
    return None

def fetch_carrefour_egypt(name):
    try:
        query = clean_product_name_for_query(name)
        url = f"https://www.carrefouregypt.com/mafegy/en/v4/search?keyword={quote(query)}&pageSize=5"
        r = requests.get(url, timeout=5, headers=HEADERS)
        if r.status_code == 200:
            try:
                data = r.json()
                products = data.get('data', {}).get('products', {}).get('results', [])
                for p in products:
                    img = p.get('thumbnail') or p.get('image')
                    if img:
                        if img.startswith('//'):
                            img = 'https:' + img
                        return img
            except Exception:
                pass
            for pattern in [
                r'"(https://[^"]*carrefouregypt[^"]*\.(?:jpg|jpeg|png|webp)[^"]*)"',
                r'"(https://[^"]*cloudinary[^"]*\.(?:jpg|jpeg|png|webp)[^"]*)"',
            ]:
                for img in re.findall(pattern, r.text):
                    img = img.replace('\\/', '/').replace('\\u002F', '/')
                    return img
    except Exception:
        pass
    return None

def fetch_noon_egypt(name):
    try:
        query = clean_product_name_for_query(name)
        url = f"https://www.noon.com/egypt-en/search/?q={quote(query)}&limit=5"
        r = requests.get(url, timeout=5, headers=HEADERS)
        if r.status_code == 200:
            for pattern in [
                r'"(https://f\.nooncdn\.com/p/[^"]+\.(?:jpg|jpeg|png|webp))"',
                r'"(https://f\.nooncdn\.com/products/[^"]+\.(?:jpg|jpeg|png|webp))"',
            ]:
                for img in re.findall(pattern, r.text):
                    return img
    except Exception:
        pass
    return None

def fetch_jumia_egypt(name):
    try:
        query = clean_product_name_for_query(name)
        url = f"https://www.jumia.com.eg/catalog/?q={quote(query)}"
        r = requests.get(url, timeout=5, headers=HEADERS)
        if r.status_code == 200:
            for pattern in [
                r'"(https://[^"]*jumia\.is/unsafe/fit-in/[^"]+/product/[^"]+\.(?:jpg|jpeg|png|webp))"',
                r'data-src="(https://[^"]*jumia\.is/unsafe[^"]+/product/[^"]+\.(?:jpg|jpeg|png|webp))"',
            ]:
                for img in re.findall(pattern, r.text, re.IGNORECASE):
                    img = img.replace('\\/', '/').replace('\\u002F', '/')
                    return img
    except Exception:
        pass
    return None

def fetch_ddgs_image(name):
    try:
        from ddgs import DDGS
        time.sleep(random.uniform(0.5, 1.2)) # للتخفيف على محرك البحث
        with DDGS(timeout=10) as d:
            results = list(d.images(
                query=name,
                region='wt-wt',
                safesearch='moderate',
                max_results=15
            ))
        
        best_url = None
        best_score = -1
        
        for r in results:
            url = r.get('image', '')
            if not url:
                continue
            try:
                w = int(r.get('width') or 0)
                h = int(r.get('height') or 0)
            except ValueError:
                w, h = 0, 0
            if w < 100 or h < 100:
                continue
                
            sc = score_url(url)
            if sc > best_score:
                best_score = sc
                best_url = url
                if sc >= 90:
                    break
                    
        return best_url, best_score
    except Exception:
        pass
    return None, 0

# ─── منطق المعالجة والتحديث ───────────────────────────────────

def process_product(product):
    pid = product['id']
    name = product['name']
    barcode = product['barcode'] or ''
    old_img = product['old_image_url'] or ''
    issue = product.get('issue', 'مفقودة')
    
    log(f"🔄 جاري محاولة حل مشكلة منتج [{pid}]: {name} (السبب: {issue})", CYAN)
    
    new_url = None
    source = ''
    score = 0
    
    # 1. البحث بالباركود أولاً
    if barcode and len(barcode) >= 5:
        # OFF
        img = fetch_openfoodfacts_barcode(barcode)
        if img and verify_image_url(img):
            new_url, source, score = img, 'OpenFoodFacts-Barcode', 97
            
        # UPC
        if not new_url:
            img = fetch_upcitemdb(barcode)
            if img and verify_image_url(img):
                new_url, source, score = img, 'UPCitemdb-Barcode', 92
                
        # OBF
        if not new_url:
            img = fetch_openbeautyfacts(barcode)
            if img and verify_image_url(img):
                new_url, source, score = img, 'OpenBeautyFacts-Barcode', 90
                
    # 2. البحث بالاسم في المتاجر المصرية
    if not new_url:
        img = fetch_carrefour_egypt(name)
        if img and verify_image_url(img):
            new_url, source, score = img, 'Carrefour-EG-Name', 85
            
    if not new_url:
        img = fetch_noon_egypt(name)
        if img and verify_image_url(img):
            new_url, source, score = img, 'Noon-EG-Name', 82
            
    if not new_url:
        img = fetch_jumia_egypt(name)
        if img and verify_image_url(img):
            new_url, source, score = img, 'Jumia-EG-Name', 80
            
    # 3. البحث العام في DuckDuckGo
    if not new_url:
        cleaned_name = clean_product_name_for_query(name)
        img, sc = fetch_ddgs_image(cleaned_name)
        if img and verify_image_url(img):
            new_url, source, score = img, 'DDGS-Image-Search', sc
            
    # حفظ النتيجة وتحديث قاعدة البيانات إذا وجدت صورة صالحة
    result_status = 'not_resolved'
    if new_url:
        result_status = 'resolved'
        log(f"  ✅ تم العثور على صورة صالحة وتعمل للمنتج [{pid}] من مصدر {source}", GREEN)
        
        # تحديث قاعدة البيانات محلياً
        try:
            with db_lock:
                conn = sqlite3.connect(DB_PATH)
                cur = conn.cursor()
                cur.execute("UPDATE products SET image_path = ? WHERE id = ?", (new_url, pid))
                conn.commit()
                conn.close()
        except Exception as e:
            log(f"  ❌ خطأ أثناء تحديث قاعدة البيانات للمنتج [{pid}]: {str(e)}", RED)
            result_status = 'db_error'
    else:
        log(f"  ⚠️ لم يتم العثور على صورة صالحة وتعمل للمنتج [{pid}]", YELLOW)
        
    res_data = {
        'product_id': pid,
        'product_name': name,
        'barcode': barcode,
        'old_image_url': old_img,
        'new_image_url': new_url or '',
        'source': source,
        'score': score,
        'status': result_status
    }
    
    # حفظ النتيجة في CSV تدريجياً
    save_to_csv(res_data)
    return res_data

def save_to_csv(row):
    global csv_initialized
    fields = ['product_id', 'product_name', 'barcode', 'old_image_url', 'new_image_url', 'source', 'score', 'status']
    os.makedirs(os.path.dirname(RESOLVED_REPORT_PATH), exist_ok=True)
    with csv_lock:
        mode = 'a' if csv_initialized else 'w'
        with open(RESOLVED_REPORT_PATH, mode, newline='', encoding='utf-8-sig') as f:
            writer = csv.DictWriter(f, fieldnames=fields)
            if not csv_initialized:
                writer.writeheader()
                csv_initialized = True
            writer.writerow(row)
            
        # إضافة لملف الـ SQL
        if row['status'] == 'resolved' and row['new_image_url']:
            sql_mode = 'a' if os.path.exists(RESOLVED_SQL_PATH) else 'w'
            with open(RESOLVED_SQL_PATH, sql_mode, encoding='utf-8') as sf:
                if sql_mode == 'w':
                    sf.write("-- تحديث الصور التي تم حل مشاكلها تلقائياً\n\n")
                img_escaped = row['new_image_url'].replace("'", "''")
                sf.write(f"-- [{row['product_id']}] {row['product_name']} | المصدر: {row['source']}\n")
                sf.write(f"UPDATE products SET image_path = '{img_escaped}' WHERE id = {row['product_id']};\n\n")

def main():
    if not os.path.exists(DB_PATH):
        log(f"Error: Database not found at {DB_PATH}", RED)
        return
        
    # 1. جلب المنتجات المطلوب حل مشاكلها
    broken_ids = {}
    
    # نقرأ التقرير السابق أولاً إذا وُجد
    if os.path.exists(BROKEN_REPORT_PATH):
        log(f"📂 تم العثور على تقرير الصور التالفة والمفقودة في: {BROKEN_REPORT_PATH}", CYAN)
        with open(BROKEN_REPORT_PATH, mode='r', encoding='utf-8-sig') as f:
            reader = csv.DictReader(f)
            for row in reader:
                try:
                    pid = int(row['product_id'])
                    broken_ids[pid] = {
                        'id': pid,
                        'name': row['product_name'],
                        'barcode': row.get('barcode', ''),
                        'old_image_url': row.get('image_url', ''),
                        'issue': row.get('issue', 'تالفة')
                    }
                except Exception:
                    pass
    else:
        log("⚠️ لم يتم العثور على ملف broken_images_report.csv. سيتم فحص المنتجات التي بدون صور مباشرة من قاعدة البيانات.", YELLOW)
        
    # نقرأ من قاعدة البيانات أي منتج نشط صورته فارغة (وليس في القائمة بعد)
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()
    cursor.execute("SELECT id, name, barcode, image_path FROM products WHERE deleted_at IS NULL AND is_active = 1 AND (image_path IS NULL OR image_path = '')")
    db_prods = cursor.fetchall()
    conn.close()
    
    for row in db_prods:
        pid = row['id']
        if pid not in broken_ids:
            broken_ids[pid] = {
                'id': pid,
                'name': row['name'],
                'barcode': row['barcode'] or '',
                'old_image_url': '',
                'issue': 'مفقودة (لا توجد صورة)'
            }
            
    products_to_fix = list(broken_ids.values())
    total_to_fix = len(products_to_fix)
    
    if total_to_fix == 0:
        log("✅ كل الصور سليمة وموجودة! لا توجد منتجات تحتاج للإصلاح.", GREEN)
        return
        
    log(f"🚀 تم تحميل {total_to_fix} منتج يحتاج لحل مشكلة صورته. بدء عملية الإصلاح التلقائي...", BOLD + BLUE)
    
    # مسح تقرير التحديث القديم إن وُجد لبدء تقرير جديد نظيف
    if os.path.exists(RESOLVED_REPORT_PATH):
        os.remove(RESOLVED_REPORT_PATH)
    if os.path.exists(RESOLVED_SQL_PATH):
        os.remove(RESOLVED_SQL_PATH)
        
    # 2. تشغيل عملية المعالجة بالتوازي باستخدام ThreadPool
    resolved_count = 0
    not_resolved_count = 0
    
    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
        futures = {executor.submit(process_product, p): p for p in products_to_fix}
        
        processed = 0
        for future in as_completed(futures):
            processed += 1
            res = future.result()
            if res['status'] == 'resolved':
                resolved_count += 1
            else:
                not_resolved_count += 1
                
            if processed % 10 == 0:
                log(f"📈 تم معالجة {processed}/{total_to_fix} منتجات (نجح: {resolved_count} | لم ينجح: {not_resolved_count})", BLUE)
                
    log("\n" + "="*50, BOLD + GREEN)
    log("📋 ملخص عملية الإصلاح التلقائي للصور:", BOLD + GREEN)
    log("="*50, BOLD + GREEN)
    log(f"✓ إجمالي المنتجات المعالجة: {total_to_fix}", BOLD)
    log(f"✓ تم إصلاح وتحديث صورها بنجاح: {resolved_count}", GREEN)
    log(f"⚠️ لم يتم العثور على بديل لها: {not_resolved_count}", YELLOW)
    log(f"📁 تقرير الإصلاح (CSV) محفوظ في: {RESOLVED_REPORT_PATH}", CYAN)
    log(f"📝 ملف النسخ الاحتياطي (SQL) محفوظ في: {RESOLVED_SQL_PATH}", CYAN)
    log("="*50, BOLD + GREEN)
    
    # 3. تشغيل المزامنة لرفع التغييرات لـ Supabase تلقائياً
    if resolved_count > 0:
        log("\n🔄 جاري تشغيل المزامنة لنشر الصور الجديدة على المتجر الإلكتروني (Supabase)...", CYAN)
        sync_command = "php -r \"define('APP_ROOT', '/home/omar/Desktop/GHARIB/POS'); require_once '/home/omar/Desktop/GHARIB/POS/bootstrap.php'; print_r(\\App\\Services\\SupabaseSyncService::fullSync());\""
        try:
            import subprocess
            res = subprocess.run(sync_command, shell=True, capture_output=True, text=True)
            if res.returncode == 0:
                log("✅ تمت مزامنة المنتجات والـ Categories المحدثة مع Supabase بنجاح!", GREEN)
                log(res.stdout, RESET)
            else:
                log(f"❌ فشل تشغيل المزامنة تلقائياً. كود الخطأ: {res.returncode}", RED)
                log(res.stderr, RED)
        except Exception as sync_err:
            log(f"❌ حدث خطأ أثناء تشغيل أمر المزامنة: {str(sync_err)}", RED)

if __name__ == '__main__':
    main()
