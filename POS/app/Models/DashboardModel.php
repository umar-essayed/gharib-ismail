<?php

namespace App\Models;

use App\Core\Model;

class DashboardModel extends Model
{
    public function cards(): array
    {
        $today = date('Y-m-d');
        $month = date('Y-m');

        $sql = [
            'today_sales'   => "SELECT COALESCE(SUM(grand_total),0) FROM sales_invoices WHERE status='posted' AND DATE(invoice_date)=:today",
            'month_sales'   => "SELECT COALESCE(SUM(grand_total),0) FROM sales_invoices WHERE status='posted' AND strftime('%Y-%m', invoice_date)=:month",
            'all_sales'     => "SELECT COALESCE(SUM(grand_total),0) FROM sales_invoices WHERE status='posted'",
            'today_invoices'=> "SELECT COUNT(*) FROM sales_invoices WHERE status='posted' AND DATE(invoice_date)=:today",
            'products_count'=> "SELECT COUNT(*) FROM products WHERE deleted_at IS NULL",
            'customers_count'=>"SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL",
            'returns_total' => "SELECT COALESCE(SUM(grand_total),0) FROM sales_returns",
            'due_total'     => "SELECT COALESCE(SUM(due_total),0) FROM sales_invoices WHERE status='posted'",
        ];

        $out = [];
        foreach ($sql as $key => $query) {
            $stmt = $this->db->prepare($query);
            $params = [];
            if (str_contains($query, ':today')) {
                $params['today'] = $today;
            }
            if (str_contains($query, ':month')) {
                $params['month'] = $month;
            }
            $stmt->execute($params);
            $out[$key] = (float) $stmt->fetchColumn();
        }

        return $out;
    }

    public function topProducts(): array
    {
        return $this->db->query(
            'SELECT p.name, SUM(si.qty) AS total_qty
             FROM sales_invoice_items si
             JOIN sales_invoices s ON s.id = si.sales_invoice_id AND s.status = "posted"
             JOIN products p ON p.id = si.product_id
             GROUP BY p.id, p.name
             ORDER BY total_qty DESC
             LIMIT 10'
        )->fetchAll();
    }

    public function salesByTodayHours(): array
    {
        $stmt = $this->db->prepare(
            'SELECT CAST(strftime(\'%H\', invoice_date) AS INTEGER) AS h, SUM(grand_total) AS total
             FROM sales_invoices
             WHERE status = "posted" AND DATE(invoice_date) = :today
             GROUP BY CAST(strftime(\'%H\', invoice_date) AS INTEGER)
             ORDER BY h ASC'
        );
        $stmt->execute(['today' => date('Y-m-d')]);

        $rows = $stmt->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['h']] = (float) $row['total'];
        }

        $out = [];
        for ($h = 0; $h < 24; $h++) {
            $out[] = [
                'd' => str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00',
                'total' => $map[$h] ?? 0.0,
            ];
        }

        return $out;
    }

    public function lowStock(): array
    {
        return $this->db->query(
            'SELECT * FROM (
                SELECT p.id, p.name, p.min_stock, COALESCE(sm.balance_after, 0) AS current_stock
                FROM products p
                LEFT JOIN (
                   SELECT s1.product_id, s1.balance_after
                   FROM stock_movements s1
                   INNER JOIN (SELECT product_id, MAX(id) AS max_id FROM stock_movements GROUP BY product_id) s2 ON s1.id = s2.max_id
                ) sm ON sm.product_id = p.id
                WHERE p.deleted_at IS NULL AND p.track_stock = 1
             ) WHERE current_stock <= min_stock
             ORDER BY current_stock ASC
             LIMIT 10'
        )->fetchAll();
    }
}
