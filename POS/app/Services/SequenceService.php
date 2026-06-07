<?php

namespace App\Services;

use App\Core\Database;
use RuntimeException;

class SequenceService
{
    public static function next(string $key): string
    {
        $db = Database::pdo();

        $stmt = $db->prepare('SELECT * FROM number_sequences WHERE seq_key = :seq_key');
        $stmt->execute(['seq_key' => $key]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Sequence not found: ' . $key);
        }

        $next = (int) $row['current_number'] + 1;
        $update = $db->prepare('UPDATE number_sequences SET current_number = :num WHERE id = :id');
        $update->execute(['num' => $next, 'id' => $row['id']]);

        return $row['prefix'] . str_pad((string) $next, (int) $row['pad_length'], '0', STR_PAD_LEFT);
    }
}
