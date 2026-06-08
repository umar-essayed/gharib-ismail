import sqlite3
import requests
import json

DB_PATH = '/home/omar/Desktop/GHARIB/POS/database/posg.sqlite'
GROQ_API_KEY = 'PLACEHOLDER_GROQ_API_KEY'
MODEL_NAME = 'llama-3.1-8b-instant'
GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions'

# 1. Get products in category
conn = sqlite3.connect(DB_PATH)
cursor = conn.cursor()
cursor.execute("SELECT id, name FROM products WHERE deleted_at IS NULL AND is_active = 1 AND category_id = (SELECT id FROM product_categories WHERE name = 'شاي وقهوة ومشروبات ساخنة') LIMIT 35")
products = [{'id': row[0], 'name': row[1]} for row in cursor.fetchall()]
conn.close()

print(f"Loaded {len(products)} products for test.")

system_prompt = (
    "You are an expert system ranking products in the category 'شاي وقهوة ومشروبات ساخنة' in Egypt.\n"
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

headers = {
    'Authorization': f'Bearer {GROQ_API_KEY}',
    'Content-Type': 'application/json'
}
payload = {
    'model': MODEL_NAME,
    'messages': [
        {'role': 'system', 'content': system_prompt},
        {'role': 'user', 'content': json.dumps(products, ensure_ascii=False)}
    ],
    'temperature': 0.1,
    'max_tokens': 2048
}

r = requests.post(GROQ_API_URL, json=payload, headers=headers)
print("Status Code:", r.status_code)
print("Response Headers:")
print(dict(r.headers))
print("Response Body:")
print(r.text)
