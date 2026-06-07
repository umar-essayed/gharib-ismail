<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CashModel;

class CashController extends Controller
{
    public function index(): void
    {
        $rows = (new CashModel())->list([
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ]);

        $this->view('cash/index', compact('rows'));
    }

    public function store(): void
    {
        validate_csrf_or_abort();
        (new CashModel())->createManual([
            'movement_type' => input('movement_type', 'expense'),
            'amount' => (float) input('amount', 0),
            'note' => trim((string) input('note')),
        ]);

        flash_success('تم تسجيل حركة الصندوق');
        $this->redirect('/cash');
    }
}
