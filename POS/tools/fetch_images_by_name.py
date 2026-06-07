#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
بحث بالاسم → أول صورة من نطاق موثوق
"""

import sqlite3
import csv
import os
import re
import sys
import time
import random
import threading
from datetime import datetime
from concurrent.futures import ThreadPoolExecutor, as_completed
from urllib.parse import urlparse

DB_PATH     = os.path.join(os.path.dirname(__file__), '..', 'database', 'posg.sqlite')
REPORT_PATH = os.path.join(os.path.dirname(__file__), '..', 'storage', 'image_by_name_v2.csv')
LIMIT       = 0      # 0 = كل المنتجات
MAX_WORKERS = 4

GREEN  = '\033[92m'; YELLOW = '\033[93m'; BLUE = '\033[94m'
CYAN   = '\033[96m'; BOLD = '\033[1m'; RESET = '\033[0m'
lock   = threading.Lock()
def log(msg, color=RESET):
    with lock:
        print(f"{color}{msg}{RESET}", flush=True)

# ═══════════════════════════════════════════════════════════════
#  نطاقات موثوقة مرتبة من الأعلى جودة للأقل
# ═══════════════════════════════════════════════════════════════
TRUSTED_TIERS = [

    # 🥇 الدرجة الأولى: CDN سوبرماركتات مصرية كبيرة (صور منتج احترافية)
    {
        'domains': [
            'mafrservices.com',      # Carrefour Egypt
            'mafretailproxy.com',    # Carrefour Egypt
            'hyperone.com.eg',       # Hyper One
            'hypermousa.com',        # Hyper Mousa
            'spinneys.com',          # Spinneys
            'metro-markets.com',     # Metro Egypt
            'b-tech.com',            # B.Tech
            'elmezantrade.com',      # El Mizan (very accurate)
            'elmezan.com',
        ],
        'score': 95,
        'label': 'سوبرماركت-مصري',
    },

    # 🥈 الدرجة الثانية: Amazon + مواقع تجارة عالمية كبيرة
    {
        'domains': [
            'media-amazon.com',      # Amazon
            'ssl-images-amazon.com',
            'images-amazon.com',
            'openfoodfacts.org',     # Open Food Facts
            'openbeautyfacts.org',
            'buycott.com',
            'barcodelookup.com',
        ],
        'score': 90,
        'label': 'amazon-دولي',
    },

    # 🥉 الدرجة الثالثة: مواقع مصرية وعربية موثوقة
    {
        'domains': [
            'oxygen-mart.com',
            'alkhan-mart.com',
            'multicst.com',
            'shakomako.co',
            'wootfi.com',
            'qebox.app',
            'yaoota.com',
            'jumia.com.eg',
            'noon.com',
            'talabat.com',
            'benselemanmarket.com',
            'riccostores.com',
            'zid.store',             # متاجر Zid السعودية
            'osmanmarket.com',
            'dokkan-albalady.com',
            'alkhan-mart.com',
        ],
        'score': 75,
        'label': 'متجر-عربي',
    },
]

# نطاقات ممنوعة كلياً (واضح إنها مش صور منتجات)
BAD_DOMAINS = {
    # سوشيال ميديا وفيديو
    'youtube.com', 'ytimg.com', 'facebook.com', 'instagram.com',
    'twitter.com', 'tiktok.com',
    # مستندات
    'scribd.com', 'scribdassets.com', 'slideshare.net',
    # stock images
    'shutterstock.com', 'gettyimages.com', 'freepik.com',
    'unsplash.com', 'pexels.com', 'pngtree.com', 'pixabay.com',
    # مواقع محتوى/بلوج بدون صور منتجات
    'medium.com', 'blogspot.com',
    'celler-presse.de',
}

def get_domain(url):
    try:
        return urlparse(url).netloc.lower().replace('www.', '')
    except Exception:
        return ''

def score_url(url):
    """يرجع درجة الجودة للرابط (أعلى = أحسن)، -1 يعني ممنوع"""
    domain = get_domain(url)
    # فحص الممنوعات
    for bad in BAD_DOMAINS:
        if bad in domain:
            return -1
    # لازم ينتهي بامتداد صورة
    if not re.search(r'\.(jpg|jpeg|png|webp)(\?|$)', url, re.IGNORECASE):
        return -1
    # فحص درجات الجودة
    for tier in TRUSTED_TIERS:
        for td in tier['domains']:
            if td in domain:
                return tier['score']
    return 40  # مقبول بس مش موثوق

# ─── تنظيف الاسم لتسهيل البحث ────────────────────────────────
def clean_product_name_for_query(name):
    q = name.strip()
    # تحويل "مثلث " في البداية لـ "جبنة مثلثات" ليكون البحث عن جبن وليس رياضيات
    if q.startswith("مثلث "):
        q = "جبنة مثلثات " + q[5:]
    elif q == "مثلث":
        q = "جبنة مثلثات"
        
    # إضافة مسافة بين الحروف والأرقام لو مفقودة (مثال: لافاش8 -> لافاش 8)
    q = re.sub(r'([\u0600-\u06FFa-zA-Z])(\d+)', r'\1 \2', q)
    q = re.sub(r'(\d+)([\u0600-\u06FFa-zA-Z])', r'\1 \2', q)
    
    # تحويل الاختصارات
    q = re.sub(r'\b(\d+)\s*ق\b', r'\1 قطع', q)
    q = re.sub(r'\b(\d+)\s*(g|gm|gm\.|gram)\b', r'\1 جرام', q)
    q = re.sub(r'\b(\d+)\s*(kg|kilo)\b', r'\1 كيلو', q)
    
    # إزالة المسافات المتكررة
    q = re.sub(r'\s+', ' ', q).strip()
    return q

# ─── جلب المنتجات ─────────────────────────────────────────────
def get_products(limit):
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cur  = conn.cursor()
    lim  = limit if limit > 0 else 99999
    cur.execute("""
        SELECT id, name FROM products
        WHERE deleted_at IS NULL AND is_active = 1
          AND (image_path IS NULL OR image_path = '')
        ORDER BY id LIMIT ?
    """, (lim,))
    rows = [dict(r) for r in cur.fetchall()]
    conn.close()
    log(f"✅ {len(rows)} منتج", GREEN)
    return rows

# ─── البحث: اسم المنتج → أحسن صورة من نطاق موثوق ────────────
def get_best_image(name):
    cleaned_name = clean_product_name_for_query(name)
    from ddgs import DDGS
    
    max_retries = 3
    for attempt in range(max_retries):
        try:
            with DDGS(timeout=15) as d:
                results = list(d.images(
                    query=cleaned_name,
                    region='wt-wt',
                    safesearch='moderate',
                    max_results=15    # نجيب 15 نتيجة ونختار الأحسن
                ))

            best_url   = None
            best_score = -1

            for r in results:
                url   = r.get('image', '')
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
                    best_url   = url
                    if sc >= 90:
                        break  # وجدنا صورة ممتازة، ما نكملش

            if best_url and best_score >= 0:
                return best_url, best_score
            return None, 0  # وجد نتائج فارغة أو غير مطابقة، لا داعي لإعادة المحاولة
        except Exception:
            if attempt < max_retries - 1:
                sleep_time = (attempt + 1) * 3 + random.uniform(0.5, 1.5)
                time.sleep(sleep_time)
            else:
                pass
    return None, 0

# ─── معالجة منتج واحد ─────────────────────────────────────────
def process(product):
    pid  = product['id']
    name = product['name']
    # Sleep to be gentle on DuckDuckGo and prevent rate limiting
    time.sleep(random.uniform(1.2, 2.5))

    url, score = get_best_image(name)

    tier_label = 'مقبول'
    color = YELLOW
    if score >= 90:
        tier_label = '🥇 ممتاز'; color = GREEN
    elif score >= 75:
        tier_label = '🥈 جيد';   color = CYAN
    elif score >= 40:
        tier_label = '🥉 مقبول'; color = YELLOW

    if url:
        log(f"  ✅ [{pid:4}] {name[:30]:<30} | {tier_label} ({score}) | {get_domain(url)}", color)
        return {'product_id': pid, 'product_name': name,
                'image_url': url, 'score': score,
                'domain': get_domain(url), 'status': 'found'}
    else:
        log(f"  ❌ [{pid:4}] {name[:30]:<30} | لم يوجد", YELLOW)
        return {'product_id': pid, 'product_name': name,
                'image_url': '', 'score': 0,
                'domain': '', 'status': 'not_found'}

# ─── حفظ تدريجي ───────────────────────────────────────────────
_init = False
_csv_lock = threading.Lock()
FIELDS = ['product_id', 'product_name', 'image_url', 'score', 'domain', 'status']

def save(row):
    global _init
    os.makedirs(os.path.dirname(REPORT_PATH), exist_ok=True)
    with _csv_lock:
        mode = 'a' if _init else 'w'
        with open(REPORT_PATH, mode, newline='', encoding='utf-8-sig') as f:
            w = csv.DictWriter(f, fieldnames=FIELDS, extrasaction='ignore')
            if not _init:
                w.writeheader()
                _init = True
            w.writerow(row)
            
        # Write SQL update progressively if image was found
        if row.get('status') == 'found' and row.get('image_url'):
            sql_path = REPORT_PATH.replace('.csv', '_update.sql')
            # If this is the first write, initialize/clear the file
            sql_mode = 'a' if (os.path.exists(sql_path) and _init) else 'w'
            with open(sql_path, sql_mode, encoding='utf-8') as sf:
                if sql_mode == 'w':
                    sf.write(f"-- ⚠️ راجع الصور قبل التنفيذ!\n")
                    sf.write(f"-- تم الإنشاء تدريجياً: {datetime.now()}\n\n")
                img = row['image_url'].replace("'", "''")
                tier = '🥇' if row.get('score', 0) >= 90 else ('🥈' if row.get('score', 0) >= 75 else '🥉')
                sf.write(f"-- {tier} [{row['product_id']}] {row['product_name']} | {row.get('domain','')} ({row.get('score', 0)})\n")
                sf.write(f"UPDATE products SET image_path = '{img}' WHERE id = {row['product_id']};\n\n")

# ─── Main ──────────────────────────────────────────────────────
def main():
    print(f"\n{BOLD}{BLUE}{'='*62}")
    print(f"  🔍 بحث الصور بالاسم - أحسن صورة من نطاق موثوق")
    print(f"{'='*62}{RESET}")
    print(f"  📦 منتجات: {LIMIT if LIMIT > 0 else 'كل المنتجات'} | خيوط: {MAX_WORKERS}")
    print(f"  🥇 الأولوية: Carrefour/HyperOne > Amazon > متاجر عربية")
    print(f"{BLUE}{'='*62}{RESET}\n")

    products = get_products(LIMIT)
    if not products:
        print("❌ مفيش منتجات!"); sys.exit(1)

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
                hi = sum(1 for x in results if x.get('score', 0) >= 90)
                log(f"\n  📈 [{done}/{len(products)}] ✅ {found_n} | 🥇 {hi} ممتاز | ⏱ {time.time()-t0:.0f}s", BLUE)

    elapsed = time.time() - t0
    found   = [r for r in results if r['status'] == 'found']
    gold    = [r for r in found if r.get('score', 0) >= 90]
    silver  = [r for r in found if 75 <= r.get('score', 0) < 90]
    bronze  = [r for r in found if r.get('score', 0) < 75]

    # إحصائيات النطاقات
    domains = {}
    for r in found:
        d = r.get('domain', 'unknown')
        domains[d] = domains.get(d, 0) + 1

    print(f"\n{BOLD}{CYAN}{'='*62}")
    print(f"  📊 النتائج")
    print(f"{'='*62}{RESET}")
    print(f"  ⏱  الوقت    : {elapsed:.1f}s ({elapsed/60:.1f} دقيقة)")
    print(f"  📦  الإجمالي : {len(results)}")
    print(f"  {GREEN}✅  وجد صورة : {len(found)} ({len(found)/len(results)*100:.1f}%){RESET}")
    print(f"  {GREEN}🥇  ممتاز(95+): {len(gold)}{RESET}")
    print(f"  {CYAN}🥈  جيد (75+) : {len(silver)}{RESET}")
    print(f"  {YELLOW}🥉  مقبول     : {len(bronze)}{RESET}")
    print(f"\n  {BOLD}📡 توزيع النطاقات:{RESET}")
    for domain, cnt in sorted(domains.items(), key=lambda x: -x[1])[:10]:
        bar = '█' * max(1, cnt * 20 // max(len(found), 1))
        print(f"     • {domain:<35}: {cnt:3}  {bar}")
    print(f"{CYAN}{'='*62}{RESET}")
    print(f"\n  📄 التقرير: {REPORT_PATH}")

    # SQL للمراجعة
    sql_path = REPORT_PATH.replace('.csv', '_update.sql')
    with open(sql_path, 'w', encoding='utf-8') as f:
        f.write(f"-- ⚠️ راجع الصور قبل التنفيذ! ({len(found)} منتج)\n")
        f.write(f"-- 🥇 ممتاز: {len(gold)} | 🥈 جيد: {len(silver)} | 🥉 مقبول: {len(bronze)}\n")
        f.write(f"-- {datetime.now()}\n\n")
        # رتّب من الأعلى جودة للأقل
        for r in sorted(found, key=lambda x: -x.get('score', 0)):
            img = r['image_url'].replace("'", "''")
            tier = '🥇' if r.get('score',0)>=90 else ('🥈' if r.get('score',0)>=75 else '🥉')
            f.write(f"-- {tier} [{r['product_id']}] {r['product_name']} | {r.get('domain','')} ({r.get('score',0)})\n")
            f.write(f"UPDATE products SET image_path = '{img}' WHERE id = {r['product_id']};\n\n")
    print(f"  📝 SQL: {sql_path}")
    print(f"  {YELLOW}⚠️  مش هيتنفذ تلقائياً — راجعه أولاً!{RESET}\n")

if __name__ == '__main__':
    main()
