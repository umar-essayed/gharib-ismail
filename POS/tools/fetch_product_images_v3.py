#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
=============================================================
  سكريبت جلب صور المنتجات v3 - مع التحقق من صحة الروابط
  Product Image Fetcher v3 - Validated URL version
=============================================================
المصادر:
  1. OpenFoodFacts (بالباركود) - 97% موثوقية
  2. UPCitemdb (بالباركود) - 92%
  3. OpenBeautyFacts (بالباركود) - 90%
  4. Carrefour Egypt API - 85%
  5. Jumia Egypt (صور المنتجات فقط) - 80%
  6. Noon Egypt (صور المنتجات فقط) - 82%
  7. OpenFoodFacts بالاسم - 60%
"""

import sqlite3
import requests
import json
import time
import csv
import os
import sys
import re
from datetime import datetime
from concurrent.futures import ThreadPoolExecutor, as_completed
import threading
from urllib.parse import quote

# ─── إعدادات ──────────────────────────────────────────────────
DB_PATH     = os.path.join(os.path.dirname(__file__), '..', 'database', 'posg.sqlite')
REPORT_PATH = os.path.join(os.path.dirname(__file__), '..', 'storage', 'image_fetch_report_v3.csv')
LIMIT       = 100    # عينة للمراجعة (غيّر لـ 0 = كل المنتجات)
MAX_WORKERS = 6
TIMEOUT     = 12
DOWNLOAD_IMAGES = False

# ─── ألوان ────────────────────────────────────────────────────
GREEN  = '\033[92m'; RED = '\033[91m'; YELLOW = '\033[93m'
BLUE   = '\033[94m'; CYAN = '\033[96m'; BOLD = '\033[1m'; RESET = '\033[0m'
print_lock = threading.Lock()

def log(msg, color=RESET):
    with print_lock:
        print(f"{color}{msg}{RESET}", flush=True)

# ─── Headers ──────────────────────────────────────────────────
HEADERS = {
    'User-Agent': (
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 '
        '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ),
    'Accept-Language': 'ar-EG,ar;q=0.9,en;q=0.8',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
}

# ─── كلمات تدل على صورة وهمية / بانر ─────────────────────────
BAD_URL_PATTERNS = [
    'stickybanner', 'banner', 'placeholder', 'logo', 'icon',
    'default', 'no-image', 'noimage', 'blank', 'spinner',
    '76f12a04',           # Noon placeholder
    'nooncdn.com/mpcms',  # Noon CMS banners
    '1170D60',            # Jumia banner
    'cms/Home',           # Jumia CMS
    'cms/home',
]

def is_valid_image_url(url):
    """يتحقق أن الرابط صورة منتج حقيقية وليس placeholder/banner"""
    if not url or len(url) < 20:
        return False
    if not url.startswith('http'):
        return False
    url_lower = url.lower()
    for bad in BAD_URL_PATTERNS:
        if bad.lower() in url_lower:
            return False
    return True

# ─── تنظيف اسم المنتج للبحث ───────────────────────────────────
def clean_name(name):
    name = re.sub(r'\d+[\s]*(g|ml|kg|لتر|ق|مل|جم|كجم|لتر)\b', '', name, flags=re.IGNORECASE)
    name = re.sub(r'\s+', ' ', name).strip()
    words = name.split()
    return ' '.join(words[:4])

# ─── جلب المنتجات من DB ───────────────────────────────────────
def get_products(limit=100):
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()
    cur.execute("""
        SELECT id, name, barcode
        FROM products
        WHERE deleted_at IS NULL AND is_active = 1
          AND (barcode IS NOT NULL AND barcode != '' OR name IS NOT NULL)
        ORDER BY id
        LIMIT ?
    """, (limit,))
    products = [dict(r) for r in cur.fetchall()]
    conn.close()
    log(f"✅ تم جلب {len(products)} منتج من قاعدة البيانات", GREEN)
    return products

# ═══════════════════════════════════════════════════════════════
#  مصدر 1: OpenFoodFacts بالباركود (97%)
# ═══════════════════════════════════════════════════════════════
def fetch_openfoodfacts_barcode(barcode):
    try:
        url = f"https://world.openfoodfacts.org/api/v0/product/{barcode}.json"
        r = requests.get(url, timeout=TIMEOUT, headers={'User-Agent': 'POS-ImageFetcher/3.0'})
        if r.status_code == 200:
            data = r.json()
            if data.get('status') == 1:
                p = data.get('product', {})
                img = p.get('image_front_url') or p.get('image_url') or p.get('image_front_small_url')
                if img and is_valid_image_url(img):
                    return {'image_url': img, 'source': 'OpenFoodFacts-BC', 'confidence': 97}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 2: UPCitemdb بالباركود (92%)
# ═══════════════════════════════════════════════════════════════
def fetch_upcitemdb(barcode):
    try:
        url = f"https://api.upcitemdb.com/prod/trial/lookup?upc={barcode}"
        r = requests.get(url, timeout=TIMEOUT, headers={
            'User-Agent': 'POS-ImageFetcher/3.0', 'Accept': 'application/json'
        })
        if r.status_code == 200:
            data = r.json()
            items = data.get('items', [])
            if items:
                for img in items[0].get('images', []):
                    if is_valid_image_url(img):
                        return {'image_url': img, 'source': 'UPCitemdb', 'confidence': 92}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 3: OpenBeautyFacts بالباركود (90%)
# ═══════════════════════════════════════════════════════════════
def fetch_openbeautyfacts(barcode):
    try:
        url = f"https://world.openbeautyfacts.org/api/v0/product/{barcode}.json"
        r = requests.get(url, timeout=TIMEOUT, headers={'User-Agent': 'POS-ImageFetcher/3.0'})
        if r.status_code == 200:
            data = r.json()
            if data.get('status') == 1:
                p = data.get('product', {})
                img = p.get('image_front_url') or p.get('image_url')
                if img and is_valid_image_url(img):
                    return {'image_url': img, 'source': 'OpenBeautyFacts', 'confidence': 90}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 4: Carrefour Egypt API (85%)
# ═══════════════════════════════════════════════════════════════
def fetch_carrefour_egypt(name):
    try:
        query = clean_name(name)
        url = f"https://www.carrefouregypt.com/mafegy/en/v4/search?keyword={quote(query)}&pageSize=5"
        r = requests.get(url, timeout=TIMEOUT, headers={**HEADERS, 'Accept': 'application/json'})
        if r.status_code == 200:
            # محاولة JSON أولاً
            try:
                data = r.json()
                products = data.get('data', {}).get('products', {}).get('results', [])
                for p in products:
                    img = p.get('thumbnail') or p.get('image')
                    if img:
                        if img.startswith('//'):
                            img = 'https:' + img
                        if is_valid_image_url(img):
                            return {'image_url': img, 'source': 'Carrefour-EG', 'confidence': 85}
            except Exception:
                pass
            # Regex fallback
            for pattern in [
                r'"(https://[^"]*carrefouregypt[^"]*\.(?:jpg|jpeg|png|webp)[^"]*)"',
                r'"(https://[^"]*cloudinary[^"]*\.(?:jpg|jpeg|png|webp)[^"]*)"',
            ]:
                for img in re.findall(pattern, r.text):
                    img = img.replace('\\/', '/').replace('\\u002F', '/')
                    if is_valid_image_url(img):
                        return {'image_url': img, 'source': 'Carrefour-EG', 'confidence': 83}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 5: Jumia Egypt - صور المنتجات الفعلية فقط (80%)
# ═══════════════════════════════════════════════════════════════
def fetch_jumia_egypt(name):
    try:
        query = clean_name(name)
        url = f"https://www.jumia.com.eg/catalog/?q={quote(query)}"
        r = requests.get(url, timeout=TIMEOUT, headers=HEADERS)
        if r.status_code == 200:
            # صور منتجات Jumia الحقيقية تكون في نمط /product/ وليس /cms/
            for pattern in [
                r'"(https://[^"]*jumia\.is/unsafe/fit-in/[^"]+/product/[^"]+\.(?:jpg|jpeg|png|webp))"',
                r'data-src="(https://[^"]*jumia\.is/unsafe[^"]+/product/[^"]+\.(?:jpg|jpeg|png|webp))"',
            ]:
                for img in re.findall(pattern, r.text, re.IGNORECASE):
                    img = img.replace('\\/', '/').replace('\\u002F', '/')
                    if is_valid_image_url(img):
                        return {'image_url': img, 'source': 'Jumia-EG', 'confidence': 80}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 6: Noon Egypt - صور المنتجات من /p/ أو /products/ (82%)
# ═══════════════════════════════════════════════════════════════
def fetch_noon_egypt(name):
    try:
        query = clean_name(name)
        url = f"https://www.noon.com/egypt-en/search/?q={quote(query)}&limit=5"
        r = requests.get(url, timeout=TIMEOUT, headers=HEADERS)
        if r.status_code == 200:
            # صور Noon الحقيقية في /p/ أو /products/ وليس /mpcms/
            for pattern in [
                r'"(https://f\.nooncdn\.com/p/[^"]+\.(?:jpg|jpeg|png|webp))"',
                r'"(https://f\.nooncdn\.com/products/[^"]+\.(?:jpg|jpeg|png|webp))"',
            ]:
                for img in re.findall(pattern, r.text):
                    if is_valid_image_url(img):
                        return {'image_url': img, 'source': 'Noon-EG', 'confidence': 82}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 7: OpenFoodFacts بالاسم (60%)
# ═══════════════════════════════════════════════════════════════
def fetch_openfoodfacts_name(name):
    try:
        query = clean_name(name)
        if len(query) < 3:
            return None
        url = (f"https://world.openfoodfacts.org/cgi/search.pl?"
               f"search_terms={quote(query)}&search_simple=1&action=process&json=1&page_size=5")
        r = requests.get(url, timeout=TIMEOUT, headers={'User-Agent': 'POS-ImageFetcher/3.0'})
        if r.status_code == 200:
            data = r.json()
            for p in data.get('products', []):
                img = p.get('image_front_url') or p.get('image_url')
                if img and is_valid_image_url(img):
                    return {'image_url': img, 'source': 'OpenFoodFacts-Name', 'confidence': 60}
    except Exception:
        pass
    return None

# ─── البحث الشامل لمنتج واحد ──────────────────────────────────
def fetch_image_for_product(product):
    barcode = (product.get('barcode') or '').strip()
    prod_id = product['id']
    name    = product['name']

    base = {
        'product_id': prod_id, 'product_name': name, 'barcode': barcode,
        'image_url': '', 'source': '', 'confidence': 0, 'status': 'not_found'
    }

    def try_fn(fn, *args):
        try:
            res = fn(*args)
            if res and res.get('image_url'):
                return res
        except Exception:
            pass
        return None

    # مرحلة 1: بالباركود (دولي، موثوق)
    if barcode:
        for fn in [fetch_openfoodfacts_barcode, fetch_upcitemdb, fetch_openbeautyfacts]:
            res = try_fn(fn, barcode)
            if res:
                log(f"  ✅ [{prod_id:4}] {name[:28]:<28} | {res['source']:<22} | {res['confidence']}%", GREEN)
                return {**base, **res, 'status': 'found'}
            time.sleep(0.05)

    # مرحلة 2: بالاسم في المواقع المصرية
    for fn in [fetch_carrefour_egypt, fetch_jumia_egypt, fetch_noon_egypt, fetch_openfoodfacts_name]:
        res = try_fn(fn, name)
        if res:
            log(f"  🔎 [{prod_id:4}] {name[:28]:<28} | {res['source']:<22} | {res['confidence']}%", CYAN)
            return {**base, **res, 'status': 'found_by_name'}
        time.sleep(0.08)

    log(f"  ❌ [{prod_id:4}] {name[:28]:<28} | لم يوجد", YELLOW)
    return base

# ─── حفظ تدريجي ───────────────────────────────────────────────
_csv_init = False
_csv_lock = threading.Lock()

def save_incremental(result):
    global _csv_init
    fields = ['product_id', 'product_name', 'barcode', 'image_url', 'source', 'confidence', 'status']
    os.makedirs(os.path.dirname(REPORT_PATH), exist_ok=True)
    with _csv_lock:
        mode = 'a' if _csv_init else 'w'
        with open(REPORT_PATH, mode, newline='', encoding='utf-8-sig') as f:
            writer = csv.DictWriter(f, fieldnames=fields, extrasaction='ignore')
            if not _csv_init:
                writer.writeheader()
                _csv_init = True
            writer.writerow(result)

# ─── إحصائيات ─────────────────────────────────────────────────
def print_stats(results, elapsed):
    found_bc   = [r for r in results if r['status'] == 'found']
    found_name = [r for r in results if r['status'] == 'found_by_name']
    not_found  = [r for r in results if r['status'] == 'not_found']
    total      = len(found_bc) + len(found_name)
    sources    = {}
    for r in results:
        if r['status'] != 'not_found':
            s = r['source']
            sources[s] = sources.get(s, 0) + 1

    print(f"\n{BOLD}{CYAN}{'='*64}")
    print(f"  📊 نتائج جلب الصور v3")
    print(f"{'='*64}{RESET}")
    print(f"  ⏱  الوقت    : {elapsed:.1f} ثانية")
    print(f"  📦  الإجمالي : {len(results)}")
    print(f"  {GREEN}✅  بالباركود : {len(found_bc)} ({len(found_bc)/len(results)*100:.1f}%){RESET}")
    print(f"  {CYAN}🔎  بالاسم    : {len(found_name)} ({len(found_name)/len(results)*100:.1f}%){RESET}")
    print(f"  {GREEN}🏆  وجد صورة  : {total} ({total/len(results)*100:.1f}%){RESET}")
    print(f"  {YELLOW}❌  لم يوجد   : {len(not_found)} ({len(not_found)/len(results)*100:.1f}%){RESET}")
    print(f"\n  {BOLD}📡 المصادر:{RESET}")
    for src, count in sorted(sources.items(), key=lambda x: -x[1]):
        bar = '█' * max(1, count // 3)
        print(f"     • {src:<25}: {count:3}  {bar}")
    print(f"{CYAN}{'='*64}{RESET}\n")

# ─── Main ──────────────────────────────────────────────────────
def main():
    print(f"\n{BOLD}{BLUE}{'='*64}")
    print(f"  🔍 سكريبت جلب صور المنتجات v3 - مع التحقق من الروابط")
    print(f"{'='*64}{RESET}")
    print(f"  📅 {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    actual_limit = LIMIT if LIMIT > 0 else 9999
    print(f"  📦 منتجات: {'كل المنتجات' if LIMIT == 0 else LIMIT} | خيوط: {MAX_WORKERS}")
    print(f"  ✅ التحقق من الروابط: مفعّل (يرفض البانرات والـ placeholders)")
    print(f"{BLUE}{'='*64}{RESET}\n")

    products = get_products(actual_limit)
    if not products:
        log("❌ لا توجد منتجات!", RED)
        sys.exit(1)

    results  = []
    completed = 0
    start_time = time.time()
    print(f"{BOLD}🚀 بدء المعالجة...{RESET}\n")

    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
        futures = {executor.submit(fetch_image_for_product, p): p for p in products}
        for future in as_completed(futures):
            result = future.result()
            results.append(result)
            save_incremental(result)
            completed += 1
            if completed % 25 == 0:
                found_so_far = sum(1 for r in results if r['status'] != 'not_found')
                elapsed = time.time() - start_time
                log(
                    f"\n  📈 [{completed}/{len(products)}] ✅ {found_so_far} وجد "
                    f"({found_so_far/completed*100:.0f}%) | ⏱ {elapsed:.0f}s",
                    BLUE
                )

    elapsed = time.time() - start_time
    log(f"\n📄 التقرير محفوظ: {REPORT_PATH}", CYAN)
    print_stats(results, elapsed)

    # عرض أول 5 صور صحيحة
    found = [r for r in results if r['status'] != 'not_found']
    if found:
        print(f"{BOLD}🏆 عينة الصور:{RESET}")
        for r in found[:5]:
            print(f"  [{r['product_id']:4}] {r['product_name'][:35]}")
            print(f"         📡 {r['source']} ({r['confidence']}%)")
            print(f"         🔗 {r['image_url'][:80]}")
            print()

    # توليد SQL للمراجعة (لا يُنفَّذ تلقائياً)
    sql_path = REPORT_PATH.replace('.csv', '_update.sql')
    found_with_url = [r for r in results if r.get('image_url')]
    if found_with_url:
        with open(sql_path, 'w', encoding='utf-8') as f:
            f.write("-- ⚠️ راجع الروابط قبل التنفيذ!\n")
            f.write("-- تحديث صور المنتجات - Image URL Update SQL\n\n")
            for r in found_with_url:
                img = r['image_url'].replace("'", "''")
                f.write(f"-- [{r['product_id']}] {r['product_name']} | {r['source']} {r['confidence']}%\n")
                f.write(f"UPDATE products SET image_path = '{img}' WHERE id = {r['product_id']};\n\n")
        log(f"📝 ملف SQL للمراجعة: {sql_path}", CYAN)
        log(f"   ⚠️  لن يُنفَّذ تلقائياً — راجعه أولاً ثم قرر!", YELLOW)

if __name__ == '__main__':
    main()
