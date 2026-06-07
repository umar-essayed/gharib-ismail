<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ShiftModel;

class ShiftController extends Controller
{
    public function index(): void
    {
        $model = new ShiftModel();
        $rows = $model->list();
        $openShift = $model->openForUser((int) current_user()['id']);

        $this->view('shifts/index', compact('rows', 'openShift'));
    }

    public function open(): void
    {
        validate_csrf_or_abort();
        $openingBalance = (float) input('opening_balance', 0);
        $note = trim((string) input('note'));

        $result = (new ShiftModel())->open($openingBalance, $note);
        flash_success('تم فتح الشيفت ' . $result['shift_no']);
        $this->redirect('/shifts');
    }

    public function close(string $id): void
    {
        validate_csrf_or_abort();
        $actual = (float) input('actual_balance', 0);
        $note = trim((string) input('note'));

        (new ShiftModel())->close((int) $id, $actual, $note);
        flash_success('تم إقفال الشيفت');
        $this->redirect('/shifts');
    }

    public function report(string $id): void
    {
        $row = (new ShiftModel())->report((int) $id);
        $this->view('shifts/report', compact('row'));
    }
}
