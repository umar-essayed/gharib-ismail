<?php

namespace App\Models;

use App\Core\Model;

class ReportModel extends Model
{
    public function sales(array $filters): array
    {
        $sql = 'SELECT s.invoice_no, s.invoice_date, c.name AS customer_name, u.full_name AS cashier_name,
                       s.grand_total, s.paid_total, s.due_total, s.payment_status
                FROM sales_invoices s
                JOIN customers c ON c.id = s.customer_id
                JOIN users u ON u.id = s.user_id
                WHERE s.status = "posted"';
        $params = [];

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(s.invoice_date) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(s.invoice_date) <= :to';
            $params['to'] = $filters['to'];
        }
        if (!empty($filters['user_id'])) {
            $sql .= ' AND s.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['customer_id'])) {
            $sql .= ' AND s.customer_id = :customer_id';
            $params['customer_id'] = (int) $filters['customer_id'];
        }

        $sql .= ' ORDER BY s.id DESC LIMIT 1000';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function purchases(array $filters): array
    {
        $sql = 'SELECT p.invoice_no, p.invoice_date, s.name AS supplier_name, u.full_name AS user_name,
                       p.grand_total, p.paid_total, p.due_total, p.status
                FROM purchase_invoices p
                JOIN suppliers s ON s.id = p.supplier_id
                JOIN users u ON u.id = p.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(p.invoice_date) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(p.invoice_date) <= :to';
            $params['to'] = $filters['to'];
        }
        if (!empty($filters['supplier_id'])) {
            $sql .= ' AND p.supplier_id = :supplier_id';
            $params['supplier_id'] = (int) $filters['supplier_id'];
        }

        $sql .= ' ORDER BY p.id DESC LIMIT 1000';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function lowStock(): array
    {
        return $this->db->query(
            'SELECT * FROM (
                SELECT p.name, p.min_stock, COALESCE(sm.balance_after, 0) AS stock
                FROM products p
                LEFT JOIN (
                   SELECT s1.product_id, s1.balance_after
                   FROM stock_movements s1
                   INNER JOIN (SELECT product_id, MAX(id) AS max_id FROM stock_movements GROUP BY product_id) s2 ON s1.id=s2.max_id
                ) sm ON sm.product_id=p.id
                WHERE p.deleted_at IS NULL
             ) WHERE stock <= min_stock
             ORDER BY stock ASC'
        )->fetchAll();
    }

    public function customerDebts(): array
    {
        return $this->db->query('SELECT id, name, phone, current_balance, credit_limit FROM customers WHERE deleted_at IS NULL AND current_balance > 0 ORDER BY current_balance DESC')->fetchAll();
    }

    public function supplierBalances(): array
    {
        return $this->db->query('SELECT id, name, phone, current_balance FROM suppliers WHERE deleted_at IS NULL AND current_balance > 0 ORDER BY current_balance DESC')->fetchAll();
    }

    public function cashMovements(array $filters = []): array
    {
        $sql = 'SELECT c.*, u.full_name AS user_name, s.shift_no FROM cash_movements c
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

        $sql .= ' ORDER BY c.id DESC LIMIT 1000';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function activityLogs(array $filters = []): array
    {
        $sql = 'SELECT a.*, u.full_name AS user_name FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id WHERE 1=1';
        $params = [];
        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(a.created_at) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(a.created_at) <= :to';
            $params['to'] = $filters['to'];
        }
        $sql .= ' ORDER BY a.id DESC LIMIT 1000';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function lookups(): array
    {
        return [
            'users' => $this->db->query('SELECT id, full_name FROM users WHERE deleted_at IS NULL ORDER BY full_name')->fetchAll(),
            'customers' => $this->db->query('SELECT id, name FROM customers WHERE deleted_at IS NULL ORDER BY name')->fetchAll(),
            'suppliers' => $this->db->query('SELECT id, name FROM suppliers WHERE deleted_at IS NULL ORDER BY name')->fetchAll(),
            'products' => $this->db->query('SELECT id, name FROM products WHERE deleted_at IS NULL ORDER BY name')->fetchAll(),
        ];
    }
}
