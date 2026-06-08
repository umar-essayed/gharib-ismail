import requests
import json

GROQ_API_KEY = 'PLACEHOLDER_GROQ_API_KEY'
MODEL_NAME = 'llama-3.1-8b-instant'
GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions'

headers = {
    'Authorization': f'Bearer {GROQ_API_KEY}',
    'Content-Type': 'application/json'
}
payload = {
    'model': MODEL_NAME,
    'messages': [
        {'role': 'system', 'content': 'You are a categorization assistant. Respond only in JSON.'},
        {'role': 'user', 'content': 'Categorize these 10 products: [{"id": 1, "name": "أرز الضحى 1ك"}, {"id": 2, "name": "مكرونة الملكة 400 جرام"}, {"id": 3, "name": "مناديل فاين 100 منديل"}, {"id": 4, "name": "بيبسي كانز 330 مل"}, {"id": 5, "name": "جبنة دومتي فيتا"}, {"id": 6, "name": "شاي ليبتون ناعم"}, {"id": 7, "name": "زيت عافية ذرة"}, {"id": 8, "name": "شوكولاتة كادبوري"}, {"id": 9, "name": "مسحوق اريال 2ك"}, {"id": 10, "name": "سمنة روابي 1ك"}]'}
    ],
    'temperature': 0.1,
    'max_tokens': 1024
}

r = requests.post(GROQ_API_URL, json=payload, headers=headers)
print("Status Code:", r.status_code)
print("Response Headers:", dict(r.headers))
print("Response Body:")
print(r.text)
