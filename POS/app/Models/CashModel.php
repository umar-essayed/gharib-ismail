<?php

namespace App\Models;

use App\Core\Model;
use App\Services\AuthService;
use App\Services\CashService;

class CashModel extends Model
{
    public function list(array $filters = []): array
    {
        $sql = 'SELECT c.*, u.full_name AS user_name, s.shift_no
                FROM cash_movements c
                LEFT JOIN users u ON u.id = c.user_id
                LEFT JOIN cash_shifts s ON s.id = c.shift_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(c.created_at) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(c.created_at) <= :to';
            $params['to'] = $filters['to'];
        }

        $sql .= ' ORDER BY c.id DESC LIMIT 500';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function createManual(array $data): void
    {
        $userId = (int) AuthService::id();
        $openShift = $this->db->prepare('SELECT id FROM cash_shifts WHERE user_id = :user_id AND status="open" ORDER BY id DESC LIMIT 1');
        $openShift->execute(['user_id' => $userId]);
        $shiftId = $openShift->fetchColumn();

        $direction = in_array($data['movement_type'], ['deposit', 'supplier_refund', 'purchase_return_receipt'], true) ? 'in' : 'out';

        CashService::movement(
            $shiftId ? (int) $shiftId : null,
            $userId,
            $data['movement_type'],
            $direction,
            (float) $data['amount'],
            null,
            null,
            $data['note'] ?? null
        );
    }
}
