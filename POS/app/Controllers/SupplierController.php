<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\SupplierModel;
use App\Services\AuthService;
use App\Services\CashService;
use App\Services\LedgerService;
use App\Services\LogService;
use App\Services\ValidationService;

class SupplierController extends Controller
{
    public function index(): void
    {
        $model = new SupplierModel();
        $q = trim((string) ($_GET['q'] ?? ''));
        $rows = $model->all($q);

        $this->view('suppliers/index', compact('rows', 'q'));
    }

    public function create(): void
    {
        $this->view('suppliers/create');
    }

    public function store(): void
    {
        validate_csrf_or_abort();
        $data = $this->payload();

        $errors = ValidationService::validate($data, [
            'name' => 'required',
            'opening_balance' => 'numeric',
        ]);
        if ($errors) {
            flash_error('يرجى استكمال بيانات المورد');
            set_old($_POST);
            $this->redirect('/suppliers/create');
        }

        $model = new SupplierModel();
        $id = $model->create($data);

        if ((float) $data['opening_balance'] > 0) {
            LedgerService::supplier($id, 'opening', (float) $data['opening_balance'], 0, 'suppliers', $id, 'رصيد افتتاحي');
        }

        LogService::audit('suppliers', $id, 'insert', null, $data);
        flash_success('تم إضافة المورد');
        $this->redirect('/suppliers');
    }

    public function edit(string $id): void
    {
        $model = new SupplierModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('المورد غير موجود');
            $this->redirect('/suppliers');
        }

        $this->view('suppliers/edit', compact('row'));
    }

    public function update(string $id): void
    {
        validate_csrf_or_abort();
        $model = new SupplierModel();
        $old = $model->find((int) $id);
        if (!$old) {
            flash_error('المورد غير موجود');
            $this->redirect('/suppliers');
        }

        $data = $this->payload();
        $model->update((int) $id, $data);

        LogService::audit('suppliers', (int) $id, 'update', $old, $data);
        flash_success('تم تحديث المورد');
        $this->redirect('/suppliers');
    }

    public function delete(string $id): void
    {
        validate_csrf_or_abort();
        $model = new SupplierModel();
        $old = $model->find((int) $id);
        if ($old) {
            $model->softDelete((int) $id);
            LogService::audit('suppliers', (int) $id, 'delete', $old, null);
            flash_success('تم حذف المورد');
        }

        $this->redirect('/suppliers');
    }

    public function statement(string $id): void
    {
        $model = new SupplierModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('المورد غير موجود');
            $this->redirect('/suppliers');
        }

        $statement = $model->statement((int) $id);
        $this->view('suppliers/statement', compact('row', 'statement'));
    }

    public function paymentForm(string $id): void
    {
        $model = new SupplierModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('المورد غير موجود');
            $this->redirect('/suppliers');
        }

        $paymentMethods = Database::pdo()->query('SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id')->fetchAll();
        $this->view('suppliers/payment', compact('row', 'paymentMethods'));
    }

    public function paymentStore(string $id): void
    {
        validate_csrf_or_abort();
        $model = new SupplierModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('المورد غير موجود');
            $this->redirect('/suppliers');
        }

        $amount = (float) input('amount', 0);
        if ($amount <= 0) {
            flash_error('قيمة السداد غير صحيحة');
            $this->redirect('/suppliers/' . (int) $id . '/payment');
        }

        $methodId = input('payment_method_id') ? (int) input('payment_method_id') : null;
        $methodName = null;
        if ($methodId) {
            $stmt = Database::pdo()->prepare('SELECT name FROM payment_methods WHERE id = :id AND is_active = 1');
            $stmt->execute(['id' => $methodId]);
            $methodName = $stmt->fetchColumn() ?: null;
        }

        $note = trim((string) input('note'));
        $noteWithMethod = trim(($note !== '' ? $note . ' - ' : '') . 'طريقة الدفع: ' . ($methodName ?: 'غير محددة'));
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $openShift = CashService::openShiftForUser((int) AuthService::id()) ?: CashService::openAnyShift();
            $movementId = CashService::movement(
                $openShift ? (int) $openShift['id'] : null,
                (int) AuthService::id(),
                'purchase_payment',
                'out',
                $amount,
                'suppliers',
                (int) $id,
                $noteWithMethod
            );

            LedgerService::supplier((int) $id, 'payment', 0, $amount, 'cash_movements', $movementId, $noteWithMethod);
            LogService::activity((int) AuthService::id(), 'suppliers.payment', 'سداد مورد #' . (int) $id . ' بقيمة ' . $amount);
            LogService::audit('suppliers', (int) $id, 'update', null, ['action' => 'payment', 'amount' => $amount]);

            $db->commit();
            flash_success('تم تسجيل سداد المورد بنجاح');
            $this->redirect('/suppliers/' . (int) $id . '/statement');
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function payload(): array
    {
        return [
            'name' => trim((string) input('name')),
            'phone' => trim((string) input('phone')),
            'email' => trim((string) input('email')),
            'address' => trim((string) input('address')),
            'opening_balance' => (float) input('opening_balance', 0),
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
        ];
    }
}
