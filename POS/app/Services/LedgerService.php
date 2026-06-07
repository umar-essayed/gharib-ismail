<?php

namespace App\Services;

use App\Core\Database;

class LedgerService
{
    public static function customer(
        int $customerId,
        string $type,
        float $debit,
        float $credit,
        ?string $refTable,
        ?int $refId,
        ?string $note
    ): void {
        $current = self::customerBalance($customerId);
        $new = $current + $debit - $credit;

        $stmt = Database::pdo()->prepare(
            'INSERT INTO customer_transactions
             (customer_id, transaction_type, reference_table, reference_id, debit, credit, balance_after, note, created_by)
             VALUES
             (:customer_id, :transaction_type, :reference_table, :reference_id, :debit, :credit, :balance_after, :note, :created_by)'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'transaction_type' => $type,
            'reference_table' => $refTable,
            'reference_id' => $refId,
            'debit' => $debit,
            'credit' => $credit,
            'balance_after' => $new,
            'note' => $note,
            'created_by' => AuthService::id(),
        ]);

        $upd = Database::pdo()->prepare('UPDATE customers SET current_balance = :balance WHERE id = :id');
        $upd->execute(['balance' => $new, 'id' => $customerId]);
    }

    public static function supplier(
        int $supplierId,
        string $type,
        float $debit,
        float $credit,
        ?string $refTable,
        ?int $refId,
        ?string $note
    ): void {
        $current = self::supplierBalance($supplierId);
        $new = $current + $debit - $credit;

        $stmt = Database::pdo()->prepare(
            'INSERT INTO supplier_transactions
             (supplier_id, transaction_type, reference_table, reference_id, debit, credit, balance_after, note, created_by)
             VALUES
             (:supplier_id, :transaction_type, :reference_table, :reference_id, :debit, :credit, :balance_after, :note, :created_by)'
        );
        $stmt->execute([
            'supplier_id' => $supplierId,
            'transaction_type' => $type,
            'reference_table' => $refTable,
            'reference_id' => $refId,
            'debit' => $debit,
            'credit' => $credit,
            'balance_after' => $new,
            'note' => $note,
            'created_by' => AuthService::id(),
        ]);

        $upd = Database::pdo()->prepare('UPDATE suppliers SET current_balance = :balance WHERE id = :id');
        $upd->execute(['balance' => $new, 'id' => $supplierId]);
    }

    public static function customerBalance(int $customerId): float
    {
        $stmt = Database::pdo()->prepare('SELECT current_balance FROM customers WHERE id = :id');
        $stmt->execute(['id' => $customerId]);
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    public static function supplierBalance(int $supplierId): float
    {
        $stmt = Database::pdo()->prepare('SELECT current_balance FROM suppliers WHERE id = :id');
        $stmt->execute(['id' => $supplierId]);
        return (float) ($stmt->fetchColumn() ?: 0);
    }
}
