<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\DashboardModel;
use App\Services\CashService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $model = new DashboardModel();
        $cards = $model->cards();
        $topProducts = $model->topProducts();
        $salesByDay = $model->salesByTodayHours();
        $lowStock = $model->lowStock();
        $openShift = CashService::openShiftForUser((int) current_user()['id']);

        $this->view('dashboard/index', compact('cards', 'topProducts', 'salesByDay', 'lowStock', 'openShift'));
    }
}
