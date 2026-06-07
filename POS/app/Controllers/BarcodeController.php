<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProductModel;

class BarcodeController extends Controller
{
    public function index(): void
    {
        $products = (new ProductModel())->all($_GET['q'] ?? '');
        $this->view('barcode/index', compact('products'));
    }

    public function print(): void
    {
        $ids = $_POST['product_ids'] ?? [];
        $quantities = $_POST['quantities'] ?? [];

        $rows = [];
        if ($ids) {
            $model = new ProductModel();
            foreach ($ids as $id) {
                $p = $model->find((int) $id);
                if ($p) {
                    $p['print_qty'] = max(1, (int)($quantities[$id] ?? $_POST['label_qty'] ?? 1));
                    $rows[] = $p;
                }
            }
        }

        $this->view('barcode/print', compact('rows'), 'layouts/print');
    }
}
