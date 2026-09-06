<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Generation;
use App\Models\Product;
use App\Services\ServiceCreditOverviewService;
use App\Services\ServiceCreditSynchronizer;
use App\Services\ServiceCreditTransactionReport;

class DashboardController extends Controller
{
    public function index(
        ServiceCreditOverviewService $creditOverview,
        ServiceCreditSynchronizer $creditSynchronizer,
        ServiceCreditTransactionReport $transactionReport,
        $section = null
    )
    {
        try {
            $stats = [
                'users_count'       => User::count(),
                'generations_count' => Generation::count(),
                'products_count'    => Product::count(),
            ];
        } catch (\Throwable $e) {
            $stats = ['users_count' => 0, 'generations_count' => 0, 'products_count' => 0];
        }

        $creditSynchronizer->sync();
        $creditData = $creditOverview->get(true);
        $creditTransactions = $transactionReport->latest(5, (float) ($creditData['exchange']['rate'] ?? 0));

        return view('admin.dashboard', [
            'stats'    => $stats,
            'topProds' => [],
            'products' => [],
            'cats'     => [],
            'models'   => [],
            'actions'  => [],
            'creditOverview' => $creditData,
            'creditTransactions' => $creditTransactions,
        ]);
    }
}
