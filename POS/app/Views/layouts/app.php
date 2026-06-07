<?php
$settings = \App\Services\SettingsService::all();
$title = $title ?? ($settings['company_name'] ?? config('app')['name']);
$supportLine = '';
$success = \App\Core\Session::getFlash('success');
$error = \App\Core\Session::getFlash('error');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = rtrim(parse_url(config('app')['base_url'], PHP_URL_PATH) ?: '', '/');
$cleanPath = $base && str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
$cleanPath = '/' . trim($cleanPath, '/');
if ($cleanPath === '//') {
    $cleanPath = '/';
}

$icons = [
    'home' => '<svg viewBox="0 0 24 24" fill="none"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-10.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
    'sales' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="1.8"/><path d="M8 14l2.5-2.5L13 14l3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'catalog' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4z" stroke="currentColor" stroke-width="1.8"/><path d="M8 10h8M8 14h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    'contacts' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.8"/><path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    'inventory' => '<svg viewBox="0 0 24 24" fill="none"><path d="M3 8l9-5 9 5-9 5-9-5Z" stroke="currentColor" stroke-width="1.8"/><path d="M3 12l9 5 9-5M3 16l9 5 9-5" stroke="currentColor" stroke-width="1.8"/></svg>',
    'finance' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3v18M7 7h7a3 3 0 1 1 0 6H10a3 3 0 1 0 0 6h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    'reports' => '<svg viewBox="0 0 24 24" fill="none"><path d="M5 19V5h14v14H5Z" stroke="currentColor" stroke-width="1.8"/><path d="M8 15v-3M12 15V9M16 15v-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    'admin' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 14a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.8"/><path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    'store' => '<svg viewBox="0 0 24 24" fill="none"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0" stroke="currentColor" stroke-width="1.8"/></svg>',
];

$menuSections = [
    [
        'title' => 'الرئيسية',
        'icon' => 'home',
        'children' => [
            ['name' => 'لوحة التحكم',                   'url' => '/dashboard',              'perm' => 'dashboard.view'],
            ['name' => 'نقطة البيع POS',                'url' => '/pos',                    'perm' => 'pos.sell'],
        ],
    ],
    [
        'title' => 'المتجر الإلكتروني',
        'icon' => 'store',
        'children' => [
            ['name' => 'طلبات المتجر الجديدة',          'url' => '/online-orders',          'perm' => 'pos.sell'],
            ['name' => 'الطلبات المقبولة والشحن',        'url' => '/online-orders/accepted', 'perm' => 'pos.sell'],
            ['name' => 'إدارة عملاء المتجر',            'url' => '/online-store/users',      'perm' => 'users.manage'],
            ['name' => 'العروض والكوبونات والبنرات',      'url' => '/online-store/promotions', 'perm' => 'promotions.manage'],
            ['name' => 'إدارة مصاريف الشحن',            'url' => '/online-store/shipping',   'perm' => 'settings.manage'],
            ['name' => 'تحليلات المتجر الإلكتروني',      'url' => '/online-store/analytics',  'perm' => 'reports.view'],
        ],
    ],
    [
        'title' => 'البيع والمشتريات',
        'icon' => 'sales',
        'children' => [
            ['name' => 'فواتير البيع', 'url' => '/sales', 'perm' => 'sales.manage'],
            ['name' => 'فواتير الشراء', 'url' => '/purchases', 'perm' => 'purchases.manage'],
            ['name' => 'مرتجع بيع', 'url' => '/returns/sales', 'perm' => 'returns.manage'],
            ['name' => 'مرتجع شراء', 'url' => '/returns/purchases', 'perm' => 'returns.manage'],
        ],
    ],
    [
        'title' => 'الأصناف',
        'icon' => 'catalog',
        'children' => [
            ['name' => 'المنتجات', 'url' => '/products', 'perm' => 'products.manage'],
            ['name' => 'العروض والخصومات', 'url' => '/promotions', 'perm' => 'promotions.manage'],
            ['name' => 'التصنيفات', 'url' => '/categories', 'perm' => 'products.manage'],
            ['name' => 'الوحدات', 'url' => '/units', 'perm' => 'products.manage'],
            ['name' => 'ملصقات الباركود', 'url' => '/barcode', 'perm' => 'barcode.print'],
        ],
    ],
    [
        'title' => 'العملاء والموردون',
        'icon' => 'contacts',
        'children' => [
            ['name' => 'العملاء', 'url' => '/customers', 'perm' => 'customers.manage'],
            ['name' => 'الموردون', 'url' => '/suppliers', 'perm' => 'suppliers.manage'],
        ],
    ],
    [
        'title' => 'المخزون',
        'icon' => 'inventory',
        'children' => [
            ['name' => 'المخزون الحالي', 'url' => '/inventory/stock', 'perm' => 'inventory.manage'],
            ['name' => 'حركات المخزون', 'url' => '/inventory/movements', 'perm' => 'inventory.manage'],
            ['name' => 'تسويات المخزون', 'url' => '/inventory/adjustments', 'perm' => 'inventory.manage'],
        ],
    ],
    [
        'title' => 'الصندوق والتقارير',
        'icon' => 'finance',
        'children' => [
            ['name' => 'الشيفتات', 'url' => '/shifts', 'perm' => 'shifts.manage'],
            ['name' => 'الصندوق', 'url' => '/cash', 'perm' => 'cash.manage'],
            ['name' => 'التقارير', 'url' => '/reports', 'perm' => 'reports.view'],
        ],
    ],
    [
        'title' => 'الإدارة',
        'icon' => 'admin',
        'children' => [
            ['name' => 'المستخدمون', 'url' => '/users', 'perm' => 'users.manage'],
            ['name' => 'الأدوار والصلاحيات', 'url' => '/roles', 'perm' => 'roles.manage'],
            ['name' => 'الإعدادات', 'url' => '/settings', 'perm' => 'settings.manage'],
            ['name' => 'الملف الشخصي', 'url' => '/profile', 'perm' => 'dashboard.view'],
        ],
    ],
];

$isActivePath = static function (string $currentPath, string $url): bool {
    return $currentPath === $url || str_starts_with($currentPath, rtrim($url, '/') . '/');
};
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/vendor/bootstrap/bootstrap.rtl.min.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell" id="appShell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="<?= url('/assets/icons/logo.jpeg') ?>" alt="Logo" class="brand-logo">
            <div class="brand-info">
                <div class="brand-title"><?= e($settings['company_name'] ?? 'POSG') ?></div>
                <div class="brand-subtitle">نظام نقاط البيع</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($menuSections as $sIndex => $section): ?>
                <?php
                $visibleChildren = [];
                $sectionActive = false;
                foreach ($section['children'] as $child) {
                    if (!can($child['perm'])) {
                        continue;
                    }
                    $visibleChildren[] = $child;
                    if ($isActivePath($cleanPath, $child['url'])) {
                        $sectionActive = true;
                    }
                }
                if (empty($visibleChildren)) {
                    continue;
                }
                $menuId = 'menuSection' . $sIndex;
                ?>
                <div class="menu-section <?= $sectionActive ? 'open' : '' ?>" data-menu-section>
                    <button class="menu-section-toggle" type="button" data-menu-toggle="<?= e($menuId) ?>" aria-expanded="<?= $sectionActive ? 'true' : 'false' ?>">
                        <span class="menu-main-icon" aria-hidden="true"><?= $icons[$section['icon']] ?? $icons['home'] ?></span>
                        <span class="menu-main-text"><?= e($section['title']) ?></span>
                        <span class="menu-caret" aria-hidden="true">▾</span>
                    </button>
                    <div class="menu-sub" id="<?= e($menuId) ?>">
                        <?php foreach ($visibleChildren as $child): ?>
                            <?php $active = $isActivePath($cleanPath, $child['url']); ?>
                            <a class="nav-link-item sub-link <?= $active ? 'active' : '' ?>" href="<?= url($child['url']) ?>">
                                <span class="sub-dot" aria-hidden="true"></span>
                                <span class="sub-text"><?= e($child['name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <button type="button" class="topbar-toggle" id="sidebarToggle" title="إظهار/إخفاء الشريط الجانبي">☰</button>
            <div class="topbar-title"><?= e($title) ?></div>
            <div class="topbar-actions">
                <div class="header-datetime" id="headerDateTime" aria-live="polite"></div>

                <!-- 🖨️ حالة طابعة الفواتير -->
                <div id="printerStatusBadge" class="d-none align-items-center"
                     style="background: #e2e8f0; color: #1e293b; border-radius: 20px;
                            padding: 5px 14px; font-size: 13px; gap: 6px; font-weight: 600; direction: rtl;"
                     title="حالة طابعة الفواتير الافتراضية">
                    🖨️ <span id="printerStatusName" style="max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block;">الطابعة</span>:
                    <span id="printerStatusState" class="badge bg-secondary">فحص...</span>
                </div>

                <?php if (can('pos.sell')): ?>
                <!-- 🛒 إشعار الطلبات الإلكترونية -->
                <a href="<?= url('/online-orders') ?>"
                   id="onlineOrdersBell"
                   title="طلبات المتجر الإلكتروني"
                   style="position:relative; display:inline-flex; align-items:center; text-decoration:none;
                          background:linear-gradient(135deg,#16a34a,#10b981); color:#fff;
                          border-radius:20px; padding:5px 14px; font-size:13px; gap:6px; font-weight:600;">
                    🛒
                    <span id="onlineOrdersCount" style="display:none;
                          position:absolute; top:-6px; right:-6px;
                          background:#ef4444; color:#fff; border-radius:50%;
                          width:20px; height:20px; font-size:11px; font-weight:700;
                          display:none; align-items:center; justify-content:center;">0</span>
                    <span id="onlineOrdersLabel">المتجر</span>
                    <span id="syncDotTopbar" style="width:8px;height:8px;border-radius:50%;background:#22c55e;
                          animation:pulse-tb 1.5s infinite;display:inline-block"></span>
                </a>
                <style>
                @keyframes pulse-tb {
                    0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.5)}
                    50%{box-shadow:0 0 0 5px rgba(34,197,94,0)}
                }
                </style>
                <?php endif; ?>

                <span class="user-pill"><?= e(current_user()['full_name'] ?? '') ?></span>
                <form method="post" action="<?= url('/logout') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-danger">خروج</button>
                </form>
            </div>
        </header>


        <main class="page-content container-fluid">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>

<script src="<?= url('/assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('/assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script src="<?= url('/assets/js/app.js') ?>"></script>

<?php if (can('pos.sell')): ?>
<script>
(function() {
    let prevCount = 0;

    function playNotificationSound() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const playBeep = (time, freq, duration) => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, time);
                gain.gain.setValueAtTime(0.25, time);
                gain.gain.exponentialRampToValueAtTime(0.0001, time + duration);
                osc.start(time);
                osc.stop(time + duration);
            };
            const now = audioCtx.currentTime;
            playBeep(now, 587.33, 0.15); // D5
            playBeep(now + 0.2, 880, 0.3); // A5
        } catch (e) {
            console.error('Audio Context Error:', e);
        }
    }

    function checkOnlineOrders() {
        fetch('<?= url('/api/online-orders/status') ?>', { credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;

                const countEl  = document.getElementById('onlineOrdersCount');
                const labelEl  = document.getElementById('onlineOrdersLabel');
                const dotEl    = document.getElementById('syncDotTopbar');
                const bellEl   = document.getElementById('onlineOrdersBell');

                if (!countEl) return;

                const count = parseInt(data.queue_count) || 0; // reusing queue_count as proxy
                // Fetch real pending orders count from the API response
                const pending = data.pending_count !== undefined ? data.pending_count : 0;

                // Sync dot color
                if (dotEl) dotEl.style.background = data.online ? '#22c55e' : '#ef4444';

                // Badge count
                if (pending > 0) {
                    countEl.textContent = pending > 99 ? '99+' : pending;
                    countEl.style.display = 'flex';
                    if (labelEl) labelEl.textContent = `${pending} طلب جديد`;

                    // تنبيه صوتي إذا وصل طلب جديد
                    if (pending > prevCount && prevCount >= 0) {
                        bellEl.style.animation = 'none';
                        bellEl.style.background = 'linear-gradient(135deg,#b45309,#f59e0b)';
                        playNotificationSound();
                        setTimeout(() => {
                            bellEl.style.background = 'linear-gradient(135deg,#1e3a5f,#2563eb)';
                        }, 3000);
                    }
                } else {
                    countEl.style.display = 'none';
                    if (labelEl) labelEl.textContent = 'المتجر';
                }

                prevCount = pending;
            })
            .catch(() => {
                const dotEl = document.getElementById('syncDotTopbar');
                if (dotEl) dotEl.style.background = '#ef4444';
            });
    }
    window.checkOnlineOrders = checkOnlineOrders;

    // فحص أولي بعد 2 ثانية من تحميل الصفحة
    setTimeout(checkOnlineOrders, 2000);
    // ثم كل 60 ثانية
    setInterval(checkOnlineOrders, 60000);
})();
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.electronAPI && typeof window.electronAPI.onPrintStatus === 'function') {
        window.electronAPI.onPrintStatus((data) => {
            showGlobalDesktopToast(data.message || 'تمت عملية الطباعة بنجاح ✓', data.success ? 'success' : 'danger');
        });
    }
    if (window.electronAPI && typeof window.electronAPI.onNewOrderReceived === 'function') {
        window.electronAPI.onNewOrderReceived(() => {
            if (typeof window.checkOnlineOrders === 'function') {
                window.checkOnlineOrders();
            }
        });
    }

    // 🖨️ فحص حالة طابعة الفواتير وتحديث شريط الحالة
    if (window.electronAPI && typeof window.electronAPI.getPrinters === 'function') {
        const configuredPrinter = <?= json_encode($settings['default_printer'] ?? '') ?>;
        const badgeEl = document.getElementById('printerStatusBadge');
        const nameEl  = document.getElementById('printerStatusName');
        const stateEl = document.getElementById('printerStatusState');

        if (badgeEl) {
            badgeEl.style.display = 'inline-flex';
            badgeEl.classList.remove('d-none');
        }

        function checkPrinter() {
            window.electronAPI.getPrinters().then(printers => {
                let target = null;
                if (configuredPrinter) {
                    target = printers.find(p => p.name === configuredPrinter);
                } else {
                    target = printers.find(p => p.isDefault);
                }

                if (!target) {
                    if (nameEl) nameEl.textContent = (configuredPrinter || 'طابعة النظام').substring(0, 12);
                    if (stateEl) {
                        stateEl.className = 'badge bg-danger';
                        stateEl.textContent = 'غير متصلة';
                    }
                    return;
                }

                if (nameEl) nameEl.textContent = target.name.substring(0, 12);
                
                const status = target.status;
                const isWindows = navigator.platform.toLowerCase().includes('win');
                
                let isReady = false;
                let statusText = 'غير متصلة';
                
                if (isWindows) {
                    if (status === 0 || status === 11 || status === 10 || status === 9) {
                        isReady = true;
                        statusText = status === 11 ? 'تطبع...' : 'جاهزة';
                    } else {
                        switch(status) {
                            case 1: statusText = 'موقوفة'; break;
                            case 2: statusText = 'خطأ'; break;
                            case 4: statusText = 'انحشار ورق'; break;
                            case 5: statusText = 'بدون ورق'; break;
                            case 8: statusText = 'غير متصلة'; break;
                            default: statusText = 'أوفلاين';
                        }
                    }
                } else {
                    // Linux/CUPS
                    if (status === 3 || status === 4) {
                        isReady = true;
                        statusText = status === 4 ? 'تطبع...' : 'جاهزة';
                    } else if (status === 5) {
                        statusText = 'متوقفة';
                    } else {
                        statusText = 'أوفلاين';
                    }
                }
                
                if (stateEl) {
                    stateEl.className = 'badge ' + (isReady ? 'bg-success' : 'bg-danger');
                    stateEl.textContent = statusText;
                }
            }).catch(err => {
                console.error('Error fetching printers for status bar:', err);
            });
        }

        checkPrinter();
        setInterval(checkPrinter, 15000); // كل 15 ثانية
    }
});

function showGlobalDesktopToast(message, type = 'success') {
    let container = document.getElementById('global-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'global-toast-container';
        container.style.cssText = 'position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:99999;display:flex;flex-direction:column;gap:10px;align-items:center;pointer-events:none';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    const bg = type === 'success' ? '#10b981' : '#ef4444'; // emerald green
    toast.style.cssText = 'pointer-events:auto;background:' + bg + ';color:#fff;padding:12px 24px;border-radius:30px;font-weight:700;font-size:14px;box-shadow:0 10px 25px rgba(0,0,0,.15);opacity:0;transform:translateY(-15px);transition:all .3s ease;direction:rtl;white-space:nowrap';
    toast.textContent = message;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 50);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-15px)';
        setTimeout(() => toast.remove(), 350);
    }, 4500);
}
</script>
</body>
</html>
