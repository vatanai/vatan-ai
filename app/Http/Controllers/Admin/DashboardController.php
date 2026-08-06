<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Generation;
use App\Models\Product;
use App\Services\ServiceCreditOverviewService;
use App\Services\ServiceCreditSynchronizer;

class DashboardController extends Controller
{
    public function index(
        ServiceCreditOverviewService $creditOverview,
        ServiceCreditSynchronizer $creditSynchronizer,
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

        return view('admin.dashboard', [
            'stats'    => $stats,
            'topProds' => [],
            'products' => [],
            'cats'     => [],
            'models'   => [],
            'actions'  => [],
            'creditOverview' => $creditOverview->get(true),
        ]);
    }
}
