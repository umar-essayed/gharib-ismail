<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\CustomerModel;
use App\Services\AuthService;
use App\Services\CashService;
use App\Services\LedgerService;
use App\Services\LogService;
use App\Services\ValidationService;

class CustomerController extends Controller
{
    public function index(): void
    {
        $model = new CustomerModel();
        $q = trim((string) ($_GET['q'] ?? ''));
        $rows = $model->all($q);

        $this->view('customers/index', compact('rows', 'q'));
    }

    public function create(): void
    {
        $this->view('customers/create');
    }

    public function store(): void
    {
        validate_csrf_or_abort();
        $data = $this->payload();

        $errors = ValidationService::validate($data, [
            'name' => 'required',
            'opening_balance' => 'numeric',
            'credit_limit' => 'numeric|min:0',
        ]);
        if ($errors) {
            flash_error('ÙŠØ±Ø¬Ù‰ Ø§Ø³ØªÙƒÙ…Ø§Ù„ Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ø¹Ù…ÙŠÙ„');
            set_old($_POST);
            $this->redirect('/customers/create');
        }

        $model = new CustomerModel();
        $id = $model->create($data);

        if ((float) $data['opening_balance'] > 0) {
            LedgerService::customer($id, 'opening', (float) $data['opening_balance'], 0, 'customers', $id, 'Ø±ØµÙŠØ¯ Ø§ÙØªØªØ§Ø­ÙŠ');
        }

        LogService::audit('customers', $id, 'insert', null, $data);
        flash_success('ØªÙ… Ø¥Ø¶Ø§ÙØ© Ø§Ù„Ø¹Ù…ÙŠÙ„');
        $this->redirect('/customers');
    }

    public function edit(string $id): void
    {
        $model = new CustomerModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('Ø§Ù„Ø¹Ù…ÙŠÙ„ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯');
            $this->redirect('/customers');
        }

        $this->view('customers/edit', compact('row'));
    }

    public function update(string $id): void
    {
        validate_csrf_or_abort();
        $model = new CustomerModel();
        $old = $model->find((int) $id);
        if (!$old) {
            flash_error('Ø§Ù„Ø¹Ù…ÙŠÙ„ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯');
            $this->redirect('/customers');
        }

        $data = $this->payload();
        $model->update((int) $id, $data);
        LogService::audit('customers', (int) $id, 'update', $old, $data);

        flash_success('ØªÙ… ØªØ­Ø¯ÙŠØ« Ø§Ù„Ø¹Ù…ÙŠÙ„');
        $this->redirect('/customers');
    }

    public function delete(string $id): void
    {
        validate_csrf_or_abort();
        $model = new CustomerModel();
        $old = $model->find((int) $id);
        if ($old) {
            if ((int) ($old['is_cash_customer'] ?? 0) === 1 && $model->activeCashCustomersCount() <= 1) {
                flash_error('لا يمكن حذف العميل النقدي الافتراضي');
                $this->redirect('/customers');
            }
            $model->softDelete((int) $id);
            LogService::audit('customers', (int) $id, 'delete', $old, null);
            flash_success('تم حذف العميل');
        }

        $this->redirect('/customers');
    }

    public function statement(string $id): void
    {
        $model = new CustomerModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('Ø§Ù„Ø¹Ù…ÙŠÙ„ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯');
            $this->redirect('/customers');
        }

        $statement = $model->statement((int) $id);
        $this->view('customers/statement', compact('row', 'statement'));
    }

    public function receiptForm(string $id): void
    {
        $model = new CustomerModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('Ø§Ù„Ø¹Ù…ÙŠÙ„ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯');
            $this->redirect('/customers');
        }

        $paymentMethods = Database::pdo()->query('SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id')->fetchAll();
        $this->view('customers/receipt', compact('row', 'paymentMethods'));
    }

    public function receiptStore(string $id): void
    {
        validate_csrf_or_abort();
        $model = new CustomerModel();
        $row = $model->find((int) $id);
        if (!$row) {
            flash_error('Ø§Ù„Ø¹Ù…ÙŠÙ„ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯');
            $this->redirect('/customers');
        }

        $amount = (float) input('amount', 0);
        if ($amount <= 0) {
            flash_error('Ù‚ÙŠÙ…Ø© Ø§Ù„Ø³Ù†Ø¯ ØºÙŠØ± ØµØ­ÙŠØ­Ø©');
            $this->redirect('/customers/' . (int) $id . '/receipt');
        }

        $methodId = input('payment_method_id') ? (int) input('payment_method_id') : null;
        $methodName = null;
        if ($methodId) {
            $stmt = Database::pdo()->prepare('SELECT name FROM payment_methods WHERE id = :id AND is_active = 1');
            $stmt->execute(['id' => $methodId]);
            $methodName = $stmt->fetchColumn() ?: null;
        }

        $note = trim((string) input('note'));
        $noteWithMethod = trim(($note !== '' ? $note . ' - ' : '') . 'Ø·Ø±ÙŠÙ‚Ø© Ø§Ù„Ø¯ÙØ¹: ' . ($methodName ?: 'ØºÙŠØ± Ù…Ø­Ø¯Ø¯Ø©'));
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $openShift = CashService::openShiftForUser((int) AuthService::id()) ?: CashService::openAnyShift();
            $movementId = CashService::movement(
                $openShift ? (int) $openShift['id'] : null,
                (int) AuthService::id(),
                'customer_receipt',
                'in',
                $amount,
                'customers',
                (int) $id,
                $noteWithMethod
            );

            LedgerService::customer((int) $id, 'payment', 0, $amount, 'cash_movements', $movementId, $noteWithMethod);
            LogService::activity((int) AuthService::id(), 'customers.receipt', 'Ø³Ù†Ø¯ Ù‚Ø¨Ø¶ Ø¹Ù…ÙŠÙ„ #' . (int) $id . ' Ø¨Ù‚ÙŠÙ…Ø© ' . $amount);
            LogService::audit('customers', (int) $id, 'update', null, ['action' => 'receipt', 'amount' => $amount]);

            $db->commit();
            flash_success('ØªÙ… ØªØ³Ø¬ÙŠÙ„ Ø³Ù†Ø¯ Ø§Ù„Ù‚Ø¨Ø¶ Ø¨Ù†Ø¬Ø§Ø­');
            $this->redirect('/customers/' . (int) $id . '/statement');
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
            'credit_limit' => (float) input('credit_limit', 0),
            'is_cash_customer' => input('is_cash_customer', '0') === '1' ? 1 : 0,
            'is_active' => input('is_active', '1') === '1' ? 1 : 0,
        ];
    }
}

