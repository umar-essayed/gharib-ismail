<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SalesModel;

class SalesController extends Controller
{
    public function index(): void
    {
        $model = new SalesModel();
        $rows = $model->list([
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
            'customer_id' => $_GET['customer_id'] ?? null,
        ]);

        $this->view('sales/index', compact('rows'));
    }

    public function show(string $id): void
    {
        $model = new SalesModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('الفاتورة غير موجودة');
            $this->redirect('/sales');
        }

        $this->view('sales/show', compact('row'));
    }

    public function print(string $id): void
    {
        $model = new SalesModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('الفاتورة غير موجودة');
            $this->redirect('/sales');
        }

        $this->view('sales/print', compact('row'), 'layouts/print');
    }

    public function cancel(string $id): void
    {
        validate_csrf_or_abort();
        $model = new SalesModel();
        $model->cancel((int) $id);
        flash_success('تم إلغاء الفاتورة بنجاح');
        $this->redirect('/sales/' . (int) $id);
    }
}
