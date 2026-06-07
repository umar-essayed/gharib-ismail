ALTER TABLE products
    ADD COLUMN IF NOT EXISTS package_type ENUM('piece','box','kg','sack') NOT NULL DEFAULT 'piece' AFTER sell_type,
    ADD COLUMN IF NOT EXISTS package_size DECIMAL(14,3) NOT NULL DEFAULT 1 AFTER package_type;
