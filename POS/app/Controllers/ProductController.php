<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProductModel;
use App\Services\AuthService;
use App\Services\LogService;
use App\Services\StockService;
use App\Services\SupabaseSyncService;
use App\Services\ValidationService;

class ProductController extends Controller
{
    public function index(): void
    {
        $model = new ProductModel();
        $q = trim((string) ($_GET['q'] ?? ''));
        $rows = $model->all($q);

        $this->view('products/index', [
            'rows' => $rows,
            'q' => $q,
        ]);
    }

    public function create(): void
    {
        $model = new ProductModel();
        $categories = $model->categories();
        $units = $model->units();

        $this->view('products/create', compact('categories', 'units'));
    }

    public function store(): void
    {
        validate_csrf_or_abort();
        $data = $this->formData();

        $errors = ValidationService::validate($data, [
            'name' => 'required',
            'sale_price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'min_stock' => 'numeric|min:0',
            'opening_stock' => 'numeric|min:0',
            'package_size' => 'numeric|min:1',
        ]);
        if ($errors) {
            flash_error('يرجى مراجعة بيانات المنتج');
            set_old($_POST);
            $this->redirect('/products/create');
        }

        $model = new ProductModel();
        $this->saveImage($data);
        $data['created_by'] = AuthService::id();

        $id = $model->create($data);

        if ((float) $data['opening_stock'] > 0 && (int) $data['track_stock'] === 1) {
            StockService::move(
                (int) \App\Services\SettingsService::get('default_warehouse_id', '1'),
                $id,
                'initial',
                (float) $data['opening_stock'],
                0,
                (float) $data['purchase_price'],
                'products',
                $id,
                'رصيد افتتاحي منتج جديد',
                AuthService::id()
            );
        }

        // ☁️ مزامنة المنتج الجديد مع المتجر الإلكتروني
        try {
            $newProduct = $model->find($id);
            if ($newProduct) {
                SupabaseSyncService::syncProduct($newProduct);
            }
        } catch (\Throwable $e) {
            // الخطأ يُحفظ تلقائياً في الطابور داخل syncProduct
        }

        LogService::audit('products', $id, 'insert', null, $data);
        flash_success('تم إضافة المنتج');
        $this->redirect('/products');
    }

    public function edit(string $id): void
    {
        $model = new ProductModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('المنتج غير موجود');
            $this->redirect('/products');
        }

        $categories = $model->categories();
        $units = $model->units();

        $this->view('products/edit', compact('row', 'categories', 'units'));
    }

    public function update(string $id): void
    {
        validate_csrf_or_abort();

        $model = new ProductModel();
        $old = $model->find((int) $id);
        if (!$old) {
            flash_error('المنتج غير موجود');
            $this->redirect('/products');
        }

        $data = $this->formData();
        $this->saveImage($data);
        if (empty($data['image_path'])) {
            $data['image_path'] = $old['image_path'] ?? null;
        }
        $data['updated_by'] = AuthService::id();

        $model->update((int) $id, $data);
        LogService::audit('products', (int) $id, 'update', $old, $data);

        // ☁️ مزامنة التحديث مع المتجر الإلكتروني
        try {
            $updated = $model->find((int) $id);
            if ($updated) {
                SupabaseSyncService::syncProduct($updated);
            }
        } catch (\Throwable $e) { /* يُحفظ في الطابور */ }

        flash_success('تم تحديث المنتج');
        $this->redirect('/products');
    }

    public function delete(string $id): void
    {
        validate_csrf_or_abort();
        $model = new ProductModel();
        $row = $model->find((int) $id);
        if ($row) {
            $model->softDelete((int) $id);
            LogService::audit('products', (int) $id, 'delete', $row, null);

            // ☁️ إخفاء المنتج من المتجر الإلكتروني (is_available = false)
            try {
                SupabaseSyncService::deleteProduct((int) $id);
            } catch (\Throwable $e) { /* يُحفظ في الطابور */ }

            flash_success('تم حذف المنتج');
        }

        $this->redirect('/products');
    }

    private function formData(): array
    {
        return [
            'category_id' => input('category_id'),
            'unit_id' => input('unit_id'),
            'name' => trim((string) input('name')),
            'sku' => trim((string) input('sku')),
            'internal_code' => trim((string) input('internal_code')),
            'barcode' => trim((string) input('barcode')),
            'scale_code' => trim((string) input('scale_code')),
            'allow_scale_barcode' => input('allow_scale_barcode', '0') === '1' ? 1 : 0,
            'weight_unit' => input('weight_unit', 'kg') === 'g' ? 'g' : 'kg',
            'purchase_price' => (float) input('purchase_price', 0),
            'sale_price' => (float) input('sale_price', 0),
            'wholesale_price' => input('wholesale_price', ''),
            'min_stock' => (float) input('min_stock', 0),
            'opening_stock' => (float) input('opening_stock', 0),
            'sell_type' => input('sell_type', 'piece'),
            'package_type' => input('package_type', 'piece'),
            'package_size' => (float) input('package_size', 1),
            'track_stock' => input('track_stock', '1') === '1' ? 1 : 0,
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
            'image_path' => null,
        ];
    }

    private function saveImage(array &$data): void
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $tmp = $_FILES['image']['tmp_name'];
        $name = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return;
        }

        $newName = 'product_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
        $target = base_path('public/uploads/' . $newName);
        if (move_uploaded_file($tmp, $target)) {
            $data['image_path'] = 'uploads/' . $newName;
        }
    }
}
