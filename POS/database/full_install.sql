


PRAGMA foreign_keys = OFF;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS failed_logins;
DROP TABLE IF EXISTS keyboard_shortcuts;
DROP TABLE IF EXISTS sale_suspensions;
DROP TABLE IF EXISTS sales_invoice_payments;
DROP TABLE IF EXISTS sales_invoice_items;
DROP TABLE IF EXISTS sales_invoices;
DROP TABLE IF EXISTS purchase_invoice_payments;
DROP TABLE IF EXISTS purchase_invoice_items;
DROP TABLE IF EXISTS purchase_invoices;
DROP TABLE IF EXISTS sales_return_items;
DROP TABLE IF EXISTS sales_returns;
DROP TABLE IF EXISTS purchase_return_items;
DROP TABLE IF EXISTS purchase_returns;
DROP TABLE IF EXISTS inventory_adjustment_items;
DROP TABLE IF EXISTS inventory_adjustments;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS cash_movements;
DROP TABLE IF EXISTS cash_shifts;
DROP TABLE IF EXISTS customer_transactions;
DROP TABLE IF EXISTS supplier_transactions;
DROP TABLE IF EXISTS product_barcodes;
DROP TABLE IF EXISTS promotions;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS product_categories;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS warehouses;
DROP TABLE IF EXISTS branches;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS number_sequences;

PRAGMA foreign_keys = ON;

CREATE TABLE branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    address VARCHAR(255) NULL,
    phone VARCHAR(30) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE warehouses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    name VARCHAR(120) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_warehouses_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(120) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL
);

CREATE TABLE role_permissions (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE failed_logins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(80) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_failed_logins_username ON failed_logins (username);
CREATE INDEX idx_failed_logins_attempted_at ON failed_logins (attempted_at);

CREATE TABLE activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    action VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_activity_logs_action ON activity_logs (action);
CREATE INDEX idx_activity_logs_created_at ON activity_logs (created_at);

CREATE TABLE audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    table_name VARCHAR(120) NOT NULL,
    record_id VARCHAR(64) NOT NULL,
    operation TEXT NOT NULL,
    old_values TEXT NULL,
    new_values TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_audit_logs_table_record ON audit_logs (table_name, record_id);

CREATE TABLE settings (
    "key" VARCHAR(120) PRIMARY KEY,
    "value" TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP 
);

CREATE TABLE number_sequences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    seq_key VARCHAR(80) NOT NULL UNIQUE,
    prefix VARCHAR(20) NOT NULL,
    current_number INTEGER NOT NULL DEFAULT 0,
    pad_length INTEGER NOT NULL DEFAULT 6,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP 
);

CREATE TABLE keyboard_shortcuts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    key_code VARCHAR(50) NOT NULL,
    key_label VARCHAR(100) NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    reference_id INTEGER NULL,
    reference_name VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_keyboard_shortcuts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, key_code)
);

CREATE INDEX idx_user_id ON keyboard_shortcuts (user_id);
CREATE INDEX idx_action_type ON keyboard_shortcuts (action_type);

CREATE TABLE payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE product_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    deleted_at DATETIME NULL,
    UNIQUE (name)
);

CREATE TABLE units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(80) NOT NULL,
    short_name VARCHAR(20) NOT NULL,
    is_weight TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    UNIQUE (name)
);

CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NULL,
    unit_id INTEGER NULL,
    name VARCHAR(190) NOT NULL,
    sku VARCHAR(80) NULL,
    internal_code VARCHAR(80) NULL,
    barcode VARCHAR(80) NULL,
    scale_code VARCHAR(20) NULL,
    allow_scale_barcode TINYINT(1) NOT NULL DEFAULT 0,
    weight_unit TEXT NOT NULL DEFAULT 'kg',
    image_path VARCHAR(255) NULL,
    purchase_price DECIMAL(14,3) NOT NULL DEFAULT 0,
    sale_price DECIMAL(14,3) NOT NULL DEFAULT 0,
    wholesale_price DECIMAL(14,3) NULL,
    min_stock DECIMAL(14,3) NOT NULL DEFAULT 0,
    opening_stock DECIMAL(14,3) NOT NULL DEFAULT 0,
    sell_type TEXT NOT NULL DEFAULT 'piece',
    package_type TEXT NOT NULL DEFAULT 'piece',
    package_size DECIMAL(14,3) NOT NULL DEFAULT 1,
    track_stock TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INTEGER NULL,
    updated_by INTEGER NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_products_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    CONSTRAINT fk_products_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_products_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (scale_code),
    UNIQUE (sku),
    UNIQUE (internal_code)
);
CREATE INDEX idx_products_name ON products (name);
CREATE INDEX idx_products_barcode ON products (barcode);

CREATE TABLE product_barcodes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    barcode VARCHAR(80) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_product_barcodes_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE (barcode)
);
CREATE INDEX idx_product_barcodes_product ON product_barcodes (product_id);

CREATE TABLE promotions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    name VARCHAR(180) NOT NULL,
    discount_type TEXT NOT NULL,
    discount_value DECIMAL(14,3) NOT NULL DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    note VARCHAR(255) NULL,
    created_by INTEGER NULL,
    updated_by INTEGER NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    CONSTRAINT fk_promotions_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_promotions_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_promotions_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_promotions_product_dates ON promotions (product_id, start_date, end_date);
CREATE INDEX idx_promotions_active_dates ON promotions (is_active, start_date, end_date);

CREATE TABLE customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(120) NULL,
    address VARCHAR(255) NULL,
    opening_balance DECIMAL(14,3) NOT NULL DEFAULT 0,
    credit_limit DECIMAL(14,3) NOT NULL DEFAULT 0,
    current_balance DECIMAL(14,3) NOT NULL DEFAULT 0,
    is_cash_customer TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    deleted_at DATETIME NULL
);
CREATE INDEX idx_customers_name ON customers (name);
CREATE INDEX idx_customers_phone ON customers (phone);

CREATE TABLE suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(120) NULL,
    address VARCHAR(255) NULL,
    opening_balance DECIMAL(14,3) NOT NULL DEFAULT 0,
    current_balance DECIMAL(14,3) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    deleted_at DATETIME NULL
);
CREATE INDEX idx_suppliers_name ON suppliers (name);
CREATE INDEX idx_suppliers_phone ON suppliers (phone);

CREATE TABLE cash_shifts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shift_no VARCHAR(40) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL,
    opening_balance DECIMAL(20,3) NOT NULL,
    expected_balance DECIMAL(20,3) NOT NULL DEFAULT 0,
    actual_balance DECIMAL(20,3) NULL,
    difference DECIMAL(20,3) NULL,
    status TEXT NOT NULL DEFAULT 'open',
    opened_at DATETIME NOT NULL,
    closed_at DATETIME NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cash_shifts_user FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE INDEX idx_cash_shifts_status ON cash_shifts (status);

CREATE TABLE cash_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shift_id INTEGER NULL,
    user_id INTEGER NULL,
    movement_type TEXT NOT NULL,
    direction TEXT NOT NULL,
    amount DECIMAL(14,3) NOT NULL,
    reference_table VARCHAR(60) NULL,
    reference_id INTEGER NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cash_movements_shift FOREIGN KEY (shift_id) REFERENCES cash_shifts(id) ON DELETE SET NULL,
    CONSTRAINT fk_cash_movements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_cash_movements_shift ON cash_movements (shift_id);
CREATE INDEX idx_cash_movements_type ON cash_movements (movement_type);

CREATE TABLE sales_invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_no VARCHAR(40) NOT NULL UNIQUE,
    branch_id INTEGER NULL,
    warehouse_id INTEGER NOT NULL,
    shift_id INTEGER NULL,
    user_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    invoice_date DATETIME NOT NULL,
    status TEXT NOT NULL DEFAULT 'posted',
    subtotal DECIMAL(14,3) NOT NULL,
    discount_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    tax_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    grand_total DECIMAL(14,3) NOT NULL,
    paid_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    due_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    payment_status TEXT NOT NULL,
    payment_method_id INTEGER NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    cancelled_at DATETIME NULL,
    cancelled_by INTEGER NULL,
    CONSTRAINT fk_sales_invoices_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    CONSTRAINT fk_sales_invoices_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_sales_invoices_shift FOREIGN KEY (shift_id) REFERENCES cash_shifts(id) ON DELETE SET NULL,
    CONSTRAINT fk_sales_invoices_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_sales_invoices_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_sales_invoices_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
    CONSTRAINT fk_sales_invoices_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_sales_invoices_date ON sales_invoices (invoice_date);
CREATE INDEX idx_sales_invoices_customer ON sales_invoices (customer_id);

CREATE TABLE sales_invoice_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sales_invoice_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    unit_id INTEGER NULL,
    barcode VARCHAR(80) NULL,
    qty DECIMAL(14,3) NOT NULL,
    sale_unit TEXT NOT NULL DEFAULT 'piece',
    stock_qty DECIMAL(14,3) NOT NULL DEFAULT 0,
    unit_price DECIMAL(14,3) NOT NULL,
    discount_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
    line_total DECIMAL(14,3) NOT NULL,
    cost_price DECIMAL(14,3) NOT NULL DEFAULT 0,
    scanned_barcode VARCHAR(80) NULL,
    is_scale_item TINYINT(1) NOT NULL DEFAULT 0,
    scale_weight DECIMAL(14,3) NULL,
    scale_price DECIMAL(14,3) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_items_invoice FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_items_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_sales_items_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
);
CREATE INDEX idx_sales_items_invoice ON sales_invoice_items (sales_invoice_id);
CREATE INDEX idx_sales_items_product ON sales_invoice_items (product_id);

CREATE TABLE sales_invoice_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sales_invoice_id INTEGER NOT NULL,
    payment_method_id INTEGER NOT NULL,
    amount DECIMAL(14,3) NOT NULL,
    reference_no VARCHAR(80) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_payments_invoice FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_payments_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
);

CREATE TABLE scale_barcode_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    barcode VARCHAR(80) NOT NULL,
    product_id INTEGER NULL,
    parsed_item_code VARCHAR(40) NULL,
    parsed_weight DECIMAL(14,3) NULL,
    parsed_price DECIMAL(14,3) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'error',
    error_message VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_scale_barcode_logs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);
CREATE INDEX idx_scale_barcode_logs_created_at ON scale_barcode_logs (created_at);
CREATE INDEX idx_scale_barcode_logs_status ON scale_barcode_logs (status);

CREATE TABLE purchase_invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_no VARCHAR(40) NOT NULL UNIQUE,
    supplier_invoice_no VARCHAR(80) NULL,
    supplier_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    warehouse_id INTEGER NOT NULL,
    invoice_date DATETIME NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    subtotal DECIMAL(14,3) NOT NULL,
    discount_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    tax_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    grand_total DECIMAL(14,3) NOT NULL,
    paid_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    due_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    payment_status TEXT NOT NULL,
    payment_method_id INTEGER NULL,
    approved_at DATETIME NULL,
    approved_by INTEGER NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    CONSTRAINT fk_purchase_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_purchase_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_purchase_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_purchase_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
    CONSTRAINT fk_purchase_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_purchase_invoices_date ON purchase_invoices (invoice_date);
CREATE INDEX idx_purchase_invoices_supplier ON purchase_invoices (supplier_id);

CREATE TABLE purchase_invoice_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_invoice_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    unit_id INTEGER NULL,
    qty DECIMAL(14,3) NOT NULL,
    purchase_unit TEXT NOT NULL DEFAULT 'piece',
    stock_qty DECIMAL(14,3) NOT NULL DEFAULT 0,
    unit_price DECIMAL(14,3) NOT NULL,
    discount_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
    line_total DECIMAL(14,3) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_items_invoice FOREIGN KEY (purchase_invoice_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_purchase_items_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_purchase_items_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
);
CREATE INDEX idx_purchase_items_invoice ON purchase_invoice_items (purchase_invoice_id);
CREATE INDEX idx_purchase_items_product ON purchase_invoice_items (product_id);

CREATE TABLE purchase_invoice_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_invoice_id INTEGER NOT NULL,
    payment_method_id INTEGER NOT NULL,
    amount DECIMAL(14,3) NOT NULL,
    reference_no VARCHAR(80) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_payments_invoice FOREIGN KEY (purchase_invoice_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_purchase_payments_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
);

CREATE TABLE sales_returns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    return_no VARCHAR(40) NOT NULL UNIQUE,
    sales_invoice_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    shift_id INTEGER NULL,
    return_date DATETIME NOT NULL,
    subtotal DECIMAL(14,3) NOT NULL,
    discount_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    tax_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    grand_total DECIMAL(14,3) NOT NULL,
    refund_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    payment_method_id INTEGER NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_returns_invoice FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id),
    CONSTRAINT fk_sales_returns_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_sales_returns_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_sales_returns_shift FOREIGN KEY (shift_id) REFERENCES cash_shifts(id) ON DELETE SET NULL,
    CONSTRAINT fk_sales_returns_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL
);
CREATE INDEX idx_sales_returns_date ON sales_returns (return_date);

CREATE TABLE sales_return_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sales_return_id INTEGER NOT NULL,
    sales_invoice_item_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    qty DECIMAL(14,3) NOT NULL,
    stock_qty DECIMAL(14,3) NOT NULL DEFAULT 0,
    unit_price DECIMAL(14,3) NOT NULL,
    discount_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
    line_total DECIMAL(14,3) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_return_items_return FOREIGN KEY (sales_return_id) REFERENCES sales_returns(id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_return_items_sales_item FOREIGN KEY (sales_invoice_item_id) REFERENCES sales_invoice_items(id),
    CONSTRAINT fk_sales_return_items_product FOREIGN KEY (product_id) REFERENCES products(id)
);
CREATE INDEX idx_sales_return_items_return ON sales_return_items (sales_return_id);

CREATE TABLE purchase_returns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    return_no VARCHAR(40) NOT NULL UNIQUE,
    purchase_invoice_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    supplier_id INTEGER NOT NULL,
    return_date DATETIME NOT NULL,
    subtotal DECIMAL(14,3) NOT NULL,
    discount_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    tax_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    grand_total DECIMAL(14,3) NOT NULL,
    refund_total DECIMAL(14,3) NOT NULL DEFAULT 0,
    payment_method_id INTEGER NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_returns_invoice FOREIGN KEY (purchase_invoice_id) REFERENCES purchase_invoices(id),
    CONSTRAINT fk_purchase_returns_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_purchase_returns_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_purchase_returns_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL
);
CREATE INDEX idx_purchase_returns_date ON purchase_returns (return_date);

CREATE TABLE purchase_return_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_return_id INTEGER NOT NULL,
    purchase_invoice_item_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    qty DECIMAL(14,3) NOT NULL,
    stock_qty DECIMAL(14,3) NOT NULL DEFAULT 0,
    unit_price DECIMAL(14,3) NOT NULL,
    discount_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
    line_total DECIMAL(14,3) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_return_items_return FOREIGN KEY (purchase_return_id) REFERENCES purchase_returns(id) ON DELETE CASCADE,
    CONSTRAINT fk_purchase_return_items_purchase_item FOREIGN KEY (purchase_invoice_item_id) REFERENCES purchase_invoice_items(id),
    CONSTRAINT fk_purchase_return_items_product FOREIGN KEY (product_id) REFERENCES products(id)
);
CREATE INDEX idx_purchase_return_items_return ON purchase_return_items (purchase_return_id);

CREATE TABLE stock_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    warehouse_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    movement_type TEXT NOT NULL,
    qty_in DECIMAL(14,3) NOT NULL DEFAULT 0,
    qty_out DECIMAL(14,3) NOT NULL DEFAULT 0,
    balance_after DECIMAL(14,3) NOT NULL DEFAULT 0,
    unit_cost DECIMAL(14,3) NOT NULL DEFAULT 0,
    reference_table VARCHAR(60) NULL,
    reference_id INTEGER NULL,
    note VARCHAR(255) NULL,
    created_by INTEGER NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_movements_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_stock_movements_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_stock_movements_product_date ON stock_movements (product_id, created_at);
CREATE INDEX idx_stock_movements_reference ON stock_movements (reference_table, reference_id);

CREATE TABLE inventory_adjustments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    adjust_no VARCHAR(40) NOT NULL UNIQUE,
    warehouse_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    adjust_date DATETIME NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_adjustments_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_inventory_adjustments_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE inventory_adjustment_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    inventory_adjustment_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    old_qty DECIMAL(14,3) NOT NULL,
    new_qty DECIMAL(14,3) NOT NULL,
    diff_qty DECIMAL(14,3) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_adjustment_items_adj FOREIGN KEY (inventory_adjustment_id) REFERENCES inventory_adjustments(id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_adjustment_items_product FOREIGN KEY (product_id) REFERENCES products(id)
);
CREATE INDEX idx_inventory_adjustment_items_adj ON inventory_adjustment_items (inventory_adjustment_id);

CREATE TABLE customer_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    transaction_type TEXT NOT NULL,
    reference_table VARCHAR(60) NULL,
    reference_id INTEGER NULL,
    debit DECIMAL(14,3) NOT NULL DEFAULT 0,
    credit DECIMAL(14,3) NOT NULL DEFAULT 0,
    balance_after DECIMAL(14,3) NOT NULL DEFAULT 0,
    note VARCHAR(255) NULL,
    created_by INTEGER NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_customer_transactions_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_customer_transactions_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_customer_transactions_customer_date ON customer_transactions (customer_id, created_at);

CREATE TABLE supplier_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER NOT NULL,
    transaction_type TEXT NOT NULL,
    reference_table VARCHAR(60) NULL,
    reference_id INTEGER NULL,
    debit DECIMAL(14,3) NOT NULL DEFAULT 0,
    credit DECIMAL(14,3) NOT NULL DEFAULT 0,
    balance_after DECIMAL(14,3) NOT NULL DEFAULT 0,
    note VARCHAR(255) NULL,
    created_by INTEGER NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_supplier_transactions_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_supplier_transactions_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_supplier_transactions_supplier_date ON supplier_transactions (supplier_id, created_at);

CREATE TABLE sale_suspensions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hold_no VARCHAR(40) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL,
    customer_id INTEGER NULL,
    payload_json TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sale_suspensions_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_sale_suspensions_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);



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

INSERT INTO settings ("key", "value") VALUES
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
('scale_barcode_prefix', '28'),
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
