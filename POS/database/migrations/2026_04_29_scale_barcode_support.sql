-- Safe migration for Barcode Scale support.
-- No DROP statements are used.
-- This script is intended to be executed via: database/migrate_scale_barcode.php

ALTER TABLE products
    MODIFY COLUMN sell_type ENUM('piece','weight') NOT NULL DEFAULT 'piece';

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS scale_code VARCHAR(20) NULL AFTER barcode,
    ADD COLUMN IF NOT EXISTS allow_scale_barcode TINYINT(1) NOT NULL DEFAULT 0 AFTER scale_code,
    ADD COLUMN IF NOT EXISTS weight_unit ENUM('kg','g') NOT NULL DEFAULT 'kg' AFTER allow_scale_barcode;

SET @scale_code_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'products'
      AND index_name = 'uq_products_scale_code'
);
SET @scale_code_duplicates := (
    SELECT COUNT(*)
    FROM (
        SELECT scale_code
        FROM products
        WHERE scale_code IS NOT NULL AND scale_code <> ''
        GROUP BY scale_code
        HAVING COUNT(*) > 1
    ) dup
);
SET @sql_scale_code_index := IF(
    @scale_code_index_exists = 0 AND @scale_code_duplicates = 0,
    'ALTER TABLE products ADD UNIQUE KEY uq_products_scale_code (scale_code)',
    'DO 1'
);
PREPARE stmt_scale_code_index FROM @sql_scale_code_index;
EXECUTE stmt_scale_code_index;
DEALLOCATE PREPARE stmt_scale_code_index;

ALTER TABLE sales_invoice_items
    MODIFY COLUMN qty DECIMAL(14,3) NOT NULL;

ALTER TABLE sales_invoice_items
    ADD COLUMN IF NOT EXISTS scanned_barcode VARCHAR(80) NULL AFTER cost_price,
    ADD COLUMN IF NOT EXISTS is_scale_item TINYINT(1) NOT NULL DEFAULT 0 AFTER scanned_barcode,
    ADD COLUMN IF NOT EXISTS scale_weight DECIMAL(14,3) NULL AFTER is_scale_item,
    ADD COLUMN IF NOT EXISTS scale_price DECIMAL(14,3) NULL AFTER scale_weight;

CREATE TABLE IF NOT EXISTS scale_barcode_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(80) NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    parsed_item_code VARCHAR(40) NULL,
    parsed_weight DECIMAL(14,3) NULL,
    parsed_price DECIMAL(14,3) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'error',
    error_message VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_scale_barcode_logs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_scale_barcode_logs_created_at (created_at),
    INDEX idx_scale_barcode_logs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, `value`) VALUES
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
('scale_max_weight_kg', '50')
ON DUPLICATE KEY UPDATE `value` = `value`;
