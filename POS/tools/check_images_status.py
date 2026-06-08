#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
إسكربت فحص حالة صور المنتجات واستخراج تقرير شامل بالصور التالفة أو المفقودة
"""

import sqlite3
import csv
import os
import urllib.request
import urllib.error
from urllib.parse import urlparse, urlunparse, quote
from concurrent.futures import ThreadPoolExecutor, as_completed

db_path = '/home/omar/Desktop/GHARIB/POS/database/posg.sqlite'
report_path = '/home/omar/Desktop/GHARIB/POS/storage/broken_images_report.csv'

# تهيئة بيانات المتصفح لتجنب حظر الـ User-Agent
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

def safe_encode_url(url):
    try:
        parts = urlparse(url)
        # تشفير المسار مع الحفاظ على الفواصل '/'
        quoted_path = quote(parts.path, safe='/')
        # تشفير الاستعلام مع الحفاظ على '=' و '&'
        quoted_query = quote(parts.query, safe='=&')
        return urlunparse((parts.scheme, parts.netloc, quoted_path, parts.params, quoted_query, parts.fragment))
    except Exception:
        return url

def check_url(product_id, name, barcode, url):
    if not url or url.strip() == '':
        return {
            'product_id': product_id,
            'product_name': name,
            'barcode': barcode,
            'image_url': '',
            'issue': 'مفقودة (لا توجد صورة)'
        }
        
    # التحقق من أن الرابط يبدأ بـ http/https
    if not (url.startswith('http://') or url.startswith('https://')):
        # إذا كان مسار محلي، نضيف البادئة الافتراضية
        url = 'https://nasriya-jomla-market.com/' + url.lstrip('/')

    # تشفير الحروف العربية في الرابط قبل الطلب لتجنب أخطاء الترميز
    url = safe_encode_url(url)

    try:
        # إرسال طلب HEAD خفيف لمعرفة حالة الرابط بسرعة
        req = urllib.request.Request(url, headers=HEADERS, method='HEAD')
        with urllib.request.urlopen(req, timeout=5) as response:
            status = response.status
            if status != 200:
                return {
                    'product_id': product_id,
                    'product_name': name,
                    'barcode': barcode,
                    'image_url': url,
                    'issue': f'خطأ HTTP {status}'
                }
    except urllib.error.HTTPError as e:
        # إذا كانت الطريقة HEAD غير مدعومة من الخادم، نجرب GET خفيف
        if e.code in [405, 403, 501]:
            try:
                req_get = urllib.request.Request(url, headers=HEADERS, method='GET')
                # نقرأ جزء بسيط جداً من الملف للتأكد من وجوده
                with urllib.request.urlopen(req_get, timeout=5) as response:
                    status = response.status
                    if status == 200:
                        return None # الصورة سليمة
            except urllib.error.HTTPError as e2:
                return {
                    'product_id': product_id,
                    'product_name': name,
                    'barcode': barcode,
                    'image_url': url,
                    'issue': f'خطأ HTTP {e2.code}'
                }
            except Exception as e2_err:
                return {
                    'product_id': product_id,
                    'product_name': name,
                    'barcode': barcode,
                    'image_url': url,
                    'issue': f'خطأ اتصال: {str(e2_err)}'
                }
        else:
            return {
                'product_id': product_id,
                'product_name': name,
                'barcode': barcode,
                'image_url': url,
                'issue': f'خطأ HTTP {e.code}'
            }
    except Exception as e_err:
        return {
            'product_id': product_id,
            'product_name': name,
            'barcode': barcode,
            'image_url': url,
            'issue': f'خطأ اتصال: {str(e_err)}'
        }

    return None # سليمة تماماً

def main():
    if not os.path.exists(db_path):
        print(f"Error: Database not found at {db_path}")
        return

    # الاتصال بقاعدة البيانات وجلب المنتجات
    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()
    
    # جلب المنتجات غير المحذوفة والنشطة
    cursor.execute("SELECT id, name, barcode, image_path FROM products WHERE deleted_at IS NULL AND is_active = 1")
    products = cursor.fetchall()
    conn.close()

    total_products = len(products)
    print(f"Loaded {total_products} active products from database. Starting validation...")

    issues_report = []
    missing_count = 0
    broken_count = 0

    # استخدام ThreadPoolExecutor للتحقق بالتوازي لتوفير الوقت
    with ThreadPoolExecutor(max_workers=40) as executor:
        futures = {
            executor.submit(
                check_url, 
                p['id'], 
                p['name'], 
                p['barcode'] or '', 
                p['image_path'] or ''
            ): p for p in products
        }

        processed = 0
        for future in as_completed(futures):
            processed += 1
            if processed % 100 == 0:
                print(f"Processed {processed}/{total_products} products...")
                
            res = future.result()
            if res:
                issues_report.append(res)
                if 'مفقودة' in res['issue']:
                    missing_count += 1
                else:
                    broken_count += 1

    # حفظ التقرير في ملف CSV
    fields = ['product_id', 'product_name', 'barcode', 'image_url', 'issue']
    with open(report_path, 'w', newline='', encoding='utf-8-sig') as f:
        writer = csv.DictWriter(f, fieldnames=fields)
        writer.writeheader()
        writer.writerows(issues_report)

    print("\n" + "="*50)
    print("📋 ملخص تقرير فحص الصور:")
    print("="*50)
    print(f"✓ إجمالي المنتجات المفحوصة: {total_products}")
    print(f"✓ صور سليمة وتعمل: {total_products - len(issues_report)}")
    print(f"⚠️ صور مفقودة (بدون رابط): {missing_count}")
    print(f"❌ صور تالفة (تعطي خطأ 404 أو اتصال): {broken_count}")
    print(f"📁 تم حفظ التقرير الكامل في: {report_path}")
    print("="*50)

if __name__ == '__main__':
    main()
