SET NAMES utf8mb4;

INSERT INTO branches (name, code, address, phone) VALUES
('الفرع الرئيسي', 'MAIN', 'العنوان الرئيسي', '01000000000');

INSERT INTO warehouses (branch_id, name, is_default) VALUES
(1, 'المخزن الرئيسي', 1);

INSERT INTO roles (name, description, is_system, is_active) VALUES
('admin', 'مدير النظام الكامل', 1, 1),
('cashier', 'كاشير', 1, 1),
('manager', 'مدير الفرع', 1, 1);

INSERT INTO permissions (code, name) VALUES
('dashboard.view', 'عرض لوحة التحكم'),
('products.manage', 'إدارة المنتجات'),
('promotions.manage', 'إدارة العروض والخصومات'),
('customers.manage', 'إدارة العملاء'),
('suppliers.manage', 'إدارة الموردين'),
('sales.manage', 'إدارة المبيعات'),
('purchases.manage', 'إدارة المشتريات'),
('returns.manage', 'إدارة المرتجعات'),
('inventory.manage', 'إدارة المخزون'),
('shifts.manage', 'إدارة الشيفتات'),
('cash.manage', 'إدارة الصندوق'),
('users.manage', 'إدارة المستخدمين'),
('roles.manage', 'إدارة الأدوار والصلاحيات'),
('reports.view', 'عرض التقارير'),
('settings.manage', 'إدارة الإعدادات'),
('barcode.print', 'طباعة ملصقات الباركود'),
('sales.cancel', 'إلغاء فاتورة بيع'),
('purchases.approve', 'اعتماد فاتورة شراء'),
('pos.sell', 'البيع عبر شاشة POS'),
('pos.modify_price', 'تعديل السعر في POS'),
('pos.modify_discount', 'تعديل الخصم في POS');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 1 AS role_id, id AS permission_id FROM permissions;

INSERT INTO role_permissions (role_id, permission_id)
SELECT 2 AS role_id, id AS permission_id
FROM permissions
WHERE code IN (
    'dashboard.view',
    'sales.manage',
    'returns.manage',
    'inventory.manage',
    'cash.manage',
    'pos.sell'
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 3 AS role_id, id AS permission_id
FROM permissions
WHERE code IN (
    'dashboard.view',
    'products.manage',
    'promotions.manage',
    'customers.manage',
    'suppliers.manage',
    'sales.manage',
    'purchases.manage',
    'returns.manage',
    'inventory.manage',
    'shifts.manage',
    'cash.manage',
    'reports.view',
    'barcode.print',
    'pos.sell',
    'purchases.approve'
);

INSERT INTO users (role_id, username, full_name, password_hash, email, phone, is_active)
VALUES
(1, 'admin', 'مدير النظام', '$2y$10$/L3VnXbWrIqJIf1h19AQS.NV0oaz0Wpantx1pUWcK4u.S0KyqbGG2', 'admin@local.test', '01000000000', 1),
(2, 'cashier', 'كاشير افتراضي', '$2y$10$/L3VnXbWrIqJIf1h19AQS.NV0oaz0Wpantx1pUWcK4u.S0KyqbGG2', 'cashier@local.test', '01000000001', 1);

INSERT INTO payment_methods (code, name, is_default, is_active) VALUES
('cash', 'نقدي', 1, 1),
('card', 'بطاقة', 0, 1),
('credit', 'آجل', 0, 1),
('mixed', 'مختلط', 0, 1);

INSERT INTO settings (`key`, `value`) VALUES
('company_name', 'متجر POSG'),
('company_phone', '01286868676'),
('company_address', 'العنوان الرئيسي'),
('tax_number', '123456789'),
('currency', 'ج.م'),
('invoice_footer', 'صل ع النبي'),
('receipt_print_mode', 'thermal'),
('default_branch_id', '1'),
('default_warehouse_id', '1'),
('require_shift_for_sale', '1'),
('allow_negative_stock', '0'),
('logo_path', ''),
('low_stock_alert_enabled', '1'),
('scale_barcode_enabled', '0'),
('scale_barcode_prefix', '20,28'),
('scale_barcode_total_length', '13'),
('scale_barcode_mode', 'weight'),
('scale_item_code_start', '3'),
('scale_item_code_length', '5'),
('scale_weight_start', '8'),
('scale_weight_length', '5'),
('scale_weight_decimals', '3'),
('scale_price_start', '8'),
('scale_price_length', '5'),
('scale_price_decimals', '2'),
('scale_check_digit_enabled', '0'),
('scale_max_weight_kg', '50');

INSERT INTO number_sequences (seq_key, prefix, current_number, pad_length) VALUES
('sales_invoice', 'SAL', 0, 6),
('purchase_invoice', 'PUR', 0, 6),
('sales_return', 'SRT', 0, 6),
('purchase_return', 'PRT', 0, 6),
('cash_shift', 'SHF', 0, 5),
('inventory_adjustment', 'ADJ', 0, 6),
('sale_hold', 'HLD', 0, 6);

INSERT INTO product_categories (name, description, is_active) VALUES
('مشروبات', 'منتجات المشروبات', 1),
('مواد غذائية', 'منتجات غذائية', 1),
('منظفات', 'منتجات النظافة', 1);

INSERT INTO units (name, short_name, is_weight, is_active) VALUES
('قطعة', 'قطعة', 0, 1),
('كيلوجرام', 'كجم', 1, 1),
('لتر', 'لتر', 0, 1);

INSERT INTO products (
    category_id, unit_id, name, sku, internal_code, barcode,
    purchase_price, sale_price, wholesale_price, min_stock, opening_stock,
    sell_type, allow_scale_barcode, scale_code, weight_unit, track_stock, is_active, created_by
) VALUES
(1, 3, 'مياه معدنية 1.5 لتر', 'SKU-1001', 'P-1001', '6221111111111', 6.500, 8.000, 7.500, 20, 100, 'piece', 0, NULL, 'kg', 1, 1, 1),
(2, 1, 'أرز 1 كجم', 'SKU-1002', 'P-1002', '6222222222222', 22.000, 27.000, 25.500, 10, 50, 'piece', 0, NULL, 'kg', 1, 1, 1),
(2, 2, 'تفاح ميزان', 'SKU-1003', 'P-1003', '2800000000001', 35.000, 45.000, 42.000, 5, 30, 'weight', 1, '10003', 'kg', 1, 1, 1);

INSERT INTO product_barcodes (product_id, barcode, is_primary) VALUES
(1, '6221111111111', 1),
(2, '6222222222222', 1),
(3, '2800000000001', 1);

INSERT INTO customers (name, phone, opening_balance, current_balance, credit_limit, is_cash_customer, is_active)
VALUES
('عميل نقدي', '0000', 0, 0, 0, 1, 1),
('عميل تجزئة', '01012345678', 0, 0, 500, 0, 1);

INSERT INTO suppliers (name, phone, opening_balance, current_balance, is_active)
VALUES
('مورد عام', '01099999999', 0, 0, 1),
('مورد مشروبات', '01088888888', 0, 0, 1);

INSERT INTO stock_movements (
    warehouse_id, product_id, movement_type, qty_in, qty_out, balance_after,
    unit_cost, reference_table, reference_id, note, created_by
)
VALUES
(1, 1, 'initial', 100, 0, 100, 6.500, 'products', 1, 'رصيد افتتاحي', 1),
(1, 2, 'initial', 50, 0, 50, 22.000, 'products', 2, 'رصيد افتتاحي', 1),
(1, 3, 'initial', 30, 0, 30, 35.000, 'products', 3, 'رصيد افتتاحي', 1);
