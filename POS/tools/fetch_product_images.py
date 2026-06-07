#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
=============================================================
  سكريبت جلب صور المنتجات عبر الباركود
  Product Image Fetcher via Barcode Lookup
=============================================================
يجلب صور المنتجات من قواعد البيانات العالمية والمصرية
باستخدام أرقام الباركود EAN/UPC

المصادر:
  1. Open Food Facts (أكبر قاعدة بيانات مفتوحة)
  2. UPCitemdb (قاعدة بيانات عالمية)
  3. Go-UPC (تجميعي عالمي)
  4. Barcode Lookup (fallback)
"""

import sqlite3
import requests
import json
import time
import csv
import os
import sys
from datetime import datetime
from concurrent.futures import ThreadPoolExecutor, as_completed
import threading

# ─── إعدادات ─────────────────────────────────────────────────
DB_PATH     = os.path.join(os.path.dirname(__file__), '..', 'database', 'posg.sqlite')
OUTPUT_DIR  = os.path.join(os.path.dirname(__file__), '..', 'storage', 'barcode_images')
REPORT_PATH = os.path.join(os.path.dirname(__file__), '..', 'storage', 'image_fetch_report.csv')
LIMIT       = 500      # عدد المنتجات في هذا الاختبار
MAX_WORKERS = 8        # عدد الخيوط المتوازية
TIMEOUT     = 10       # ثوان انتظار لكل طلب

# ─── ألوان الطرفية ─────────────────────────────────────────
GREEN  = '\033[92m'
RED    = '\033[91m'
YELLOW = '\033[93m'
BLUE   = '\033[94m'
CYAN   = '\033[96m'
BOLD   = '\033[1m'
RESET  = '\033[0m'

# ─── قفل للطباعة الآمنة في الخيوط ─────────────────────────
print_lock = threading.Lock()

def log(msg, color=RESET):
    with print_lock:
        print(f"{color}{msg}{RESET}")

# ─── جلب المنتجات من قاعدة البيانات ────────────────────────
def get_products(limit=500):
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()
    cursor.execute("""
        SELECT id, name, barcode
        FROM products
        WHERE deleted_at IS NULL
          AND is_active = 1
          AND barcode IS NOT NULL
          AND barcode != ''
        ORDER BY id
        LIMIT ?
    """, (limit,))
    products = [dict(row) for row in cursor.fetchall()]
    conn.close()
    log(f"✅ تم جلب {len(products)} منتج من قاعدة البيانات", GREEN)
    return products

# ─── مصدر 1: Open Food Facts ────────────────────────────────
def fetch_from_openfoodfacts(barcode):
    try:
        url = f"https://world.openfoodfacts.org/api/v0/product/{barcode}.json"
        r = requests.get(url, timeout=TIMEOUT, headers={'User-Agent': 'POS-ImageFetcher/1.0'})
        if r.status_code == 200:
            data = r.json()
            if data.get('status') == 1:
                product = data.get('product', {})
                image = (
                    product.get('image_front_url') or
                    product.get('image_url') or
                    product.get('image_front_small_url')
                )
                if image:
                    name = product.get('product_name', '') or product.get('product_name_ar', '')
                    return {
                        'image_url': image,
                        'source': 'OpenFoodFacts',
                        'confidence': 95,
                        'remote_name': name
                    }
    except Exception:
        pass
    return None

# ─── مصدر 2: UPCitemdb ──────────────────────────────────────
def fetch_from_upcitemdb(barcode):
    try:
        url = f"https://api.upcitemdb.com/prod/trial/lookup?upc={barcode}"
        r = requests.get(url, timeout=TIMEOUT, headers={
            'User-Agent': 'POS-ImageFetcher/1.0',
            'Accept': 'application/json'
        })
        if r.status_code == 200:
            data = r.json()
            items = data.get('items', [])
            if items:
                item = items[0]
                images = item.get('images', [])
                if images:
                    return {
                        'image_url': images[0],
                        'source': 'UPCitemdb',
                        'confidence': 90,
                        'remote_name': item.get('title', '')
                    }
    except Exception:
        pass
    return None

# ─── مصدر 3: Open Beauty Facts (للمنتجات التجميلية) ────────
def fetch_from_openbeautyfacts(barcode):
    try:
        url = f"https://world.openbeautyfacts.org/api/v0/product/{barcode}.json"
        r = requests.get(url, timeout=TIMEOUT, headers={'User-Agent': 'POS-ImageFetcher/1.0'})
        if r.status_code == 200:
            data = r.json()
            if data.get('status') == 1:
                product = data.get('product', {})
                image = product.get('image_front_url') or product.get('image_url')
                if image:
                    return {
                        'image_url': image,
                        'source': 'OpenBeautyFacts',
                        'confidence': 85,
                        'remote_name': product.get('product_name', '')
                    }
    except Exception:
        pass
    return None

# ─── مصدر 4: Open Products World ────────────────────────────
def fetch_from_opengtindb(barcode):
    try:
        url = f"https://www.digit-eyes.com/gtin/aHR0cHM6Ly93d3cuZGlnaXQtZXllcy5jb20vY2dpLWJpbi9zZWFyY2gudXBjP3NrZXk9JnVwY0NvZGU={barcode}&language=en"
        # هذا مجاني محدود - نتجاهله ونستخدم بديل
        pass
    except Exception:
        pass
    return None

# ─── مصدر 5: Barcodelookup scraping (fallback) ──────────────
def fetch_from_go_upc(barcode):
    """محاولة من go-upc.com"""
    try:
        url = f"https://go-upc.com/api/v1/code/{barcode}"
        r = requests.get(url, timeout=TIMEOUT, headers={
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36'
        })
        if r.status_code == 200:
            data = r.json()
            product = data.get('product', {})
            image = product.get('imageUrl', '')
            if image:
                return {
                    'image_url': image,
                    'source': 'GoUPC',
                    'confidence': 80,
                    'remote_name': product.get('name', '')
                }
    except Exception:
        pass
    return None

# ─── البحث عبر كل المصادر ───────────────────────────────────
def fetch_image_for_product(product):
    barcode = product['barcode'].strip()
    prod_id = product['id']
    name    = product['name']

    # ترتيب المصادر من الأكثر موثوقية للأقل
    fetchers = [
        fetch_from_openfoodfacts,
        fetch_from_upcitemdb,
        fetch_from_openbeautyfacts,
        fetch_from_go_upc,
    ]

    for fetcher in fetchers:
        result = fetcher(barcode)
        if result and result.get('image_url'):
            log(
                f"  ✅ [{prod_id}] {name[:30]:<30} | {result['source']:<20} | {result['confidence']}% | {barcode}",
                GREEN
            )
            return {
                'product_id': prod_id,
                'product_name': name,
                'barcode': barcode,
                'image_url': result['image_url'],
                'source': result['source'],
                'confidence': result['confidence'],
                'remote_name': result.get('remote_name', ''),
                'status': 'found'
            }
        # تأخير صغير بين المحاولات
        time.sleep(0.1)

    log(
        f"  ❌ [{prod_id}] {name[:30]:<30} | لم يوجد | {barcode}",
        YELLOW
    )
    return {
        'product_id': prod_id,
        'product_name': name,
        'barcode': barcode,
        'image_url': '',
        'source': '',
        'confidence': 0,
        'remote_name': '',
        'status': 'not_found'
    }

# ─── حفظ النتائج ─────────────────────────────────────────────
def save_report(results):
    os.makedirs(os.path.dirname(REPORT_PATH), exist_ok=True)
    with open(REPORT_PATH, 'w', newline='', encoding='utf-8-sig') as f:
        writer = csv.DictWriter(f, fieldnames=[
            'product_id', 'product_name', 'barcode',
            'image_url', 'source', 'confidence',
            'remote_name', 'status'
        ])
        writer.writeheader()
        writer.writerows(results)
    log(f"\n📄 تم حفظ التقرير: {REPORT_PATH}", CYAN)

# ─── إحصائيات ────────────────────────────────────────────────
def print_stats(results, elapsed):
    found    = [r for r in results if r['status'] == 'found']
    not_found = [r for r in results if r['status'] == 'not_found']

    # توزيع المصادر
    sources = {}
    for r in found:
        s = r['source']
        sources[s] = sources.get(s, 0) + 1

    print(f"\n{BOLD}{CYAN}{'='*60}")
    print(f"  📊 نتائج جلب الصور")
    print(f"{'='*60}{RESET}")
    print(f"  ⏱  الوقت المستغرق   : {elapsed:.1f} ثانية")
    print(f"  📦  إجمالي المنتجات  : {len(results)}")
    print(f"  {GREEN}✅  وجدنا صورة       : {len(found)} ({len(found)/len(results)*100:.1f}%){RESET}")
    print(f"  {YELLOW}❌  لم يوجد صورة    : {len(not_found)} ({len(not_found)/len(results)*100:.1f}%){RESET}")
    print(f"\n  {BOLD}📡 توزيع المصادر:{RESET}")
    for src, count in sorted(sources.items(), key=lambda x: -x[1]):
        print(f"     • {src:<22}: {count}")
    print(f"{CYAN}{'='*60}{RESET}\n")

# ─── النقطة الرئيسية ─────────────────────────────────────────
def main():
    print(f"\n{BOLD}{BLUE}{'='*60}")
    print(f"  🔍 سكريبت جلب صور المنتجات عبر الباركود")
    print(f"  Product Image Fetcher via Global Barcode DBs")
    print(f"{'='*60}{RESET}")
    print(f"  📅 {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"  🗄  DB: {DB_PATH}")
    print(f"  📝 Report: {REPORT_PATH}")
    print(f"  🔢 المنتجات: {LIMIT} | الخيوط: {MAX_WORKERS}")
    print(f"{BLUE}{'='*60}{RESET}\n")

    # جلب المنتجات
    products = get_products(LIMIT)
    if not products:
        log("❌ لا توجد منتجات للمعالجة!", RED)
        sys.exit(1)

    results = []
    start_time = time.time()
    completed = 0

    print(f"{BOLD}🚀 بدء المعالجة المتوازية ({MAX_WORKERS} خيوط)...{RESET}\n")

    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
        futures = {executor.submit(fetch_image_for_product, p): p for p in products}
        for future in as_completed(futures):
            result = future.result()
            results.append(result)
            completed += 1
            # تقدم كل 50 منتج
            if completed % 50 == 0:
                found_so_far = sum(1 for r in results if r['status'] == 'found')
                elapsed = time.time() - start_time
                log(
                    f"\n  📈 تقدم: {completed}/{len(products)} | "
                    f"✅ {found_so_far} وجد | "
                    f"⏱ {elapsed:.0f}s مضت",
                    BLUE
                )

    elapsed = time.time() - start_time

    # حفظ التقرير
    save_report(results)

    # طباعة الإحصائيات
    print_stats(results, elapsed)

    # عرض أفضل 10 صور وجدت
    found = [r for r in results if r['status'] == 'found']
    if found:
        print(f"{BOLD}🏆 عينة من أول 5 صور:{RESET}")
        for r in found[:5]:
            print(f"  • [{r['product_id']}] {r['product_name'][:35]}")
            print(f"    🔗 {r['image_url'][:80]}")
            print()

if __name__ == '__main__':
    main()
