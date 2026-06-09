<?php

use App\Controllers\AuthController;
use App\Controllers\BarcodeController;
use App\Controllers\CashController;
use App\Controllers\CashierKeyboardController;
use App\Controllers\CategoryController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\InventoryController;
use App\Controllers\OnlineOrdersController;
use App\Controllers\OnlineStoreController;
use App\Controllers\PosController;
use App\Controllers\ProductController;
use App\Controllers\ProfileController;
use App\Controllers\PromotionController;
use App\Controllers\PurchaseController;
use App\Controllers\ReportController;
use App\Controllers\ReturnController;
use App\Controllers\RoleController;
use App\Controllers\SalesController;
use App\Controllers\SettingsController;
use App\Controllers\ShiftController;
use App\Controllers\SupplierController;
use App\Controllers\UnitController;
use App\Controllers\UserController;

$router->get('/', function (): void {
    header('Location: ' . url('/dashboard'));
    exit;
});

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);

$router->get('/dashboard', [DashboardController::class, 'index'], ['auth', 'permission:dashboard.view']);
$router->get('/profile', [ProfileController::class, 'index'], ['auth']);

// Cashier Keyboard Shortcuts
$router->get('/cashier-keyboard', [CashierKeyboardController::class, 'index'], ['auth']);
$router->post('/cashier-keyboard', [CashierKeyboardController::class, 'store'], ['auth']);
$router->post('/cashier-keyboard/{id}/update', [CashierKeyboardController::class, 'update'], ['auth']);
$router->post('/cashier-keyboard/{id}/delete', [CashierKeyboardController::class, 'delete'], ['auth']);
$router->post('/cashier-keyboard/{id}/toggle', [CashierKeyboardController::class, 'toggle'], ['auth']);

$router->get('/categories', [CategoryController::class, 'index'], ['auth', 'permission:products.manage']);
$router->post('/categories', [CategoryController::class, 'store'], ['auth', 'permission:products.manage']);
$router->post('/categories/{id}/update', [CategoryController::class, 'update'], ['auth', 'permission:products.manage']);
$router->post('/categories/{id}/delete', [CategoryController::class, 'delete'], ['auth', 'permission:products.manage']);

$router->get('/units', [UnitController::class, 'index'], ['auth', 'permission:products.manage']);
$router->post('/units', [UnitController::class, 'store'], ['auth', 'permission:products.manage']);
$router->post('/units/{id}/update', [UnitController::class, 'update'], ['auth', 'permission:products.manage']);

$router->get('/products', [ProductController::class, 'index'], ['auth', 'permission:products.manage']);
$router->get('/products/create', [ProductController::class, 'create'], ['auth', 'permission:products.manage']);
$router->post('/products', [ProductController::class, 'store'], ['auth', 'permission:products.manage']);
$router->get('/products/{id}/edit', [ProductController::class, 'edit'], ['auth', 'permission:products.manage']);
$router->post('/products/{id}/update', [ProductController::class, 'update'], ['auth', 'permission:products.manage']);
$router->post('/products/{id}/delete', [ProductController::class, 'delete'], ['auth', 'permission:products.manage']);

$router->get('/promotions', [PromotionController::class, 'index'], ['auth', 'permission:promotions.manage']);
$router->post('/promotions', [PromotionController::class, 'store'], ['auth', 'permission:promotions.manage']);
$router->post('/promotions/{id}/update', [PromotionController::class, 'update'], ['auth', 'permission:promotions.manage']);
$router->post('/promotions/{id}/delete', [PromotionController::class, 'delete'], ['auth', 'permission:promotions.manage']);

$router->get('/customers', [CustomerController::class, 'index'], ['auth', 'permission:customers.manage']);
$router->get('/customers/create', [CustomerController::class, 'create'], ['auth', 'permission:customers.manage']);
$router->post('/customers', [CustomerController::class, 'store'], ['auth', 'permission:customers.manage']);
$router->get('/customers/{id}/edit', [CustomerController::class, 'edit'], ['auth', 'permission:customers.manage']);
$router->post('/customers/{id}/update', [CustomerController::class, 'update'], ['auth', 'permission:customers.manage']);
$router->post('/customers/{id}/delete', [CustomerController::class, 'delete'], ['auth', 'permission:customers.manage']);
$router->get('/customers/{id}/statement', [CustomerController::class, 'statement'], ['auth', 'permission:customers.manage']);
$router->get('/customers/{id}/receipt', [CustomerController::class, 'receiptForm'], ['auth', 'permission:customers.manage']);
$router->post('/customers/{id}/receipt', [CustomerController::class, 'receiptStore'], ['auth', 'permission:customers.manage']);

$router->get('/suppliers', [SupplierController::class, 'index'], ['auth', 'permission:suppliers.manage']);
$router->get('/suppliers/create', [SupplierController::class, 'create'], ['auth', 'permission:suppliers.manage']);
$router->post('/suppliers', [SupplierController::class, 'store'], ['auth', 'permission:suppliers.manage']);
$router->get('/suppliers/{id}/edit', [SupplierController::class, 'edit'], ['auth', 'permission:suppliers.manage']);
$router->post('/suppliers/{id}/update', [SupplierController::class, 'update'], ['auth', 'permission:suppliers.manage']);
$router->post('/suppliers/{id}/delete', [SupplierController::class, 'delete'], ['auth', 'permission:suppliers.manage']);
$router->get('/suppliers/{id}/statement', [SupplierController::class, 'statement'], ['auth', 'permission:suppliers.manage']);
$router->get('/suppliers/{id}/payment', [SupplierController::class, 'paymentForm'], ['auth', 'permission:suppliers.manage']);
$router->post('/suppliers/{id}/payment', [SupplierController::class, 'paymentStore'], ['auth', 'permission:suppliers.manage']);

$router->get('/pos', [PosController::class, 'index'], ['auth', 'permission:pos.sell']);
$router->get('/pos/search', [PosController::class, 'search'], ['auth', 'permission:pos.sell']);
$router->post('/pos/sell', [PosController::class, 'sell'], ['auth', 'permission:pos.sell']);
$router->post('/pos/hold', [PosController::class, 'hold'], ['auth', 'permission:pos.sell']);
$router->get('/pos/suspended', [PosController::class, 'suspended'], ['auth', 'permission:pos.sell']);
$router->get('/pos/suspended/{id}/resume', [PosController::class, 'resume'], ['auth', 'permission:pos.sell']);
$router->post('/pos/suspended/{id}/delete', [PosController::class, 'removeSuspended'], ['auth', 'permission:pos.sell']);

// Online Orders — لوحة إدارة طلبات المتجر الإلكتروني
$router->get('/online-orders', [OnlineOrdersController::class, 'index'], ['auth', 'permission:pos.sell']);
$router->get('/online-orders/accepted', [OnlineOrdersController::class, 'accepted'], ['auth', 'permission:pos.sell']);
$router->post('/online-orders/full-sync', [OnlineOrdersController::class, 'fullSync'], ['auth', 'permission:products.manage']);
$router->post('/online-orders/process-queue', [OnlineOrdersController::class, 'processQueue'], ['auth', 'permission:pos.sell']);
$router->post('/online-orders/{id}/accept', [OnlineOrdersController::class, 'accept'], ['auth', 'permission:pos.sell']);
$router->post('/online-orders/{id}/cancel', [OnlineOrdersController::class, 'cancel'], ['auth', 'permission:pos.sell']);
$router->post('/online-orders/{id}/ship', [OnlineOrdersController::class, 'ship'], ['auth', 'permission:pos.sell']);
$router->post('/online-orders/{id}/complete', [OnlineOrdersController::class, 'complete'], ['auth', 'permission:pos.sell']);
$router->get('/api/online-orders/status', [OnlineOrdersController::class, 'syncStatus'], ['auth']);
$router->get('/api/online-orders/pending', [OnlineOrdersController::class, 'pendingOrders'], ['auth']);
$router->post('/api/webhook/new-order', [OnlineOrdersController::class, 'handleNewOrderWebhook']);

// E-commerce Store Management — المتجر الإلكتروني
$router->get('/api/online-store/stats', [OnlineStoreController::class, 'apiStats'], ['auth']);
$router->get('/online-store/users', [OnlineStoreController::class, 'storeUsers'], ['auth', 'permission:users.manage']);
$router->post('/online-store/users/update-points', [OnlineStoreController::class, 'updateUserPoints'], ['auth', 'permission:users.manage']);
$router->post('/online-store/users/delete', [OnlineStoreController::class, 'deleteStoreUser'], ['auth', 'permission:users.manage']);

$router->get('/online-store/promotions', [OnlineStoreController::class, 'storePromotions'], ['auth', 'permission:promotions.manage']);
$router->post('/online-store/banners/create', [OnlineStoreController::class, 'createBanner'], ['auth', 'permission:promotions.manage']);
$router->post('/online-store/banners/delete', [OnlineStoreController::class, 'deleteBanner'], ['auth', 'permission:promotions.manage']);
$router->post('/online-store/coupons/create', [OnlineStoreController::class, 'createCoupon'], ['auth', 'permission:promotions.manage']);
$router->post('/online-store/coupons/toggle', [OnlineStoreController::class, 'toggleCoupon'], ['auth', 'permission:promotions.manage']);
$router->post('/online-store/coupons/delete', [OnlineStoreController::class, 'deleteCoupon'], ['auth', 'permission:promotions.manage']);

$router->get('/online-store/shipping', [OnlineStoreController::class, 'storeShipping'], ['auth', 'permission:settings.manage']);
$router->post('/online-store/shipping/update', [OnlineStoreController::class, 'updateShippingSettings'], ['auth', 'permission:settings.manage']);
$router->post('/online-store/shipping/zones/add', [OnlineStoreController::class, 'addShippingZone'], ['auth', 'permission:settings.manage']);
$router->post('/online-store/shipping/zones/delete', [OnlineStoreController::class, 'deleteShippingZone'], ['auth', 'permission:settings.manage']);

$router->get('/online-store/analytics', [OnlineStoreController::class, 'analytics'], ['auth', 'permission:reports.view']);
$router->get('/api/online-store/analytics', [OnlineStoreController::class, 'apiAnalytics'], ['auth', 'permission:reports.view']);

$router->get('/sales', [SalesController::class, 'index'], ['auth', 'permission:sales.manage']);
$router->get('/sales/{id}', [SalesController::class, 'show'], ['auth', 'permission:sales.manage']);
$router->get('/sales/{id}/print', [SalesController::class, 'print'], ['auth', 'permission:sales.manage']);
$router->post('/sales/{id}/cancel', [SalesController::class, 'cancel'], ['auth', 'permission:sales.cancel']);

$router->get('/purchases', [PurchaseController::class, 'index'], ['auth', 'permission:purchases.manage']);
$router->get('/purchases/products/search', [PurchaseController::class, 'searchProducts'], ['auth', 'permission:purchases.manage']);
$router->get('/purchases/create', [PurchaseController::class, 'create'], ['auth', 'permission:purchases.manage']);
$router->post('/purchases', [PurchaseController::class, 'store'], ['auth', 'permission:purchases.manage']);
$router->get('/purchases/{id}/edit', [PurchaseController::class, 'edit'], ['auth', 'permission:purchases.manage']);
$router->post('/purchases/{id}/update', [PurchaseController::class, 'update'], ['auth', 'permission:purchases.manage']);
$router->get('/purchases/{id}', [PurchaseController::class, 'show'], ['auth', 'permission:purchases.manage']);
$router->get('/purchases/{id}/print', [PurchaseController::class, 'print'], ['auth', 'permission:purchases.manage']);
$router->post('/purchases/{id}/approve', [PurchaseController::class, 'approve'], ['auth', 'permission:purchases.approve']);

$router->get('/returns/sales', [ReturnController::class, 'salesIndex'], ['auth', 'permission:returns.manage']);
$router->get('/returns/sales/create', [ReturnController::class, 'salesCreate'], ['auth', 'permission:returns.manage']);
$router->get('/returns/sales/items/{invoiceId}', [ReturnController::class, 'salesItems'], ['auth', 'permission:returns.manage']);
$router->post('/returns/sales', [ReturnController::class, 'salesStore'], ['auth', 'permission:returns.manage']);
$router->get('/returns/sales/{id}', [ReturnController::class, 'salesShow'], ['auth', 'permission:returns.manage']);
$router->get('/returns/sales/{id}/print', [ReturnController::class, 'salesPrint'], ['auth', 'permission:returns.manage']);

$router->get('/returns/purchases', [ReturnController::class, 'purchasesIndex'], ['auth', 'permission:returns.manage']);
$router->get('/returns/purchases/create', [ReturnController::class, 'purchasesCreate'], ['auth', 'permission:returns.manage']);
$router->get('/returns/purchases/items/{invoiceId}', [ReturnController::class, 'purchaseItems'], ['auth', 'permission:returns.manage']);
$router->post('/returns/purchases', [ReturnController::class, 'purchasesStore'], ['auth', 'permission:returns.manage']);
$router->get('/returns/purchases/{id}', [ReturnController::class, 'purchasesShow'], ['auth', 'permission:returns.manage']);
$router->get('/returns/purchases/{id}/print', [ReturnController::class, 'purchasesPrint'], ['auth', 'permission:returns.manage']);

$router->get('/inventory/stock', [InventoryController::class, 'stock'], ['auth', 'permission:inventory.manage']);
$router->get('/inventory/movements', [InventoryController::class, 'movements'], ['auth', 'permission:inventory.manage']);
$router->get('/inventory/adjustments', [InventoryController::class, 'adjustments'], ['auth', 'permission:inventory.manage']);
$router->post('/inventory/adjustments', [InventoryController::class, 'adjustStore'], ['auth', 'permission:inventory.manage']);

$router->get('/shifts', [ShiftController::class, 'index'], ['auth', 'permission:shifts.manage']);
$router->post('/shifts/open', [ShiftController::class, 'open'], ['auth', 'permission:shifts.manage']);
$router->post('/shifts/{id}/close', [ShiftController::class, 'close'], ['auth', 'permission:shifts.manage']);
$router->get('/shifts/{id}/report', [ShiftController::class, 'report'], ['auth', 'permission:shifts.manage']);

$router->get('/cash', [CashController::class, 'index'], ['auth', 'permission:cash.manage']);
$router->post('/cash', [CashController::class, 'store'], ['auth', 'permission:cash.manage']);

$router->get('/users', [UserController::class, 'index'], ['auth', 'permission:users.manage']);
$router->post('/users', [UserController::class, 'store'], ['auth', 'permission:users.manage']);
$router->post('/users/{id}/update', [UserController::class, 'update'], ['auth', 'permission:users.manage']);
$router->post('/users/{id}/delete', [UserController::class, 'delete'], ['auth', 'permission:users.manage']);

$router->get('/roles', [RoleController::class, 'index'], ['auth', 'permission:roles.manage']);
$router->get('/roles/create', [RoleController::class, 'create'], ['auth', 'permission:roles.manage']);
$router->post('/roles', [RoleController::class, 'store'], ['auth', 'permission:roles.manage']);
$router->get('/roles/{id}/edit', [RoleController::class, 'edit'], ['auth', 'permission:roles.manage']);
$router->post('/roles/{id}/update', [RoleController::class, 'update'], ['auth', 'permission:roles.manage']);

$router->get('/reports', [ReportController::class, 'index'], ['auth', 'permission:reports.view']);

$router->get('/settings', [SettingsController::class, 'index'], ['auth', 'permission:settings.manage']);
$router->post('/settings', [SettingsController::class, 'save'], ['auth', 'permission:settings.manage']);
$router->post('/settings/backup', [SettingsController::class, 'backupDownload'], ['auth', 'permission:settings.manage']);
$router->post('/settings/backup/restore', [SettingsController::class, 'backupRestore'], ['auth', 'permission:settings.manage']);
$router->post('/settings/danger-reset', [SettingsController::class, 'dangerReset'], ['auth', 'permission:settings.manage']);

$router->get('/barcode', [BarcodeController::class, 'index'], ['auth', 'permission:barcode.print']);
$router->post('/barcode/print', [BarcodeController::class, 'print'], ['auth', 'permission:barcode.print']);

// API Endpoints
$router->get('/api/cashier-keyboard', [CashierKeyboardController::class, 'apiList'], ['auth']);
$router->get('/api/settings', function (): void {
    header('Content-Type: application/json');
    echo json_encode(\App\Services\SettingsService::all());
    exit;
});
$router->post('/api/settings/save-tunnel', function (): void {
    validate_csrf_or_abort();
    $token = trim((string) input('token', ''));
    $domain = trim((string) input('domain', ''));
    $printer = trim((string) input('printer', ''));
    $labelPrinter = trim((string) input('label_printer', ''));

    \App\Services\SettingsService::set('cloudflare_tunnel_token', $token);
    \App\Services\SettingsService::set('cloudflare_tunnel_domain', $domain);
    \App\Services\SettingsService::set('default_printer', $printer);
    \App\Services\SettingsService::set('label_printer', $labelPrinter);

    // تحديث رابط الويب هوك في Supabase تلقائياً
    \App\Services\SupabaseSyncService::updateWebhookUrl($domain);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
});

