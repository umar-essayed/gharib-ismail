<?php

namespace App\Services;

use App\Core\Database;

class SettingsService
{
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT `key`, `value` FROM settings');
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['key']] = $row['value'];
        }

        return $settings;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $stmt = Database::pdo()->prepare('SELECT `value` FROM settings WHERE `key` = :key');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        return $value === false ? $default : (string) $value;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO settings (`key`, `value`) VALUES (:key, :value)
             ON CONFLICT(`key`) DO UPDATE SET `value` = excluded.value'
        );
        $stmt->execute(['key' => $key, 'value' => $value]);
    }
}
