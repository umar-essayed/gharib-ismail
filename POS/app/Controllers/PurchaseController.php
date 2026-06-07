<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProductModel;
use App\Models\PurchaseModel;
use App\Models\SupplierModel;

class PurchaseController extends Controller
{
    public function searchProducts(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q === '') {
            $this->json(['data' => []]);
            return;
        }

        $rows = (new ProductModel())->findByBarcodeOrName($q);
        $this->json(['data' => $rows]);
    }

    public function index(): void
    {
        $model = new PurchaseModel();
        $rows = $model->list([
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ]);

        $this->view('purchases/index', compact('rows'));
    }

    public function create(): void
    {
        $suppliers = (new SupplierModel())->all('');
        $products = (new ProductModel())->all('');
        $paymentMethods = \App\Core\Database::pdo()->query('SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id')->fetchAll();

        $this->view('purchases/create', compact('suppliers', 'products', 'paymentMethods'));
    }

    public function store(): void
    {
        validate_csrf_or_abort();
        $items = json_decode((string) input('items_json', '[]'), true) ?: [];

        $model = new PurchaseModel();
        $result = $model->create([
            'supplier_id' => (int) input('supplier_id'),
            'supplier_invoice_no' => trim((string) input('supplier_invoice_no')),
            'warehouse_id' => (int) \App\Services\SettingsService::get('default_warehouse_id', '1'),
            'status' => input('status', 'approved'),
            'payment_method_id' => input('payment_method_id') ? (int) input('payment_method_id') : null,
            'paid_total' => (float) input('paid_total', 0),
            'note' => trim((string) input('note')),
            'items' => $items,
        ]);

        flash_success('تم حفظ فاتورة الشراء رقم ' . $result['invoice_no']);
        $this->redirect('/purchases/' . $result['id']);
    }

    public function edit(string $id): void
    {
        $model = new PurchaseModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('فاتورة الشراء غير موجودة');
            $this->redirect('/purchases');
        }
        if ($row['status'] !== 'draft') {
            flash_error('لا يمكن تعديل فاتورة بعد الاعتماد');
            $this->redirect('/purchases/' . (int) $id);
        }

        $suppliers = (new SupplierModel())->all('');
        $products = (new ProductModel())->all('');
        $paymentMethods = \App\Core\Database::pdo()->query('SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id')->fetchAll();
        $this->view('purchases/edit', compact('row', 'suppliers', 'products', 'paymentMethods'));
    }

    public function update(string $id): void
    {
        validate_csrf_or_abort();
        $items = json_decode((string) input('items_json', '[]'), true) ?: [];

        $model = new PurchaseModel();
        $model->updateDraft((int) $id, [
            'supplier_id' => (int) input('supplier_id'),
            'supplier_invoice_no' => trim((string) input('supplier_invoice_no')),
            'warehouse_id' => (int) \App\Services\SettingsService::get('default_warehouse_id', '1'),
            'payment_method_id' => input('payment_method_id') ? (int) input('payment_method_id') : null,
            'paid_total' => (float) input('paid_total', 0),
            'note' => trim((string) input('note')),
            'items' => $items,
        ]);

        flash_success('تم تحديث فاتورة الشراء المسودة');
        $this->redirect('/purchases/' . (int) $id);
    }

    public function show(string $id): void
    {
        $model = new PurchaseModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('فاتورة الشراء غير موجودة');
            $this->redirect('/purchases');
        }

        $this->view('purchases/show', compact('row'));
    }

    public function print(string $id): void
    {
        $model = new PurchaseModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('فاتورة الشراء غير موجودة');
            $this->redirect('/purchases');
        }

        $this->view('purchases/print', compact('row'), 'layouts/print');
    }

    public function approve(string $id): void
    {
        validate_csrf_or_abort();
        $model = new PurchaseModel();
        $model->approve((int) $id);
        flash_success('تم اعتماد الفاتورة');
        $this->redirect('/purchases/' . (int) $id);
    }
}
