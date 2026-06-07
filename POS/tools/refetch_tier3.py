#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
إعادة البحث عن صور الفئة الثالثة المقبولة وتنقيتها بدقة فائقة
"""

import sqlite3
import csv
import os
import re
import sys
import time
import random
import threading
from urllib.parse import urlparse
from ddgs import DDGS

storage_dir = '/home/omar/Desktop/GHARIB/POS/storage'
input_csv = os.path.join(storage_dir, 'tier3_acceptable.csv')
output_csv = os.path.join(storage_dir, 'tier3_acceptable.csv') # Overwrite with clean results
output_sql = os.path.join(storage_dir, 'tier3_acceptable_update.sql')

# ═══════════════════════════════════════════════════════════════
#  النطاقات والكلمات الموثوقة للتصفية الصارمة
# ═══════════════════════════════════════════════════════════════
TRUSTED_TIERS = [
    {
        'domains': [
            'mafrservices.com', 'mafretailproxy.com', 'hyperone.com.eg',
            'hypermousa.com', 'spinneys.com', 'metro-markets.com',
            'b-tech.com', 'elmezantrade.com', 'elmezan.com',
        ],
        'score': 95,
    },
    {
        'domains': [
            'media-amazon.com', 'ssl-images-amazon.com', 'images-amazon.com',
            'openfoodfacts.org', 'openbeautyfacts.org', 'buycott.com',
            'barcodelookup.com', 'upcitemdb.com', 'amazon.eg'
        ],
        'score': 90,
    },
    {
        'domains': [
            'oxygen-mart.com', 'alkhan-mart.com', 'multicst.com',
            'shakomako.co', 'wootfi.com', 'qebox.app', 'yaoota.com',
            'jumia.com.eg', 'noon.com', 'talabat.com', 'benselemanmarket.com',
            'riccostores.com', 'zid.store', 'osmanmarket.com', 'dokkan-albalady.com'
        ],
        'score': 75,
    }
]

WHITELIST_KEYWORDS = [
    'market', 'hyper', 'super', 'store', 'mall', 'grocery', 'shop', 'mart',
    'noon', 'jumia', 'amazon', 'souq', 'carrefour', 'fathalla', 'seoudi',
    'elmezan', 'dokan', 'gomla', 'kazyon', 'spinneys', 'metro', 'btech',
    'openfoodfacts', 'openbeautyfacts', 'barcodelookup', 'upcitemdb', 'talabat',
    'el-dahan', 'mahsoul', 'kazyon', 'ragab', 'oscar', 'gourmet', 'lulu',
    'otomart', 'cleopatra', 'nooncdn', 'jumia.is'
]

BAD_DOMAINS = {
    'youtube.com', 'ytimg.com', 'facebook.com', 'instagram.com',
    'twitter.com', 'tiktok.com', 'scribd.com', 'scribdassets.com',
    'slideshare.net', 'shutterstock.com', 'gettyimages.com', 'freepik.com',
    'unsplash.com', 'pexels.com', 'pngtree.com', 'pixabay.com',
    'medium.com', 'blogspot.com', 'celler-presse.de', 'wikipedia.org',
    'alamy.com', 'pinterest.com', 'researchgate.net', 'wixmp.com', 'gravatar.com'
}

def get_domain(url):
    try:
        return urlparse(url).netloc.lower().replace('www.', '')
    except Exception:
        return ''

def score_url(url):
    domain = get_domain(url)
    # Check bad domains
    for bad in BAD_DOMAINS:
        if bad in domain:
            return -1
            
    # Must have image extension
    if not re.search(r'\.(jpg|jpeg|png|webp)(\?|$)', url, re.IGNORECASE):
        return -1
        
    # Check trusted tiers
    for tier in TRUSTED_TIERS:
        for td in tier['domains']:
            if td in domain:
                return tier['score']
                
    # Check whitelist keywords (if it contains market, store, etc. we accept with score 50)
    for kw in WHITELIST_KEYWORDS:
        if kw in domain:
            return 50
            
    # If it is not in the whitelist and doesn't contain the keywords, REJECT!
    return -1

def clean_product_name_for_query(name):
    q = name.strip()
    if q.startswith("مثلث "):
        q = "جبنة مثلثات " + q[5:]
    elif q == "مثلث":
        q = "جبنة مثلثات"
    q = re.sub(r'([\u0600-\u06FFa-zA-Z])(\d+)', r'\1 \2', q)
    q = re.sub(r'(\d+)([\u0600-\u06FFa-zA-Z])', r'\1 \2', q)
    q = re.sub(r'\b(\d+)\s*ق\b', r'\1 قطع', q)
    q = re.sub(r'\b(\d+)\s*(g|gm|gm\.|gram)\b', r'\1 جرام', q)
    q = re.sub(r'\b(\d+)\s*(kg|kilo)\b', r'\1 كيلو', q)
    q = re.sub(r'\s+', ' ', q).strip()
    return q

def search_product(name):
    cleaned = clean_product_name_for_query(name)
    print(f"Searching: '{name}' -> '{cleaned}'")
    
    # Try searching with cleaned name
    best_url, best_score = run_ddg_search(cleaned)
    if best_url:
        return best_url, best_score
        
    # Try searching with cleaned name + "سوبرماركت" as fallback
    print(f"  Fallback search: '{cleaned} سوبر ماركت'")
    return run_ddg_search(cleaned + " سوبر ماركت")

def run_ddg_search(query):
    try:
        with DDGS(timeout=15) as d:
            results = list(d.images(query, region='wt-wt', safesearch='moderate', max_results=15))
            best_url = None
            best_score = -1
            for r in results:
                url = r.get('image', '')
                try:
                    w = int(r.get('width') or 0)
                    h = int(r.get('height') or 0)
                except ValueError:
                    w, h = 0, 0
                if not url or w < 100 or h < 100:
                    continue
                sc = score_url(url)
                if sc > best_score:
                    best_score = sc
                    best_url = url
                    if sc >= 90:
                        break # Found excellent image, stop search
            if best_url and best_score >= 50:
                return best_url, best_score
    except Exception as e:
        print(f"  Search error: {e}")
    return None, 0

# Read current products from tier3_acceptable.csv
products_to_retry = []
if os.path.exists(input_csv):
    with open(input_csv, 'r', encoding='utf-8-sig') as f:
        reader = csv.DictReader(f)
        for row in reader:
            products_to_retry.append({
                'product_id': row['product_id'],
                'product_name': row['product_name']
            })

if not products_to_retry:
    print("No products to re-fetch.")
    exit(0)

print(f"Loaded {len(products_to_retry)} products to re-fetch.")

# Process and search
updated_rows = []
success_count = 0

for p in products_to_retry:
    pid = p['product_id']
    name = p['product_name']
    
    time.sleep(random.uniform(1.2, 2.5)) # Be gentle
    url, score = search_product(name)
    
    if url:
        print(f"  => SUCCESS: {url} (Score: {score})")
        success_count += 1
        updated_rows.append({
            'product_id': pid,
            'product_name': name,
            'image_url': url,
            'score': score,
            'domain': get_domain(url),
            'status': 'found'
        })
    else:
        print("  => NOT FOUND / FILTERED OUT")
        updated_rows.append({
            'product_id': pid,
            'product_name': name,
            'image_url': '',
            'score': 0,
            'domain': '',
            'status': 'not_found'
        })

# Write updated CSV
fields = ['product_id', 'product_name', 'image_url', 'score', 'domain', 'status']
with open(output_csv, 'w', newline='', encoding='utf-8-sig') as cf:
    writer = csv.DictWriter(cf, fieldnames=fields)
    writer.writeheader()
    writer.writerows(updated_rows)

# Write updated SQL
found_rows = [r for r in updated_rows if r['status'] == 'found']
with open(output_sql, 'w', encoding='utf-8') as sf:
    sf.write(f"-- ⚠️ فئة: مقبول (النسخة المصفاة والصارمة بعد إعادة البحث)\n")
    sf.write(f"-- العدد الجديد الناجح: {len(found_rows)} منتج من إجمالي {len(updated_rows)}\n\n")
    for r in found_rows:
        img = r['image_url'].replace("'", "''")
        sf.write(f"-- [{r['product_id']}] {r['product_name']} | {r['domain']} ({r['score']})\n")
        sf.write(f"UPDATE products SET image_path = '{img}' WHERE id = {r['product_id']};\n\n")

print(f"\nCompleted! Re-fetched: {len(updated_rows)} | Successfully found with strict filter: {success_count} ({success_count/len(updated_rows)*100:.1f}%)")
