#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
=============================================================
  سكريبت جلب صور المنتجات v2 - النسخة المحسنة للسوق المصري
  Product Image Fetcher v2 - Egypt-Optimized
=============================================================
يبحث عن الصور عبر:
  1. قواعد بيانات الباركود الدولية (OpenFoodFacts, UPCitemdb)
  2. البحث بالاسم عبر OpenFoodFacts
  3. Jumia Egypt
  4. Noon Egypt
  5. Carrefour Egypt
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
from urllib.parse import quote, urlencode

# ─── إعدادات ─────────────────────────────────────────────────
DB_PATH      = os.path.join(os.path.dirname(__file__), '..', 'database', 'posg.sqlite')
REPORT_PATH  = os.path.join(os.path.dirname(__file__), '..', 'storage', 'image_fetch_report_v2.csv')
IMAGES_DIR   = os.path.join(os.path.dirname(__file__), '..', 'database', 'uploads', 'products')
LIMIT        = 100   # عينة للمراجعة (غيّرها لـ 0 = كل المنتجات)
MAX_WORKERS  = 6
TIMEOUT      = 12

# هل نحمّل الصور فعلياً؟ (True = تحميل + حفظ، False = روابط فقط)
DOWNLOAD_IMAGES = False

# ─── ألوان ───────────────────────────────────────────────────
GREEN  = '\033[92m'; RED = '\033[91m'; YELLOW = '\033[93m'
BLUE   = '\033[94m'; CYAN = '\033[96m'; BOLD = '\033[1m'; RESET = '\033[0m'
print_lock = threading.Lock()

def log(msg, color=RESET):
    with print_lock:
        print(f"{color}{msg}{RESET}", flush=True)

HEADERS = {
    'User-Agent': (
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 '
        '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ),
    'Accept-Language': 'ar-EG,ar;q=0.9,en;q=0.8',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
}

# كلمات تدل على صورة مش منتج (بانرات، placeholders...)
BAD_URL_PATTERNS = [
    'stickybanner', 'banner', 'placeholder', 'logo', 'icon',
    'default', 'no-image', 'noimage', 'blank', 'spinner',
    '76f12a04', 'nooncdn.com/mpcms',  # Noon placeholder
    '1170D60',                         # Jumia banner
    'cms/Home',                        # Jumia CMS banners
]

def is_valid_image_url(url):
    """يتحقق أن الرابط صورة منتج حقيقية"""
    if not url or len(url) < 20:
        return False
    url_lower = url.lower()
    for bad in BAD_URL_PATTERNS:
        if bad.lower() in url_lower:
            return False
    return url.startswith('http')

# ─── جلب المنتجات من DB ──────────────────────────────────────
def get_products(limit=500):
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
    log(f"✅ تم جلب {len(products)} منتج", GREEN)
    return products

# ─── تنظيف اسم المنتج للبحث ──────────────────────────────────
def clean_name(name):
    """يزيل الأحجام والأرقام ويبقى الكلمات الجوهرية"""
    name = re.sub(r'\d+[\s]*(g|ml|kg|لتر|ق|مل|جم|كجم|لتر)\b', '', name, flags=re.IGNORECASE)
    name = re.sub(r'\s+', ' ', name).strip()
    # خذ أول 3-4 كلمات فقط
    words = name.split()
    return ' '.join(words[:4])

# ═══════════════════════════════════════════════════════════════
#  مصدر 1: Open Food Facts بالباركود
# ═══════════════════════════════════════════════════════════════
def fetch_openfoodfacts_barcode(barcode):
    try:
        url = f"https://world.openfoodfacts.org/api/v0/product/{barcode}.json"
        r = requests.get(url, timeout=TIMEOUT, headers={'User-Agent': 'POS-ImageFetcher/2.0'})
        if r.status_code == 200:
            data = r.json()
            if data.get('status') == 1:
                p = data.get('product', {})
                img = p.get('image_front_url') or p.get('image_url') or p.get('image_front_small_url')
                if img:
                    return {'image_url': img, 'source': 'OpenFoodFacts-BC', 'confidence': 97}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 2: UPCitemdb
# ═══════════════════════════════════════════════════════════════
def fetch_upcitemdb(barcode):
    try:
        url = f"https://api.upcitemdb.com/prod/trial/lookup?upc={barcode}"
        r = requests.get(url, timeout=TIMEOUT, headers={'User-Agent': 'POS-ImageFetcher/2.0', 'Accept': 'application/json'})
        if r.status_code == 200:
            data = r.json()
            items = data.get('items', [])
            if items and items[0].get('images'):
                return {'image_url': items[0]['images'][0], 'source': 'UPCitemdb', 'confidence': 92}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 3: Open Beauty Facts بالباركود
# ═══════════════════════════════════════════════════════════════
def fetch_openbeautyfacts(barcode):
    try:
        url = f"https://world.openbea# ═══════════════════════════════════════════════════════════════
#  مصدر 5: Jumia Egypt - بحث صحيح بصور المنتجات
# ═══════════════════════════════════════════════════════════════
def fetch_jumia_egypt(name):
    try:
        query = clean_name(name)
        url = f"https://www.jumia.com.eg/catalog/?q={quote(query)}"
        r = requests.get(url, timeout=TIMEOUT, headers=HEADERS)
        if r.status_code == 200:
            # نبحث عن صور المنتجات الفعلية (مش بانرات)
            # صور Jumia للمنتجات تكون في نمط: /unsafe/fit-in/NxN/filters:fill(white)/product/
            matches = re.findall(
                r'"([^"]*jumia\.is/unsafe/fit-in/[^"]+/product/[^"]+\.(?:jpg|jpeg|png|webp)[^"]*)"',
                r.text, re.IGNORECASE
            )
            if not matches:
                # نمط بديل: data-src لصور المنتجات
                matches = re.findall(
                    r'data-src="(https://[^"]*jumia\.is/unsafe[^"]+/product/[^"]+)"',
                    r.text
                )
            for img in matches:
                img = img.replace('\\/', '/').replace('\\u002F', '/')
                if img.startswith('//'):
                    img = 'https:' + img
                if is_valid_image_url(img):
                    return {'image_url': img, 'source': 'Jumia-EG', 'confidence': 80}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 6: Noon Egypt - بحث صحيح بـ API
# ═══════════════════════════════════════════════════════════════
def fetch_noon_egypt(name):
    try:
        query = clean_name(name)
        # استخدام API Noon المباشر
        url = f"https://www.noon.com/egypt-en/search/?q={quote(query)}&limit=5"
        r = requests.get(url, timeout=TIMEOUT, headers=HEADERS)
        if r.status_code == 200:
            # صور Noon الحقيقية تكون في: f.nooncdn.com/p/ أو /products/
            matches = re.findall(
                r'"(https://f\.nooncdn\.com/p/[^"]+\.(?:jpg|jpeg|png|webp))"',
                r.text
            )
            if not matches:
                matches = re.findall(
                    r'"(https://f\.nooncdn\.com/products/[^"]+\.(?:jpg|jpeg|png|webp))"',
                    r.text
                )
            for img in matches:
                if is_valid_image_url(img):
                    return {'image_url': img, 'source': 'Noon-EG', 'confidence': 82}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 7: Carrefour Egypt API
# ═══════════════════════════════════════════════════════════════
def fetch_carrefour_egypt(name):
    try:
        query = clean_name(name)
        url = f"https://www.carrefouregypt.com/mafegy/en/v4/search?keyword={quote(query)}&pageSize=5"
        r = requests.get(url, timeout=TIMEOUT, headers={**HEADERS, 'Accept': 'application/json'})
        if r.status_code == 200:
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
            # Regex fallback - نبحث عن روابط الصور الحقيقية
            matches = re.findall(
                r'"(https://[^"]*carrefouregypt[^"]*\.(?:jpg|jpeg|png|webp)[^"]*)"',
                r.text
            )
            if not matches:
                matches = re.findall(
                    r'"(https://[^"]*cloudinary[^"]*\.(?:jpg|jpeg|png|webp)[^"]*)"',
                    r.text
                )
            for img in matches:
                img = img.replace('\\/', '/').replace('\\u002F', '/')
                if is_valid_image_url(img):
                    return {'image_url': img, 'source': 'Carrefour-EG', 'confidence': 83}
    except Exception:
        pass
    return None═════════════════════════
def fetch_noon_egypt(name):
    try:
        query = clean_name(name)
        url = f"https://www.noon.com/egypt-en/search/?q={quote(query)}"
        r = requests.get(url, timeout=TIMEOUT, headers={**HEADERS, 'Accept': 'application/json'})
        if r.status_code == 200:
            # ابحث عن صور المنتجات
            matches = re.findall(r'"imageUrl"\s*:\s*"([^"]+\.(?:jpg|jpeg|png|webp)[^"]*)"', r.text)
            if not matches:
                matches = re.findall(r'src="(https://f\.nooncdn\.com[^"]+\.(?:jpg|jpeg|png|webp))"', r.text)
            if matches:
                img = matches[0]
                return {'image_url': img, 'source': 'Noon-EG', 'confidence': 78}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 7: Carrefour Egypt
# ═══════════════════════════════════════════════════════════════
def fetch_carrefour_egypt(name):
    try:
        query = clean_name(name)
        url = f"https://www.carrefouregypt.com/mafegy/en/v4/search?keyword={quote(query)}&pageSize=5"
        r = requests.get(url, timeout=TIMEOUT, headers={**HEADERS, 'Accept': 'application/json'})
        if r.status_code == 200:
            try:
                data = r.json()
                products = data.get('data', {}).get('products', {}).get('results', [])
                for p in products:
                    img = p.get('thumbnail') or p.get('image')
                    if img:
                        if img.startswith('//'):
                            img = 'https:' + img
                        return {'image_url': img, 'source': 'Carrefour-EG', 'confidence': 80}
            except Exception:
                pass
            # fallback: regex
            matches = re.findall(r'"thumbnail"\s*:\s*"([^"]+\.(?:jpg|jpeg|png|webp|jpg\?[^"]*)[^"]*)"', r.text)
            if matches:
                img = matches[0].replace('\\u002F', '/').replace('\\/', '/')
                if img.startswith('//'):
                    img = 'https:' + img
                return {'image_url': img, 'source': 'Carrefour-EG', 'confidence': 78}
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  مصدر 8: Barcode Monster (قاعدة مصرية/عربية)
# ═══════════════════════════════════════════════════════════════
def fetch_barcodemonster(barcode):
    try:
        url = f"https://www.barcodelookup.com/{barcode}"
        r = requests.get(url, timeout=TIMEOUT, headers=HEADERS)
        if r.status_code == 200:
            matches = re.findall(r'<img[^>]+class="[^"]*product-image[^"]*"[^>]+src="([^"]+)"', r.text)
            if not matches:
                matches = re.findall(r'"image"\s*:\s*"([^"]+\.(?:jpg|jpeg|png))"', r.text)
            if matches:
                img = matches[0]
                if img.startswith('http') and 'placeholder' not in img.lower():
                    return {'image_url': img, 'source': 'BarcodeLookup', 'confidence': 88}
    except Exception:
        pass
    return None

# ─── البحث الشامل لمنتج واحد ─────────────────────────────────
def fetch_image_for_product(product):
    barcode  = (product.get('barcode') or '').strip()
    prod_id  = product['id']
    name     = product['name']

    result_base = {
        'product_id': prod_id,
        'product_name': name,
        'barcode': barcode,
        'image_url': '',
        'source': '',
        'confidence': 0,
        'status': 'not_found'
    }

    def try_source(fn, *args):
        try:
            res = fn(*args)
            if res and res.get('image_url'):
                return res
        except Exception:
            pass
        return None

    # ── مرحلة 1: بالباركود (دولي) ──────────────────────────
    if barcode:
        for fn in [fetch_openfoodfacts_barcode, fetch_upcitemdb, fetch_openbeautyfacts, fetch_barcodemonster]:
            res = try_source(fn, barcode)
            if res:
                log(f"  ✅ [{prod_id:4}] {name[:28]:<28} | {res['source']:<22} | {res['confidence']}% | BC", GREEN)
                return {**result_base, **res, 'status': 'found'}
            time.sleep(0.05)

    # ── مرحلة 2: بالاسم (مصري + دولي) ──────────────────────
    for fn in [fetch_carrefour_egypt, fetch_jumia_egypt, fetch_noon_egypt, fetch_openfoodfacts_name]:
        res = try_source(fn, name)
        if res:
            log(f"  🔎 [{prod_id:4}] {name[:28]:<28} | {res['source']:<22} | {res['confidence']}% | Name", CYAN)
            return {**result_base, **res, 'status': 'found_by_name'}
        time.sleep(0.08)

    log(f"  ❌ [{prod_id:4}] {name[:28]:<28} | لم يوجد", YELLOW)
    return result_base

# ─── تحميل الصورة وحفظها ─────────────────────────────────────
def download_image(result):
    if not DOWNLOAD_IMAGES or not result.get('image_url'):
        return result
    try:
        os.makedirs(IMAGES_DIR, exist_ok=True)
        ext = 'jpg'
        url = result['image_url']
        m = re.search(r'\.(jpg|jpeg|png|webp)(\?|$)', url, re.IGNORECASE)
        if m:
            ext = m.group(1).lower()
        filename = f"product_{result['product_id']}.{ext}"
        filepath = os.path.join(IMAGES_DIR, filename)
        r = requests.get(url, timeout=15, headers=HEADERS)
        if r.status_code == 200 and len(r.content) > 1000:
            with open(filepath, 'wb') as f:
                f.write(r.content)
            result['local_path'] = f"products/{filename}"
            log(f"  💾 حُفظت: {filename}", BLUE)
    except Exception as e:
        pass
    return result

# ─── حفظ تدريجي ──────────────────────────────────────────────
_csv_initialized = False
_csv_lock = threading.Lock()

def save_result_incremental(result):
    """يحفظ كل نتيجة فوراً بدل الانتظار للنهاية"""
    global _csv_initialized
    fields = ['product_id', 'product_name', 'barcode', 'image_url', 'source', 'confidence', 'status']
    os.makedirs(os.path.dirname(REPORT_PATH), exist_ok=True)
    with _csv_lock:
        mode = 'a' if _csv_initialized else 'w'
        with open(REPORT_PATH, mode, newline='', encoding='utf-8-sig') as f:
            writer = csv.DictWriter(f, fieldnames=fields, extrasaction='ignore')
            if not _csv_initialized:
                writer.writeheader()
                _csv_initialized = True
            writer.writerow(result)

# ─── حفظ التقرير الكامل ────────────────────────────────────────
def save_report(results):
    # التقرير الكامل يكون محفوظاً بالفعل تدريجياً
    log(f"\n📄 تم حفظ التقرير: {REPORT_PATH}", CYAN)

# ─── إحصائيات ────────────────────────────────────────────────
def print_stats(results, elapsed):
    found_bc   = [r for r in results if r['status'] == 'found']
    found_name = [r for r in results if r['status'] == 'found_by_name']
    not_found  = [r for r in results if r['status'] == 'not_found']
    total_found = len(found_bc) + len(found_name)

    sources = {}
    for r in results:
        if r['status'] != 'not_found':
            s = r['source']
            sources[s] = sources.get(s, 0) + 1

    print(f"\n{BOLD}{CYAN}{'='*62}")
    print(f"  📊 نتائج جلب الصور - النسخة المحسنة")
    print(f"{'='*62}{RESET}")
    print(f"  ⏱  الوقت المستغرق   : {elapsed:.1f} ثانية")
    print(f"  📦  إجمالي المنتجات  : {len(results)}")
    print(f"  {GREEN}✅  وجد بالباركود    : {len(found_bc)} ({len(found_bc)/len(results)*100:.1f}%){RESET}")
    print(f"  {CYAN}🔎  وجد بالاسم       : {len(found_name)} ({len(found_name)/len(results)*100:.1f}%){RESET}")
    print(f"  {GREEN}🏆  إجمالي الصور     : {total_found} ({total_found/len(results)*100:.1f}%){RESET}")
    print(f"  {YELLOW}❌  لم يوجد          : {len(not_found)} ({len(not_found)/len(results)*100:.1f}%){RESET}")
    print(f"\n  {BOLD}📡 توزيع المصادر:{RESET}")
    for src, count in sorted(sources.items(), key=lambda x: -x[1]):
        bar = '█' * (count // 2)
        print(f"     • {src:<25}: {count:3}  {bar}")
    print(f"{CYAN}{'='*62}{RESET}\n")

# ─── Main ─────────────────────────────────────────────────────
def main():
    print(f"\n{BOLD}{BLUE}{'='*62}")
    print(f"  🔍 سكريبت جلب صور المنتجات v2 - مُحسَّن للسوق المصري")
    print(f"{'='*62}{RESET}")
    print(f"  📅 {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"  📦 منتجات: {LIMIT} | خيوط: {MAX_WORKERS} | تحميل: {'نعم' if DOWNLOAD_IMAGES else 'لا'}")
    print(f"  📡 المصادر: Barcode DBs → Carrefour EG → Jumia EG → Noon EG → OFF Search")
    print(f"{BLUE}{'='*62}{RESET}\n")

    actual_limit = LIMIT if LIMIT > 0 else 9999
    products = get_products(actual_limit)
    if not products:
        log("❌ لا توجد منتجات!", RED)
        sys.exit(1)

    results = []
    completed = 0
    start_time = time.time()

    print(f"{BOLD}🚀 بدء المعالجة ({MAX_WORKERS} خيوط متوازية)...{RESET}\n")

    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
        futures = {executor.submit(fetch_image_for_product, p): p for p in products}
        for future in as_completed(futures):
            result = future.result()
            if DOWNLOAD_IMAGES:
                result = download_image(result)
            results.append(result)
            save_result_incremental(result)  # حفظ فوري
            completed += 1
            if completed % 25 == 0:
                found_so_far = sum(1 for r in results if r['status'] != 'not_found')
                elapsed = time.time() - start_time
                rate = found_so_far / completed * 100
                log(
                    f"\n  📈 [{completed}/{len(products)}] ✅ {found_so_far} وجد ({rate:.0f}%) | ⏱ {elapsed:.0f}s",
                    BLUE
                )

    elapsed = time.time() - start_time
    save_report(results)
    print_stats(results, elapsed)

    # عرض أول 5 صور
    found = [r for r in results if r['status'] != 'not_found']
    if found:
        print(f"{BOLD}🏆 عينة الصور المُجمَّعة:{RESET}")
        for r in found[:5]:
            print(f"  [{r['product_id']:4}] {r['product_name'][:35]}")
            print(f"         📡 {r['source']} ({r['confidence']}%)")
            print(f"         🔗 {r['image_url'][:80]}")
            print()

    # حفظ قائمة الـ SQL لتحديث الصور
    sql_path = REPORT_PATH.replace('.csv', '_update.sql')
    found_with_url = [r for r in results if r.get('image_url')]
    if found_with_url:
        with open(sql_path, 'w', encoding='utf-8') as f:
            f.write("-- تحديث مسارات الصور في قاعدة البيانات\n")
            f.write("-- Image URL Update SQL\n\n")
            for r in found_with_url:
                img = r['image_url'].replace("'", "''")
                f.write(f"-- [{r['product_id']}] {r['product_name']}\n")
                f.write(f"UPDATE products SET image_path = '{img}' WHERE id = {r['product_id']};\n\n")
        log(f"📝 ملف SQL للتحديث: {sql_path}", CYAN)

if __name__ == '__main__':
    main()
