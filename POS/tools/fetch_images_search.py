#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
=============================================================
  سكريبت جلب صور المنتجات - Image Search النسخة النهائية
  DuckDuckGo (ddgs) + OpenFoodFacts - فلتر صارم للجودة
=============================================================
"""

import sqlite3
import requests
import time
import csv
import os
import re
import sys
import random
from datetime import datetime
from concurrent.futures import ThreadPoolExecutor, as_completed
import threading
from urllib.parse import quote, urlparse

# ─── إعدادات ──────────────────────────────────────────────────
DB_PATH     = os.path.join(os.path.dirname(__file__), '..', 'database', 'posg.sqlite')
REPORT_PATH = os.path.join(os.path.dirname(__file__), '..', 'storage', 'image_search_final.csv')
LIMIT       = 50      # 0 = كل المنتجات
MAX_WORKERS = 8
TIMEOUT     = 15

GREEN  = '\033[92m'; RED = '\033[91m'; YELLOW = '\033[93m'
BLUE   = '\033[94m'; CYAN = '\033[96m'; BOLD = '\033[1m'; RESET = '\033[0m'
print_lock = threading.Lock()
def log(msg, color=RESET):
    with print_lock:
        print(f"{color}{msg}{RESET}", flush=True)

UAS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/119.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
]
def hdr():
    return {'User-Agent': random.choice(UAS), 'Accept-Language': 'ar,en-US;q=0.9,en;q=0.8'}

# ─── قواعد الفلتر ─────────────────────────────────────────────

# نطاقات ممنوعة
BAD_DOMAINS = {
    # تصميم وstock
    'pngtree.com','canva.com','freepik.com','shutterstock.com','gettyimages.com',
    'dreamstime.com','vectorstock.com','alamy.com','depositphotos.com','unsplash.com',
    'pexels.com','pixabay.com','vecteezy.com','flaticon.com','rawpixel.com',
    '123rf.com','pikwizard.com','stockvault.net','govisually.com',
    # بلوج وأعمال
    'hitpaw.com','netsolutions.com','medium.com','blogspot.com','wordpress.com',
    'wix.com','squarespace.com','hubspot.com','forbes.com','entrepreneur.com',
    'businessinsider.com','techcrunch.com','pragmaticinstitute.com',
    'corporatefinanceinstitute.com','productplan.com','aha.io',
    # سوشيال
    'youtube.com','facebook.com','instagram.com','twitter.com','pinterest.com','tiktok.com',
    # أخرى
    'wikipedia.org','wikimedia.org','docs.google.com','slideshare.net',
}

# كلمات في الرابط = صورة وهمية
BAD_URL_KWS = [
    'banner','placeholder','logo','icon','default','no-image','noimage',
    'blank','spinner','loading','cms/Home','1170D60','nooncdn.com/mpcms',
    '76f12a04','data:image','captcha','screenshot','substitute-product',
    'product-marketer','product-photography','midjourney',
]

# كلمات في مسار الرابط = مقال/بلوج
BAD_PATH_KWS = [
    'blog','article','news','how-to','tutorial','guide','tips',
    'review','marketing','branding','infographic','presentation',
    'product-development','product-marketer','product-photography',
]

def is_valid_url(url):
    if not url or len(url) < 15 or not url.startswith('http'):
        return False
    url_l = url.lower()
    for kw in BAD_URL_KWS:
        if kw.lower() in url_l:
            return False
    if not re.search(r'\.(jpg|jpeg|png|webp)(\?|$)', url, re.IGNORECASE):
        return False
    try:
        p = urlparse(url)
        domain = p.netloc.lower().replace('www.', '')
        for bd in BAD_DOMAINS:
            if bd in domain:
                return False
        path = p.path.lower()
        for bk in BAD_PATH_KWS:
            if bk in path:
                return False
    except Exception:
        pass
    return True

# ─── نطاقات موثوقة للباركود ───────────────────────────────────
TRUSTED_ECOM = [
    'amazon','jumia','noon','carrefour','mafrservices','yaoota',
    'spinneys','metro','riccostores','hyperone','barcodelookup',
    'openfoodfacts','openbeautyfacts','talabat','instashop',
]

def is_trusted_domain(url):
    try:
        domain = urlparse(url).netloc.lower()
        return any(t in domain for t in TRUSTED_ECOM)
    except Exception:
        return False

# ─── جلب المنتجات ─────────────────────────────────────────────
def get_products(limit=50):
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()
    lim = limit if limit > 0 else 99999
    cur.execute("""
        SELECT id, name, barcode FROM products
        WHERE deleted_at IS NULL AND is_active = 1
          AND (image_path IS NULL OR image_path = '')
        ORDER BY id LIMIT ?
    """, (lim,))
    rows = [dict(r) for r in cur.fetchall()]
    conn.close()
    log(f"✅ تم جلب {len(rows)} منتج بدون صورة", GREEN)
    return rows

def clean_query(name):
    c = re.sub(r'\d+[\s]*(g|ml|kg|لتر|ق|مل|جم|كجم)\b', '', name, flags=re.IGNORECASE)
    return re.sub(r'\s+', ' ', c).strip()

# ═══════════════════════════════════════════════════════════════
#  1: OpenFoodFacts API (97% - الأدق)
# ═══════════════════════════════════════════════════════════════
def src_openfoodfacts(barcode):
    if not barcode or len(barcode) < 6:
        return None
    try:
        url = f"https://world.openfoodfacts.org/api/v0/product/{barcode}.json"
        r = requests.get(url, timeout=TIMEOUT, headers={'User-Agent': 'POS/5.0'})
        if r.status_code == 200 and r.json().get('status') == 1:
            p = r.json()['product']
            img = p.get('image_front_url') or p.get('image_url') or p.get('image_front_small_url')
            if img and is_valid_url(img):
                return (img, 'OpenFoodFacts', 97)
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  2: DuckDuckGo بالاسم العربي (72%)
# ═══════════════════════════════════════════════════════════════
def src_ddg_arabic(name, barcode=''):
    try:
        from ddgs import DDGS
        q = f"{clean_query(name)} منتج مصري"
        words = q.split()
        q = ' '.join(words[:6])
        with DDGS(timeout=TIMEOUT) as d:
            results = list(d.images(
                query=q, region='eg-ar', safesearch='moderate',
                size='Medium', type_image='photo', max_results=8
            ))
        for r in results:
            url = r.get('image', '')
            w, h = r.get('width', 0) or 0, r.get('height', 0) or 0
            if url and is_valid_url(url) and w >= 100 and h >= 100:
                return (url, 'DuckDuckGo-AR', 72)
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  3: DuckDuckGo بالباركود EAN (85% - من مواقع موثوقة فقط)
# ═══════════════════════════════════════════════════════════════
def src_ddg_ean(barcode):
    if not barcode or len(barcode) < 6:
        return None
    try:
        from ddgs import DDGS
        for q in [f"EAN {barcode}", f"{barcode} buy online"]:
            with DDGS(timeout=TIMEOUT) as d:
                results = list(d.images(
                    query=q, region='wt-wt', safesearch='moderate',
                    size='Medium', type_image='photo', max_results=6
                ))
            for r in results:
                url = r.get('image', '')
                w, h = r.get('width', 0) or 0, r.get('height', 0) or 0
                if url and is_valid_url(url) and w >= 150 and h >= 150:
                    if is_trusted_domain(url):  # من مواقع موثوقة فقط
                        return (url, 'DDG-EAN', 85)
    except Exception:
        pass
    return None

# ═══════════════════════════════════════════════════════════════
#  4: DuckDuckGo بالإنجليزي (62%)
# ═══════════════════════════════════════════════════════════════
def src_ddg_english(name, barcode=''):
    try:
        from ddgs import DDGS
        en_part = re.sub(r'[\u0600-\u06FF]+', '', name).strip()
        if len(en_part) >= 3:
            q = f"{en_part} product egypt"
        elif barcode:
            q = f"{barcode} food product"
        else:
            return None
        words = q.split()
        q = ' '.join(words[:6])
        with DDGS(timeout=TIMEOUT) as d:
            results = list(d.images(
                query=q, region='wt-wt', safesearch='moderate',
                size='Medium', type_image='photo', max_results=6
            ))
        for r in results:
            url = r.get('image', '')
            w, h = r.get('width', 0) or 0, r.get('height', 0) or 0
            if url and is_valid_url(url) and w >= 100 and h >= 100:
                return (url, 'DDG-EN', 62)
    except Exception:
        pass
    return None

# ─── البحث الشامل لمنتج واحد ──────────────────────────────────
def process(product):
    pid     = product['id']
    name    = product['name']
    barcode = (product.get('barcode') or '').strip()

    base = {
        'product_id': pid, 'product_name': name, 'barcode': barcode,
        'image_url': '', 'source': '', 'confidence': 0, 'status': 'not_found'
    }

    def try_src(fn, *args):
        try:
            return fn(*args)
        except Exception:
            return None

    # 1: OpenFoodFacts
    res = try_src(src_openfoodfacts, barcode)
    if res:
        log(f"  ✅ [{pid:4}] {name[:27]:<27} | {res[1]:<16} | {res[2]}%", GREEN)
        return {**base, 'image_url': res[0], 'source': res[1], 'confidence': res[2], 'status': 'found'}

    # 2: DDG بالاسم العربي
    time.sleep(random.uniform(0.2, 0.5))
    res = try_src(src_ddg_arabic, name, barcode)
    if res:
        log(f"  🦆 [{pid:4}] {name[:27]:<27} | {res[1]:<16} | {res[2]}%", CYAN)
        return {**base, 'image_url': res[0], 'source': res[1], 'confidence': res[2], 'status': 'found'}

    # 3: DDG بالباركود EAN (مواقع موثوقة فقط)
    if barcode:
        time.sleep(random.uniform(0.2, 0.4))
        res = try_src(src_ddg_ean, barcode)
        if res:
            log(f"  🔢 [{pid:4}] {name[:27]:<27} | {res[1]:<16} | {res[2]}%", CYAN)
            return {**base, 'image_url': res[0], 'source': res[1], 'confidence': res[2], 'status': 'found'}

    # 4: DDG بالإنجليزي
    time.sleep(random.uniform(0.1, 0.3))
    res = try_src(src_ddg_english, name, barcode)
    if res:
        log(f"  🌐 [{pid:4}] {name[:27]:<27} | {res[1]:<16} | {res[2]}%", BLUE)
        return {**base, 'image_url': res[0], 'source': res[1], 'confidence': res[2], 'status': 'found'}

    log(f"  ❌ [{pid:4}] {name[:27]:<27} | لم يوجد", YELLOW)
    return base

# ─── حفظ تدريجي ───────────────────────────────────────────────
_init = False
_lock = threading.Lock()
FIELDS = ['product_id', 'product_name', 'barcode', 'image_url', 'source', 'confidence', 'status']

def save(row):
    global _init
    os.makedirs(os.path.dirname(REPORT_PATH), exist_ok=True)
    with _lock:
        mode = 'a' if _init else 'w'
        with open(REPORT_PATH, mode, newline='', encoding='utf-8-sig') as f:
            w = csv.DictWriter(f, fieldnames=FIELDS, extrasaction='ignore')
            if not _init:
                w.writeheader()
                _init = True
            w.writerow(row)

def stats(results, elapsed):
    found  = [r for r in results if r['status'] == 'found']
    nf     = [r for r in results if r['status'] == 'not_found']
    srcs = {}
    for r in found:
        srcs[r['source']] = srcs.get(r['source'], 0) + 1
    print(f"\n{BOLD}{CYAN}{'='*64}")
    print(f"  📊 نتائج Image Search - النسخة النهائية")
    print(f"{'='*64}{RESET}")
    print(f"  ⏱  الوقت    : {elapsed:.1f}s")
    print(f"  📦  الإجمالي : {len(results)}")
    print(f"  {GREEN}🏆  وجد صورة : {len(found)} ({len(found)/len(results)*100:.1f}%){RESET}")
    print(f"  {YELLOW}❌  لم يوجد  : {len(nf)} ({len(nf)/len(results)*100:.1f}%){RESET}")
    print(f"  ⚡  السرعة   : {len(results)/elapsed:.1f} منتج/ثانية")
    print(f"\n  {BOLD}📡 المصادر:{RESET}")
    for src, cnt in sorted(srcs.items(), key=lambda x: -x[1]):
        bar = '█' * max(1, cnt * 20 // max(len(found), 1))
        print(f"     • {src:<18}: {cnt:3}  {bar}")
    print(f"{CYAN}{'='*64}{RESET}\n")

def main():
    print(f"\n{BOLD}{BLUE}{'='*64}")
    print(f"  🔍 Image Search - النسخة النهائية")
    print(f"  OpenFoodFacts + DuckDuckGo (EAN + AR + EN)")
    print(f"{'='*64}{RESET}")
    print(f"  📅 {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"  📦 منتجات: {LIMIT if LIMIT > 0 else 'كل المنتجات'} | خيوط: {MAX_WORKERS}")
    print(f"  🛡  فلتر صارم: نطاقات ممنوعة + كشف المقالات + مواقع موثوقة للباركود")
    print(f"{BLUE}{'='*64}{RESET}\n")

    products = get_products(LIMIT)
    if not products:
        log("❌ مفيش منتجات!", RED); sys.exit(1)

    results, done = [], 0
    t0 = time.time()
    print(f"{BOLD}🚀 بدء البحث...{RESET}\n")

    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as ex:
        futs = {ex.submit(process, p): p for p in products}
        for fut in as_completed(futs):
            r = fut.result()
            results.append(r)
            save(r)
            done += 1
            if done % 10 == 0:
                found_n = sum(1 for x in results if x['status'] == 'found')
                log(f"\n  📈 [{done}/{len(products)}] ✅ {found_n} ({found_n/done*100:.0f}%) | ⏱ {time.time()-t0:.0f}s", BLUE)

    elapsed = time.time() - t0
    log(f"\n✅ انتهى! {REPORT_PATH}", GREEN)
    stats(results, elapsed)

    # عرض الصور
    found = [r for r in results if r['status'] == 'found']
    if found:
        print(f"{BOLD}🏆 كل الصور الموجودة:{RESET}")
        for r in found:
            print(f"  [{r['product_id']:4}] {r['product_name'][:35]}")
            print(f"         📡 {r['source']} ({r['confidence']}%)")
            print(f"         🔗 {r['image_url'][:85]}")
            print()

    # SQL للمراجعة
    sql_path = REPORT_PATH.replace('.csv', '_update.sql')
    with_url = [r for r in results if r.get('image_url')]
    if with_url:
        with open(sql_path, 'w', encoding='utf-8') as f:
            f.write(f"-- ⚠️ راجع الصور قبل التنفيذ! ({len(with_url)} منتج)\n")
            f.write(f"-- {datetime.now()}\n\n")
            for r in with_url:
                img = r['image_url'].replace("'", "''")
                f.write(f"-- [{r['product_id']}] {r['product_name']} | {r['source']} {r['confidence']}%\n")
                f.write(f"UPDATE products SET image_path = '{img}' WHERE id = {r['product_id']};\n\n")
        log(f"📝 SQL للمراجعة: {sql_path}", CYAN)
        log("   ⚠️  مش هيتنفذ تلقائياً — راجعه أولاً!", YELLOW)

if __name__ == '__main__':
    main()
