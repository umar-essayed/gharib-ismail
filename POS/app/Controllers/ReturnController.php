<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ReturnModel;
use App\Models\SalesModel;
use App\Models\PurchaseModel;

class ReturnController extends Controller
{
    public function salesIndex(): void
    {
        $rows = (new ReturnModel())->salesReturns();
        $this->view('returns/sales_index', compact('rows'));
    }

    public function salesCreate(): void
    {
        $invoices = (new SalesModel())->list();
        $paymentMethods = \App\Core\Database::pdo()->query('SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id')->fetchAll();
        $openShift = \App\Services\CashService::openShiftForUser((int) current_user()['id']);
        $this->view('returns/sales_create', compact('invoices', 'paymentMethods', 'openShift'));
    }

    public function salesItems(string $invoiceId): void
    {
        $invoice = (new SalesModel())->find((int) $invoiceId);
        if (!$invoice) {
            $this->json(['error' => 'Invoice not found'], 404);
            return;
        }

        $rows = (new ReturnModel())->salesInvoiceItemsWithAvailable((int) $invoiceId);
        $this->json(['data' => $rows]);
    }

    public function salesStore(): void
    {
        validate_csrf_or_abort();
        $items = json_decode((string) input('items_json', '[]'), true) ?: [];

        $result = (new ReturnModel())->createSalesReturn([
            'sales_invoice_id' => (int) input('sales_invoice_id'),
            'items' => $items,
            'refund_total' => (float) input('refund_total', 0),
            'payment_method_id' => input('payment_method_id') ? (int) input('payment_method_id') : null,
            'shift_id' => input('shift_id') ? (int) input('shift_id') : null,
            'note' => trim((string) input('note')),
        ]);

        flash_success('تم تسجيل مرتجع البيع ' . $result['return_no']);
        $this->redirect('/returns/sales/' . $result['id']);
    }

    public function salesShow(string $id): void
    {
        $row = (new ReturnModel())->salesReturnFind((int) $id);
        if (!$row) {
            flash_error('المستند غير موجود');
            $this->redirect('/returns/sales');
        }

        $this->view('returns/sales_show', compact('row'));
    }

    public function salesPrint(string $id): void
    {
        $row = (new ReturnModel())->salesReturnFind((int) $id);
        if (!$row) {
            flash_error('المستند غير موجود');
            $this->redirect('/returns/sales');
        }

        $this->view('returns/sales_print', compact('row'), 'layouts/print');
    }

    public function purchasesIndex(): void
    {
        $rows = (new ReturnModel())->purchaseReturns();
        $this->view('returns/purchase_index', compact('rows'));
    }

    public function purchasesCreate(): void
    {
        $invoices = (new PurchaseModel())->list();
        $paymentMethods = \App\Core\Database::pdo()->query('SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id')->fetchAll();
        $this->view('returns/purchase_create', compact('invoices', 'paymentMethods'));
    }

    public function purchaseItems(string $invoiceId): void
    {
        $invoice = (new PurchaseModel())->find((int) $invoiceId);
        if (!$invoice) {
            $this->json(['error' => 'Invoice not found'], 404);
            return;
        }

        $this->json(['data' => $invoice['items']]);
    }

    public function purchasesStore(): void
    {
        validate_csrf_or_abort();
        $items = json_decode((string) input('items_json', '[]'), true) ?: [];

        $result = (new ReturnModel())->createPurchaseReturn([
            'purchase_invoice_id' => (int) input('purchase_invoice_id'),
            'items' => $items,
            'refund_total' => (float) input('refund_total', 0),
            'payment_method_id' => input('payment_method_id') ? (int) input('payment_method_id') : null,
            'note' => trim((string) input('note')),
        ]);

        flash_success('تم تسجيل مرتجع الشراء ' . $result['return_no']);
        $this->redirect('/returns/purchases/' . $result['id']);
    }

    public function purchasesShow(string $id): void
    {
        $row = (new ReturnModel())->purchaseReturnFind((int) $id);
        if (!$row) {
            flash_error('المستند غير موجود');
            $this->redirect('/returns/purchases');
        }

        $this->view('returns/purchase_show', compact('row'));
    }

    public function purchasesPrint(string $id): void
    {
        $row = (new ReturnModel())->purchaseReturnFind((int) $id);
        if (!$row) {
            flash_error('المستند غير موجود');
            $this->redirect('/returns/purchases');
        }

        $this->view('returns/purchase_print', compact('row'), 'layouts/print');
    }
}
