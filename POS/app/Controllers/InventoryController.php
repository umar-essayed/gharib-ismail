<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InventoryModel;
use App\Models\ProductModel;

class InventoryController extends Controller
{
    public function stock(): void
    {
        $model = new InventoryModel();
        $rows = $model->stockSummary();
        $this->view('inventory/stock', compact('rows'));
    }

    public function movements(): void
    {
        $model = new InventoryModel();
        $rows = $model->movements([
            'product_id' => $_GET['product_id'] ?? null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ]);
        $products = (new ProductModel())->all('');

        $this->view('inventory/movements', compact('rows', 'products'));
    }

    public function adjustments(): void
    {
        $products = (new ProductModel())->all('');
        $warehouses = (new InventoryModel())->warehouses();
        $this->view('inventory/adjustments', compact('products', 'warehouses'));
    }

    public function adjustStore(): void
    {
        validate_csrf_or_abort();
        $items = json_decode((string) input('items_json', '[]'), true) ?: [];

        $result = (new InventoryModel())->adjust([
            'warehouse_id' => (int) input('warehouse_id', 1),
            'note' => trim((string) input('note')),
            'items' => $items,
        ]);

        flash_success('تم تنفيذ التسوية رقم ' . $result['adjust_no']);
        $this->redirect('/inventory/movements');
    }
}
