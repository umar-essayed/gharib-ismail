<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ReportModel;

class ReportController extends Controller
{
    public function index(): void
    {
        $model = new ReportModel();
        $lookups = $model->lookups();

        $sales = $model->sales($_GET);
        $purchases = $model->purchases($_GET);
        $lowStock = $model->lowStock();
        $customerDebts = $model->customerDebts();
        $supplierBalances = $model->supplierBalances();
        $cash = $model->cashMovements($_GET);
        $activity = $model->activityLogs($_GET);

        $this->view('reports/index', compact(
            'lookups',
            'sales',
            'purchases',
            'lowStock',
            'customerDebts',
            'supplierBalances',
            'cash',
            'activity'
        ));
    }
}
