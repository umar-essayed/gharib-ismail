#!/bin/bash
# ═══════════════════════════════════════════
#  تشغيل سيرفر الكاشير — الناصرية جمله ماركت
# ═══════════════════════════════════════════

# إيقاف أي سيرفر قديم
pkill -f "php -S 0.0.0.0:8080" 2>/dev/null
sleep 1

# تشغيل السيرفر بتوقيت القاهرة
export TZ=Africa/Cairo
cd /home/omar/Desktop/GHARIB/POS
php -S 0.0.0.0:8080 -t public/ public/index.php >> storage/logs/server.log 2>&1 &
echo $! > /tmp/pos_server.pid
echo "✅ Server started — PID: $(cat /tmp/pos_server.pid)"
echo "🌐 http://localhost:8080"
