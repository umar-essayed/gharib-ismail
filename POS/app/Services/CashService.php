<?php

namespace App\Services;

use App\Core\Database;

class CashService
{
    public static function openShiftForUser(int $userId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM cash_shifts WHERE user_id = :user_id AND status = "open" ORDER BY id DESC LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $shift = $stmt->fetch();

        return $shift ?: null;
    }

    public static function openAnyShift(): ?array
    {
        $stmt = Database::pdo()->query('SELECT * FROM cash_shifts WHERE status = "open" ORDER BY id DESC LIMIT 1');
        $shift = $stmt->fetch();

        return $shift ?: null;
    }

    public static function movement(
        ?int $shiftId,
        ?int $userId,
        string $type,
        string $direction,
        float $amount,
        ?string $refTable,
        ?int $refId,
        ?string $note
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO cash_movements
             (shift_id, user_id, movement_type, direction, amount, reference_table, reference_id, note)
             VALUES
             (:shift_id, :user_id, :movement_type, :direction, :amount, :reference_table, :reference_id, :note)'
        );
        $stmt->execute([
            'shift_id' => $shiftId,
            'user_id' => $userId,
            'movement_type' => $type,
            'direction' => $direction,
            'amount' => $amount,
            'reference_table' => $refTable,
            'reference_id' => $refId,
            'note' => $note,
        ]);
        $movementId = (int) Database::pdo()->lastInsertId();

        if ($shiftId) {
            $operator = $direction === 'in' ? '+' : '-';
            $upd = Database::pdo()->prepare("UPDATE cash_shifts SET expected_balance = expected_balance {$operator} :amount WHERE id = :id");
            $upd->execute(['amount' => $amount, 'id' => $shiftId]);
        }

        return $movementId;
    }
}
