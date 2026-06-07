<?php

namespace App\Services;

use App\Core\Database;
use RuntimeException;

class ScaleBarcodeParser
{
    public function isScaleBarcode(string $barcode): bool
    {
        $barcode = $this->normalizeBarcode($barcode);
        if ($barcode === '') {
            return false;
        }

        $config = $this->config();
        if (!$config['enabled']) {
            return false;
        }
        if (strlen($barcode) !== $config['total_length']) {
            return false;
        }
        if (!$this->matchesAnyPrefix($barcode, $config['prefixes'])) {
            return false;
        }
        if (!preg_match('/^\d+$/', $barcode)) {
            return false;
        }

        return true;
    }

    public function parse(string $barcode): array
    {
        $barcode = $this->normalizeBarcode($barcode);
        $config = $this->config();

        if (!$config['enabled']) {
            throw new RuntimeException('ميزة باركود الميزان غير مفعلة من الإعدادات');
        }
        if (strlen($barcode) !== $config['total_length']) {
            throw new RuntimeException('باركود الميزان غير صحيح: الطول غير مطابق للإعدادات');
        }
        if (!preg_match('/^\d+$/', $barcode)) {
            throw new RuntimeException('باركود الميزان يجب أن يحتوي على أرقام فقط');
        }
        if (!$this->matchesAnyPrefix($barcode, $config['prefixes'])) {
            throw new RuntimeException('باركود الميزان لا يطابق البادئة المحددة');
        }

        if ($config['check_digit_enabled'] && !$this->isValidEan13($barcode)) {
            throw new RuntimeException('باركود الميزان غير صحيح (فشل التحقق من رقم المراجعة)');
        }

        $itemCodeRaw = $this->sliceSegment(
            $barcode,
            $config['item_code_start'],
            $config['item_code_length'],
            'كود الصنف'
        );
        $itemCode = ltrim($itemCodeRaw, '0');
        if ($itemCode === '') {
            $itemCode = '0';
        }

        $weight = null;
        $price = null;

        if ($config['mode'] === 'weight') {
            $weightRaw = $this->sliceSegment(
                $barcode,
                $config['weight_start'],
                $config['weight_length'],
                'الوزن'
            );
            $weight = $this->toDecimal($weightRaw, $config['weight_decimals']);
        } else {
            $priceRaw = $this->sliceSegment(
                $barcode,
                $config['price_start'],
                $config['price_length'],
                'السعر'
            );
            $price = $this->toDecimal($priceRaw, $config['price_decimals']);
        }

        return [
            'barcode' => $barcode,
            'item_code_raw' => $itemCodeRaw,
            'item_code' => $itemCode,
            'mode' => $config['mode'],
            'weight' => $weight,
            'price' => $price,
            'config' => $config,
        ];
    }

    public function resolveProduct(array $parsedData): ?array
    {
        $codeRaw = (string) ($parsedData['item_code_raw'] ?? '');
        $codeNormalized = (string) ($parsedData['item_code'] ?? '');
        if ($codeRaw === '' && $codeNormalized === '') {
            return null;
        }

        $db = Database::pdo();
        $candidates = array_values(array_unique(array_filter([$codeRaw, $codeNormalized], static function ($v) {
            return $v !== '';
        })));

        if (!$candidates) {
            return null;
        }

        $placeholders = implode(', ', array_fill(0, count($candidates), '?'));
        $sql = 'SELECT p.*
                FROM products p
                WHERE p.deleted_at IS NULL
                  AND p.is_active = 1
                  AND (
                      p.scale_code IN (' . $placeholders . ')
                      OR p.barcode IN (' . $placeholders . ')
                      OR EXISTS (
                          SELECT 1
                          FROM product_barcodes pb
                          WHERE pb.product_id = p.id
                            AND pb.barcode IN (' . $placeholders . ')
                      )
                  )
                ORDER BY CASE WHEN p.scale_code IN (' . $placeholders . ') THEN 0 ELSE 1 END, p.id ASC
                LIMIT 1';

        $params = array_merge($candidates, $candidates, $candidates, $candidates);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        return $this->resolveProductByBarcodeItemCode($parsedData);
    }

    public function calculateLine(array $product, array $parsedData): array
    {
        $unitPrice = (float) ($product['sale_price'] ?? 0);
        if ($unitPrice <= 0) {
            throw new RuntimeException('لا يمكن بيع الصنف الوزني لأن سعر الكيلو يساوي صفر');
        }

        $config = $parsedData['config'] ?? $this->config();
        $mode = (string) ($parsedData['mode'] ?? $config['mode']);

        $weight = 0.0;
        $price = null;

        if ($mode === 'price') {
            $price = (float) ($parsedData['price'] ?? 0);
            if ($price <= 0) {
                throw new RuntimeException('سعر باركود الميزان غير صحيح');
            }
            $weight = $price / $unitPrice;
        } else {
            $weight = (float) ($parsedData['weight'] ?? 0);
        }

        $weight = round($weight, 3);
        $minWeight = 0.001;
        $maxWeight = $this->maxWeightKg();

        if ($weight < $minWeight) {
            throw new RuntimeException('وزن الصنف أقل من الحد الأدنى المسموح (0.001 كجم)');
        }
        if ($weight > $maxWeight) {
            throw new RuntimeException('وزن الصنف أكبر من الحد الأقصى المسموح (' . number_format($maxWeight, 3) . ' كجم)');
        }

        $lineTotal = round($weight * $unitPrice, 3);

        return [
            'qty' => $weight,
            'unit_price' => round($unitPrice, 3),
            'line_total' => $lineTotal,
            'is_scale_item' => 1,
            'scale_weight' => $weight,
            'scale_price' => $price !== null ? round($price, 3) : null,
        ];
    }

    public function shouldAllowScaleSale(array $product): bool
    {
        $sellType = (string) ($product['sell_type'] ?? 'piece');
        $allowScale = (int) ($product['allow_scale_barcode'] ?? 0) === 1;
        return $sellType === 'weight' || $allowScale;
    }

    public function logParseAttempt(array $data): void
    {
        if (!$this->scaleLogsTableExists()) {
            return;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO scale_barcode_logs
             (barcode, product_id, parsed_item_code, parsed_weight, parsed_price, status, error_message, created_at)
             VALUES (:barcode, :product_id, :parsed_item_code, :parsed_weight, :parsed_price, :status, :error_message, datetime(\'now\'))'
        );
        $stmt->execute([
            'barcode' => (string) ($data['barcode'] ?? ''),
            'product_id' => !empty($data['product_id']) ? (int) $data['product_id'] : null,
            'parsed_item_code' => $data['parsed_item_code'] !== null ? (string) $data['parsed_item_code'] : null,
            'parsed_weight' => $data['parsed_weight'] !== null ? (float) $data['parsed_weight'] : null,
            'parsed_price' => $data['parsed_price'] !== null ? (float) $data['parsed_price'] : null,
            'status' => (string) ($data['status'] ?? 'error'),
            'error_message' => $data['error_message'] !== null ? (string) $data['error_message'] : null,
        ]);
    }

    private function config(): array
    {
        $enabled = SettingsService::get('scale_barcode_enabled', '0') === '1';
        $mode = SettingsService::get('scale_barcode_mode', 'weight');
        if (!in_array($mode, ['weight', 'price'], true)) {
            $mode = 'weight';
        }

        $prefixRaw = trim((string) SettingsService::get('scale_barcode_prefix', '28'));

        return [
            'enabled' => $enabled,
            'prefix' => $prefixRaw,
            'prefixes' => $this->normalizePrefixes($prefixRaw),
            'total_length' => $this->toPositiveInt(SettingsService::get('scale_barcode_total_length', '13'), 13),
            'mode' => $mode,
            'item_code_start' => $this->toPositiveInt(SettingsService::get('scale_item_code_start', '3'), 3),
            'item_code_length' => $this->toPositiveInt(SettingsService::get('scale_item_code_length', '5'), 5),
            'weight_start' => $this->toPositiveInt(SettingsService::get('scale_weight_start', '8'), 8),
            'weight_length' => $this->toPositiveInt(SettingsService::get('scale_weight_length', '5'), 5),
            'weight_decimals' => $this->toNonNegativeInt(SettingsService::get('scale_weight_decimals', '3'), 3),
            'price_start' => $this->toPositiveInt(SettingsService::get('scale_price_start', '8'), 8),
            'price_length' => $this->toPositiveInt(SettingsService::get('scale_price_length', '5'), 5),
            'price_decimals' => $this->toNonNegativeInt(SettingsService::get('scale_price_decimals', '2'), 2),
            'check_digit_enabled' => SettingsService::get('scale_check_digit_enabled', '0') === '1',
        ];
    }

    private function sliceSegment(string $barcode, int $startOneBased, int $length, string $label): string
    {
        $start = $startOneBased - 1;
        if ($start < 0 || $length <= 0) {
            throw new RuntimeException('إعدادات باركود الميزان غير صحيحة في جزء ' . $label);
        }

        $value = substr($barcode, $start, $length);
        if ($value === false || strlen($value) !== $length) {
            throw new RuntimeException('تعذر استخراج ' . $label . ' من باركود الميزان');
        }

        return $value;
    }

    private function toDecimal(string $value, int $decimals): float
    {
        $number = (float) ltrim($value, '0');
        if ($number === 0.0) {
            $number = 0.0;
        }

        if ($decimals <= 0) {
            return $number;
        }

        return $number / (10 ** $decimals);
    }

    private function toPositiveInt(?string $value, int $fallback): int
    {
        $n = (int) $value;
        return $n > 0 ? $n : $fallback;
    }

    private function toNonNegativeInt(?string $value, int $fallback): int
    {
        $n = (int) $value;
        return $n >= 0 ? $n : $fallback;
    }

    private function maxWeightKg(): float
    {
        $configured = (float) SettingsService::get('scale_max_weight_kg', '50');
        if ($configured < 0.001) {
            return 50.0;
        }
        return $configured;
    }

    private function normalizeBarcode(string $barcode): string
    {
        return preg_replace('/\s+/', '', trim($barcode)) ?? '';
    }

    private function normalizePrefixes(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,;|]+/', $raw) ?: [];
        $parts = array_values(array_unique(array_filter(array_map(static function ($value) {
            $candidate = preg_replace('/\D+/', '', (string) $value);
            return $candidate !== null ? trim($candidate) : '';
        }, $parts), static function ($value) {
            return $value !== '';
        })));

        if (count($parts) === 1 && $parts[0] === '28') {
            $parts[] = '20';
        }

        return $parts;
    }

    private function matchesAnyPrefix(string $barcode, array $prefixes): bool
    {
        if (!$prefixes) {
            return true;
        }

        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($barcode, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function resolveProductByBarcodeItemCode(array $parsedData): ?array
    {
        $itemCodeRaw = (string) ($parsedData['item_code_raw'] ?? '');
        if ($itemCodeRaw === '') {
            return null;
        }

        $config = $parsedData['config'] ?? $this->config();
        $totalLength = max(1, (int) ($config['total_length'] ?? 13));
        $itemStart = max(1, (int) ($config['item_code_start'] ?? 3));
        $itemLength = max(1, (int) ($config['item_code_length'] ?? 5));
        $prefixes = array_values(array_filter((array) ($config['prefixes'] ?? []), static function ($prefix) {
            return (string) $prefix !== '';
        }));

        $prefixSql = '';
        $params = [];
        if ($prefixes) {
            $parts = [];
            foreach ($prefixes as $prefix) {
                $parts[] = 'p.barcode LIKE ?';
                $params[] = $prefix . '%';
            }
            $prefixSql = ' AND (' . implode(' OR ', $parts) . ')';
        }

        $sql = 'SELECT p.*
                FROM products p
                WHERE p.deleted_at IS NULL
                  AND p.is_active = 1
                  AND p.barcode IS NOT NULL
                  AND p.barcode <> ""
                  AND LENGTH(p.barcode) = ?
                  AND SUBSTR(p.barcode, ' . $itemStart . ', ' . $itemLength . ') = ?'
                . $prefixSql .
                ' ORDER BY
                    CASE WHEN p.sell_type = "weight" THEN 0 ELSE 1 END,
                    CASE WHEN p.allow_scale_barcode = 1 THEN 0 ELSE 1 END,
                    p.id DESC
                  LIMIT 5';

        array_unshift($params, $totalLength, $itemCodeRaw);
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if (!$rows) {
            return null;
        }

        if (count($rows) === 1) {
            return $rows[0];
        }

        // If many products share same embedded item code, only trust an explicitly enabled one.
        $explicit = array_values(array_filter($rows, static function ($row) {
            return (int) ($row['allow_scale_barcode'] ?? 0) === 1;
        }));
        if (count($explicit) === 1) {
            return $explicit[0];
        }

        return null;
    }

    private function scaleLogsTableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            $db = Database::pdo();
            $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='scale_barcode_logs'");
                $exists = (bool) $stmt->fetchColumn();
            } else {
                $stmt = $db->query("SHOW TABLES LIKE 'scale_barcode_logs'");
                $exists = (bool) $stmt->fetchColumn();
            }
        } catch (\Throwable $e) {
            $exists = false;
        }

        return $exists;
    }

    private function isValidEan13(string $barcode): bool
    {
        if (!preg_match('/^\d{13}$/', $barcode)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $barcode[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit === (int) $barcode[12];
    }
}
