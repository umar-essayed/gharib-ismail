<?php

namespace App\Models;

use App\Core\Model;
use App\Services\AuthService;
use App\Services\LogService;
use App\Services\SequenceService;
use RuntimeException;

class ShiftModel extends Model
{
    public function list(): array
    {
        return $this->db->query(
            'SELECT s.*, u.full_name AS user_name
             FROM cash_shifts s
             JOIN users u ON u.id = s.user_id
             ORDER BY s.id DESC LIMIT 300'
        )->fetchAll();
    }

    public function openForUser(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cash_shifts WHERE user_id = :user_id AND status = "open" ORDER BY id DESC LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function open(float $openingBalance, ?string $note): array
    {
        $userId = (int) AuthService::id();
        if ($this->openForUser($userId)) {
            throw new RuntimeException('لديك شيفت مفتوح بالفعل');
        }

        $this->db->beginTransaction();
        try {
            $shiftNo = SequenceService::next('cash_shift');
            $stmt = $this->db->prepare(
                'INSERT INTO cash_shifts (shift_no, user_id, opening_balance, expected_balance, status, opened_at, note)
                 VALUES (:shift_no, :user_id, :opening_balance, :expected_balance, "open", datetime(\'now\'), :note)'
            );
            $stmt->execute([
                'shift_no' => $shiftNo,
                'user_id' => $userId,
                'opening_balance' => $openingBalance,
                'expected_balance' => $openingBalance,
                'note' => $note,
            ]);
            $id = (int) $this->db->lastInsertId();

            LogService::audit('cash_shifts', $id, 'insert', null, ['shift_no' => $shiftNo]);
            $this->db->commit();
            return ['id' => $id, 'shift_no' => $shiftNo];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function close(int $shiftId, float $actualBalance, ?string $note): void
    {
        $shift = $this->find($shiftId);
        if (!$shift) {
            throw new RuntimeException('الشيفت غير موجود');
        }
        if ($shift['status'] !== 'open') {
            throw new RuntimeException('الشيفت مغلق بالفعل');
        }

        $expected = (float) $shift['expected_balance'];
        $difference = $actualBalance - $expected;

        $stmt = $this->db->prepare(
            'UPDATE cash_shifts
             SET status = "closed", closed_at = datetime(\'now\'), actual_balance = :actual_balance, difference = :difference, note = COALESCE(note, "") || :close_note
             WHERE id = :id'
        );
        $stmt->execute([
            'actual_balance' => $actualBalance,
            'difference' => $difference,
            'close_note' => $note ? ' | إقفال: ' . $note : '',
            'id' => $shiftId,
        ]);

        LogService::audit('cash_shifts', $shiftId, 'close_shift', ['status' => 'open'], ['status' => 'closed', 'difference' => $difference]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT s.*, u.full_name AS user_name FROM cash_shifts s JOIN users u ON u.id = s.user_id WHERE s.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function report(int $id): array
    {
        $shift = $this->find($id);
        if (!$shift) {
            throw new RuntimeException('الشيفت غير موجود');
        }

        $mov = $this->db->prepare('SELECT * FROM cash_movements WHERE shift_id = :id ORDER BY id DESC');
        $mov->execute(['id' => $id]);
        $shift['movements'] = $mov->fetchAll();

        $sales = $this->db->prepare('SELECT invoice_no, grand_total, paid_total FROM sales_invoices WHERE shift_id = :id ORDER BY id DESC');
        $sales->execute(['id' => $id]);
        $shift['sales'] = $sales->fetchAll();

        return $shift;
    }
}
