ALTER TABLE sales_invoice_items
    ADD COLUMN IF NOT EXISTS sale_unit ENUM('piece','box','kg','sack') NOT NULL DEFAULT 'piece' AFTER qty,
    ADD COLUMN IF NOT EXISTS stock_qty DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER sale_unit;

ALTER TABLE purchase_invoice_items
    ADD COLUMN IF NOT EXISTS purchase_unit ENUM('piece','box','kg','sack') NOT NULL DEFAULT 'piece' AFTER qty,
    ADD COLUMN IF NOT EXISTS stock_qty DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER purchase_unit;

ALTER TABLE sales_return_items
    ADD COLUMN IF NOT EXISTS stock_qty DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER qty;

ALTER TABLE purchase_return_items
    ADD COLUMN IF NOT EXISTS stock_qty DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER qty;

UPDATE sales_invoice_items SET stock_qty = qty WHERE stock_qty = 0;
UPDATE purchase_invoice_items SET stock_qty = qty WHERE stock_qty = 0;
UPDATE sales_return_items SET stock_qty = qty WHERE stock_qty = 0;
UPDATE purchase_return_items SET stock_qty = qty WHERE stock_qty = 0;
